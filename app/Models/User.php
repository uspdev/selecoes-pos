<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Traits\HasRoles;
use Uspdev\Replicado\Pessoa;
use Uspdev\SenhaunicaSocialite\Traits\HasSenhaunica;

class User extends Authenticatable
{
    use HasFactory, HasRoles, HasSenhaUnica, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'codpes',
        'telefone',
        'local',
        'email_confirmado',
        'is_admin',
        'config',
    ];

    # colocando data aqui ele já envia um objeto carbon
    protected $dates = ['last_login_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * O config está com o formato json no BD
     * https://laravel.com/docs/8.x/eloquent-mutators#array-and-json-casting
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'config' => 'array',
    ];

    public const rules = [
        'codpes' => 'required',
        'name' => 'required',
        'email' => 'email:rfc',
        'telefone' => '',
    ];

    protected const fields = [
        [
            'name' => 'codpes',
            'label' => 'Número USP',
        ],
        [
            'name' => 'name',
            'label' => 'Nome',
        ],
        [
            'name' => 'email',
            'label' => 'Email',
        ],
        [
            'name' => 'telefone',
            'label' => 'Telefone',
        ],
        [
            'name' => 'last_login_at',
            'label' => 'Ultimo login',
            'format' => 'timestamp',
        ],
        [
            'name' => 'is_admin',
            'label' => 'Admin',
            'format' => 'boolean',
        ],
    ];

    public static function getFields()
    {
        $fields = self::fields;
        return $fields;
    }

    public static function criarPorCodpes($codpes)
    {
        $user = new User;
        $user->codpes = $codpes;
        if (config('selecoes-pos.usar_replicado')) {

            //caso utilize o replicado, porém a pessoa não apareça, insere um usuário fake e atualiza o mesmo com dados da senha única no login
            $user->email = (Pessoa::email($codpes)) ?: $codpes . '@usuarios.usp.br';

            // se já existe usuário local com este e-mail, promove-o a não local... podemos ter segurança de fazer isso, pois estamos retornando do senha única
            $user_old = self::where('email', $user->email)->first();
            if (!is_null($user_old)) {
                $user = $user_old;
                $user->codpes = $codpes;
                $user->local = 0;
            }

            $pessoa = Pessoa::dump($codpes);
            if ($pessoa)
                $user->name = ($pessoa['nompesttd']);
            else
                $user->name = $codpes;
            $user->telefone = Pessoa::obterRamalUsp($codpes);
        } else {
            $user->email = $codpes . '@usuarios.usp.br';
            $user->name = $codpes;
        }
        $user->save();
        return $user;
    }

    public static function obterPorCodpes($codpes)
    {
        return User::where('codpes', $codpes)->first();
    }

    /**
     * Obtém se já existir ou cria um novo objeto de usuário
     *
     * @param Int $codpes Número USP a ser procurado ou criado
     * @return Obj $user Objeto do usuário criado
     */
    public static function obterOuCriarPorCodpes($codpes)
    {
        $user = User::obterPorCodpes($codpes);
        if (empty($user))
            $user = User::criarPorCodpes($codpes);
        return $user;
    }

    /**
     * Troca o perfil do usuário
     *
     * @param String $perfil [usuario, docente, gerente ou admin]
     * @return Array [success=>[true||false], msg=>mensagem de sucesso]
     */
    public function trocarPerfil($perfil)
    {
        $ret = [
            'success' => false,
            'msg' => '',
        ];
        switch ($perfil) {
            case 'usuario':
                session(['perfil' => 'usuario']);
                $ret['success'] = true;
                $ret['msg'] = 'Perfil mudado para Usuário com sucesso.';
                break;

            case 'docente':
                if (Gate::allows('docente')) {
                    session(['perfil' => 'docente']);
                    $ret['success'] = true;
                    $ret['msg'] = 'Perfil mudado para Docente com sucesso.';
                }
                break;

            case 'gerente':
                if (Gate::allows('gerente')) {
                    session(['perfil' => 'gerente']);
                    $ret['success'] = true;
                    $ret['msg'] = 'Perfil mudado para Gerente com sucesso.';
                }
                break;

            case 'admin':
                if (Gate::allows('admin')) {
                    session(['perfil' => 'admin']);
                    $ret['success'] = true;
                    $ret['msg'] = 'Perfil mudado para Admin com sucesso.';
                }
                break;
        }
        return $ret;
    }

    public static function codpesExiste($codpes)
    {
        return self::where('codpes', $codpes)->exists();
    }

    public static function emailExiste($email)
    {
        return self::where('email', $email)->exists();
    }

    public function associarProgramaFuncao(?string $programa, string $funcao)
    {
        if (is_null($programa))
            $this->programas()->newPivotStatement()->insert([    // insere manualmente registro na tabela relacional... não funciona fazer attach de usuário para um programa inexistente
                'user_id' => $this->id,
                'programa_id' => null,
                'funcao' => $funcao,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        else
            $this->programas()->attach(Programa::where('nome', $programa)->first()->id, ['funcao' => $funcao]);
    }

    public function desassociarProgramaFuncao(?string $programa, string $funcao)
    {
        // remove manualmente registro na tabela relacional... não funciona fazer detach de usuário para um programa inexistente, nem detach em que se especifica o pivot
        DB::table('user_programa')
            ->where('user_id', $this->id)
            ->where('programa_id', is_null($programa) ? null : Programa::where('nome', $programa)->first()->id)
            ->where('funcao', $funcao)
            ->delete();
    }

    public function listarProgramasGerenciados()
    {
        if ((session('perfil') == 'admin') ||
            (DB::table('user_programa')    // não dá pra partir de $this->, pelo fato de programa_id ser null na tabela relacional
                 ->where('user_id', $this->id)
                 ->whereNull('programa_id')
                 ->where(function ($query) {
                     $query->where('funcao', 'Serviço de Pós-Graduação')
                         ->orWhere('funcao', 'Coordenadores da Pós-Graduação');
                 })->exists()))
            return Programa::all();
        else
            return $this->programas;
    }

    public function listarProgramasGerenciadosFuncao(string $funcao)
    {
        if ((session('perfil') == 'admin') ||
            (DB::table('user_programa')    // não dá pra partir de $this->, pelo fato de programa_id ser null na tabela relacional
                 ->where('user_id', $this->id)
                 ->whereNull('programa_id')
                 ->where(function ($query) use ($funcao) {
                     $query->where('funcao', $funcao);
                 })->exists()))
            return Programa::whereHas('users', function ($query) use ($funcao) {
                $query->where('user_programa.funcao', $funcao);
            })->get();
        else
            return $this->programas()->where('funcao', $funcao)->get();
    }

    public function gerenciaPrograma(?int $programa_id = null)
    {
        if ($this->programas()->where('programa_id', $programa_id)->exists())
            return true;

        return DB::table('user_programa')    // não dá pra partir de $this->, pelo fato de programa_id ser null na tabela relacional
                   ->where('user_id', $this->id)
                   ->whereNull('programa_id')
                   ->whereIn('funcao', ['Serviço de Pós-Graduação', 'Coordenadores da Pós-Graduação'])
                   ->exists();
    }

    public function gerenciaProgramaFuncao(string $funcao, ?int $programa_id = null)
    {
        if (in_array($funcao, ['Serviço de Pós-Graduação', 'Coordenadores da Pós-Graduação']))
            return DB::table('user_programa')    // não dá pra partir de $this->, pelo fato de programa_id ser null na tabela relacional
                       ->where('user_id', $this->id)
                       ->whereNull('programa_id')
                       ->where('funcao', $funcao)
                       ->exists();

        return $this->programas()->where('programa_id', $programa_id)->where('funcao', $funcao)->exists();
    }

    /**
     * Accessor getter para funcao_maxima
     * Utilizado somente para quem é gerente ou docente
     */
    public function getFuncaoMaximaAttribute()
    {
        $funcoesUsuario = DB::table('user_programa')->where('user_id', $this->id)->pluck('funcao')->filter()->toArray();    // não dá pra partir de $this->, pelo fato de programa_id ser null na tabela relacional
        if (empty($funcoesUsuario))
            return null;

        $funcoesOrdenadas = ['Coordenadores da Pós-Graduação', 'Serviço de Pós-Graduação', 'Coordenadores do Programa', 'Secretários(as) do Programa', 'Docentes do Programa'];
        foreach ($funcoesOrdenadas as $funcao)
            if (in_array($funcao, $funcoesUsuario))
                return $funcao;    // retorna a primeira função do usuário que aparece na lista de funções ordenadas, ou seja, a função mais alta

        return array_values($funcoesUsuario)[0];
    }

    /**
     * Relacionamento n:n com solicitação de isenção de taxa:
     */
    public function solicitacoesisencaotaxa()
    {
        return $this->belongsToMany('App\Models\SolicitacaoIsencaoTaxa', 'user_solicitacaoisencaotaxa', 'user_id', 'solicitacaoisencaotaxa_id')->withTimestamps();    // se eu não especificar o nome do campo como solicitacaoisencaotaxa_id, o Laravel vai pensar que é solicitacao_isencao_taxa_id, e vai dar erro
    }

    /**
     * Relacionamento n:n com inscrição:
     */
    public function inscricoes()
    {
        return $this->belongsToMany('App\Models\Inscricao', 'user_inscricao')->withTimestamps();
    }

    /**
     * Relacionamento n:n com matrícula:
     */
    public function matriculas()
    {
        return $this->belongsToMany('App\Models\Matricula', 'user_matricula')->withTimestamps();
    }

    /**
     * Relacionamento n:n com setor, atributo funcao:
     *  - Gerente, Usuario
     */
    public function setores()
    {
        return $this->belongsToMany('App\Models\Setor', 'user_setor')->withPivot('funcao')->withTimestamps();
    }

    /**
     * Relacionamento n:n com programa, atributo funcao:
     *  - Secretario, Coordenador do Programa, Coordenador da Pos-Graduacao
     */
    public function programas()
    {
        return $this->belongsToMany('App\Models\Programa', 'user_programa')->withPivot('funcao')->withTimestamps();
    }

    // este método é invocado pelo senhaunica-socialite, por isso é preciso que ele exista aqui
    // ele só é invocado quando alguém assume a identidade de um usuário que nunca antes logou no sistema (e que, portanto, nem está gravado na tabela)
    static function findOrCreateFromReplicado($codpes) {
        return User::obterOuCriarPorCodpes($codpes);
    }
}

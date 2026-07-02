<?php

namespace App\Models;

use App\Jobs\AlertaCandidatosIncompletude;
use App\Observers\SelecaoObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Uspdev\Replicado\Estrutura;

class Selecao extends Model
{
    use \Glorand\Model\Settings\Traits\HasSettingsField;
    use HasFactory;

    # selecoes não segue convenção do laravel para nomes de tabela
    protected $table = 'selecoes';

    public $defaultSettings = [
        'instrucoes' => '',
    ];

    public $settingsRules = [
        'instrucoes' => 'nullable',
    ];

    # valores default na criação de nova seleção
    # (o campo template de $attributes contém um modelo padrão para o formulário de inscrição da seleção)
    protected $attributes = [
        'estado' => 'Em Elaboração',
        'template' => '{
            "nome": {
                "label": "Nome",
                "type": "text",
                "validate": "required",
                "order": 0
            },
            "nome_social": {
                "label": "Nome Social",
                "type": "text",
                "help": "Decreto Estadual n. 55.588, de 17/03/2010",
                "order": 1
            },
            "tipo_de_documento": {
                "label": "Tipo de Documento",
                "type": "select",
                "value": [
                    {
                        "label": "RG",
                        "value": "rg",
                        "order": 0
                    },
                    {
                        "label": "RNE",
                        "value": "rne",
                        "order": 1
                    },
                    {
                        "label": "Passaporte",
                        "value": "passaporte",
                        "order": 2
                    }
                ],
                "help": "Utilize o passaporte apenas se não possuir documento de identidade brasileira (RG)",
                "validate": "required",
                "order": 2
            },
            "numero_do_documento": {
                "label": "Número do Documento",
                "type": "text",
                "validate": "required",
                "order": 3
            },
            "data_vencto_passaporte": {
                "label": "Data de Vencimento do Passaporte",
                "type": "date",
                "order": 4
            },
            "cpf": {
                "label": "CPF",
                "type": "text",
                "validate": "required",
                "order": 5
            },
            "titulo_de_eleitor": {
                "label": "Título de Eleitor",
                "type": "text",
                "order": 6
            },
            "documento_militar": {
                "label": "Documento Militar",
                "type": "text",
                "help": "Quando pertinente",
                "order": 7
            },
            "nome_da_mae": {
                "label": "Nome da Mãe",
                "type": "text",
                "validate": "required",
                "order": 8
            },
            "nome_do_pai": {
                "label": "Nome do Pai",
                "type": "text",
                "order": 9
            },
            "data_de_nascimento": {
                "label": "Data de Nascimento",
                "type": "date",
                "validate": "required",
                "order": 10
            },
            "local_de_nascimento": {
                "label": "Local de Nascimento",
                "type": "text",
                "validate": "required",
                "order": 11
            },
            "uf_de_nascimento": {
                "label": "UF de Nascimento",
                "type": "select",
                "value": [
                    {
                        "label": "AC",
                        "value": "ac",
                        "order": 0
                    },
                    {
                        "label": "AL",
                        "value": "al",
                        "order": 1
                    },
                    {
                        "label": "AM",
                        "value": "am",
                        "order": 2
                    },
                    {
                        "label": "AP",
                        "value": "ap",
                        "order": 3
                    },
                    {
                        "label": "BA",
                        "value": "ba",
                        "order": 4
                    },
                    {
                        "label": "CE",
                        "value": "ce",
                        "order": 5
                    },
                    {
                        "label": "DF",
                        "value": "df",
                        "order": 6
                    },
                    {
                        "label": "ES",
                        "value": "es",
                        "order": 7
                    },
                    {
                        "label": "GO",
                        "value": "go",
                        "order": 8
                    },
                    {
                        "label": "MA",
                        "value": "ma",
                        "order": 9
                    },
                    {
                        "label": "MG",
                        "value": "mg",
                        "order": 10
                    },
                    {
                        "label": "MS",
                        "value": "ms",
                        "order": 11
                    },
                    {
                        "label": "MT",
                        "value": "mt",
                        "order": 12
                    },
                    {
                        "label": "PA",
                        "value": "pa",
                        "order": 13
                    },
                    {
                        "label": "PB",
                        "value": "pb",
                        "order": 14
                    },
                    {
                        "label": "PE",
                        "value": "pe",
                        "order": 15
                    },
                    {
                        "label": "PI",
                        "value": "pi",
                        "order": 16
                    },
                    {
                        "label": "PR",
                        "value": "pr",
                        "order": 17
                    },
                    {
                        "label": "RJ",
                        "value": "rj",
                        "order": 18
                    },
                    {
                        "label": "RN",
                        "value": "rn",
                        "order": 19
                    },
                    {
                        "label": "RO",
                        "value": "ro",
                        "order": 20
                    },
                    {
                        "label": "RR",
                        "value": "rr",
                        "order": 21
                    },
                    {
                        "label": "RS",
                        "value": "rs",
                        "order": 22
                    },
                    {
                        "label": "SC",
                        "value": "sc",
                        "order": 23
                    },
                    {
                        "label": "SE",
                        "value": "se",
                        "order": 24
                    },
                    {
                        "label": "SP",
                        "value": "sp",
                        "order": 25
                    },
                    {
                        "label": "TO",
                        "value": "to",
                        "order": 26
                    }
                ],
                "validate": "required",
                "order": 12
            },
            "sexo": {
                "label": "Sexo",
                "type": "select",
                "value": [
                    {
                        "label": "Masculino",
                        "value": "masculino",
                        "order": 0
                    },
                    {
                        "label": "Feminino",
                        "value": "feminino",
                        "order": 1
                    }
                ],
                "validate": "required",
                "order": 13
            },
            "raca_cor": {
                "label": "Raça/Cor",
                "type": "select",
                "value": [
                    {
                        "label": "Amarela",
                        "value": "amarela",
                        "order": 0
                    },
                    {
                        "label": "Branca",
                        "value": "branca",
                        "order": 1
                    },
                    {
                        "label": "Indígena",
                        "value": "indigena",
                        "order": 2
                    },
                    {
                        "label": "Parda",
                        "value": "parda",
                        "order": 3
                    },
                    {
                        "label": "Preta",
                        "value": "preta",
                        "order": 4
                    },
                    {
                        "label": "Prefiro Não Responder",
                        "value": "prefiro_nao_responder",
                        "order": 5
                    }
                ],
                "validate": "required",
                "order": 14
            },
            "declaro_ppi": {
                "label": "Declaro, para os devidos fins, que sou preto, pardo ou indígena",
                "type": "radio",
                "value": [
                    {
                        "label": "Não",
                        "value": "nao",
                        "order": 0
                    },
                    {
                        "label": "Sim",
                        "value": "sim",
                        "order": 1
                    }
                ],
                "validate": "required",
                "order": 15
            },
            "portador_de_deficiencia": {
                "label": "Portador de Deficiência",
                "type": "radio",
                "value": [
                    {
                        "label": "Não",
                        "value": "nao",
                        "order": 0
                    },
                    {
                        "label": "Sim",
                        "value": "sim",
                        "order": 1
                    }
                ],
                "validate": "required",
                "order": 16
            },
            "qual_a_sua_deficiencia": {
                "label": "Qual a sua deficiência",
                "type": "text",
                "order": 17
            },
            "condicoes_prova": {
                "label": "Condições Necessárias para a Realização da Prova",
                "type": "textarea",
                "order": 18
            },
            "cep": {
                "label": "CEP",
                "type": "text",
                "validate": "required",
                "order": 19
            },
            "endereco_residencial": {
                "label": "Endereço Residencial",
                "type": "text",
                "validate": "required",
                "order": 20
            },
            "numero": {
                "label": "Número",
                "type": "text",
                "validate": "required",
                "order": 21
            },
            "complemento": {
                "label": "Complemento",
                "type": "text",
                "order": 22
            },
            "bairro": {
                "label": "Bairro",
                "type": "text",
                "validate": "required",
                "order": 23
            },
            "cidade": {
                "label": "Cidade",
                "type": "text",
                "validate": "required",
                "order": 24
            },
            "uf": {
                "label": "UF",
                "type": "select",
                "value": [
                    {
                        "label": "AC",
                        "value": "ac",
                        "order": 0
                    },
                    {
                        "label": "AL",
                        "value": "al",
                        "order": 1
                    },
                    {
                        "label": "AM",
                        "value": "am",
                        "order": 2
                    },
                    {
                        "label": "AP",
                        "value": "ap",
                        "order": 3
                    },
                    {
                        "label": "BA",
                        "value": "ba",
                        "order": 4
                    },
                    {
                        "label": "CE",
                        "value": "ce",
                        "order": 5
                    },
                    {
                        "label": "DF",
                        "value": "df",
                        "order": 6
                    },
                    {
                        "label": "ES",
                        "value": "es",
                        "order": 7
                    },
                    {
                        "label": "GO",
                        "value": "go",
                        "order": 8
                    },
                    {
                        "label": "MA",
                        "value": "ma",
                        "order": 9
                    },
                    {
                        "label": "MG",
                        "value": "mg",
                        "order": 10
                    },
                    {
                        "label": "MS",
                        "value": "ms",
                        "order": 11
                    },
                    {
                        "label": "MT",
                        "value": "mt",
                        "order": 12
                    },
                    {
                        "label": "PA",
                        "value": "pa",
                        "order": 13
                    },
                    {
                        "label": "PB",
                        "value": "pb",
                        "order": 14
                    },
                    {
                        "label": "PE",
                        "value": "pe",
                        "order": 15
                    },
                    {
                        "label": "PI",
                        "value": "pi",
                        "order": 16
                    },
                    {
                        "label": "PR",
                        "value": "pr",
                        "order": 17
                    },
                    {
                        "label": "RJ",
                        "value": "rj",
                        "order": 18
                    },
                    {
                        "label": "RN",
                        "value": "rn",
                        "order": 19
                    },
                    {
                        "label": "RO",
                        "value": "ro",
                        "order": 20
                    },
                    {
                        "label": "RR",
                        "value": "rr",
                        "order": 21
                    },
                    {
                        "label": "RS",
                        "value": "rs",
                        "order": 22
                    },
                    {
                        "label": "SC",
                        "value": "sc",
                        "order": 23
                    },
                    {
                        "label": "SE",
                        "value": "se",
                        "order": 24
                    },
                    {
                        "label": "SP",
                        "value": "sp",
                        "order": 25
                    },
                    {
                        "label": "TO",
                        "value": "to",
                        "order": 26
                    }
                ],
                "validate": "required",
                "order": 25
            },
            "celular": {
                "label": "Celular",
                "type": "text",
                "validate": "required",
                "order": 26
            },
            "e_mail": {
                "label": "E-mail",
                "type": "email",
                "validate": "required",
                "order": 27
            },
            "declaro_concordo_termos": {
                "label": "Declaro estar ciente e concordo com os <a href=\"#\">termos de inscrição no Programa de Pós-Graduação do(a) {{NOME UNIDADE}}</a>",
                "type": "checkbox",
                "validate": "required",
                "order": 28
            },
            "declaro_revisei_inscricao": {
                "label": "Declaro que revisei todas as informações inseridas neste formulário e que elas estão corretas, e venho requerer minha inscrição como candidato(a) à vaga no Programa de Pós-Graduação no(a) {{NOME UNIDADE}}",
                "type": "checkbox",
                "validate": "required",
                "order": 29
            },
            "declaro_ciente_nao_presencial": {
                "label": "Declaro estar ciente de que o processo seletivo será realizado no formato não presencial, on-line, e que a <u>Comissão de Seleção não se responsabiliza por eventuais falhas técnicas por parte do(a) candidato(a) (tais como falta de internet, cortes de som, corte de luz, etc.) durante a realização das provas e das arguições relizadas online</u>. A sugestão é que o(a) candidato(a) se organize com antecedência para o bom andamento da prova",
                "type": "checkbox",
                "validate": "required",
                "order": 30
            }
        }',
    ];

    protected $fillable = [
        'ingresso_semestre',
        'ingresso_ano',
        'nome',
        'descricao',
        'tem_taxa',
        'fluxo_continuo',
        'solicitacoesisencaotaxa_datahora_inicio',
        'solicitacoesisencaotaxa_datahora_fim',
        'inscricoesmatriculas_datahora_inicio',
        'inscricoesmatriculas_datahora_fim',
        'boleto_valor',
        'boleto_texto',
        'boleto_data_vencimento',
        'boleto_offset_vencimento',
        'email_inscricaomatriculaaprovacao_texto',
        'email_inscricaomatricularejeicao_texto',
        'categoria_id',
        'programa_id',
        'estado',
        'template',
    ];

    // uso no crud generico
    protected const fields = [
        [
            'name' => 'categoria_id',
            'label' => 'Categoria',
            'type' => 'select',
            'model' => 'Categoria',
            'data' => [],
        ],
        [
            'name' => 'programa_id',
            'label' => 'Programa',
            'type' => 'select',
            'model' => 'Programa',
            'data' => [],
        ],
        [
            'name' => 'ingresso_semestre',
            'label' => 'Semestre de Ingresso',
            'type' => 'radio',
            'data' => ['0' => 'Não definido', '1' => '1º', '2' => '2º'],
        ],
        [
            'name' => 'ingresso_ano',
            'label' => 'Ano de Ingresso',
            'type' => 'select',
            'data' => [],
            ],
        [
            'name' => 'descricao',
            'label' => 'Descrição',
        ],
        [
            'name' => 'tem_taxa',
            'label' => 'Taxa de Inscrição/Matrícula para a Seleção',    // a view altera este label dinamicamente
            'type' => 'checkbox',
        ],
        [
            'name' => 'fluxo_continuo',
            'label' => 'Fluxo Contínuo (solicitações de isenção de taxa e inscrições/matrículas ocorrem no mesmo período)',    // a view altera este label dinamicamente
            'type' => 'checkbox',
        ],
        [
            'name' => 'solicitacoesisencaotaxa_datahora_inicio',
            'label' => 'Início das Solicitações de Isenção de Taxa',
            'type' => 'datetime',
        ],
        [
            'name' => 'solicitacoesisencaotaxa_datahora_fim',
            'label' => 'Fim das Solicitações de Isenção de Taxa',
            'type' => 'datetime',
        ],
        [
            'name' => 'inscricoesmatriculas_datahora_inicio',
            'label' => 'Início das Inscrições/Matrículas',    // a view altera este label dinamicamente
            'type' => 'datetime',
        ],
        [
            'name' => 'inscricoesmatriculas_datahora_fim',
            'label' => 'Fim das Inscrições/Matrículas',    // a view altera este label dinamicamente
            'type' => 'datetime',
        ],
        [
            'name' => 'boleto_data_vencimento',
            'label' => 'Data de Vencimento do Boleto',
            'type' => 'date',
        ],
        [
            'name' => 'boleto_offset_vencimento',
            'label' => 'Dias Úteis para Pagamento do Boleto',
            'type' => 'integer',
        ],
        [
            'name' => 'boleto_valor',
            'label' => 'Valor do Boleto (R$)',
            'type' => 'number',
        ],
        [
            'name' => 'boleto_texto',
            'label' => 'Eventuais Informações Adicionais no Boleto',
        ],
        [
            'name' => 'email_inscricaomatriculaaprovacao_texto',
            'label' => 'Eventuais Informações Adicionais no E-mail de Aprovação da Inscrição/Matrícula',    // a view altera este label dinamicamente
        ],
        [
            'name' => 'email_inscricaomatricularejeicao_texto',
            'label' => 'Eventuais Informações Adicionais no E-mail de Rejeição da Inscrição/Matrícula',    // a view altera este label dinamicamente
        ],
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        Selecao::observe(SelecaoObserver::class);
    }

    // uso no crud generico
    public static function getFields()
    {
        $fields = self::fields;
        foreach ($fields as &$field)
            if (substr($field['name'], -3) == '_id') {
                $class = '\\App\\Models\\' . $field['model'];
                $field['data'] = $class::allToSelect();
            } elseif ($field['name'] == 'ingresso_ano') {
                $anoAtual = date('Y');
                $field['data'] = array_combine(range($anoAtual, $anoAtual + 3), range($anoAtual, $anoAtual + 3));
            }
        return $fields;
    }

    /**
     * template
     * retorna os campos do template do formulario
     */
    public static function getTemplateFields()
    {
        return ['label', 'type', 'can', 'visible_campo', 'visible_valor', 'help', 'value', 'validate'];
    }

    /**
     * retorna todas as seleções autorizadas para o usuário
     * utilizado nas views common, para o select
     */
    public static function allToSelect()
    {
        $selecoes = self::get();
        $ret = [];
        foreach ($selecoes as $selecao)
            if (Gate::allows('selecoes.view'))
                $ret[$selecao->id] = $selecao->nome . ' (' . $selecao->categoria->nome . ')';
        return $ret;
    }

    /**
     * lista de estados padrão
     */
    public static function estados()
    {
        // nos estados, quando se diz "Inscrições", pode ser na verdade "Matrículas"
        // mantemos "Inscrições" pois as views trocam o texto para "Matrículas" quando for o caso
        return ['Em Elaboração',
                'Aguardando Início das Solicitações de Isenção de Taxa e das Inscrições/Matrículas', 'Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas',    // usados nos casos de fluxo contínuo com taxa
                'Aguardando Início das Solicitações de Isenção de Taxa', 'Período de Solicitações de Isenção de Taxa',                                                           // usados nos casos de fluxo normal com taxa
                'Aguardando Início das Inscrições/Matrículas', 'Periodo de Inscrições/Matrículas',                                                                               // usados nos casos sem taxa
                'Encerrada'];
    }

    /**
     * Accessor getter para $config
     */
    public function getConfigAttribute(string $value)
    {
        $value = json_decode($value);

        $out = new \StdClass;
        $out->status = $value->status ?? config('selecoes.config.status');
        return $out;
    }

    /**
     * Accessor setter para $config
     */
    public function setConfigAttribute(string|array $value)
    {
        // quando este método é invocado pelo seeder, $value vem como string JSON
        // quando este método é invocado pelo MVC, $value vem como array

        if (is_string($value)) {
            $value_decoded = json_decode($value, true); // Decodifica como array associativo
            if (is_array($value_decoded) && (json_last_error() == JSON_ERROR_NONE))
                $value = $value_decoded;    // se $value veio como string JSON, vamos utilizar $value_decoded, de modo a poder acessá-lo mais abaixo como array
        }

        $config = new \StdClass;
        $config->status = $value['status'];
        $this->attributes['config'] = json_encode($config);
    }

    /**
     * Accessor para $template
     */
    public function getTemplateAttribute(string $value)
    {
        return (empty($value)) ? '{}' : $value;
    }

    /**
     * Menu Seleções, lista as seleções
     *
     * @return coleção de seleções
     */
    public static function listarSelecoes()
    {
        return self::whereIn('programa_id', \Auth::user()->listarProgramasGerenciados()->pluck('id'))              // seleções de programas que o usuário gerencia, e também...
                    ->orWhere(function ($query) {
                        $query->where('categoria_id', Categoria::where('nome', 'Aluno Especial')->value('id'));    // seleções para alunos especiais (sem programa), desde que, neste segundo caso...
                        $query->where(function ($query) {
                            if (session('perfil') == 'admin')                                                      // desde que o usuário seja admin, ou...
                                return;
                            $query->orWhereExists(function ($query) {
                                $query->select(\DB::raw(1))
                                    ->from('user_programa')
                                    ->where('user_id', \Auth::id())
                                    ->whereIn('funcao', ['Serviço de Pós-Graduação', 'Coordenadores da Pós-Graduação']);    // ou que o usuário seja do Serviço de Pós-Graduação ou Coordenadores da Pós-Graduação
                            });
                        });
                    })
                    ->get();
    }

    /**
     * Mostra lista de categorias e respectivas seleções
     * para selecionar e solicitar isenção de taxa
     *
     * @return \Illuminate\Http\Response
     */
    public static function listarSelecoesParaNovaSolicitacaoIsencaoTaxa()
    {
        $categorias = Categoria::get();                                  // primeiro vamos pegar todas as seleções
        foreach ($categorias as $categoria) {                            // e depois filtrar as que não pode
            $selecoes = $categoria->selecoes;                            // primeiro vamos pegar todas as seleções
            $selecoes = $selecoes->filter(fn($selecao) => in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Solicitações de Isenção de Taxa']));    // só aceita as seleções que estejam em período de solicitações de isenção de taxa
            $categoria->selecoes = $selecoes;
        }
        return $categorias;                                              // retorna as seleções dentro de categorias
    }

    /**
     * Mostra lista de categorias e respectivas seleções
     * para selecionar e criar nova inscrição
     *
     * @return \Illuminate\Http\Response
     */
    public static function listarSelecoesParaNovaInscricao()
    {
        $categorias = Categoria::get();                                  // primeiro vamos pegar todas as seleções
        foreach ($categorias as $categoria) {                            // e depois filtrar as que não pode
            $selecoes = $categoria->selecoes;                            // primeiro vamos pegar todas as seleções
            $selecoes = $selecoes->filter(fn($selecao) =>
                in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Inscrições/Matrículas'])    // só aceita as seleções que estejam em período de inscrições
                && !$selecao->isMatricula()
            );
            foreach ($selecoes as $selecao)
                $selecao->niveis = $selecao->niveislinhaspesquisa->sortBy('nivel_id')->pluck('nivel')->unique();
            $categoria->selecoes = $selecoes;
        }
        return $categorias;                                              // retorna as seleções dentro de categorias
    }

    /**
     * Mostra lista de categorias e respectivas seleções
     * para selecionar e criar nova matrícula
     *
     * @return \Illuminate\Http\Response
     */
    public static function listarSelecoesParaNovaMatricula()
    {
        $categorias = Categoria::get();                                  // primeiro vamos pegar todas as seleções
        foreach ($categorias as $categoria) {                            // e depois filtrar as que não pode
            $selecoes = $categoria->selecoes;                            // primeiro vamos pegar todas as seleções
            $selecoes = $selecoes->filter(fn($selecao) =>
                in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Inscrições/Matrículas'])    // só aceita as seleções que estejam em período de matrículas
                && $selecao->isMatricula()
            );
            foreach ($selecoes as $selecao)
                $selecao->niveis = $selecao->niveislinhaspesquisa->sortBy('nivel_id')->pluck('nivel')->unique();
            $categoria->selecoes = $selecoes;
        }
        return $categorias;                                              // retorna as seleções dentro de categorias
    }

    /**
     * Atualiza o status da seleção
     * Esta é a máquina de fluxo de estados da seleção
     */
    public function atualizarStatus()
    {
        $tiposarquivo_required = TipoArquivo::where('classe_nome', 'Seleções')->where('obrigatorio', true)->pluck('nome')->filter(function ($nome) {
            return ($nome !== 'Normas para Isenção de Taxa') || $this->tem_taxa;
        })->toArray();
        $possui_todos_os_arquivos_required = true;
        foreach ($tiposarquivo_required as $tipoarquivo_required)
            if (!$this->arquivos->contains('pivot.tipo', $tipoarquivo_required)) {
                $possui_todos_os_arquivos_required = false;
                break;
            }

        $outras_condicoes_satisfeitas = (($this->categoria->nome !== 'Aluno Especial') ? !$this->niveislinhaspesquisa->isEmpty() : !$this->disciplinas->isEmpty());
        if (!$possui_todos_os_arquivos_required || !$outras_condicoes_satisfeitas)
            $this->update(['estado' => 'Em Elaboração']);
        else {
            $agora = Carbon::now();
            if ($this->tem_taxa) {
                if (!$this->fluxo_continuo) {
                    // fluxo normal com taxa
                    if ($agora < $this->solicitacoesisencaotaxa_datahora_inicio)
                        $this->update(['estado' => 'Aguardando Início das Solicitações de Isenção de Taxa']);
                    elseif (($this->solicitacoesisencaotaxa_datahora_inicio <= $agora) && ($agora <= $this->solicitacoesisencaotaxa_datahora_fim))
                        $this->update(['estado' => 'Período de Solicitações de Isenção de Taxa']);
                    elseif (($this->solicitacoesisencaotaxa_datahora_fim < $agora) && ($agora < $this->inscricoesmatriculas_datahora_inicio))
                        $this->update(['estado' => 'Aguardando Início das Inscrições/Matrículas']);
                    elseif (($this->inscricoesmatriculas_datahora_inicio <= $agora) && ($agora <= $this->inscricoesmatriculas_datahora_fim))
                        $this->update(['estado' => 'Período de Inscrições/Matrículas']);
                } else {
                    // fluxo contínuo com taxa
                    if ($agora < $this->inscricoesmatriculas_datahora_inicio)
                        $this->update(['estado' => 'Aguardando Início das Solicitações de Isenção de Taxa e das Inscrições/Matrículas']);
                    elseif (($this->inscricoesmatriculas_datahora_inicio <= $agora) && ($agora <= $this->inscricoesmatriculas_datahora_fim))
                        $this->update(['estado' => 'Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas']);
                }
            } else
                // sem taxa
                if ($agora < $this->inscricoesmatriculas_datahora_inicio)
                    $this->update(['estado' => 'Aguardando Início das Inscrições/Matrículas']);
                elseif (($this->inscricoesmatriculas_datahora_inicio <= $agora) && ($agora <= $this->inscricoesmatriculas_datahora_fim))
                    $this->update(['estado' => 'Período de Inscrições/Matrículas']);

            if ($this->inscricoesmatriculas_datahora_fim < $agora)
                $this->update(['estado' => 'Encerrada']);
        }
    }

    public function reagendarTarefas()
    {
        // este método é invocado tanto na criação quanto na alteração de seleção
        // quando o usuário altera uma seleção, eventualmente ele pode alterar as datas de fim
        // neste caso, ao invés de alterarmos as datas/horas dos jobs da seleção, simplesmente os removemos e os recriamos logo em seguida, considerando as datas de fim eventualmente alteradas

        // remove jobs de alerta de solicitações de isenção de taxa, inscrições e matrículas não concluídas
        foreach (DB::table('jobs')->where('payload->displayName', 'App\Jobs\AlertaCandidatosIncompletude')->get() as $job) {
            $command = obterCommandDoJob($job);
            if ($command) {
                $selecao_id = obterPropriedadePrivada($command, 'selecao_id');
                if ($selecao_id == $this->id)
                    DB::table('jobs')->where('id', $job->id)->delete();
            }
        }

        // (re)agenda job de alerta de solicitações de isenção de taxa não concluídas
        if ($this->tem_taxa) {
            $job_datahora = Carbon::parse($this->solicitacoesisencaotaxa_datahora_fim)->subHours(24);
            if ($job_datahora > now())
                AlertaCandidatosIncompletude::dispatch($this->id, 'SolicitacaoIsencaoTaxa')->delay($job_datahora);
        }

        if (!$this->isMatricula()) {
            // (re)agenda job de alerta de inscrições não concluídas
            $job_datahora = Carbon::parse($this->inscricoesmatriculas_datahora_fim)->subHours(24);
            if ($job_datahora > now())
                AlertaCandidatosIncompletude::dispatch($this->id, 'Inscricao')->delay($job_datahora);
        } else {
            // (re)agenda job de alerta de matrículas não concluídas
            $job_datahora = Carbon::parse($this->inscricoesmatriculas_datahora_fim)->subHours(24);
            if ($job_datahora > now())
                AlertaCandidatosIncompletude::dispatch($this->id, 'Matricula')->delay($job_datahora);
        }
    }

    public function contarSolicitacoesIsencaoTaxaPorAno()
    {
        return SolicitacaoIsencaoTaxa::contarSolicitacoesIsencaoTaxaPorAno($this);
    }

    public function contarSolicitacoesIsencaoTaxaPorMes(int $ano)
    {
        return SolicitacaoIsencaoTaxa::contarSolicitacoesIsencaoTaxaPorMes($ano, $this);
    }

    public function contarInscricoesPorAno()
    {
        return Inscricao::contarInscricoesPorAno($this);
    }

    public function contarInscricoesPorMes(int $ano)
    {
        return Inscricao::contarInscricoesPorMes($ano, $this);
    }

    public function contarMatriculasPorAno()
    {
        return Matricula::contarMatriculasPorAno($this);
    }

    public function contarMatriculasPorMes(int $ano)
    {
        return Matricula::contarMatriculasPorMes($ano, $this);
    }

    // função para generalizar o nome da unidade, baseando-se no valor do .env
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->injetarUnidadeNoTemplate();
    }

    // como o template é protegido, precisamos de um método para inserir o nome da unidade
    private function injetarUnidadeNoTemplate()
    {
        if (empty($this->attributes['template'])) return;

        $unidade = Estrutura::obterUnidade(config('senhaunica.codigoUnidade'))['nomund'] ?? 'Unidade';

        $this->attributes['template'] = str_replace(
            '{{NOME UNIDADE}}',
            $unidade,
            $this->attributes['template']
        );
    }

    public function isMatricula()
    {
        if (!$this->categoria)    // acontece quando a seleção está sendo criada, ainda não tem categoria associada
            return false;

        return (($this->categoria->nome == 'Aluno Especial') || $this->programa->matricula);
    }

    /**
     * Seleção possui Solicitações de Isenção de Taxa
     */
    public function solicitacoesisencaotaxa()
    {
        return $this->hasMany('App\Models\SolicitacaoIsencaoTaxa');
    }

    /**
     * Seleção possui Inscrições
     */
    public function inscricoes()
    {
        return $this->hasMany('App\Models\Inscricao');
    }

    /**
     * Seleção possui Matrículas
     */
    public function matriculas()
    {
        return $this->hasMany('App\Models\Matricula');
    }

    /**
     * relacionamento com arquivos
     */
    public function arquivos()
    {
        return $this->belongsToMany('App\Models\Arquivo', 'arquivo_selecao')->withPivot('tipo')->withTimestamps();
    }

    /**
     * relacionamento com linhas de pesquisa/temas
     */
    public function linhaspesquisa()
    {
        return $this->belongsToMany('App\Models\LinhaPesquisa', 'selecao_linhapesquisa', 'selecao_id', 'linhapesquisa_id')->withTimestamps();
    }

    /**
     * relacionamento com disciplinas
     */
    public function disciplinas()
    {
        return $this->belongsToMany('App\Models\Disciplina', 'selecao_disciplina', 'selecao_id', 'disciplina_id')->withTimestamps();
    }

    /**
     * relacionamento com motivos de isenção de taxa
     */
    public function motivosisencaotaxa()
    {
        return $this->belongsToMany('App\Models\MotivoIsencaoTaxa', 'selecao_motivoisencaotaxa', 'selecao_id', 'motivoisencaotaxa_id')->withTimestamps();
    }

    /**
     * relacionamento com tipos de arquivo
     */
    public function tiposarquivo()
    {
        return $this->belongsToMany('App\Models\TipoArquivo', 'selecao_tipoarquivo', 'selecao_id', 'tipoarquivo_id')->withTimestamps();
    }

    /**
     * Relacionamento: seleção pertence a categoria
     */
    public function categoria()
    {
        return $this->belongsTo('App\Models\Categoria');
    }

    /**
     * Relacionamento: seleção pertence a programa
     */
    public function programa()
    {
        return $this->belongsTo('App\Models\Programa');
    }

    /**
     * relacionamento com combinações de níveis com linhas de pesquisa/temas
     */
    public function niveislinhaspesquisa()
    {
        return $this->belongsToMany('App\Models\NivelLinhaPesquisa', 'selecao_nivellinhapesquisa', 'selecao_id', 'nivellinhapesquisa_id')->withTimestamps();
    }
}

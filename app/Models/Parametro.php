<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class Parametro extends Model
{
    use HasFactory;

    protected $fillable = [
        'boleto_codigo_fonte_recurso',
        'boleto_estrutura_hierarquica',
        'boleto_momento_envio',
        'link_inscricao_termos',
        'link_acompanhamento_especiais',
        'processos_especiais',
        'max_disciplinas_aluno_especial',
        'email_servicoposgraduacao',
        'email_secaoinformatica',
        'email_gerenciamentosite',
    ];

    // uso no crud generico
    protected const fields = [
        [
            'name' => 'boleto_codigo_fonte_recurso',
            'label' => 'Código Fonte do Recurso para Boleto',
            'type' => 'integer',
        ],
        [
            'name' => 'boleto_estrutura_hierarquica',
            'label' => 'Estrutura Hierárquica para Boleto',
        ],
        [
            'name' => 'boleto_momento_envio',
            'label' => 'Momento de Geração e Envio do Boleto',
            'type' => 'radio',
            'data' => ['Envio da Inscrição/Matrícula' => 'Envio da Inscrição/Matrícula', 'Aprovação da Inscrição/Matrícula' => 'Aprovação da Inscrição/Matrícula'],
        ],
        [
            'name' => 'link_inscricao_termos',
            'label' => 'Link para os Termos de Inscrição',
        ],
        [
            'name' => 'link_acompanhamento_especiais',
            'label' => 'Endereço no Site da Unidade para Acompanhamento do Processo pelos Candidatos a Aluno Especial',
        ],
        [
            'name' => 'processos_especiais',
            'label' => 'Processo(s) Utilizado(s) para Aluno Especial',
            'type' => 'select',
            'data' => ['Inscrição' => 'Inscrição', 'Inscrição e Matrícula' => 'Inscrição e Matrícula', 'Matrícula' => 'Matrícula'],
        ],
        [
            'name' => 'max_disciplinas_aluno_especial',
            'label' => 'Número Máximo de Disciplinas Permitidas a Aluno Especial',
            'type' => 'integer',
        ],
        [
            'name' => 'email_servicoposgraduacao',
            'label' => 'E-mail do Serviço de Pós-Graduação',
        ],
        [
            'name' => 'email_secaoinformatica',
            'label' => 'E-mail da Seção de Informática',
        ],
        [
            'name' => 'email_gerenciamentosite',
            'label' => 'E-mail da Equipe de Gerenciamento do Site da Unidade',
        ],
    ];

    // uso no crud generico
    public static function getFields()
    {
        return self::fields;
    }

    public function especiaisFazInscricoes()
    {
        return str_contains($this->processos_especiais, 'Inscrição');
    }

    public function especiaisFazMatriculas()
    {
        return str_contains($this->processos_especiais, 'Matrícula');
    }

    public function permiteTaxa()
    {
        return true;    // vai depender do vínculo (a ser implementado no futuro, quando este selecoes-pos se tornar selecoes)
    }
}

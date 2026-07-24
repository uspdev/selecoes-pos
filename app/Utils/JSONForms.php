<?php

namespace App\Utils;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Spatie\Html\Facades\Html;

class JSONForms
{
    /**
     * Valida os campos do formulário
     *
     * @param $request Campos do formulário a serem validados
     * @param $selecao Seleção de onde vai pegar as regras de validação
     *
     * @return Array Contendo a validação
     */
    public static function buildRules($request, $selecao, $classe_nome)
    {
        $template = json_decode($selecao->{'template_' . ClasseUtils::obterClasseNomePlural($classe_nome)});
        $validate = [];
        if ($template)
            foreach ($template as $key => $json)
                if (isset($json->validate)) {
                    $field = 'extras.' . $key;
                    $validate[$field] = $json->validate;
                }
        return $validate;
    }

    /**
     * Renderiza o formulário como array contendo html
     */
    protected static function JSON2Form($selecao, $template, $data, $perfil, $classe_nome)
    {
        // em $template, tenho todos os campos do formulário da classe em questão

        // no template de solicitações de isenção de taxa, não é gravado o campo de motivos de isenção de taxa, por ele ser preenchido dinamicamente abaixo
        // no template de inscrições, não é gravado o campo de linhas de pesquisa, por ele ser preenchido dinamicamente abaixo
        // no template de inscrições, não é gravado o campo de orientador, por ele ser preenchido dinamicamente abaixo

        $form = [];
        foreach ($template as $key => $json) {
            $input = [];
            $type = $json->type;
            $value = $data->$key ?? null;

            $required_attrib = '';
            $required_string = '';
            if (isset($json->validate) && $json->validate) {
                $required_attrib = ' required';
                $required_string = ' <small class="text-required"' . (in_array($key, ['cpf', 'uf_de_nascimento']) ? ' id="' . $key . '_required"' : '') . '>(*)</small>';
            }

            $label = $template->$key->label;
            $label_parts = explode (' ', $label);
            $label_last_word = array_pop($label_parts);
            $label_formatted = implode(' ', $label_parts) . ' <span style="white-space: nowrap;">' . $label_last_word . $required_string . '</span>';
            $html_string          =   '<div class="col-sm-3">' . PHP_EOL .
                                        '<label class="col-form-label va-middle" for="extras[' . $key . ']">' . $label_formatted . '</label> ' . PHP_EOL .
                                        '</div>' . PHP_EOL;
            $html_string_motivoisencaotaxa = '';
            $html_string_linhapesquisa = '';
            $html_string_orientador = '';

            switch ($type) {
                case 'select':
                    $json->value = JSONForms::simplifyTemplate($json->value);
                    $html_string .=   '<div class="col-sm-9">' . PHP_EOL .
                                        '<select class="form-control w-100" name="extras[' . $key . ']" id="extras[' . $key . ']"' . $required_attrib . '>' . PHP_EOL .
                                        '<option value="" disabled selected>Selecione...</option>' . PHP_EOL;
                    foreach ($json->value as $key2 => $option)    // não posso reaproveitar $key aqui, pois ele será utilizado mais abaixo
                        $html_string .=   '<option value="' . $key2 . '"' . ($key2 == $value ? ' selected' : '') . '>' . $option . '</option>' . PHP_EOL;
                    $html_string .=     '</select>' . PHP_EOL .
                                        '</div>' . PHP_EOL;
                    break;

                case 'date':
                    $html_string .=   '<div class="col-sm-2">' . PHP_EOL .
                                        '<input class="form-control datepicker hasDatePicker" name="extras[' . $key . ']" id="extras[' . $key . ']" type="text" value="' . $value . '"' . $required_attrib . '>' . PHP_EOL .
                                        '</div>' . PHP_EOL;
                    break;

                case 'radio':
                    $json->value = JSONForms::simplifyTemplate($json->value);
                    $html_string  =   '<div class="col-sm-12 d-flex flex-column" style="gap: 10px;">' . PHP_EOL .
                                        '<div class="d-flex align-items-center">' . PHP_EOL .
                                        $label . '&nbsp;' . $required_string . PHP_EOL .
                                        '</div>' . PHP_EOL;
                    $primeiro_item = true;
                    foreach ($json->value as $key2 => $option) {    // não posso reaproveitar $key aqui, pois ele será utilizado mais abaixo
                        $html_string .= '<div class="d-flex align-items-center gap-2">' . PHP_EOL .
                                        '&nbsp; &nbsp;' . PHP_EOL .
                                        '<input style="margin: 0; position: relative; top: -1px;" name="extras[' . $key . ']" id="extras[' . $key . '_' . $key2 . ']" value="' . $key2 . '" type="radio"' . ($key2 == $value ? ' checked' : '') . ($primeiro_item ? $required_attrib : '') . '>' . PHP_EOL .
                                        '<label style="margin: 0; padding-left: 5px; position: relative; top: -2px;" for="extras[' . $key . '_' . $key2 . ']">' . $option . '</label>' . PHP_EOL .
                                        '</div>' . PHP_EOL;
                        $primeiro_item = false;
                    }
                    $html_string .=   '</div>' . PHP_EOL;
                    break;

                case 'checkbox':
                    $html_string  =   '<div class="col-sm-12 d-flex align-items-center" style="gap: 10px;">' . PHP_EOL .
                                        '<input class="form-control" style="width: auto; margin: 0;" name="extras[' . $key . ']" id="extras[' . $key . ']" type="checkbox"' . ($value == 'on' ? ' checked' : '') . $required_attrib . '>' . PHP_EOL .
                                        '<label style="margin: 0;" for="extras[' . $key . ']">' . $label . ' ' . $required_string . '</label> ' . PHP_EOL .
                                        '</div>' . PHP_EOL;
                    break;

                case 'textarea':
                    $html_string .=   '<div class="col-sm-9">' . PHP_EOL .
                                        '<textarea class="form-control w-100" name="extras[' . $key . ']" id="extras[' . $key . ']" rows="3"' . $required_attrib . '>' . $value . '</textarea>' . PHP_EOL .
                                        '</div>' . PHP_EOL;
                    break;

                case 'label':
                    $html_string  =   '<div class="col-sm-12">' . PHP_EOL .
                                        '<label class="col-form-label va-middle" for="extras[' . $key . ']">' . $label_formatted . '</label> ' . PHP_EOL .
                                        '</div>' . PHP_EOL;
                    break;

                default:              // contempla os tipos text, number e email
                    $largura = 9;
                    $html_string_adicional = '';
                    if (($key == 'cep') || (strpos($key, 'cep_') === 0)) {
                        $largura = 2;
                        $html_string_adicional .= '<a href="javascript:void(0);" onclick="consultar_cep(\'' . $key . '\')" id="consultar_' . $key . '" class="btn btn-primary">Consultar CEP</a>';
                    }
                    $html_string .=   '<div class="col-sm-' . $largura . '">' . PHP_EOL .
                                        '<input class="form-control w-100" name="extras[' . $key . ']" id="extras[' . $key . ']" type="' . $type . '" value="' . $value . '"' . $required_attrib . '>' . PHP_EOL .
                                        '</div>' . PHP_EOL .
                                        $html_string_adicional;
                    if (($key == 'nome') && ($classe_nome == 'SolicitacaoIsencaoTaxa')) {
                        $html_string_motivoisencaotaxa .=
                                        '<div class="col-sm-3">' . PHP_EOL .
                                        '<label class="col-form-label va-middle" for="extras[motivo_isencao_taxa]">Motivo&nbsp;<small class="text-required">(*)</small></label>' . PHP_EOL .
                                        '</div>' . PHP_EOL .
                                        '<div class="col-sm-9">' . PHP_EOL .
                                        '<select class="form-control w-100" name="extras[motivo_isencao_taxa]" id="extras[motivo_isencao_taxa]" required>' . PHP_EOL .
                                            '<option value="" disabled selected>Selecione...</option>' . PHP_EOL;
                        foreach ($selecao->motivosisencaotaxa as $motivoisencaotaxa)
                            $html_string_motivoisencaotaxa .=
                                            '<option value="' . $motivoisencaotaxa->id . '"' . ((isset($data->motivo_isencao_taxa) && ($motivoisencaotaxa->id == $data->motivo_isencao_taxa)) ? ' selected' : '') . '>' . $motivoisencaotaxa->nome . '</option>' . PHP_EOL;
                        $html_string_motivoisencaotaxa .=
                                        '</select>' . PHP_EOL .
                                        '</div>' . PHP_EOL;
                    }
                    if (($key == 'nome') && in_array($classe_nome, ['Inscricao', 'Matricula']) && $selecao->exigeNivel() && $selecao->exigeLinhaPesquisa()) {
                        $html_string_linhapesquisa .=
                                        '<div class="col-sm-3">' . PHP_EOL .
                                        '<label class="col-form-label va-middle" for="extras[linha_pesquisa]">Linha de <span style="white-space: nowrap;">Pesquisa/Tema&nbsp;<small class="text-required">(*)</small></span></label>' . PHP_EOL .
                                        '</div>' . PHP_EOL .
                                        '<div class="col-sm-9">' . PHP_EOL .
                                        '<select class="form-control w-100" name="extras[linha_pesquisa]" id="extras[linha_pesquisa]" required>' . PHP_EOL .
                                            '<option value="" disabled selected>Selecione...</option>' . PHP_EOL;
                        foreach ($selecao->niveislinhaspesquisa as $nivellinhapesquisa)
                            if ($nivellinhapesquisa->nivel_id == $data->nivel)
                                $html_string_linhapesquisa .=
                                            '<option value="' . $nivellinhapesquisa->linhapesquisa_id . '"' . ((isset($data->linha_pesquisa) && ($nivellinhapesquisa->linhapesquisa_id == $data->linha_pesquisa)) ? ' selected' : '') . '>' . $nivellinhapesquisa->linhapesquisa->nome . '</option>' . PHP_EOL;
                        $html_string_linhapesquisa .=
                                        '</select>' . PHP_EOL .
                                        '</div>' . PHP_EOL;
                    }
                    if (($key == 'nome') && ($classe_nome == 'Inscricao') && $selecao->exigePrograma() && $selecao->exigeOrientador()) {
                        $html_string_orientador .=
                                        '<div class="col-sm-3">' . PHP_EOL .
                                        '<label class="col-form-label va-middle" for="extras[orientador]">Orientador(a)&nbsp;<small class="text-required">(*)</small></span></label>' . PHP_EOL .
                                        '</div>' . PHP_EOL .
                                        '<div class="col-sm-9">' . PHP_EOL .
                                        '<select class="form-control w-100" name="extras[orientador]" id="extras[orientador]" required>' . PHP_EOL .
                                            '<option value="" disabled selected>Selecione...</option>' . PHP_EOL;
                        foreach ($selecao->programa->obterPessoasFuncao('Docentes do Programa') as $orientador)
                            $html_string_orientador .=
                                            '<option value="' . $orientador->id . '"' . ((isset($data->orientador) && ($orientador->id == $data->orientador)) ? ' selected' : '') . '>' . $orientador->nome . '</option>' . PHP_EOL;
                        $html_string_orientador .=
                                        '</select>' . PHP_EOL .
                                        '</div>' . PHP_EOL;
                    }
            }

            // fora do fluxo normal: inclui campo de motivo de isenção de taxa, pois ele não fica no $template
            // este campo é inserido antes do nome
            if ($html_string_motivoisencaotaxa != '')
                $form[] = [new HtmlString($html_string_motivoisencaotaxa)];

            // fora do fluxo normal: inclui campo de linha de pesquisa, pois ele não fica no $template
            // este campo é inserido antes do nome
            if ($html_string_linhapesquisa != '')
                $form[] = [new HtmlString($html_string_linhapesquisa)];

            // fora do fluxo normal: inclui campo de orientador, pois ele não fica no $template
            // este campo é inserido antes do nome
            if ($html_string_orientador != '')
                $form[] = [new HtmlString($html_string_orientador)];

            // se a visibilidade do campo for condicional...
            if (!empty($json->visible_campo)) {
                $html_string .= PHP_EOL .
                                '<script type="text/javascript">' . PHP_EOL;

                // ... gero um código javascript para exibí-lo/ocultá-lo
                $html_string .= ('document.addEventListener("DOMContentLoaded", function () {' . PHP_EOL .    // este addEventListener faz com que se aguarde a carga completa da página para executar o código a seguir... se não fizéssemos isso, tentaria executar imediatamente, dando erro, pois as bibliotecas do jQuery são carregadas ao final da página
                                    '    var condicionante_campo, condicionante_valor, condicional_campo;' . PHP_EOL .
                                    '    condicionante_campo = $(\'[name="extras[' . $json->visible_campo . ']"]\');' . PHP_EOL .
                                    '    condicional_campo = $(\'[name="extras[' . $key . ']"]\');' . PHP_EOL .
                                    '    function atualizaVisibilidade_' . $key . '() {' . PHP_EOL .
                                    '        if (condicionante_campo.is(\':checkbox\'))' . PHP_EOL .
                                    '            condicionante_valor = condicionante_campo.is(\':checked\') ? \'true\' : \'false\';' . PHP_EOL .
                                    '        else if (condicionante_campo.is(\':radio\'))' . PHP_EOL .
                                    '            condicionante_valor = condicionante_campo.filter(\':checked\').val();' . PHP_EOL .
                                    '        else' . PHP_EOL .
                                    '            condicionante_valor = condicionante_campo.val();' . PHP_EOL .
                                    '        if (condicional_campo.data(\'was_required\') === undefined)' . PHP_EOL .
                                    '            condicional_campo.data(\'was_required\', condicional_campo.prop(\'required\'));' . PHP_EOL .
                                    '        if (condicionante_valor == \'' . $json->visible_valor . '\') {' . PHP_EOL .
                                    '            condicional_campo.closest(\'.form-group\').show();' . PHP_EOL .
                                    '            if (condicional_campo.data(\'was_required\'))' . PHP_EOL .
                                    '                condicional_campo.prop(\'required\', true);' . PHP_EOL .
                                    '        } else {' . PHP_EOL .
                                    '            if (condicional_campo.is(\':checkbox, :radio\'))' . PHP_EOL .
                                    '                condicional_campo.prop(\'checked\', false);' . PHP_EOL .
                                    '            else if (condicional_campo.is(\'select\'))' . PHP_EOL .
                                    '                condicional_campo.prop(\'selectedIndex\', 0);' . PHP_EOL .
                                    '            else' . PHP_EOL .
                                    '                condicional_campo.val(\'\');' . PHP_EOL .
                                    '            condicional_campo.prop(\'required\', false);' . PHP_EOL .
                                    '            condicional_campo.closest(\'.form-group\').hide();' . PHP_EOL .
                                    '        }' . PHP_EOL .
                                    '    }' . PHP_EOL);

                // ... gero um código javascript para alterar sua visibilidade quando da carga da página
                $html_string .= ('    atualizaVisibilidade_' . $key . '();' . PHP_EOL);

                // ... gero um código javascript para alterar sua visibilidade quando da alteração do valor do campo que dá a condicionalidade
                $html_string .= ('    function aplicarEvento(elemento) {' . PHP_EOL .
                                    '        if (elemento.is(\'select, :radio\'))' . PHP_EOL .
                                    '            return \'change\';' . PHP_EOL .
                                    '        else if (elemento.is(\':checkbox\'))' . PHP_EOL .
                                    '            return \'change click\';' . PHP_EOL .
                                    '        else' . PHP_EOL .
                                    '            return \'input change\';' . PHP_EOL .
                                    '    }' . PHP_EOL .
                                    '    condicionante_campo.on(aplicarEvento(condicionante_campo), function() {' . PHP_EOL .
                                    '        atualizaVisibilidade_' . $key . '();' . PHP_EOL .
                                    '    });' . PHP_EOL .
                                    '});' . PHP_EOL);

                $html_string .= '</script>' . PHP_EOL;
            }

            // fluxo normal: prepara para incluir o campo propriamente dito
            $input[] = new HtmlString($html_string);

            // fluxo normal: prepara para incluir help
            if (isset($json->help)) {
                $html_string =      '<div class="col-sm-3">&nbsp;</div>' . PHP_EOL .
                                    '<div class="col-sm-9">' . PHP_EOL .
                                        '<small class="form-text text-muted">' . $json->help . '</small>' . PHP_EOL .
                                    '</div>' . PHP_EOL;
                $input[] = new HtmlString($html_string);
            }

            // fluxo normal: inclui o campo propriamente dito, desde que "can for igual ao perfil" ou "se não houver can"
            if (($perfil && isset($json->can) && $json->can == $perfil) || (!$perfil && !isset($json->can)))
                $form[] = $input;
        }

        return $form;
    }

    /**
     * Trata as entradas para renderizar o formulário
     */
    public static function generateForm($selecao, string $classe_nome, $objeto = null, $perfil = null)
    {
        $template = json_decode($selecao->{'template_' . ClasseUtils::obterClasseNomePlural($classe_nome)});
        $form = [];
        if ($template) {
            $data = $objeto ? json_decode($objeto->extras) : null;
            $form = JSONForms::JSON2Form($selecao, $template, $data, $perfil, $classe_nome);
        }
        return $form;
    }

    /**
     * Simplifica a estrutura do template do select
     */
    public static function simplifyTemplate($template)
    {
        $result = [];
        foreach ($template as $item) {
            $item = (array) $item;
            $key = removeAccents(Str::of($item['value'])->lower()->replace([' ', '-'], '_'));
            $result[$key] = $item['label'];
        }
        return json_decode(json_encode($result, true));
    }

    /**
     * Remove caracteres não aceitáveis no JSON
     */
    public static function fixJson($json)
    {
        // troca todo e qualquer \" por "
        $json = str_replace('\"', '"', json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // " volta a ser \" se estivermos num contexto de <a href="...">...</a>
        // ou seja, <a href="...">...</a> se torna <a href=\"...\">...</a>
        $json = preg_replace('/<a\s+href\s*=\s*"/i', '<a href=\\"', $json);
        $json = preg_replace_callback('/<a\s+href\s*=\s*\\\".*?<\/a>/i', function ($matches) {
            return preg_replace('/">/', '\\">', $matches[0]);
        }, $json);

        return $json;
    }

    /*
     * Obtém o maior valor do dado campo no dado JSON
     */
    public static function getLastIndex($json, $field)
    {
        $lastIndex = -1;
        if ((!empty($json)) && (is_array($json)))
            foreach ($json as $item) {
                $item = (is_array($item) ? json_decode(json_encode($item)) : $item);
                $value = $item->$field;
                if (isset($value) && (!empty($value)) && is_numeric($value))
                    if ($value > $lastIndex)
                        $lastIndex = $value;
            }
        return $lastIndex;
    }

    /*
     * Ordena os campos do template, bem como os valores dos campos de tipo select e radio do template
     */
    public static function orderTemplate($template)
    {
        $template = json_decode($template, true);
        if (!empty($template)) {
            $ordered_template = array_column($template, 'order');
            array_multisort($ordered_template, SORT_ASC, $template);
            foreach ($template as &$field)
                if (!empty($field) &&
                    (($field['type'] == 'select') || ($field['type'] == 'radio')) &&
                    isset($field['value']) &&
                    is_array($field['value'])) {
                    $ordered_templatevalue = array_column($field['value'], 'order');
                    array_multisort($ordered_templatevalue, SORT_ASC, $field['value']);    // não colocar $field['value'] aqui, senão o array_multisort não consegue ordenar
                }
        }
        return json_encode($template);
    }
}

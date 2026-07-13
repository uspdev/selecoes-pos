<?php

    $inscricao_ou_matricula = '';
    $inscricao_ou_matricula_plural = '';
    $objetivo = '';

    if ($selecao->categoria->nome != 'Aluno Especial') {
        if ($selecao->programa->fazInscricoes()) {
            $inscricao_ou_matricula = 'inscrição';
            $inscricao_ou_matricula_plural = 'inscrições';
            $objetivo = 'o processo seletivo ' . $selecao->nome;
        } elseif ($selecao->programa->fazMatriculas()) {
            $inscricao_ou_matricula = 'matrícula';
            $inscricao_ou_matricula_plural = 'matrículas';
            $objetivo = 'o programa ' . $selecao->programa->nomeCompleto();
        }
    } else {
        if (Parametro::first()->especiaisFazInscricoes()) {
            $inscricao_ou_matricula = 'inscrição';
            $inscricao_ou_matricula_plural = 'inscrições';
        } elseif (Parametro::first()->especiaisFazMatriculas()) {
            $inscricao_ou_matricula = 'matrícula';
            $inscricao_ou_matricula_plural = 'matrículas';
        }
        $objetivo = 'aluno especial';
    }

<?php

$solicitacoesisencaotaxa = [
    [
        'text' => '<i class="far fa-plus-square"></i> Nova Solicitação de Isenção de Taxa',
        'url' => 'solicitacoesisencaotaxa/create',
        'can' => 'solicitacoesisencaotaxa.create',
    ],
    [
        'text' => '<i class="far fa-list-alt"></i> Minhas Solicitações de Isenção de Taxa',
        'url' => 'solicitacoesisencaotaxa',
        'can' => 'solicitacoesisencaotaxa.viewTheir',
    ],
];

$inscricoes = [
    [
        'text' => '<i class="far fa-plus-square"></i> Nova Inscrição',
        'url' => 'inscricoes/create',
        'can' => 'inscricoes.create',
    ],
    [
        'text' => '<i class="far fa-list-alt"></i> Minhas Inscrições',
        'url' => 'inscricoes',
        'can' => 'inscricoes.viewTheir',
    ],
];

$matriculas = [
    [
        'text' => '<i class="far fa-plus-square"></i> Nova Matrícula',
        'url' => 'matriculas/create',
        'can' => 'matriculas.create',
    ],
    [
        'text' => '<i class="far fa-list-alt"></i> Minhas Matrículas',
        'url' => 'matriculas',
        'can' => 'matriculas.viewTheir',
    ],
];

$admin = [
    [
        'text' => '<i class="fas fa-boxes"></i> Categorias',
        'url' => 'categorias',
        'can' => 'categorias.viewAny',
    ],
    [
        'text' => '<i class="fa fa-map-marker"></i> Programas',
        'url' => 'programas',
        'can' => 'programas.viewAny',
    ],
    [
        'text' => '<i class="fa fa-bookmark"></i> Linhas de Pesquisa/Temas',
        'url' => 'linhaspesquisa',
        'can' => 'linhaspesquisa.viewAny',
    ],
    [
        'text' => '<i class="fa fa-book"></i> Disciplinas',
        'url' => 'disciplinas',
        'can' => 'disciplinas.viewAny',
    ],
    [
        'text' => '<i class="fa fa-gift"></i> Motivos de Isenção de Taxa',
        'url' => 'motivosisencaotaxa',
        'can' => 'motivosisencaotaxa.viewAny',
    ],
    [
        'text' => '<i class="fa fa-file"></i> Orientadores(as)',
        'url' => 'orientadores',
        'can' => 'orientadores.viewAny',
    ],
    [
        'text' => '<i class="fa fa-file"></i> Tipos de Documento',
        'url' => 'tiposarquivo',
        'can' => 'tiposarquivo.viewAny',
    ],
    [
        'type' => 'divider',
        'can'=> 'parametros.viewAny'
    ],
    [
        'text' => '<i class="fas fa-cogs"></i> Parâmetros',
        'url' => 'parametros',
        'can' => 'parametros.viewAny',
    ],
    [
        'text' => '<i class="fas fa-users-cog"></i> Funções',
        'url' => 'funcoes',
        'can' => 'funcoes.viewAny',
    ],
    [
        'text' => '<i class="fa fa-list-ul"></i> Usuários Locais',
        'url' => 'localusers',
        'can' => 'localusers.viewAny',
    ],
    [
        'type' => 'divider',
        'can'=> 'parametros.viewAny'
    ],
    [
        'text' => '<i class="fa fa-trash-alt"></i> Limpeza de Dados',
        'url' => 'limpezadados',
        'can' => 'limpezadados.showForm',
    ],
];

$menu = [
    [
        'text' => '<i class="fa fa-user-cog" aria-hidden="true"></i> Solicitações de Isenção de Taxa',
        'submenu' => $solicitacoesisencaotaxa,
        'can' => 'solicitacoesisencaotaxa.create',
    ],
    [
        'text' => '<i class="far fa-list-alt"></i> Solicitações de Isenção de Taxa',
        'url' => 'solicitacoesisencaotaxa',
        'can' => 'solicitacoesisencaotaxa.viewAny',
    ],
    [
        'text' => '<i class="fa fa-user-cog" aria-hidden="true"></i> Inscrições',
        'submenu' => $inscricoes,
        'can' => 'inscricoes.create',
    ],
    [
        'text' => '<i class="far fa-list-alt"></i> Inscrições',
        'url' => 'inscricoes',
        'can' => 'inscricoes.viewAny',
    ],
    [
        'text' => '<i class="fa fa-user-cog" aria-hidden="true"></i> Matrículas',
        'submenu' => $matriculas,
        'can' => 'matriculas.create',
    ],
    [
        'text' => '<i class="far fa-list-alt"></i> Matrículas',
        'url' => 'matriculas',
        'can' => 'matriculas.viewAny',
    ],
    [
        'text' => '<i class="fas fa-tasks ml-2"></i> Seleções',
        'url' => 'selecoes',
        'can' => 'selecoes.viewAny',
    ],
    [
        'text' => '<i class="fa fa-user-cog" aria-hidden="true"></i> Administração',
        'submenu' => $admin,
        'can' => 'admin.viewAny',
    ],
];

$trocarPerfil = [
    [
        'text' => '<i class="fas fa-users ml-2"></i> Meu Perfil',
        'url' => 'users/meuperfil',
        'can' => 'usuario',
    ],
    [
        'type' => 'divider',
        'can' => 'trocarPerfil',
    ],
    [
        'type' => 'header',
        'text' => '<b><i class="fas fa-id-badge"></i>  Trocar perfil</b>',
        'can' => 'trocarPerfil',
    ],
    [
        'text' => '&nbsp; Admin',
        'url' => 'users/perfil/admin',
        'can' => 'admin',
    ],
    [
        'text' => '&nbsp; Gerente',
        'url' => 'users/perfil/gerente',
        'can' => 'gerente',
    ],
    [
        'text' => '&nbsp; Docente',
        'url' => 'users/perfil/docente',
        'can' => 'docente',
    ],
    [
        'text' => '&nbsp; Usuário',
        'url' => 'users/perfil/usuario',
        'can' => 'trocarPerfil',
    ],
];

$right_menu = [
    [
        'key' => 'laravel-tools',
        'can' => 'perfiladmin',
    ],
    [
        'key' => 'senhaunica-socialite',
        'can' => 'perfiladmin',
    ],
    [
        'text' => '<span class="badge badge-danger">Admin</span>',
        'url' => '#',
        'can' => 'perfiladmin',
    ],
    [
        'text' => '<span class="badge badge-warning">Gerente</span>',
        'url' => '#',
        'can' => 'perfilgerente',
    ],
    [
        'text' => '<i class="fas fa-cog"></i>',
        'title' => 'Configurações',
        'submenu' => $trocarPerfil,
        'align' => 'right',
        'can' => 'usuario',
    ],
];

return [
    # valor default para a tag title, dentro da section title.
    # valor pode ser substituido pela aplicação.
    'title' => config('app.name'),

    # USP_THEME_SKIN deve ser colocado no .env da aplicação
    'skin' => env('USP_THEME_SKIN', 'uspdev'),

    # chave da sessão. Troque em caso de colisão com outra variável de sessão.
    'session_key' => 'laravel-usp-theme',

    # usado na tag base, permite usar caminhos relativos nos menus e demais elementos html
    # na versão 1 era dashboard_url
    'app_url' => config('app.url'),

    # login e logout
    'logout_method' => 'POST',
    'logout_url' => 'logout',
    'login_url' => 'login',

    # menus
    'menu' => $menu,
    'right_menu' => $right_menu,

    # mensagens flash - https://uspdev.github.io/laravel#31-mensagens-flash
    'mensagensFlash' => false,

    # container ou container-fluid
    'container' => 'container-fluid',
];

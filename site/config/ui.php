<?php

return [
    'default_layout'    => 'main',
    'vars'              => [
        'theme_color'   => "#b3d4fc",
        'app_name'      => 'JF Framework',
        'description'   => 'Framework PHP simples e versátil',
        'author'        => 'Márcio Jalber [marciojalber@gmail.com]',
        'since'         => '16/07/2019',
        'keywords'      => 'framework,php,produtividade',
    ],
    'menu'              => [
        'intro'         => [
            'label'     => 'Introdução',
            'icon'      => 'home',
            'pages'     => [
                'requisitos'    => 'Requisitos',
                'instalacao'    => 'Instalação',
                'estrutura'     => 'Pastas e Arquivos',
                'config'        => 'Configurações',
                'rotas'         => 'Rotas',
            ],
        ],
        'arquitetura'   => [
            'label'     => 'Arquitetura',
            'icon'      => 'table_chart',
            'pages'     => [
                'features'  => 'Features',
                'rules'     => 'Rules',
                'tests'     => 'Tests',
                'models'    => 'Models',
                'routines'  => 'Routines',
                'services'  => 'Services',
                'types'     => 'Types',
            ],
        ],
        'frontend'      => [
            'label'     => 'Frontend',
            'icon'      => 'devices',
            'pages'     => [
                'layouts'       => 'Layouts',
                'partials'      => 'Partials',
                'pages'         => 'Pages',
                'page-partials' => 'Page Partials',
                'controllers'   => 'Controllers',
            ],
        ],
        'bd'            => [
            'label'     => 'Banco-de-dados',
            'icon'      => 'storage',
            'pages'     => [
                'db'            => 'DB',
                'daos'          => 'DAOs',
                'dtos'          => 'DTOs',
                'sqlbuilder'    => 'SQLBuilder',
            ],
        ],
        'autodoc'       => [
            'label'     => 'AutoDoc',
            'icon'      => 'description',
            'pages'     => [
                'autodoc' => 'autodoc',
            ],
        ],
    ],
];
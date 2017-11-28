<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "mkvarnish".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'MK Varnish',
    'description' => '',
    'category' => 'plugin',
    'author' => 'Michael Wagner',
    'author_email' => 'dev@dmk-ebusiness.de',
    'author_company' => 'DMK E-Business GmbH',
    'shy' => '',
    'dependencies' => '',
    'version' => '1.0.1',
    'conflicts' => '',
    'priority' => '',
    'module' => '',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 1,
    'lockType' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '6.2.0-8.7.99'
        ],
        'conflicts' => [],
        'suggests' => []
    ],
    'suggests' => [],
    'classmap' => [
        'Classes/'
    ]
];

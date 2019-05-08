<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "mkvarnish".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'MK Varnish',
    'description' => 'This extension takes care of connecting TYPO3 to Varnish servers for proper caching.',
    'category' => 'plugin',
    'author' => 'Michael Wagner',
    'author_email' => 'dev@dmk-ebusiness.de',
    'author_company' => 'DMK E-Business GmbH',
    'shy' => '',
    'dependencies' => '',
    'version' => '1.0.8',
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
    'autoload' => [
        'classmap' => [
            'Classes'
        ]
    ]
];

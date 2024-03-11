<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "mkvarnish".
 ***************************************************************/

$EM_CONF['mkvarnish'] = [
    'title' => 'MK Varnish',
    'description' => 'This extension takes care of connecting TYPO3 to Varnish servers for proper caching.',
    'category' => 'plugin',
    'author' => 'Michael Wagner',
    'author_email' => 'dev@dmk-ebusiness.de',
    'author_company' => 'DMK E-Business GmbH',
    'shy' => '',
    'dependencies' => '',
    'version' => '12.0.0',
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
            'typo3' => '11.5.7-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'suggests' => [],
    'autoload' => [
        'classmap' => [
            'Classes',
        ],
    ],
];

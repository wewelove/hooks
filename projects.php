<?php
return [
    'hooks' => [
        'type' => 'github',
        'name' => 'wewelove/hooks',
        'path' => '/data/wwwroot/default/hooks',
        'branch' => 'master',
        'secret' => 'qwer1234',
        'composer' => 'install --prefer-dist --no-dev'
    ],
    'fcoder' => [
        'type' => 'github',
        'name' => 'wewelove/fcoder',
        'path' => '/data/wwwroot/default/fcoder',
        'branch' => 'gh-page',
        'secret' => 'qwer1234',
        'composer' => ''
    ],
];
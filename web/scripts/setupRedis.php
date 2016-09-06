<?php

$options = getopt(
    '',
    [
        'redis-host::',
        'magento-path::'
    ]
);

$magentoPath = !empty($options['magento-path']) ? $options['magento-path'] : '/var/www/magento2';
$redisHost = !empty($options['redis-host']) ? $options['redis-host'] : 'redis';

$conf = include $magentoPath . '/app/etc/env.php';

$conf['session'] = [
    'save' => 'redis',
    'redis' =>
        [
            'host' => $redisHost,
            'port' => '6379',
            'password' => '',
            'timeout' => '2.5',
            'persistent_identifier' => '',
            'database' => '0',
            'compression_threshold' => '2048',
            'compression_library' => 'gzip',
            'log_level' => '1',
            'max_concurrency' => '6',
            'break_after_frontend' => '5',
            'break_after_adminhtml' => '30',
            'first_lifetime' => '600',
            'bot_first_lifetime' => '60',
            'bot_lifetime' => '7200',
            'disable_locking' => '0',
            'min_lifetime' => '60',
            'max_lifetime' => '2592000'
        ]
];

$conf['cache']['frontend'] =
    [
        'default' =>
            [
                'backend' => 'Cm_Cache_Backend_Redis',
                'backend_options' =>
                    [
                        'server' => $redisHost,
                        'port' => '6379'
                    ],
            ],
        'page_cache' =>
            [
                'backend' => 'Cm_Cache_Backend_Redis',
                'backend_options' =>
                    [
                        'server' => $redisHost,
                        'port' => '6379',
                        'database' => '1',
                        'compress_data' => '0'
                    ]
            ]
    ];

file_put_contents($magentoPath . '/app/etc/env.php', "<?php\n return " . var_export($conf, true) . ';');

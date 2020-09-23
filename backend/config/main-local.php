<?php

$config = [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '4LEgqAqIixEpDwIjMv-vjR9w9ty52916',
        ],
        'view' => [
            'theme' => [
                'basePath' => '@app/static',
                'baseUrl' => '@web/static',
                'pathMap' => [
                    '@app/views' => '@app/static',
                ],
            ],
        ],
    ],
];

if (!YII_ENV_TEST) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;

<?php

$config = [
    'components' => [
        'request' => [
            'cookieValidationKey' => 'yC3QJ9P3Dql8b40IAEnB4AOOqelwl89E',
            'enableCsrfValidation' => false,
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

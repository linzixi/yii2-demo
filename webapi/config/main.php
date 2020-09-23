<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/code.php'
);
return [
    'id' => 'app-webapi',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'webapi\controllers',
    //'defaultRoute' => 'user/index/index',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-webapi',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-webapi', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'shudi-sass-custom',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/report/<code:\w+>.html' => '/view-code/report',
                '/warning/<code:\w+>.html' => '/view-code/report',
            ],
        ],
        'service' => [
             'class' => 'core\src\WebApiService'
         ],
    ],
    'modules' => [
        'test' => [
            'class' => 'webapi\modules\test\Module',
        ],
    ],
    'params' => $params,
];

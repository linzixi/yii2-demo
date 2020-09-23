<?php
$db = [
    'host'     => getenv("DB_HOST"),
    'dbname'   => getenv("DB_NAME"),
    'username' => getenv("DB_USERNAME"),
    'password' => getenv("DB_PASSWORD")
];
switch (getenv("CACHE","file")){
    case "file":
        $cache = [
            'class' => 'yii\caching\FileCache',
            "cachePath" => "@webapi/runtime/cache",
        ];
        $redis = null;
        break;
    case "redis":
        $cache = [
            'class' => 'yii\redis\Cache',
            'keyPrefix' => 'shudisass_'
        ];
        $redis = [
            'class' => 'yii\redis\Connection',
            'hostname' => YII_ENV == 'pro'?'xxxxxxx':'xxxxxx',
            'port' => 6379,
            'database' => 8,
        ];
        $redis['password'] =YII_ENV== "pro" ?  'xxxx' : "xxxxxx";
}

return [
    'components' => [
        'cache' => $cache,
        'redis' => $redis,
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => "mysql:host={$db['host']};dbname={$db['dbname']}",
            'username' => $db['username'],
            'password' => $db['password'],
            'charset' => 'utf8mb4',
        ]
    ],
];

<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
use terry\nlp\RpcLog;

RpcLog::config([
    'level' => RpcLog::INFO,
]);
function show($data)
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
}
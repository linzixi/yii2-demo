<?php

/**
 * Author: huanw2010@gmail.com
 * Date: 2019/10/8 11:03
 */
namespace terry\nlp;
class LogFileSink extends LogAbstractSink implements LogSinkInterface
{
    protected $settings = [
        'logFile' => './rpc.log',
    ];

    public function flush($message)
    {
        file_put_contents($this->settings['logFile'], $message, FILE_APPEND);
    }
}
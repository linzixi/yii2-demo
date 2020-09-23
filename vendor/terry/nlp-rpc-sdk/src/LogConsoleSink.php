<?php

/**
 * Author: huanw2010@gmail.com
 * Date: 2019/10/8 11:02
 */
namespace terry\nlp;
class LogConsoleSink extends LogAbstractSink implements LogSinkInterface
{
    public function flush($message)
    {
        echo $message;
    }
}
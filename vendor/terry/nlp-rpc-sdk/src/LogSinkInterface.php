<?php

/**
 * Author: huanw2010@gmail.com
 * Date: 2019/10/8 11:01
 */
namespace terry\nlp;
interface LogSinkInterface
{
    public function flush($message);

    public function getSettings();
}
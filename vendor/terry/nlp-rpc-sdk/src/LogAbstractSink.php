<?php

/**
 * Author: huanw2010@gmail.com
 * Date: 2019/10/8 11:57
 */
namespace terry\nlp;
abstract class LogAbstractSink implements LogSinkInterface
{
    protected $settings = [];

    public function __construct($settings = [])
    {
        $this->settings = array_merge($this->settings, $settings);
    }

    public function getSettings()
    {
        return $this->settings;
    }
}
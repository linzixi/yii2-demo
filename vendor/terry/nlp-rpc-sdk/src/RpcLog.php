<?php

/**
 * Author: huanw2010@gmail.com
 * Date: 2019/8/27 22:58
 */

namespace terry\nlp;

class RpcLog
{
    private static $levels = [
        'ERROR' => 0x1,
        'WARN' => 0x2,
        'INFO' => 0x3,
        'DEBUG' => 0x4,
    ];

    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARN = 'WARN';
    const ERROR = 'ERROR';

    /**
     * @var $logSink LogSinkInterface
     */
    private static $logSink;
    private static $settings = [];

    private static function getConfig($key)
    {
        return isset(self::$settings[$key]) ? self::$settings[$key] : null;
    }

    private static $isInitiated = false;

    public static function config($config = [])
    {
        $default = [
            'level' => self::WARN,
            'logSink' => 'terry\nlp\LogFileSink',
            'logFile' => dirname(__FILE__) . '/rpc.log',
        ];

        if (!is_array($config)) {
            throw  new RpcException("\$config must be assigned an array");
        }
        self::$settings = array_merge($default, self::$settings, $config);
        $logSink = self::getConfig('logSink');
        if (!class_exists($logSink)) {
            throw new RpcException("logSink $logSink not exists");
        }
        self::$logSink = new $logSink(self::$settings);
        self::$isInitiated = true;
    }

    private static function message($message, $level)
    {
        if (!self::$isInitiated) {
            self::config();
            RpcLog::info("initiate RpcLog config");
        }
        if (self::$levels[self::getConfig('level')] >= self::$levels[$level]) {
            $tpl = sprintf("[%s][%s]", $level, date("Y-m-d H:i:s"));
            if (is_array($message)) {
                $message = vsprintf($message[0], array_slice($message, 1));
            }
            $message = $tpl . " " . $message . "\n";
            self::$logSink->flush($message);
        }
    }

    public static function debug($message)
    {
        self::message($message, self::DEBUG);
    }

    public static function info($message)
    {
        self::message($message, self::INFO);
    }

    public static function warn($message)
    {
        self::message($message, self::WARN);
    }


    public static function error($message)
    {
        self::message($message, self::ERROR);
    }

    public static function getLogSink()
    {
        return self::$logSink;
    }

    public static function getSettings()
    {
        return self::$settings;
    }

}
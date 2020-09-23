<?php
/**
 * Author: huanw2010@gmail.com
 * Date: 2019/8/28 9:45
 */

namespace terry\nlp;
defined("RPC_SERVICE_ADDRESS") || define("RPC_SERVICE_ADDRESS", "10.94.183.131:51001");

class RpcService
{
    private static $serviceMap = [
        'SentimentShort' => 'nlp-sentiment',
        'SentimentLong' => 'nlp-sentiment',
        'SentimentWeibo' => 'nlp-sentiment',
        'SegWord' => 'nlp-segword',
        'ContentCategory' => 'nlp-mg',
        'LocationRecognition' => 'nlp-mg',
        'EntityRecognition' => 'nlp-mg',
        'SimQuery' => 'nlp-mg',
        'Sim' => 'nlp-misc',
        'KsfTag' => 'nlp-misc',
        'SuoBeiLocation' => 'nlp-misc',
        'KzTag' => 'nlp-misc',
        'HxTag' => 'nlp-misc',
        'ShunyaPubTag' => 'nlp-shunya',
        'ShunyaTag' => 'nlp-shunya',
        'RecognizeCartType' => 'nlp-shunya',
        'EmTags' => 'nlp-national',
        'EmPolicyTag' => 'nlp-national',
        'EmCluster' => 'nlp-national',
        'EmClassify' => 'nlp-national',
        'DemoTag' => 'nlp-demo',
        'SpamPolitic' => 'nlp-spam',
        'SpamSuobei' => 'nlp-spam',
        'SpamSuobeiWeather' => 'nlp-spam',
        'Summary' => 'nlp-demo',
        'AutoAspectSentiment' => 'nlp-aspect',
    ];
    static $clients = [];
    private static $services = [];

    /**
     * @param $host
     * @param bool $refresh
     * @return mixed
     */
    private static function getClient($host, $refresh = false)
    {
        if (empty(self::$clients[$host]) || $refresh) {
            RpcLog::debug(['Connect to rpc server [%s]', $host]);
            self::$clients[$host] = new \Nlp\NlpClient($host, [
                'credentials' => \Grpc\ChannelCredentials::createInsecure(),
            ]);
        }

        return self::$clients[$host];
    }

    /**
     * get rpc module entry point
     * @param $module
     * @return array
     */
    public static function getServiceHostByModule($module)
    {
        $req = new \Nlp\NlpStringRequest();
        $req->setContent($module);
        list($reply, $status) = self::getClient(RPC_SERVICE_ADDRESS)->Service($req)->wait();
        if ($status->code != 0) {
            return [];
        }
        $message = $reply->getRes();
        $hosts = [];
        foreach ($message as $host) {
            $hosts[] = $host;
        }
        return $hosts;
    }

    /**
     * @param $method
     * @param bool $refresh
     * @return array|mixed
     * @throws RpcException
     */
    public static function getServiceHost($method, $refresh = false)
    {
        $method = ucfirst($method);
        if (!isset(self::$serviceMap[$method])) {
            throw new RpcException("$method not exits");
        }
        $serviceName = self::$serviceMap[$method];
        if (empty(self::$services[$serviceName]) || $refresh) {
            RpcLog::debug(["get service [%s]", $serviceName]);
            $hosts = self::getServiceHostByModule($serviceName);
            self::$services[$serviceName] = $hosts;
        }

        return self::$services[$serviceName];
    }

    /**
     * set rpc server addresses of a specified service
     * @param $serviceName
     * @param $hosts
     */
    public static function setServiceHost($serviceName, $hosts)
    {
        self::$services[$serviceName] = $hosts;
    }

    /**
     * Get service client for request
     * @param $method
     * @param bool $refresh
     * @return mixed
     * @throws RpcException
     */
    public static function getServiceClient($method, $refresh = false)
    {
        $hosts = self::getServiceHost($method, $refresh);
        if (empty($hosts)) {
            throw new RpcException("Cannot obtain the host of method [$method]");
        }
        $host = $hosts[array_rand($hosts)];
        return self::getClient($host, $refresh);
    }

    /**
     * send rpc request to server
     * @param $method
     * @param $request
     * @return mixed
     */
    public static function request($method, $request)
    {
        $max = 3;
        $n = 1;
        $refresh = false;
        $reply = null;
        while ($n <= $max) {
            $n++;
            if ($refresh) {
                RpcLog::info(["Retry [%s] [%d] times", $method, $n - 2]);
            }
            list($reply, $status) = call_user_func([self::getServiceClient($method, $refresh), ucfirst($method)], $request)->wait();
            // Connect Failed
            if ($status->code == 14) {
                $refresh = true;
                RpcLog::warn(["call [%s] failed", $method]);
                continue;
            }
            break;
        }

        return $reply;
    }


    /**
     * get rpc modules
     * @return array
     */
    public static function getModules()
    {
        return array_unique(array_values(self::$serviceMap));
    }
}
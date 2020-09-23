<?php

namespace webapi\services;

use Yii;

/**
 * Class ApiService
 * @package webapi\services
 */
class ApiService
{
    /**
     * APP ID和APP SECRET目前版本暂时用不到
     * TOOKEN 将来会加入刷新机制，所以不用常量
     */
    const HOST = 'https://member.gsdata.cn';
    const APP_ID = '8619565156331662';
    const APP_SECRET = 'DQmzvCui4RQABNAUHyPqatJOC9ectSnR';
    //目前版本请手动填写分配给你的TOKEN
    protected $token = 'NG9acS4weZNd6rpDj68yrFvx37jOhdrS';
    const DEV_HOST = 'http://dev-member.gstai.com';

    /**
     * @param string $path 接口相对路径如：/api/v1/member/info
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function call($path, $params = []){
        $host = YII_ENV != 'local'?self::HOST:self::DEV_HOST;
        $params['token'] = $this->token;
        $return = $this->fetch($host. $path, $params);
        $this->afterCall($return);
        /** @var $return array*/
        return $return;
    }

    /**
     * 使用curl从接口获取数据
     * @param string $url API完全路径
     * @param array $params 提交参数
     * @return string JSON格式返回信息
     */
    private function fetch($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * 接口返回数据后的处理
     * 你可以在这里做一些基本处理，比如将JSON转为数组，方便其余模块处理
     * @param string $return 从接口返回的原始json串
     * @throws \Exception
     */
    private function afterCall(&$return)
    {
        $return = json_decode($return, true);
        if (!isset($return['errcode'])) throw new \Exception('服务器未响应');
        if ($return['errcode'] > 0) throw new \Exception($return['errmsg'], $return['errcode']);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 43869
 * Date: 2020/3/20
 * Time: 17:16
 */

namespace webapi\services;


use core\extensions\Jwt;
use core\models\Company;

class AutoLoginService {


    public function getReturnUrl($return_url,$return_type,$token){
        $domain = getHost();
        $info = Company::find()->where(['domain' => $domain])->one();
        //判断是否为测试环境
        if ( $domain == "shudi.gsdata.cn" )  {
            $custom_domain = "shudicustom.gsdata.cn";
        }else{
            $custom_domain = "shudicustom.gsdata.cn";
        }
        $protocol =  $info->protocol;
        switch ($return_type) {
            case "custom":
                $url = $protocol."://".$custom_domain."/".$return_url."?token=".$token;break;
            default:
                $url = $protocol."://".$domain."/".$return_url;
        }
        return $url;

    }

    public function redirectLogin() {
        $token = \Yii::$app->request->get("token");
        $domain = getHost();
        if ($token) {
            return $this->tokenLogin($token);
        }
        exit; //@todo
        switch ($domain) {
            case "oemdemo.gsdata.cn":
                $default_logout_url = "https://cmclogin.flydev.chinamcloud.cn/";
                $login_id = \Yii::$app->request->get("login_id");
                $login_tid = \Yii::$app->request->get("login_tid");
                $login_return_url = \Yii::$app->request->get("login_return_url",$default_logout_url);
                $return = $this->validateStatus($login_id, $login_tid);
                if ($return){
                    $uid = 3;
                    return $this->otherLogin($uid);
                }else{//执行退出逻辑
                    $this->logout();
                    return ["url" => $login_return_url];
                }
                break;
            case "qhy.gstai.com":
                $default_logout_url = "http://group.dmqhyadmin.com/";
                $login_return_url = \Yii::$app->request->get("login_return_url",$default_logout_url);
                $login_id = \Yii::$app->request->get("login_id");
                $login_tid = \Yii::$app->request->get("login_tid");
                $return = $this->validateStatus($login_id, $login_tid);
                if ($return){
                    $uid = 178;
                    return $this->otherLogin($uid);
                }else{//执行退出逻辑
                    $this->logout();
                    return ["url" => $login_return_url];
                }
                break;

        }
        return false;
    }


    public function tokenLogin($token) {
        setcookie("Authorization", $token, 0, "/");
        return true;
    }

    public function otherLogin($uid) {
        $token = Jwt::createToken($uid);
        setcookie("Authorization", $token, 0, "/");
        return $token;
    }


    public function logout(){
        setcookie("Authorization", null, -1, "/");
    }

    public function validateStatus($login_id, $login_tid) {
        $url = $this->getApiUrl();
        $headers = [];
        $headers[] = 'Content-Type: application/json';
        $params = [
            "login_id"  => $login_id,
            "login_tid" => $login_tid
        ];
        $response = $this->httpRequest($url,$headers,"POST",$params);
        if (!$response) return false;
        $response = json_decode($response,true);
        if ($response['code'] == 10000) return true;//登陆状态
        if ($response['code'] == 99998 )return false;//退出状态
    }


    public function getApiUrl() {
        $array_params = []; #业务 get 参数。如果业务参数是 post，则不需要参与
        $api_url         = "https://api.dmqhyadmin.com";
        $AccessKeyID     = "tPykD0wZjN2VpeF1";
        $AccessKeySecret = "BPOx4J0gfKyC8ubGIEH1McAXtZ6nQj7p";
        //签名计算
        $url = $api_url.'/cmc/login/get-login-auth'; #该 url 即为后台添加 API4
        //  网关后给出的接口访问地址并非原始服务地址。
        $public_array = [ #签名公共参数
            'AccessKeyId'      => $AccessKeyID, #框架颁发给服务的 AccesseyId
            'ServiceKey'       => "xcfx", #框架颁发给服务的 Servi
            'Format'           => 'JSON', #返回值的类型，目前仅支持 JSO
            'SignatureMethod'  => 'HMAC-SHA1', #签名方式，目前仅支持 HMAC- HA1
            'Timestamp'        => time(), #请求秒级时间戳
            'SignatureVersion' => '1.0', #签名算法版本，目前版本是 1.0
            'SignatureNonce'   => rand(0,1000000000), #唯一随机数，用于防止网络重放攻击。用户在不同请求间要使用不同的随机数值
        ];
        $array = $public_array + $array_params;
        ksort($array);
        $params = null;
        $count = count($array);
        $i = 0;
        foreach ($array as $k => $v) {
            $i++;
            if ($i < $count) {
                $params .= rawurlencode($k) . '=' . rawurlencode($v) . '&';
            } else {
                $params .= rawurlencode($k) . '=' . rawurlencode($v);
            }
        }
        $str = 'GET&%2F&' . rawurlencode($params);
        $key = $AccessKeySecret . '&';
        $signature = "";
        if (function_exists('hash_hmac')) {
            $signature = base64_encode(hash_hmac("sha1", $str, $key, true));
        } else {
            $blocksize = 64;
            $hashfunc = 'sha1';
            if (strlen($key) > $blocksize) {
                $key = pack('H*', $hashfunc($key));
            }
            $key = str_pad($key, $blocksize, chr(0x00));
            $ipad = str_repeat(chr(0x36), $blocksize);
            $opad = str_repeat(chr(0x5c), $blocksize);
            $hmac = pack('H*', $hashfunc(($key ^ $opad) . pack('H*', $hashfunc
                (($key ^ $ipad) . $str))));
            $signature = base64_encode($hmac);
        }
        $array['Signature'] = $signature; #签名结果串，关于签名的计算方法，请参见签名机制。
        $j = 0;
        $send_params = null;
        $send_count = count($array);
        foreach ($array as $k => $v) {
            $j++;
            if ($j < $send_count) {
                $send_params .= $k . '=' . rawurlencode($v) . '&';
            } else {
                $send_params .= $k . '=' . rawurlencode($v);
            }
        }
        return $url . '?' . $send_params;
    }

    public function httpRequest($url, $headers = [], $method = 'GET', $params = null , $time_out = 0){
        if (is_array($params)) {
            if($method == 'GET'){
                $requestString = http_build_query($params);
            }else{
                $requestString = json_encode($params);
            }
        } else {
            $requestString = $params ? : '';
        }
        if (empty($headers)) {
            $headers = array('Content-type: text/json');
        } elseif (!is_array($headers)) {
            parse_str($headers,$headers);
        }
        // setting the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // setting the POST FIELD to curl
        switch ($method){
            case "GET" : curl_setopt($ch, CURLOPT_HTTPGET, 1);break;
            case "POST": curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);break;
            case "PUT" : curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);break;
            case "DELETE":  curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);break;
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        if($errno && $httpCode != '200'){
            return false;
        }
        //close the connection
        curl_close($ch);
        return $response;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/12/2
 * Time: 16:50
 */

namespace webapi\extensions;


use yii\db\Exception;

class RpcClient
{

    private $serviceName;
    protected $version = "1.0";
    protected $host = "tcp://0.0.0.0:18307";
    private $rpcConfig = [
        'EsInterface' => 'App\Rpc\Lib\EsInterface',
        'SchemeService' => 'App\Rpc\Lib\SchemeInterface',
    ];

    public function __construct($serviceName = "EsInterface")
    {
        if (array_key_exists($serviceName, $this->rpcConfig)) {
            $this->class = $this->rpcConfig[$serviceName];
            $this->serviceName = $serviceName;
        }
    }

    public function setVersion($version){
        $this->version = $version;
    }
    // __call() is triggered when invoking inaccessible methods in an object context.
    //调用不可访问的方法
    public function __call($actionName, $arguments)
    {
        if (!isset($this->class) || !$this->class) {
            return  new Exception("服务异常");
        }
        return $this->request( $this->host, $this->class,$actionName,$arguments,$this->version);
    }

    protected function request($host, $class, $method, $param, $version = '1.0', $ext = []) {

       // var_dump($host,$class,$method,$param,$version);die;
        $elo = "\r\n\r\n";
        $fp = stream_socket_client($host, $errno, $errstr);
        if (!$fp) {
           return  new Exception("stream_socket_client fail errno={$errno} errstr={$errstr}");
        }
        $req = [
            "jsonrpc" => '2.0',
            "method" => sprintf("%s::%s::%s", $version, $class, $method),
            'params' => $param,
            'id' => '',
            'ext' => $ext,
        ];
        $data = json_encode($req) . $elo;
        fwrite($fp, $data);

        $result = '';
        while (!feof($fp)) {
            $tmp = stream_socket_recvfrom($fp, 1024);

            if ($pos = strpos($tmp, $elo)) {
                $result .= substr($tmp, 0, $pos);
                break;
            } else {
                $result .= $tmp;
            }
        }

        fclose($fp);
        return json_decode($result, true);
    }
}

<?php
/**
 * 会员系统SDK
 */
namespace webapi\extensions;

use yii\base\UserException;
use Yii;

class MemberSdk
{
    /**
     * APP ID和APP SECRET目前版本暂时用不到
     * TOOKEN 将来会加入刷新机制，所以不用常量
     */
    const HOST = 'https://member.gsdata.cn';
    const HOST_DEV = 'http://dev-member.gstai.com';

    const APP_ID = 'xxxxxxxx';
    const APP_SECRET = 'xxxxxxxx';

    protected $token = 'xxxxxxxx';      //目前版本请手动填写分配给你的TOKEN

  
    //请求接口
    const API_VC = '/api/v1/member/verify_coupon';
    const API_QB = '/api/v1/member/get_qinbei';
    const API_M_INFO = '/api/v1/member/info';
    const API_Fb_INFO = '/api/v1/member/forbidden_info';
    const API_CONSUME_QB = '/api/v1/member/consume_qinbei'; //清贝消费
    const API_EXCHANGE_CERTIFICATE = '/api/v1/member/exchange_certificate';
    const API_ADD_FORBIDDEN = '/api/v1/member/add_forbidden'; //封禁用户
    const API_USER_NOTICE = '/api/v1/member/get_notice'; //用户消息

    /**
     * 查询用户是否被封禁
     * @param $params
     * member_id必填
     * username ip 选填
     * @return array
     */
    public function forbiddenInfo($params)
    {
        $return =  $this->call(self::API_Fb_INFO, $params);
        $result = [];
        if (empty($return)) {
            $result['error'] = 1;
            $result['msg'] = '当前网络不给力，请刷新重试';
        }elseif ($return['errcode'] == 0) {
            $result['error'] = 0;
            $result['msg'] = $return['data'];
        } else {
            $result['error'] = 1;
            $result['msg'] = $return['errmsg'];
        }
        return $result;
    }

    /**
     * 会员信息
     * @param[] 以下任意一项来查询用户信息，当然也可以提交多个字段，来查询同时满足情况的用户
     * mobile
     * ninkname
     * email
     * loginname
     */
    public function memberInfo($params)
    {
        $return =  $this->call(self::API_M_INFO, $params);
        $result = [];
        if (empty($return)) {
            $result['error'] = 1;
            $result['msg'] = '当前网络不给力，请刷新重试';
        }elseif ($return['errcode'] == 0) {
            $result['error'] = 0;
            $result['msg'] = $return['userinfo'];
        } else {
            $result['error'] = 1;
            $result['msg'] = $return['errmsg'];
        }
        return $result;
    }

    /**
     * 清贝余额
     * @param $params
     * member_id必填
     * username ip 选填
     * @return array
     */
    public function qinBei($params)
    {
        $return =  $this->call(self::API_QB, $params);
        $result = [];
        if (empty($return)) {
            $result['error'] = 1;
            $result['msg'] = '当前网络不给力，请刷新重试';
        }elseif ($return['errcode'] == 0) {
            $result['error'] = 0;
            $result['msg'] = $return['userinfo'];
        } else {
            $result['error'] = 1;
            $result['msg'] = $return['errmsg'];
        }
        return $result;
    }

    /**
     * 优惠券验证
     * @param $params
     * @return array
     */
    public function verifyCoupon($params)
    {
        $return =  $this->call(self::API_VC, $params);
        $result = [];
        if (empty($return)) {
            $result['error'] = 1;
            $result['msg'] = '当前网络不给力，请刷新重试';
        }elseif ($return['errcode'] == 0) {
            $result['error'] = 0;
            $result['msg'] = $return['userinfo'];
        } else {
            $result['error'] = 1;
            $result['msg'] = $return['errmsg'];
        }
        return $result;
    }

    /**
     * 验证/使用兑换券  新消费类型需手动添加
     * 历史类型 costtype =[
     *   1=>'会员充值',2=>'购买API',3=>'分钟级监测',4=>'微信账号回溯',5=>'购买或续费微信大屏',
     *   6=>'购买或续费kpi公号位',7=>'分钟级监测失败返还',8=>'微博榜单导出',9=>'榜单导出',10=>'微博账号回溯',
     *   11=>'头条账号回溯',12=>'微信授权号榜单导出',3=>'普通用户抖音榜单导出',14=>'普通用户快手榜单导出'
     * ];
     */
    public function consumeQB($params)
    {
        $result = [];
        if(!isset($params['costtype']) || !in_array($params['costtype'], array_keys(self::$consume_qb_ype))){
            $result['error'] = 1;
            $result['msg'] = '清贝消费类型错误，请检查';
        }
        $params['costtype_desc'] = self::$consume_qb_ype[$params['costtype']];
        $return =  $this->call(self::API_CONSUME_QB, $params);
        if (empty($return)) {
            $result['error'] = 1;
            $result['msg'] = '当前网络不给力，请刷新重试';
        }elseif ($return['errcode'] == 0) {
            $result['error'] = 0;
            $result['msg'] = $return['userinfo'];
        } else {
            $result['error'] = 1;
            $result['msg'] = $return['errmsg'];
        }
        return $result;
    }

    /**
     * 验证/使用兑换券
     */
    public function exchangeCertificate($params)
    {
        $return =  $this->call(self::API_EXCHANGE_CERTIFICATE,$params);
        $result = [];
        if (empty($return)) {
            $result['error'] = 1;
            $result['msg'] = '当前网络不给力，请刷新重试';
        }elseif ($return['errcode'] == 0) {
            $result['error'] = 0;
            $result['msg'] = $return['userinfo'];
        } else {
            $result['error'] = 1;
            $result['msg'] = $return['errmsg'];
        }
        return $result;
    }

    /**
     * 添加封禁用户
     * @param $params
     * member_id必填
     * username ip 选填
     * @return array
     */
    public function addForbidden($params)
    {
        $return =  $this->call(self::API_ADD_FORBIDDEN, $params);
        $result = [];
        if (empty($return)) {
            $result['error'] = 1;
            $result['msg'] = '当前网络不给力，请刷新重试';
        }elseif ($return['errcode'] == 0) {
            $result['error'] = 0;
            $result['msg'] = $return['msg'];
        } else {
            $result['error'] = 1;
            $result['msg'] = $return['msg'];
        }
        return $result;
    }

    /**
     * 未读站内信提醒
     * member_id必填
     * @return array
     */
    public function getNotice($params)
    {
        $params['publish_type'] = '1';
        $return =  $this->call(self::API_USER_NOTICE, $params);
        $result = [];
        if (empty($return)) {
            $result['error'] = 1;
            $result['msg'] = '当前网络不给力，请刷新重试';
        }elseif ($return['errcode'] == 0) {
            $result['error'] = 0;
            $result['msg'] = $return['userinfo'];
        } else {
            $result['error'] = 1;
            $result['msg'] = $return['errmsg'];
        }
        return $result;
    }


    /**
     * 请求接口
     * @param string $path 接口相对路径如：/api/v1/member/info
     * @param array $params 提交参数
     * @return //接口返回数据，已转为数组
     */
    public function call($path, $params = [])
    {
        $HOST =  self::HOST;
        if (in_array(YII_ENV, ['local', 'dev'])) {
            $HOST = self::HOST_DEV;
        }

        $params['token'] = $this->token;
        $return = $this->fetch($HOST . $path, $params);
        $this->afterCall($return);
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
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
        if (!isset($return['errcode'])) throw new UserException('服务器繁忙，请稍后刷新重试');
    }
}

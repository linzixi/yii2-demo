<?php


namespace webapi\extensions;

/**
 * 清博开放平台接口api
 * Class Databus
 * @package webapi\extensions
 */
class DatabusSdk
{
    private $app_key1 = 'xxxx';
    private $app_secret1 = 'xxxxx';

    private $requesturl;

    //+------------------平台账号搜索-----------------
    /**
     * 微信公号检索
     * @param $nickname
     * @param int $page
     * @return mixed
     */
    public function weixinSearch($nickname, $page=1)
    {
        $params = [];
        $params['wx_nickname'] = $nickname;
        $params['page'] = $page;
        $params['limit'] = 20;
        $route = '/account/weixin/search';
        $rs = $this->request($params,$route);
        return $rs;
    }

    /**
     * 微博账号检索
     * @param $nickname
     * @param int $page
     * @return mixed
     */
    public function weiboSearch($nickname, $page=1)
    {
        $params = [];
        $params['nickname'] = $nickname;
        $params['page'] = $page;
        $params['limit'] = 20;
        $route = '/account/weibo/search';
        $rs = $this->request($params,$route);
        return $rs;
    }

    /**
     * 头条账号检索
     * @param $nickname
     * @param int $page
     * @return mixed
     */
    public function toutiaoSearch($nickname, $page=1)
    {
        $params = [];
        $params['nickname'] = $nickname;
        $params['page'] = $page;
        $params['limit'] = 20;
        $route = '/account/toutiao/search';
        $rs = $this->request($params,$route);
        return $rs;
    }

    /**
     * 抖音账号检索
     * @param $nickname
     * @param int $page
     * @return mixed
     */
    public function douyinSearch($nickname, $page=1)
    {
        $params = [];
        $params['douyin_name'] = $nickname;
        $params['page'] = $page;
        $params['limit'] = 20;
        $route = '/account/douyin/search';
        $rs = $this->request($params,$route);
        return $rs;
    }

    /**
     * 快手账号检索
     * @param $nickname
     * @param int $page
     * @return mixed
     */
    public function kuaishouSearch($nickname, $page=1)
    {
        $params = [];
        $params['kuaishou_name'] = $nickname;
        $params['page'] = $page;
        $params['limit'] = 20;
        $route = '/account/kuaishou/search';
        $rs = $this->request($params,$route);
        return $rs;
    }

    //+-------------微信分组操作-----------------
    /**
     * 添加微信分组
     * @param $group_name
     * @return mixed
     */
    public function addWeixinGroup($group_name)
    {
        $params = [];
        $params['group_name'] = $group_name;
        $route = '/myrank/weixin/group-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 往分组中添加微信公众号
     * @param $group_id
     * @param $wx_biz
     * @return mixed
     */
    public function addWeixinAcc($group_id,$wx_biz)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['wx_biz'] = $wx_biz;
        $route = '/myrank/weixin/acct-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 通过微信文章url添加公众号到分组
     * @param $group_id
     * @param $wx_url
     * @return mixed
     */
    public function addWeixinAccUrl($group_id,$wx_url)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['wx_url'] = $wx_url;
        $route = '/myrank/weixin/acct-add-by-url';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 删除分组内的微信公众号
     * @param $group_id
     * @param $account_id
     * @return mixed
     */
    public function delWeixinAcc($group_id,$account_id)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['account_id'] = $account_id;
        $route = '/myrank/weixin/acct-del';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 微信榜单
     * @param $rank_date
     * @param $type
     * @param $group_id
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function weixinRankData($rank_date, $type, $group_id, $page = 1, $limit = 50)
    {
        if($type == 'day'){
            $route = '/myrank/weixin/day';
        }elseif ($type == 'week'){
            $route = '/myrank/weixin/week';
        }else{
            $route = '/myrank/weixin/month';
        }
        $params = [];
        $params['rank_date'] = $rank_date;
        $params['group_id'] = $group_id;
        $params['limit'] = $limit;
        $params['page'] = $page;
        $rs = $this->request($params,$route);
        return $rs;
    }

    //+-----------------微博分组操作---------------------
    /**
     * 添加微博分组
     * @param $group_name
     * @return mixed
     */
    public function addWeiboGroup($group_name)
    {
        $params = [];
        $params['group_name'] = $group_name;
        $route = '/myrank/weibo/group-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 往分组中添加微博账号
     * @param $group_id
     * @param $weibo_uid
     * @return mixed
     */
    public function addWeiboAcc($group_id,$weibo_uid)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['weibo_uid'] = $weibo_uid;
        $route = '/myrank/weibo/acct-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 通过主页url添加微博号到分组
     * @param $group_id
     * @param $weibo_url
     * @return mixed
     */
    public function addWeiboAccUrl($group_id,$weibo_url)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['weibo_url'] = $weibo_url;
        $route = '/myrank/weibo/acct-url-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 删除分组内的微博号
     * @param $group_id
     * @param $weibo_uid
     * @return mixed
     */
    public function delWeiboAcc($group_id,$weibo_uid)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['weibo_uid'] = $weibo_uid;
        $route = '/myrank/weibo/acct-del';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 微博榜单
     * @param $rank_date
     * @param $type
     * @param $group_id
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function weiboRankData($rank_date, $type, $group_id, $page = 1, $limit = 50)
    {
        if($type == 'day'){
            $route = '/myrank/weibo/day';
        }elseif($type == 'week'){
            $route = '/myrank/weibo/week';
        }else{
            $route = '/myrank/weibo/month';
        }
        $params = [];
        $params['rank_date'] = $rank_date;
        $params['group_id'] = $group_id;
        $params['limit'] = $limit;
        $params['page'] = $page;
        $rs = $this->request($params,$route);
        return $rs;
    }

    /**
     * 查询微博账号信息
     * @param $wb_id
     * @return mixed
     */
    public function getWbInfo($wb_id)
    {
        $params['weibo_uid'] = $wb_id;
        $route = '/account/weibo/attribute';
        $rs = $this->request($params, $route);
        return $rs;
    }

    //+-----------------头条分组操作---------------------
    /**
     * 添加头条分组
     * @param $group_name
     * @return mixed
     */
    public function addToutiaoGroup($group_name)
    {
        $params = [];
        $params['group_name'] = $group_name;
        $route = '/myrank/toutiao/group-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 往分组中添加头条账号
     * @param $group_id
     * @param $toutiao_user_id
     * @return mixed
     */
    public function addToutiaoAcc($group_id,$toutiao_user_id)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['user_id'] = $toutiao_user_id;
        $route = '/myrank/toutiao/acct-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 通过主页url添加头条号到分组
     * @param $group_id
     * @param $toutiao_url
     * @return mixed
     */
    public function addToutiaoUrl($group_id,$toutiao_url)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['toutiao_url'] = $toutiao_url;
        $route = '/myrank/toutiao/acct-url-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 删除分组内的头条号
     * @param $group_id
     * @param $toutiao_user_id
     * @return mixed
     */
    public function delToutiaoAcc($group_id,$toutiao_user_id)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['user_id'] = $toutiao_user_id;
        $route = '/myrank/toutiao/acct-del';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 头条榜单
     * @param $rank_date
     * @param $type
     * @param $group_id
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function toutiaoRankData($rank_date, $type, $group_id, $page = 1, $limit = 50)
    {
        if($type == 'day'){
            $route = '/myrank/toutiao/day';
        }elseif ($type == 'week'){
            $route = '/myrank/toutiao/week';
        }else{
            $route = '/myrank/toutiao/month';
        }
        $params = [];
        $params['rank_date'] = $rank_date;
        $params['group_id'] = $group_id;
        $params['page'] = $page;
        $params['limit'] = $limit;
        $rs = $this->request($params,$route);
        return $rs;
    }

    /**
     * 查询微头条号信息
     * @param $wb_id
     * @return mixed
     */
    public function getTtInfo($tt_id)
    {
        $params['toutiao_user_id'] = $tt_id;
        $route = '/account/toutiao/attribute';
        $rs = $this->request($params, $route);
        return $rs;
    }

    //+-----------------抖音分组操作---------------------
    /**
     * 添加douyin分组
     * @param $group_name
     * @return mixed
     */
    public function addDouyinGroup($group_name)
    {
        $params = [];
        $params['group_name'] = $group_name;
        $route = '/myrank/douyin/group-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 往分组中添加抖音账号
     * @param $group_id
     * @param $douyin_id
     * @return mixed
     */
    public function addDouyinAcc($group_id,$douyin_id)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['douyin_id'] = $douyin_id;
        $route = '/myrank/douyin/acct-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 通过主页url添加抖音号到分组
     * @param $group_id
     * @param $douyin_url
     * @return mixed
     */
    public function addDouyinUrl($group_id,$douyin_url)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['douyin_url'] = $douyin_url;
        $route = '/myrank/douyin/acct-url-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 删除分组内的抖音号
     * @param $group_id
     * @param $douyin_id
     * @return mixed
     */
    public function delDouyinAcc($group_id,$douyin_id)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['douyin_id'] = $douyin_id;
        $route = '/myrank/douyin/acct-del';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 抖音榜单
     * @param $rank_date
     * @param $type
     * @param $group_id
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function douyinRankData($rank_date, $type, $group_id, $page = 1, $limit = 50)
    {
        if($type == 'day'){
            $route = '/myrank/douyin/day';
        }elseif($type == 'week'){
            $route = '/myrank/douyin/week';
        }else{
            $route = '/myrank/douyin/month';
        }
        $params = [];
        $params['rank_date'] = $rank_date;
        $params['group_id'] = $group_id;
        $params['page'] = $page;
        $params['limit'] = $limit;
        $rs = $this->request($params,$route);
        return $rs;
    }

    //+-----------------快手分组操作---------------------
    /**
     * 添加douyin分组
     * @param $group_name
     * @return mixed
     */
    public function addKuaishouGroup($group_name)
    {
        $params = [];
        $params['group_name'] = $group_name;
        $route = '/myrank/kuaishou/group-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 往分组中添加快手账号
     * @param $group_id
     * @param $kuaishou_id
     * @return mixed
     */
    public function addKuaishouAcc($group_id,$kuaishou_id)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['kuaishou_id'] = $kuaishou_id;
        $route = '/myrank/kuaishou/acct-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 通过主页url添加快手号到分组
     * @param $group_id
     * @param $kuaishou_url
     * @return mixed
     */
    public function addKuaishouUrl($group_id,$kuaishou_url)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['kuaishou_url'] = $kuaishou_url;
        $route = '/myrank/kuaishou/acct-url-add';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 删除分组内的快手号
     * @param $group_id
     * @param $kuaishou_id
     * @return mixed
     */
    public function delKuaishouAcc($group_id,$kuaishou_id)
    {
        $params = [];
        $params['group_id'] = $group_id;
        $params['kuaishou_id'] = $kuaishou_id;
        $route = '/myrank/kuaishou/acct-del';
        $rs = $this->request($params, $route);
        return $rs;
    }

    /**
     * 快手榜单
     * @param $rank_date
     * @param $type
     * @param $group_id
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function kuaishouRankData($rank_date, $type, $group_id, $page = 1, $limit = 50)
    {
        if($type == 'day'){
            $route = '/myrank/kuaishou/day';
        }elseif($type == 'week'){
            $route = '/myrank/kuaishou/week';
        }else{
            $route = '/myrank/kuaishou/month';
        }
        $params = [];
        $params['rank_date'] = $rank_date;
        $params['group_id'] = $group_id;
        $params['page'] = $page;
        $params['limit'] = $limit;
        $rs = $this->request($params,$route);
        return $rs;
    }

    //+-----------------榜单最新日期----------------------------
    /**
     * 微信榜单最新日期
     * @return mixed
     */
    public function weixinlatest()
    {
        $route = '/myrank/weixin/latest';
        $rs = $this->request('',$route);
        return $rs;
    }

    /**
     * 微博最新榜单日期
     * @return mixed
     */
    public function weibolatest()
    {
        $route = '/rank/weibo/latest';
        $rs = $this->request('',$route);
        return $rs;
    }

    /**
     * 头条最新榜单日期
     * @return mixed
     */
    public function toutiaolatest()
    {
        $route = '/myrank/toutiao/latest';
        $rs = $this->request('',$route);
        return $rs;
    }

    /**
     * 抖音最新榜单日期
     * @return mixed
     */
    public function douyinlatest()
    {
        $route = '/rank/douyin/latest';
        $rs = $this->request('',$route);
        return $rs;
    }

    /**
     * 快手最新榜单日期
     * @return mixed
     */
    public function kuaishoulatest()
    {
        $route = '/rank/kuaishou/latest';
        $rs = $this->request('',$route);
        return $rs;
    }


    //+----------------------------------------------------------

    public function request($param , $route , $type = 'wx')
    {
        $this->requesturl = 'http://databus.gsdata.cn:8888/api/service';
        $paramStr = $this->ASCIIarr($param);
        if($type == 'wx'){
            $sign = md5($this->app_secret1.$paramStr[1].$this->app_secret1);
            $accesstoken = base64_encode($this->app_key1.':'.$sign.':'.$route);
        }

        $headers[] = 'Content-Type:application/x-www-form-urlencoded; charset=utf-8';
        $headers[] = 'access-token: '.$accesstoken;
        if(!empty($param)){
            $this->requesturl .= '?'.$paramStr[0];
        }
        $res = $this->httpGetHeader($this->requesturl,$headers);

        return json_decode($res,1);
    }


    private function ASCIIarr($params = array())
    {
        //echo '&amptimes';die;
        if (!empty($params)) {
            $p = ksort($params);
            if ($p) {
                $str1 = '';
                $str2 = '_';
                foreach ($params as $k => $val) {
                    $str1 .= $k . '=' . urlencode($val) . '&';
                }
                foreach ($params as $k => $val) {
                    $str2 .= $k . $val;
                }
                $str1 = rtrim($str1, '&');
                $str2 .= '_';
                return [$str1, $str2];
            }
        }else{
            return ['','__'];
        }
    }

    private function httpGetHeader($url,$header)
    {
        $oCurl = curl_init ();
        if (stripos ( $url, "https://" ) !== FALSE) {
            curl_setopt ( $oCurl, CURLOPT_SSL_VERIFYPEER, FALSE );
            curl_setopt ( $oCurl, CURLOPT_SSL_VERIFYHOST, FALSE );
        }

        curl_setopt( $oCurl, CURLOPT_NOSIGNAL,1);    //注意，毫秒超时一定要设置这个
        curl_setopt( $oCurl, CURLOPT_TIMEOUT_MS,18000);  //超时毫秒，cURL 7.16.2中被加入

        curl_setopt ( $oCurl, CURLOPT_URL, $url );
        curl_setopt ( $oCurl, CURLOPT_HTTPHEADER, $header );
        curl_setopt ( $oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec ( $oCurl );
        $aStatus = curl_getinfo ( $oCurl );
        curl_close ( $oCurl );
        if (intval ( $aStatus ["http_code"] ) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }
}
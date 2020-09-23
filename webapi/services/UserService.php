<?php
/**
 * 用户服务
 * @author xiao
 */
namespace webapi\services;

use core\extensions\Jwt;
use webapi\models\Scheme;
use webapi\models\SysLog;
use webapi\models\SysMenu;
use webapi\models\SysUser;
use webapi\models\SysRole;
use Yii;

class UserService
{
    public static $uid; //用户登录后的用户id
    public static $rid; //用户登录后的角色id
    public static $user_base; //用户登录后的基本信息 ['company_id'=>1,'rid'=>1,'user_name'=>'123']

    protected $schemeId;
    protected $timeLimit;//会员最大查询时间范围

    /**
     * 用户登陆接口 返回token
     * @param $username
     * @param $password
     */
    public function login($user_name, $password)
    {
        //查询系统
        Yii::$app->service->CompanyService->getCompanyInfo();
        $this->checkCompany();
        //查询用户
        $user = SysUser::find()->where(['user_name' => $user_name, 'company_id' => CompanyService::$company_id, 'status' => 0])->asArray()->one();
        $this->checkUser($user, $password);
        //生成token
        $token = Jwt::createToken($user['id'], [
            'company_id' => CompanyService::$company_id,
            'rid' => $user['rid'],
            'user_name' => $user_name,
        ]);
        //更新用户登录信息
        $this -> updateUserLogin($user['id']);
        //更新日志
        Yii::$app ->service->LogService->saveSysLog('登录系统', CompanyService::$company_id, $user['id'], SysLog::TYPE_THREE);

        return $token;
    }

    /**
     * 检查系统
     */
    public function checkCompany()
    {
        $is_err = false;
        $err_msg = '';
        if (!CompanyService::$company_info) {
            $is_err = true;
            $err_msg = '系统不存在';
        }
        if (!$is_err && isset(CompanyService::$company_info['status']) && (CompanyService::$company_info['status'] == -1)) {
            $is_err = true;
            $err_msg = '企业账号已被删除';
        }
        if (!$is_err && CompanyService::$company_info['open_status'] == 0) {
            $is_err = true;
            $err_msg = '企业账号还未开通';
        }
        if (!$is_err && CompanyService::$company_info['end_time'] && (CompanyService::$company_info['end_time'] != '0000-00-00 00:00:00') && (CompanyService::$company_info['end_time'] <= date('Y-m-d H:i:s'))) {
            $is_err = true;
            $err_msg = '企业账号已于' . CompanyService::$company_info['end_time'] . '到期';
        }
        if ($is_err) {
            //登出
            $this->logout();
            return jsonErrorReturn('fail', $err_msg);
        }
    }

    /**
     * 检查用户
     * @param $user
     * @param string $password
     * @param bool $is_login //true登陆检查 false正常请求检查
     */
    public function checkUser($user, $password = '', $is_login = true)
    {
        $is_err = false;
        $err_msg = '';
        //检查用户
        if (!$user) {
            $is_err = true;
            if ($is_login) {
                $err_msg = '用户名不正确';
            }else {
                $err_msg = '用户不存在';
            }
        }
        //判断密码
        if (!$is_err && $is_login && $user['password'] != encryptPassword($password, $user['salt'])) {
            $is_err = true;
            $err_msg = "密码不正确";
        }
        //判断是否禁用
        if (!$is_err && $user['is_ban'] == SysUser::$is_ban_one) {
            $is_err = true;
            $err_msg = '您已被禁用，请联系管理员。';
        }
        //判断状态
        if (!$is_err && $user['status'] == -1) {
            $is_err = true;
            $err_msg = "您已被从系统中删除";
        }
        //判断时间
        $now_date = date('Y-m-d H:i:s');
        if (!$is_err && $user['end_time'] && ($user['end_time'] != '0000-00-00 00:00:00') && ($user['end_time'] <= $now_date)) {
            $is_err = true;
            $err_msg = "用户账号已于{$user['end_time']}到期";
        }
        if ($is_err) {
            //登出
            $this->logout();
            return jsonErrorReturn('fail', $err_msg);
        }
    }

    /**
     * 判断用户是否登陆
     * @return bool
     */
    public function checkLogin($authorization = '')
    {
        $token = isset($_COOKIE['Authorization']) ? $_COOKIE['Authorization'] : '';
        if ($authorization) {
            $token = $authorization;
        }
        if (!$token) return false;
        Jwt::validateToken($token, ($authorization ? true : false));
        //print_r(self::$uid );exit;
        if (self::$uid && CompanyService::$company_id){
            //检查企业
            $this->checkCompany();
            //检查用户
            $user_info = Yii::$app -> cache -> get(RedisKeyService::getKey(1004, CompanyService::$company_id . ':' . self::$uid));
            if ($user_info) {
                $user_info = json_decode($user_info, true);
            }else {
                $user_info = SysUser::find()->select('is_ban,status,end_time')->where(['id' => self::$uid])->asArray()->one();
                Yii::$app -> cache -> set(RedisKeyService::getKey(1004, CompanyService::$company_id . ':' . self::$uid), json_encode($user_info, JSON_UNESCAPED_UNICODE), (24 * 3600));
            }
            $this->checkUser($user_info, '' , false);

            return true;
        }
        return false;
    }

    /**
     * 用户退出
     */
    public function logout()
    {
        $token = Yii::$app -> request -> get('Authorization','');
        if(empty($token)) {
            $token = isset($_COOKIE['Authorization']) ? $_COOKIE['Authorization'] : '';
        }
        if ($token) {
            //删除登陆信息
            Jwt::deleteToken($token);
            //更新用户信息缓存
            Yii::$app->cache->delete(RedisKeyService::getKey(1004, CompanyService::$company_id . ':' . UserService::$uid));
            //清除cookie
            setcookie("Authorization", null, -1, "/");
        }
    }

    /**************************************【以下方法暂未用到】************************************************/
    /**修改密码
     * @param $mobile
     * @param $pwd
     */
    public function resetPwd($mobile, $pwd){
        $this->companyId = CompanyService::$company_id;
        //判断用户是否存在
        $user =  User::findOne(["tel"=>$mobile,"company_id"=>  $this->companyId ]);
        if (!$user) return jsonErrorReturn("fail","用户信息异常");

        $salt = mt_rand(100000, 999999);
        $user ->salt = $salt;
        $password = encryptPassword($pwd,$salt);
        $user ->password = $password;
        return $user->save();
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo($company_id = null, $uid = null)
    {
        if (!$company_id) {
            $company_id = CompanyService::$company_id;
        }
        if (!$uid) {
            $uid = self::$uid;
        }
        //获取用户信息
        return  SysUser::find()->where(['id' => $uid, 'company_id' => $company_id])->asArray()->one();
    }

    /**
     * 获取用户ID
     * @return mixed
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * 获取企业id
     * @return mixed
     */
    public function getCompanyId(){
        return $this->companyId;
    }

    /**
     * @return mixed
     * 获取最后一次访问的方案ID
     */
    public function getLastSchemeId(){
        /** @var $userExtend UserExtend*/
        $userExtend =  UserExtend::find()->where(['user_id'=>$this->uid])->one();
        return $userExtend -> last_scheme_id;
    }

    /**
     * 获取用户头像
     * @return mixed
     */
    public function getUserHeadImg() {
        $user_info = $this -> getUserInfo();
        return $user_info && mb_strlen($user_info -> icon, 'utf-8')?$user_info -> icon:'';
    }

    //获取会员的信息查询时间范围
    public function getUserDateLimit(){
        if ( $this->timeLimit ) return $this->timeLimit;
        //读取筛选时间限制
        $days = Yii::$app->service->UserAuthorityService->searchDaysLimit($this->uid);
        $startDate = date("Y-m-d",time() - 86400 * $days);
        $endDate = date("Y-m-d",time());
        return $this->timeLimit = compact("startDate","endDate");
    }

    /**
     * 获取用户对应的企业剩余短信量
     * @param int $userId
     * @return int
     */
    public function getRemainSmsNum($userId = 0) {
        if(!$userId) {
            $userId = $this -> getUid();
        }
        //获取用户对应的短信数量
        $left_num = Yii::$app->service->UserAuthorityService->getUserSmsNum($userId);
        return $left_num;
    }

    /**
     * 更新用户上次登录信息
     */
    private function updateUserLogin($uid)
    {
//        $user = SysUser::findOne(['id' => $uid, 'sys_id' => $sys_id]);
//        $user -> last_login_at = date('Y-m-d H:i:s');
//        $user -> last_login_ip = Yii::$app -> request -> userIP;
//        $user -> save();
        SysUser::updateAll(['last_login_ip' => Yii::$app -> request -> userIP, 'last_login_time' => date('Y-m-d H:i:s')], 'id=:uid', ['uid' => $uid]);
    }

    /**
     * 通过用户ID获取用户信息
     * @param int $userId
     */
    public function getUserInfoByUserId($userId)
    {
        return  User::findOne(['id' => $userId]);
    }

    /**
     * 用户是否过期
     * @param $userId
     * @return bool
     */
    public function isExpire($userId){
        return Yii::$app -> service -> UserExtendService -> isUserExpire($userId);
    }

    /**
     * 判断用户选择的时间是否超出允许的时间
     * @param $startTime
     * @param $endTime
     * @return bool
     */
    public function isOutDays($startTime,$endTime){
        $days = Yii::$app -> service -> UserAuthorityService -> searchDaysLimit(UserService()->getUid());
        if((strtotime($endTime) - strtotime($startTime)) > ($days * $days)){
            return false;
        }
        return true;
    }

    /**
     * 通过用户ID获取所属企业的ID
     * @param int $userId
     * @return int
     */
    public function getCompanyIdByUserId($userId) {
        $user = $this -> getUserInfoByUserId($userId);
        return $user?intval($user -> company_id):0;
    }

    /**
     * 检查用户当前方案数是否超过限制
     */
    public function checkUserSchemeNum(){
        $uid = $this->getUid();
        //判断当前企业的总方案数
     /*   $cid = CompanyService::$company_id;
        $companyAuth = Yii::$app->globalService->CompanyAuthorityService->getCompanyAuthorityByCompanyId($cid);
        $companyUseSchemeNum  =  Yii::$app->globalService->CompanyAuthorityService->getCompanyUseSchemeNum($cid);
        $companyUseKeywordNum =  Yii::$app->globalService->CompanyAuthorityService->getCompanyUseKeywordsNum($cid);
        if ($companyAuth['scheme_num'] < $companyUseSchemeNum || $companyAuth['monitoring_words_num'] < $companyUseKeywordNum ){
            return jsonErrorReturn("fail","您的账号权限已经超出限制！请删除方案！如需升级需求请联系客服");
        }*/
        //总方案数
        $schemeLimit = Yii::$app->service->UserAuthorityService->schemeNumLimit($uid);
        if( $schemeLimit != -1 ) {  //获取当前总方案
            $count = Scheme::find()->where(['user_id'=>$uid,"status"=>Scheme::STATUS_NORMAL])->count();
            if ($count > $schemeLimit )  return jsonErrorReturn("fail","总方案数不能超过".$schemeLimit."个!请删除后再进行操作！");
        }
        //获取总分析词个数限制
        $limit = Yii::$app->service->UserAuthorityService->monitoringWordsNumLimit($uid);
        //获取已经添加的分析词个数
        $hasUse = Yii::$app->service->SchemeService->getKeywordsTotal($uid);
        if ($hasUse > $limit) return jsonErrorReturn("fail","总分析词数量超出".$limit."个上限！请删除后再进行操作！");

    }

}
<?php

namespace webapi\services;

use webapi\models\UserAuthority;
use webapi\models\UserExtend;
use Yii;

/**
 * 获取以及更新数据不允许私自处理，必须要调用本服务方法处理
 * Class UserExtendService
 * @package webapi\services
 */
class UserExtendService
{
    //用户关闭状态
    const STATUS_CLOSED = 0;
    //用户正常状态
    const STATUS_NORMAL = 1;
    //用户删除状态
    const STATUS_DEL = 2;

    /**
     * 新增/更新用户扩展信息
     * @param array $data
     * @return bool|null|UserExtend|static
     */
    public function updateUserExtend($data) {
        if(!isset($data['user_id'])) {
            return false;
        }
        $data['user_id'] = intval($data['user_id']);
        $userExtend = UserExtend::findOne(['user_id' => $data['user_id']]);
        if(!$userExtend) {
            $userExtend = new UserExtend();
            $userExtend -> create_time = time();
            $userExtend -> user_id = $data['user_id'];
        }
        $userExtend -> update_time = time();
        if(isset($data['expire_time'])) {
            $userExtend -> expire_time = intval($data['expire_time']);
        }
        if(isset($data['last_scheme_id'])) {
            $userExtend -> last_scheme_id = intval($data['last_scheme_id']);
        }
        if(isset($data['status'])) {
            $userExtend -> status = intval($data['status']);
        }
        if(isset($data['open_member_time'])) {
            $userExtend -> open_member_time = $data['open_member_time'];
        }
        if(isset($data['remark'])) {
            $userExtend -> remark = trim($data['remark']);
        }
        return $userExtend -> save() ? UserExtend::findOne(['user_id' => $userExtend -> user_id]) : false;
    }

    /**
     * 获取用户扩展信息
     * @param int $userId
     * @return bool|null|UserExtend|UserExtendService|static
     */
    public function getUserExtendByUserId($userId = 0) {
        if(!$userId) {
            $userId = Yii::$app -> service -> UserService -> getUid();
        }
        $userExtend = UserExtend::findOne(['user_id' => $userId]);
        if(!$userExtend) {
            $userExtend = $this -> updateUserExtend(['user_id' => $userId]);
        }
        return $userExtend ? $userExtend : false;
    }

    /**
     * 会员是否过期
     * @param int $userId
     * @return bool
     */
    public function isUserExpire($userId = 0) {
        $userExtend = $this -> getUserExtendByUserId($userId);
        if(!$userExtend || $userExtend -> expire_time < time()) {
            return true;
        }
        return false;
    }

    /**
     * 更新最近使用时间
     * @param int $userId
     */
    public function updateLatelyUseTime($userId = 0) {
        if(!$userId) {
            $userId = Yii::$app -> service -> UserService -> getUid();
        }
        $userExtend = $this -> getUserExtendByUserId($userId);
        $userExtend -> lately_use_time = time();
        $userExtend -> save();
    }
}
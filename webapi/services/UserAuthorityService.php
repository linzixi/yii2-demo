<?php

namespace webapi\services;

use core\models\CompanyAuthority;
use webapi\models\UserAuthority;
use webapi\models\UserExtend;
use Yii;

/**
 * Class UserAuthority
 * @package webapi\services
 */
class UserAuthorityService
{
    /**
     * 新增用户权益
     * @param array $data
     * @return bool|null|UserAuthority|static
     */
    public function createUserAuthority($data) {
        if(!isset($data['user_id'])) {
            return false;
        }
        $userAuthority = new UserAuthority();
        $userAuthority -> user_id = $data['user_id'];
        $userAuthority -> update_time =  time();
        if(isset($data['days'])) {
            $userAuthority -> days = intval($data['days']);
        }
        if(isset($data['type'])) {
            $userAuthority -> type = intval($data['type']);
        }
        if(isset($data['qing_bei_num'])) {
            $userAuthority -> qing_bei_num = $data['qing_bei_num']*1;
        }
        if(isset($data['monitoring_words_num'])) {
            $userAuthority -> monitoring_words_num = intval($data['monitoring_words_num']);
        }
        if(isset($data['search_days'])) {
            $userAuthority -> search_days = intval($data['search_days']);
        }
        if(isset($data['export_data_num'])) {
            $userAuthority -> export_data_num = intval($data['export_data_num']);
        }
        if(isset($data['support_three_ways_warning'])) {
            $userAuthority -> support_three_ways_warning = intval($data['support_three_ways_warning']);
        }
        if(isset($data['support_artificial_warning_set'])) {
            $userAuthority -> support_artificial_warning_set = intval($data['support_artificial_warning_set']);
        }
        if(isset($data['support_full_text_search'])) {
            $userAuthority -> support_full_text_search = intval($data['support_full_text_search']);
        }
        if(isset($data['free_briefing_num'])) {
            $userAuthority -> free_briefing_num = intval($data['free_briefing_num']);
        }
        if(isset($data['support_hot_event_library'])) {
            $userAuthority -> support_hot_event_library = intval($data['support_hot_event_library']);
        }
        if(isset($data['support_report_download'])) {
            $userAuthority -> support_report_download = intval($data['support_report_download']);
        }
        if(isset($data['single_export_data_num'])) {
            $userAuthority -> single_export_data_num = intval($data['single_export_data_num']);
        }
        if(isset($data['start_time'])) {
            $userAuthority -> start_time = $data['start_time'];
        }
        if(isset($data['end_time'])) {
            $userAuthority -> end_time = $data['end_time'];
        }
        if(isset($data['state'])) {
            $userAuthority -> state = intval($data['state']);
        }
        if(isset($data['contacts_num_limit'])) {
            $userAuthority -> contacts_num_limit = intval($data['contacts_num_limit']);
        }
        if(isset($data['wechat_contacts_num_limit'])) {
            $userAuthority -> wechat_contacts_num_limit = intval($data['wechat_contacts_num_limit']);
        }
        if(isset($data['email_contacts_num_limit'])) {
            $userAuthority -> email_contacts_num_limit = intval($data['email_contacts_num_limit']);
        }
        if(isset($data['scheme_num'])) {
            $userAuthority -> scheme_num = intval($data['scheme_num']);
        }
        if(isset($data['contrast_scheme_num'])) {
            $userAuthority -> contrast_scheme_num = intval($data['contrast_scheme_num']);
        }
        return $userAuthority -> save() ? true : false;
    }

    /**
     * 获取目前用户的权限
     * @param int $userId
     * @return bool|null|UserAuthority|UserAuthorityService|static
     */
    public function getUserAuthorityByUserId($userId = 0,$company_id = 0) {
        if(!$userId) {
            $userId = Yii::$app -> service -> UserService -> getUid();
        }
        //$company_id存在代表为后台脚本使用
      //  $company_id = $company_id ? $company_id :  CompanyService::$company_id;
        $model = UserAuthority::find();
        $userAuthority = $model
            ->where([
                'state' => UserAuthority::STATE_NORMAL,
                'user_id' => $userId,
            //    'company_id' => $company_id
            ])
            ->one();
        return $userAuthority ? $userAuthority : false;
    }

    /**
     * 分析词个数限制
     * @param int $userId
     * @return int|mixed
     */
    public function monitoringWordsNumLimit($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority ? intval($userAuthority -> monitoring_words_num) : 0;
    }

    /**
     * 允许筛选的天数
     * @param int $userId
     * @return int|mixed
     */
    public function searchDaysLimit($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority ? intval($userAuthority -> search_days) : 0;
    }

    /**
     * 是否支持邮件、微信、短信预警
     * @param int $userId
     * @return bool
     */
    public function isSupportThreeWaysWarning($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority && $userAuthority -> support_three_ways_warning == 1 ? true : false;
    }

    /**
     * 是否支持人工预警设置
     * @param int $userId
     * @return bool
     */
    public function isSupportArtificialWarningSet($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority && $userAuthority -> support_artificial_warning_set == 1 ? true : false;
    }

    /**
     * 是否支持全文检索
     * @param int $userId
     * @return bool
     */
    public function isSupportFullTextSearch($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority && $userAuthority -> support_full_text_search == 1 ? true : false;
    }

    /**
     * 允许生成的简报数，注意-1表示没有限制
     * @param int $userId
     * @return int
     */
    public function freeBriefingNum($userId = 0,$company_id = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId,$company_id);
        return $userAuthority ? intval($userAuthority -> free_briefing_num) : 0;
    }

    /**
     * 是否支持热点事件库
     * @param int $userId
     * @return bool
     */
    public function isSupportHotEventLibrary($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority && $userAuthority -> support_hot_event_library == 1 ? true : false;
    }

    /**
     * 是否支持报告下载
     * @param int $userId
     * @return bool
     */
    public function isSupportReportDownload($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority && $userAuthority -> support_report_download == 1 ? true : false;
    }

    /**
     * 单次下载限制数量
     * @param int $userId
     * @return int
     */
    public function singleDownloadNumLimit($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        //获取用户对应的公司权限
        $companyId = Yii::$app -> service -> UserService -> getCompanyIdByUserId($userId);
        if(!$companyId) {
            return 0;
        }
        /** @var  $companyAuthority CompanyAuthority*/
        $companyAuthority = Yii::$app -> globalService -> AuthorityService -> getCompanyAuthorityByCompanyId($companyId);
        if(!$companyAuthority || !$userAuthority) {
            return 0;
        }
        if($companyAuthority -> single_export_data_num > $userAuthority -> single_export_data_num) {
            return intval($userAuthority -> single_export_data_num);
        } else {
            return intval($companyAuthority -> single_export_data_num);
        }
    }

    /**
     * 更新免费简报数
     * @param int $num
     * @param int $userId
     * @return bool
     * @throws \yii\db\Exception
     */
    public function updateFreeBriefingNum($num, $userId = 0,$company_id = 0) {
        if(!$userId) {
            $userId = Yii::$app -> service -> UserService -> getUid();
        }
        //是否是无限简报数
        $freeBriefingNum = $this -> freeBriefingNum($userId,$company_id);
        if($freeBriefingNum == -1) {
            return true;
        } else if($freeBriefingNum < $num) {
            return false;
        }
        $userExtend = Yii::$app -> service -> UserExtendService -> getUserExtendByUserId($userId);
        if(!$userExtend) {
            return false;
        }
        $set = '`free_briefing_num` = `free_briefing_num` - :free_briefing_num,`update_time` = :update_time';
        $where = '`state` = 1 and `user_id` = :user_id';
        $where .= ' and `type` = :type and `start_time` < :start_time';
        $where .= ' and `end_time` > :end_time and `free_briefing_num` >= :free_briefing_num';
        $sql = 'update `user_authority` set '.$set.' where '.$where;
        $data = [
            ':update_time' => time(),
            ':user_id' => $userId,
            ':start_time' => time(),
            ':end_time' => time(),
            ':free_briefing_num' => intval($num)
        ];
        $res = Yii::$app->db->createCommand($sql, $data)->execute();
        if(!$res) {
            return false;
        }
        return true;
    }

    /**
     * 获取短信联系人数量限制(-1表示无限)
     * @param int $userId
     * @return int
     */
    public function contactsNumLimit($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority ? intval($userAuthority -> contacts_num_limit) : 0;
    }

    /**
     * 获取微信联系人数量限制(-1表示无限)
     * @param int $userId
     * @return int
     */
    public function wechatContactsNumLimit($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority ? intval($userAuthority -> wechat_contacts_num_limit) : 0;
    }

    /**
     * 获取邮箱联系人数量限制(-1表示无限)
     * @param int $userId
     * @return int
     */
    public function emailContactsNumLimit($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority ? intval($userAuthority -> email_contacts_num_limit) : 0;
    }

    /**
     * 方案数量限制(-1表示无限)
     * @param int $userId
     * @return int
     */
    public function schemeNumLimit($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority ? intval($userAuthority -> scheme_num) : 0;
    }

    /**
     * 对比方案数量限制(-1表示无限)
     * @param int $userId
     * @return int
     */
    public function contrastSchemeNumLimit($userId = 0) {
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority -> contrast_scheme_num ? intval($userAuthority -> contrast_scheme_num) : 0;
    }

    /**
     * 获取剩余短信数
     * @param $userId
     * @return int
     */
    public function getUserSmsNum($userId){
        $userAuthority = $this -> getUserAuthorityByUserId($userId);
        return $userAuthority -> free_sms_num ? intval($userAuthority -> free_sms_num) : 0;
    }

    /**
     * 获取当前用户权限
     * @return array
     */
    public function getUserAuthority() {
        $userExtend = Yii::$app->service->UserExtendService->getUserExtendByUserId();
        $authority = [
            'openMultiSchemeInfo' => $userExtend?($userExtend->open_multi_scheme_info==1?1:-1):-1,//多方案信息汇总菜单
        ];
        return $authority;
    }
}
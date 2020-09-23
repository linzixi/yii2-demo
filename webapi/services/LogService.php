<?php
/**
 * 系统日志服务
 * @author xiao
 * @date 2020年6月12日09:56:33
 */
namespace webapi\services;

use webapi\models\SysLog;
use Yii;

class LogService
{
    /**
     * 保存系统操作日志
     */
    public function saveSysLog($content, $company_id = 0, $uid = 0, $type = SysLog::TYPE_SYS)
    {
        if (!$content) {
            return;
        }
        $params = [
            'company_id' => $company_id ? $company_id : (CompanyService::$company_id ? CompanyService::$company_id : 0),
            'type' => $type,
            'content' => $content,
            'c_uid' => $uid ? $uid : (UserService::$uid ? UserService::$uid : 0),
            'c_time' => date('Y-m-d H:i:s'),
        ];
        Yii::$app->db->createCommand()->insert(SysLog::tableName(), $params)->execute();
    }
}
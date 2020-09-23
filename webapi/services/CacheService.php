<?php
/**
 * @Author:    Zhangshaolong<729562806@qq.com>
 * @Date:      2019/8/1
 * 缓存service
 */
namespace webapi\services;

use webapi\models\SchemeBriefdoc;
use webapi\models\DownloadLog;
use Yii;

class CacheService
{
    /**
     * 获取当前的进度
     * @param  $uuid
     * @param string $type
     * @return mixed
     */
    public function getCurrentProgress($uuid, $type='brief') {
        $cache = Yii::$app->cache;
        $cache_key = $type."_{$uuid}_briefdoc_progress";
        $cur_progress = @$cache->get($cache_key);
        return $cur_progress*1;
    }
    /**
     * @param $uuid
     * @param $status
     * @param string $type
     * @return array
     */
    public function getBriefdocProgress($uuid,$status,$type='brief')
    {
        $cache = Yii::$app->cache;
        $cache_key = $type."_{$uuid}_briefdoc_progress";
        $cache_time = 600;
        $default_progress = $step=0;
        $cur_progress = @$cache->get($cache_key);

        if($type == 'brief'){
            if ($status == SchemeBriefdoc::BRIEF_DOC_NOTMAKE) {
                //未生成
                $default_progress = 20;
                $step = rand(1, 2);
            } else if ($status == SchemeBriefdoc::BRIEF_DOC_MAKING) {
                //生成中
                $default_progress = 30;
                $step = rand(1, 2);
            } else if ($status == SchemeBriefdoc::BRIEF_DOC_MAKED || $status == SchemeBriefdoc::BRIEF_DOC_ERROR) {
                //已生成或生成失败(兼容前端)
                $cur_progress = 100;
                $step=0;
                $cache_time = 1;
            }
            $next_progress = !empty($cur_progress) ? intval($cur_progress) + $step : $default_progress + $step;
            if (!in_array($status, [SchemeBriefdoc::BRIEF_DOC_MAKED, SchemeBriefdoc::BRIEF_DOC_ERROR]) && $next_progress >= 90) {
                $next_progress = 90;
            }
        }else{
            if ($status == DownloadLog::STATUS_WAIT_START) {
                //未生成
                $default_progress = 20;
                $step = rand(1, 2);
            } else if ($status == DownloadLog::STATUS_EXPORTING) {
                //生成中
                $default_progress = 30;
                $step = rand(1, 2);
            } else if ($status == DownloadLog::STATUS_ALREADY_EXPORT) {
                //已生成
                $cur_progress = 100;
                $step=0;
                $cache_time = 1;
            }else if ($status == DownloadLog::STATUS_FAIL) {
                //下载失败
                $cur_progress = 100;
                $step=0;
                $cache_time = 1;
            }
            $next_progress = !empty($cur_progress) ? intval($cur_progress) + $step : $default_progress + $step;
            if ($status != DownloadLog::STATUS_ALREADY_EXPORT && $status != DownloadLog::STATUS_FAIL && $next_progress >= 90) {
                $next_progress = 90;
            }
        }

        if($next_progress == 100) {
            $cache->delete($cache_key);
        } else {
            $cache->set($cache_key, $next_progress, $cache_time);
        }
        return $next_progress;
    }


    /**
     * 短信验证码code存储
     * @param $mobile
     * @return int
     */
    public function setMobileCode($mobile,$code){
        $cache = Yii::$app->cache;
        $key = "msg_code_".$mobile;
        $cache->set($key,$code,600);
        return $code;
    }

    /**
     * 短信验证码验证
     */
    public function checkMobileCode($mobile,$code){
        $key = "msg_code_".$mobile;
        $cache = Yii::$app->cache;
        $hasCode = $cache->get($key);
        if ($hasCode && $hasCode == $code) {
            //生成修改签名
            $signKey = md5($mobile."check_sign");
            $cache->set($signKey,$mobile,300);
            $cache->delete($key);
            return $signKey;
        }
        return false;
    }

    /**
     * 验证修改密码的sign
     * @param $mobile
     * @param $sign
     * @return string
     */
    public function checkResetSign( $signKey ){

        $cache = Yii::$app->cache;
        $signValue = $cache->get($signKey);
        if ( $signValue ) {
            return $signValue;
        }
        return false;
    }

    public function deleteResetSign($sign){
        $cache = Yii::$app->cache;
        return $cache->delete($sign);
    }

}

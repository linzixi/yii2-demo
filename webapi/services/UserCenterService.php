<?php

namespace webapi\services;

use webapi\extensions\EsBuildQuery;
use webapi\models\DownloadLog;
use webapi\models\EsQueryErrorLog;
use webapi\models\SchemeBriefdoc;
use webapi\models\UserCollection;
use Yii;
use yii\helpers\ArrayHelper;
use webapi\extensions\Oss;

/**
 * Class UserCenterService
 * @package webapi\services
 */
class UserCenterService {
    //文章收藏类型
    const COLLECT_TYPE_NEWS = 1;

    //收藏类型集合
    public static $collectTypeArr = [self::COLLECT_TYPE_NEWS];

    //收藏
    const STATUS_COLLECT = 1;

    //取消收藏
    const STATUS_CANCEL_COLLECT = 2;
	
	   //下载任务取消缓存key
    const DOWNLOAD_CANCEL_KEY = 'download_cancel_';
    const DOWNLOAD_CANCELING = 1;

    /**
     * 获取文章信息集合
     * @param array $data 收藏的文章记录集合
     * @return array
     */
    public function convertNewsInfo($data) {
        //查询到对应的文章信息
        $typeId = array_column($data, 'type_id');
        foreach ($typeId as $item){
           $idInfo  =   SchemeService()->enDecorateNewsUuid($item);
           $typeIdArr[]= $idInfo->uuid;
        }
        $typeIdArr = array_values(array_filter($typeIdArr));
        if (!$typeIdArr) {
            return [];
        }
        $es = new EsBuildQuery();
        $result = $es->from(0)
            ->size(count($data))
            ->newsUuid($typeIdArr)
            ->query();
        if ($result && isset($result['newsList']) && $result['newsList']) {
            return array_column($result['newsList'], NULL, 'news_uuid');
        } else {
            return [];
        }
    }

    /**
     * 收藏记录列表
     * @param array $where
     * @param int $page
     * @param int $perpage
     * @return array
     */
    public function listCollectionRecord($where, $page, $perpage) {
        $offset = ($page - 1) * $perpage;
        $fields = 'type, type_id, id, extra';
        $data = UserCollection::find()
            ->where($where)
            ->orderBy('id DESC')
            ->offset($offset)
            ->limit($perpage)
            ->select($fields)
            ->asArray()
            ->all();
        //返回的数据列表
        $list = array();
        if ($data && is_array($data)) {
            $newsList = $this->convertNewsInfo($data);
            foreach ($data as $value) {
                //额外信息
                $extra = $value['extra']?json_decode($value['extra'], true):[];
                $info = [
                    'title'       => '',
                    'summary'     => '',
                    'publishTime' => '',
                    'source'      => '',
                    'sourceType'  => ''
                ];
                switch ($value['type']) {
                    case 1:
                        $idInfo = SchemeService()->enDecorateNewsUuid($value['type_id']);
                        $newsUuid = $idInfo->uuid;
                        if($extra) {
                            $info = [
                                'title'       => $extra['title'],
                                'summary'     => $extra['summary'],
                                'sourceType'  => $extra['source_type'],
                                'publishTime' => $extra['publish_time'],
                                'source'      => $extra['source'],
                                'isEffective' => isset($newsList[$newsUuid])?1:2
                            ];
                        } else {
                            if (isset($newsList[$newsUuid])) {
                                if (mb_strlen($newsList[$newsUuid]['media_name'], 'utf-8')) {
                                    $source = $newsList[$newsUuid]['media_name'];
                                } else {
                                    $source = $newsList[$newsUuid]['platform_name'];
                                }
                                $info = [
                                    'title'       => $newsList[$newsUuid]['news_title'],
                                    'summary'     => $newsList[$newsUuid]['news_digest'],
                                    'sourceType'  => $newsList[$newsUuid]['platform'],
                                    'publishTime' => $newsList[$newsUuid]['news_posttime'],
                                    'source'      => $source,
                                    'isEffective' => 1
                                ];
                            }
                        }
                        break;
                }
                $list[] = array_merge([
                    'id'     => $value['id'],
                    'type'   => $value['type'],
                    'typeId' => $value['type_id']
                ], $info);
            }
            unset($value);
        }
        //总数
        $total = UserCollection::find()
            ->where($where)
            ->count();
        return array(
            'list'  => $list,
            'total' => intval($total),
            'limit' => $perpage
        );
    }

    /**
     * 收藏/取消收藏
     * @param int $userId
     * @param int $type
     * @param string $typeId
     * @param int $status
     * @return bool
     */
    public function doCollect($userId, $type, $typeId, $status) {
        $userCollect = UserCollection::findOne([
            'status'  => [self::STATUS_COLLECT, self::STATUS_CANCEL_COLLECT],
            'user_id' => $userId,
            'type'    => $type,
            'type_id' => $typeId
        ]);
        if ($userCollect) {
            if ($userCollect->status != $status) {
                //有收藏记录，更新收藏状态
                $userCollect->status = $status;
            }
            if(!$userCollect -> extra) {
                $userCollect -> extra = json_encode([]);
            }
            $userCollect->update_time = time();
            return $userCollect->save() ? true : false;
        } else {
            $userCollect = new UserCollection();
            $data = [
                'user_id'     => $userId,
                'type'        => $type,
                'type_id'     => $typeId,
                'update_time' => time(),
                'create_time' => time(),
                'status'      => $status
            ];
            $extra = [];
            switch ($type) {
                case 1:
                    $newsList = $this -> convertNewsInfo([[
                        'type_id' => $typeId
                    ]]);
                    $idInfo = SchemeService() -> enDecorateNewsUuid($typeId);
                    $newsUuid = $idInfo -> uuid;
                    if (isset($newsList[$newsUuid])) {
                        if (mb_strlen($newsList[$newsUuid]['media_name'], 'utf-8')) {
                            $source = $newsList[$newsUuid]['media_name'];
                        } else {
                            $source = $newsList[$newsUuid]['platform_name'];
                        }
                        $extra = [
                            'title' => $newsList[$newsUuid]['news_title'],
                            'summary' => $newsList[$newsUuid]['news_digest'],
                            'source_type' => $newsList[$newsUuid]['platform'],
                            'publish_time' => $newsList[$newsUuid]['news_posttime'],
                            'source' => $source,
                        ];
                    }
                    break;
            }
            $data['extra'] = json_encode($extra, JSON_UNESCAPED_UNICODE);
            $userCollect->setAttributes($data);
            return $userCollect->save() ? true : false;
        }
        return true;
    }

    /**
     * 获取指定uuid里面的收藏状态
     * @param $user_id
     * @param array $newsID
     * @return array
     */
    public function getCollectByNewsId($user_id, $newsID) {
        $return = UserCollection::find()
            ->where(['status' => self::STATUS_COLLECT, "user_id" => $user_id, "type" => 1, "type_id" => $newsID])
            ->asArray()
            ->all();
        $list =  ArrayHelper::getColumn($return, 'type_id');

        return $list;
    }

    /**
     * 下载记录列表
     * @param array $where
     * @param int $page
     * @param int $perpage
     * @return array
     */
    public function listDownloadRecords($where, $page, $perpage) {
        $offset = ($page - 1) * $perpage;
        $fields = '`name`, `count`, `create_time`, `id`, `url`, `status`';
        $model = DownloadLog::find();
        $data = $model
            ->where($where)
            ->andWhere(['<>', 'status', DownloadLog::STATUS_DEL])
            ->andWhere(['<>', 'status', DownloadLog::STATUS_CANCEL])
            ->orderBy('id DESC')
            ->offset($offset)
            ->limit($perpage)
            ->select($fields)
            ->asArray()
            ->all();
        //返回的数据列表
        $list = array();
        if ($data && is_array($data)) {
            foreach ($data as $value) {
                //对http输出替换成https的
                $url = $value['url'];
                if ($value['url'] && strpos($value['url'],"http://") === 0 ){
                    $url  = str_replace("http://","https://",$value['url']);
                }

                $list[] = array(
                    'title'      => $value['name'],//标题
                    'dataNum'    => $value['count'],//数据量
                    'createTime' => $value['create_time'],//生成时间
                    'recordId'   => $value['id'] * 1,//下载记录ID
                    'url'        => $url ? $url : '',//下载地址
                    'process'    => Yii::$app->service->CacheService->getBriefdocProgress($value['id'],$value['status'], 'download'),
                    'status'     => intval($value['status'])
                );
            }
            unset($value);
        }
        //总数
        $total = DownloadLog::find()
            ->where($where)
            ->andWhere(['<>', 'status', DownloadLog::STATUS_DEL])
            ->andWhere(['<>', 'status', DownloadLog::STATUS_CANCEL])
            ->count();
        return array(
            'list'  => $list,
            'total' => intval($total),
            'limit' => $perpage
        );
    }

    /**
     * 删除下载记录
     * @param int $recordId
     * @param int $userId
     * @return bool
     * @throws \OSS\Core\OssException
     */
    public function deleteDownLog($recordId, $userId) {
        $downloadLog = DownloadLog::findOne(['id' => $recordId, 'user_id' => $userId]);
        if ($downloadLog) {
            $oss = new Oss();
            if (!empty($downloadLog->oss_bucket) && !empty($downloadLog->oss_object)) { //只删除新系统写入的oss文件
                $oss->deleteObject($downloadLog->oss_bucket, $downloadLog->oss_object);
            }
            $downloadLog->status = DownloadLog::STATUS_DEL;
            if (!$downloadLog->save(false)) {
                return false;
            }
        }
        return true;
    }

	  /**
     * 重新下载
     * @param int $recordId
     * @param int $userId
     * @return bool
     * @throws \OSS\Core\OssException
     */
    public function reDownLog($recordId, $userId) {
        $downloadLog = DownloadLog::findOne(['id' => $recordId, 'user_id' => $userId]);
        if ($downloadLog) {
            $oss = new Oss();
            if (!empty($downloadLog->oss_bucket) && !empty($downloadLog->oss_object)) { //只删除新系统写入的oss文件
                $oss->deleteObject($downloadLog->oss_bucket, $downloadLog->oss_object);
            }
            $downloadLog->status = DownloadLog::STATUS_WAIT_START;
            if (!$downloadLog->save(false)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 取消下载
     * @param int $recordId
     * @param int $userId
     * @return bool
     * @throws \OSS\Core\OssException
     */
    public function cancelDownLog($recordId,$userId) {
        $rows = DownloadLog::updateAll([
            'status' => DownloadLog::STATUS_CANCEL
        ],[
            'and',
            ['id' => $recordId],
            ['user_id' => $userId],
            ['in','status',[DownloadLog::STATUS_WAIT_START,DownloadLog::STATUS_EXPORTING]]
        ]);
        if($rows){
            Yii::$app->globalService->DownloadService->setDownloadCancelCache(self::DOWNLOAD_CANCELING,$recordId);
            return true;
        }
        return false;
    }
	
    /**
     * 信息下载记录表
     * @param $uid
     * @param $sid
     * @param $data
     * @return bool
     */
    public function createDownLog($uid, $sid, $data) {

        $downloadLog = new DownloadLog();
        $data = [
            'user_id'     => $uid,
            'name'        => $data['name'],//名称
            'request'     => $data['request'],//导出条件
            'sim_request' => $data['sim_request'],//导出条件
            'keyname'     => $data['keyname'],//选择的字段
            'create_time' => date("Y-m-d H:i:s", time()),
            'count'       => $data['count'],//文章数量
            'status'      => 0,
            'type'        => 1,
            'sort'        => 1,
            'module'      => 1,
            'scheme_id'   => $sid
        ];
        $downloadLog->setAttributes($data);
        return $downloadLog->save(false) ? true : false;
    }

    /**
     * 读取用户正在导出和未导出的数目
     * @param $uid
     * @return mixed
     */
    public function getDownloadIngNum($uid) {
        return DownloadLog::find()->where([
            'user_id' => $uid,
            "status"  => [DownloadLog::STATUS_WAIT_START, DownloadLog::STATUS_EXPORTING]
        ])->sum("count");
    }

    /**
     *  减去用户的短信数,注意赠送无限短信时，消费短信后无需调用此方法
     * @param $uid int 扣除用户
     * @param $num int 扣除短信数
     * @return array
     */
    public function subtractionUserSms($uid, $num) {

        $transaction = Yii::$app->db->beginTransaction();
        if ($num > 0) {
            //直接减去所属用户的短信
            $where_ = ' where  user_id = :user_id';
            $sql = "select free_sms_num,user_id from `user_authority`  {$where_} for UPDATE ";
            $buy = Yii::$app->db->createCommand($sql, [
                    ':user_id' => $uid]
            )->queryOne();
            if ($buy) {
                $where_ = ' where user_id = :user_id and free_sms_num >= :free_sms_num';
                $set = ' free_sms_num = free_sms_num - :free_sms_num ';
                $sql = 'update `user_authority` set ' . $set . $where_;
                $res = Yii::$app->db->createCommand($sql, [
                    ':user_id' => $uid,
                    ':free_sms_num' => $num
                ])->execute();
                if ($res === false) {
                    $transaction->rollBack();
                    return ['success' => false, "msg" => "扣除短信失败"];
                }
            } else {
                $transaction->rollBack();
                return ['success' => false, "msg" => "短信余额不足！"];
            }
        }
        $transaction->commit();
        return ['success' => true, "msg" => "扣除成功！"];
    }

    /**
     * 获取下载进度
     * @param array $ids
     * @return array
     */
    public function getDownloadProgress($ids) {
        $downloadLog = DownloadLog::findAll(['id' => $ids]);
        $list = [];
        if ($downloadLog) {
            foreach ($downloadLog as $download) {
                $list[] = [
                    'recordId' => $download->id,
                    'url'      => $download->url,
                    'process'  => Yii::$app->service->CacheService->getBriefdocProgress($download->id,$download->status, 'download'),
                    'status'   => $download->status
                ];
            }
            unset($download);
        }
        return $list;
    }

    /**
     * 是否可以删除下载记录
     * @param int $downloadLogId
     * @param int $userId
     * @return bool
     */
    public function isCanDelDownloadLog($downloadLogId, $userId = 0) {
        if(!$userId) {
            $userId = Yii::$app -> service -> UserService -> getUid();
        }
        $downloadLog = DownloadLog::findOne([
            'id' => $downloadLogId,
            'user_id' => $userId
        ]);
        if(!$downloadLog || !$downloadLog -> url) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 是否可以重新下载
     * @param int $downloadLogId
     * @param int $userId
     * @return bool
     */
    public function isCanReDownloadLog($downloadLogId, $userId = 0) {
        if(!$userId) {
            $userId = Yii::$app -> service -> UserService -> getUid();
        }
        $downloadLog = DownloadLog::findOne([
            'id' => $downloadLogId,
            'user_id' => $userId
        ]);
        if(!$downloadLog || $downloadLog -> status != DownloadLog::STATUS_FAIL) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 是否可以取消下载
     * @param int $downloadLogId
     * @param int $userId
     * @return bool
     */
    public function isCanCancelDownloadLog($downloadLogId, $userId = 0) {
        if(!$userId) {
            $userId = Yii::$app -> service -> UserService -> getUid();
        }
        $downloadLog = DownloadLog::findOne([
            'id' => $downloadLogId,
            'user_id' => $userId
        ]);
        if(!$downloadLog || ($downloadLog -> status != DownloadLog::STATUS_WAIT_START && $downloadLog -> status != DownloadLog::STATUS_EXPORTING)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * es查询错误记录
     * @param string/array $content
     * @param string/array $result
     */
    public function createEsQueryErrorLog($content, $result) {
        $esQueryErrorLog = new EsQueryErrorLog();
        $esQueryErrorLog -> content = is_array($content)?json_encode($content):$content;
        $esQueryErrorLog -> result = is_array($result)?json_encode($result):$result;
        $esQueryErrorLog -> create_time = date('Y-m-d H:i:s');
        $esQueryErrorLog -> save();
    }
}
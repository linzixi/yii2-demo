<?php


namespace webapi\services;

use Kafka\Exception;
use webapi\models\AccountDouyinAll;
use webapi\models\AccountKuaishouAll;
use webapi\models\AccountToutiaoAll;
use webapi\models\AccountWebAll;
use webapi\models\AccountWeiboAll;
use webapi\models\AccountWxAll;
use webapi\models\Company;
use webapi\models\CompanyDouyin;
use webapi\models\CompanyKuaishou;
use webapi\models\CompanyToutiao;
use webapi\models\CompanyWeb;
use webapi\models\CompanyWeibo;
use webapi\models\CompanyWx;
use webapi\models\TagRegion;
use webapi\models\SysPageAuth;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * 地区管理业务逻辑
 * Class RegionService
 * @package webapi\services
 */
class RegionService
{
    // 获取平台对应的表名
    public function getClass($type)
    {
        $table_arr = [
            'wx' => "\webapi\models\CompanyWx",
            'web' => "\webapi\models\CompanyWeb",
            'weibo' => "\webapi\models\CompanyWeibo",
            'douyin' => "\webapi\models\CompanyDouyin",
            'kuaishou' => "\webapi\models\CompanyKuaishou",
            'media_toutiao' => "\webapi\models\CompanyToutiao",
        ];

        return $table_arr[$type];
    }

    /**
     * 右侧账号列表
     * @param $uid
     * @param $company_id
     * @param $id
     * @param $type
     * @param $page
     * @param $perpage
     * @param $search
     * @return array|void
     */
    public function getAccList($uid,$company_id,$id,$type,$page,$perpage,$search)
    {
        $services = new PageAuthService();
        $tag = $services->getTagList();
        //+ 判断该用户是否有权限
        $is_auth = 0;
        if(!empty($tag['media_tag'])){
            foreach ($tag['media_tag'] as $m) {
                if($m['e_name'] == $type){
                    $is_auth = 1;
                }
            }
        }
        if($is_auth == 0){
            return jsonErrorReturn('authorityError');
        }

        switch ($type) {
            case 'wx':
                $query = CompanyWx::find()->alias('c')
                    ->select('c.id,a.wx_name account_name,a.wx_nickname nickname,c.region_ids')
                    ->leftJoin(AccountWxAll::tableName().' a','a.nickname_id=c.nickname_id')
                    ->where(['c.company_id'=>$company_id]);
                if($search){
                    $query->andWhere(['like','a.wx_nickname',$search]);
                }
                break;
            case 'weibo':
                $query = CompanyWeibo::find()->alias('c')
                    ->select('c.id,a.weibo_uid account_name,a.nickname,c.region_ids')
                    ->leftJoin(AccountWeiboAll::tableName().' a','a.weibo_uid=c.weibo_uid')
                    ->where(['c.company_id'=>$company_id]);
                if($search){
                    $query->andWhere(['like','a.nickname',$search]);
                }
                break;
            case 'media_toutiao':
                $query = CompanyToutiao::find()->alias('c')
                    ->select('c.id,a.toutiao_user_id account_name,a.nickname,c.region_ids')
                    ->leftJoin(AccountToutiaoAll::tableName().' a','a.toutiao_user_id=c.toutiao_user_id')
                    ->where(['c.company_id'=>$company_id]);
                if($search){
                    $query->andWhere(['like','a.nickname',$search]);
                }
                break;
            case 'douyin':
                $query = CompanyDouyin::find()->alias('c')
                    ->select('c.id,a.douyin_code account_name,a.douyin_name nickname,c.region_ids')
                    ->leftJoin(AccountDouyinAll::tableName().' a','a.douyin_id=c.douyin_id')
                    ->where(['c.company_id'=>$company_id]);
                if($search){
                    $query->andWhere(['like','a.douyin_name',$search]);
                }
                break;
            case 'kuaishou':
                $query = CompanyKuaishou::find()->alias('c')
                    ->select('c.id,a.kuaishou_code account_name,a.kuaishou_name nickname,c.region_ids')
                    ->leftJoin(AccountKuaishouAll::tableName().' a','a.kuaishou_id=c.kuaishou_id')
                    ->where(['c.company_id'=>$company_id]);
                if($search){
                    $query->andWhere(['like','a.kuaishou_name',$search]);
                }
                break;
            case 'web':
                $query = CompanyWeb::find()->alias('c')
                    ->select('c.id,a.domain_sec account_name,a.web_name nickname,c.region_ids')
                    ->leftJoin(AccountWebAll::tableName().' a','a.domain_sec=c.domain')
                    ->where(['c.company_id'=>$company_id]);
                if($search){
                    $query->andWhere(['like','a.web_name',$search]);
                }
                break;
            default:
                $query = null;
                break;
        }

        if($id == '-1'){
            $query->andWhere(['is','c.region_ids',null]);
        }

        if($id>0){
            $query->andWhere("find_in_set($id,c.region_ids)");
        }

        $total = $query->count();
        $offset = ($page-1)*$perpage;
        $list = $query->offset($offset)->limit($perpage)->asArray()->all();
        $newList = [];
        if($total>0){
            foreach ($list as $k => $v) {
                $tags = [];
                if($v['region_ids']){
                    $region_ids = explode(',',$v['region_ids']);
                    $tags = TagRegion::find()->select('tag_name')
                        ->where(['in', 'id', $region_ids])->andWhere(['status' => 1])->asArray()->all();
                    $tags = array_column($tags,'tag_name');
                }
                $newList[] = [
                    'id'=> $v['id'],
                    'nickname'=> $v['nickname'],
                    'account_name'=> $v['account_name'],
                    'tags'=> $tags
                ];
            }
        }

        return [
            'total'=> $total,
            'list'=> $newList
        ];
    }

    /**
     * 新建/修改地区标签
     * @param $uid
     * @param $company_id
     * @param $id
     * @param $pid
     * @param $name
     * @return bool|void
     */
    public function getAddRegion($uid,$company_id,$id,$pid,$name)
    {
        //判断地区数量是否超出限制
        $num = TagRegion::find()->where(['company_id' => $company_id, 'status' => 1])->count();
        if (CompanyService::$company_info['region_create_num'] && $num >= CompanyService::$company_info['region_create_num']) {
            return jsonErrorReturn('fail', '地区可添加数量已达限制');
        }

        //+ 公司信息
        $info = Company::find()->where(['id' => $company_id])->one();
        if(empty($info)){
            return jsonErrorReturn('companyError');
        }

        //+ 如果有父级id，查看父级是否存在
        if($pid > 0){
            $isHas = TagRegion::findOne($pid);
            if(empty($isHas)){
                return jsonErrorReturn('paramsError','该父级地区不存在');
            }
        }

        //+ id>0是修改，否则为新增
        if($id > 0){
            $model = TagRegion::findOne($id);
            //+ 判断该地区标签是否存在
            if(empty($model)){
                return jsonErrorReturn('paramsError','该地区标签不存在');
            }
        }else{
            $model = new TagRegion();
            $model->company_id = $company_id;
            $model->company_index = $info->company_index;
            $model->uid = $uid;
            $model->pid = $pid;
            $model->tag_remark = $name;
        }
        $model->tag_name = $name;
        $model->updated_time = date('Y-m-d H:i:s');

        $res = $model->save();
        if($res !== false){
            //+ 系统日志
            $edit = $id == 0 ? '新增' : '修改';
            $content = '地区管理:'.$edit.'地区标签:'.$name;
            \Yii::$app->service->LogService->saveSysLog($content);
        }
        return $res;
    }

    /**
     * 删除地区标签
     * @param $id
     */
    public function delTags($id)
    {
        $model = TagRegion::findOne($id);
        if(empty($model)){
            return jsonErrorReturn('paramsError','该地区标签不存在');
        }

        $ids_all = [$id];
        //查询子级
        $list = TagRegion::find()->select('id,pid')->where(['company_id' => CompanyService::$company_id, 'pid' => $id])->asArray()->all();
        if ($list) {
            $ids = array_column($list, 'id');
            $ids_all = array_merge($ids_all, $ids);
            $list_two = TagRegion::find()->select('id,pid')->where(['company_id' => CompanyService::$company_id, 'pid' => $ids])->asArray()->all();
            if ($list_two) {
                $ids = array_column($list_two, 'id');
                $ids_all = array_merge($ids_all, $ids);
            }
        }
        $transaction = Yii::$app->db->beginTransaction();
        try{
            //删除
            TagRegion::updateAll(['status' => 0, 'updated_time' => date('Y-m-d H:i:s')], ['id' => $ids_all]);
            //删除角色地区权限
            SysPageAuth::deleteAll(['company_id' => CompanyService::$company_id, 'type' => SysPageAuth::TYPE_REGION, 'related_id' => $ids_all]);

            //系统日志
            $content = '地区管理:删除地区【' . $model['tag_name'].'】' . (count($ids_all)>1?'及其下属地区':'');
            Yii::$app->service->LogService->saveSysLog($content);

            $transaction->commit();
            return true;
        }catch (Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    /**
     * 媒体账号的标签详情
     * @param $uid
     * @param $company_id
     * @param $type
     * @param $id
     */
    public function getAccInfo($uid,$company_id,$type,$id)
    {
        $services = new PageAuthService();
        $tag = $services->getTagList('tree');
        $regionArr = $tag['region_tag'];

        if(empty($regionArr)){
            return jsonErrorReturn('paramsError', '暂无地区标签可操作');
        }

        if($id == 0){
            $regionArr = $this->tagsInit($regionArr,[]);
            return $regionArr;
        }

        switch ($type) {
            case 'wx':
                $accInfo = CompanyWx::find()->where(['company_id'=>$company_id])
                    ->andWhere(['id'=> $id])->asArray()->one();
                break;
            case 'weibo':
                $accInfo = CompanyWeibo::find()->where(['company_id'=>$company_id])
                    ->andWhere(['id'=> $id])->asArray()->one();
                break;
            case 'media_toutiao':
                $accInfo = CompanyToutiao::find()->where(['company_id'=>$company_id])
                    ->andWhere(['id'=> $id])->asArray()->one();
                break;
            case 'douyin':
                $accInfo = CompanyDouyin::find()->where(['company_id'=>$company_id])
                    ->andWhere(['id'=> $id])->asArray()->one();
                break;
            case 'kuaishou':
                $accInfo = CompanyKuaishou::find()->where(['company_id'=>$company_id])
                    ->andWhere(['id'=> $id])->asArray()->one();
                break;
            case 'web':
                $accInfo = CompanyWeb::find()->where(['company_id'=>$company_id])
                    ->andWhere(['id'=> $id])->asArray()->one();
                break;
            default:
                $accInfo = [];
                break;
        }
        $neddArr=[];
        if(!empty($accInfo)) {
            $neddArr = isset($accInfo['region_ids'])?explode(',',$accInfo['region_ids']):[];
        }
        $regionArr = $this->tagsInit($regionArr,$neddArr);
        return $regionArr;
    }

    protected function tagsInit(&$regionArr,$needArr=[]){
        foreach ($regionArr as $k => $v) {
            $regionArr[$k]['checked'] = false;
            $regionArr[$k]['title'] = $regionArr[$k]['tag_name'];
            unset($regionArr[$k]['tag_name']);
            if(in_array($v['id'],$needArr)){
                $regionArr[$k]['checked'] = true;
            }
            if(isset($regionArr[$k]['child'])){
                $regionArr[$k]['children'] = $regionArr[$k]['child'];
                unset($regionArr[$k]['child']);
                $regionArr[$k]['children'] = $this->tagsInit($regionArr[$k]['children'],$needArr);
            }
        }
        return $regionArr;
    }

    /**
     * 打标签操作
     * @param $uid
     * @param $company_id
     * @param $type
     * @param $tag_ids
     * @param $account_ids
     * @return bool|int|void
     */
    public function actionAddTags($uid,$company_id,$type,$tag_ids,$account_ids)
    {
        //+ 判断标签是否正确
        $tagArr = explode(',', $tag_ids);
        $tagCount = TagRegion::find()->where(['company_id'=>$company_id])
            ->andWhere(['in', 'id', $tagArr])->count();
        if(!empty($tag_ids) && $tagCount < count($tagArr)){
            return jsonErrorReturn('paramsError', '存在错误标签，请重新操作');
        }

        //+ 判断需要打标签的账号该用户是否拥有操作权限
        $accArr = explode(',', $account_ids);
        $class = $this->getClass($type);
        $obj = Yii::createObject($class);
        $accCount = $obj::find()->where(['company_id'=>$company_id])
            ->andWhere(['in', 'id', $accArr])->count();
        /*switch ($type) {
            case 'wx':
                $accCount = CompanyWx::find()->where(['company_id'=>$company_id])
                    ->andWhere(['in', 'id', $accArr])->count();
                break;
            case 'weibo':
                $accCount = CompanyWeibo::find()->where(['company_id'=>$company_id])
                    ->andWhere(['in', 'id', $accArr])->count();
                break;
            case 'media_toutiao':
                $accCount = CompanyToutiao::find()->where(['company_id'=>$company_id])
                    ->andWhere(['in', 'id', $accArr])->count();
                break;
            case 'douyin':
                $accCount = CompanyDouyin::find()->where(['company_id'=>$company_id])
                    ->andWhere(['in', 'id', $accArr])->count();
                break;
            case 'kuaishou':
                $accCount = CompanyKuaishou::find()->where(['company_id'=>$company_id])
                    ->andWhere(['in', 'id', $accArr])->count();
                break;
            case 'web':
                $accCount = CompanyWeb::find()->where(['company_id'=>$company_id])
                    ->andWhere(['in', 'id', $accArr])->count();
                break;
           default:
               $accCount = 0;
                break;
        }*/
        if($accCount < count($accArr)){
            return jsonErrorReturn('paramsError', '存在不属于该公司账号');
        }

        //+ 打标签
        //| 将新标签覆盖之前的标签 单个账号覆盖 多个账号追加
        if (count($accArr) == 1) {
            $res = $obj::updateAll(['region_ids'=> $tag_ids], ['id' => $account_ids]);
        } else {
            $region = $obj::find()->select('id,region_ids')->where(['id' => $accArr])->asArray()->all();
            $region = ArrayHelper::index($region, 'id');
            foreach ($region as &$item) {
                $region_arr = array_unique(array_merge(explode(',', $tag_ids), explode(',', $item['region_ids'])));
                $item['region_ids'] = implode(',', $region_arr);
                $res = $obj::updateAll(['region_ids'=> $item['region_ids']], ['id' => $item['id']]);
            }
        }
        /*switch ($type) {
            case 'wx':
                $res = CompanyWx::updateAll(['region_ids'=> $tag_ids], ['in', 'id', $accArr]);
                break;
            case 'weibo':
                $res = CompanyWeibo::updateAll(['region_ids'=> $tag_ids], ['in', 'id', $accArr]);
                break;
            case 'media_toutiao':
                $res = CompanyToutiao::updateAll(['region_ids'=> $tag_ids], ['in', 'id', $accArr]);
                break;
            case 'douyin':
                $res = CompanyDouyin::updateAll(['region_ids'=> $tag_ids], ['in', 'id', $accArr]);
                break;
            case 'kuaishou':
                $res = CompanyKuaishou::updateAll(['region_ids'=> $tag_ids], ['in', 'id', $accArr]);
                break;
            case 'web':
                $res = CompanyWeb::updateAll(['region_ids'=> $tag_ids], ['in', 'id', $accArr]);
                break;
            default:
                $res = false;
                break;
        }*/

        switch ($type) {
            case 'wx':
                $query = CompanyWx::find()->alias('c')
                    ->select('a.wx_nickname nickname')
                    ->leftJoin(AccountWxAll::tableName().' a','a.nickname_id=c.nickname_id')
                    ->where(['c.company_id'=>$company_id])
                    ->andWhere(['in', 'c.id', $accArr]);
                break;
            case 'weibo':
                $query = CompanyWeibo::find()->alias('c')
                    ->select('a.nickname')
                    ->leftJoin(AccountWeiboAll::tableName().' a','a.weibo_uid=c.weibo_uid')
                    ->where(['c.company_id'=>$company_id])
                    ->andWhere(['in', 'c.id', $accArr]);
                break;
            case 'media_toutiao':
                $query = CompanyToutiao::find()->alias('c')
                    ->select('a.nickname')
                    ->leftJoin(AccountToutiaoAll::tableName().' a','a.toutiao_user_id=c.toutiao_user_id')
                    ->where(['c.company_id'=>$company_id])
                    ->andWhere(['in', 'c.id', $accArr]);
                break;
            case 'douyin':
                $query = CompanyDouyin::find()->alias('c')
                    ->select('a.douyin_name nickname')
                    ->leftJoin(AccountDouyinAll::tableName().' a','a.douyin_id=c.douyin_id')
                    ->where(['c.company_id'=>$company_id])
                    ->andWhere(['in', 'c.id', $accArr]);
                break;
            case 'kuaishou':
                $query = CompanyKuaishou::find()->alias('c')
                    ->select('a.kuaishou_name nickname')
                    ->leftJoin(AccountKuaishouAll::tableName().' a','a.kuaishou_id=c.kuaishou_id')
                    ->where(['c.company_id'=>$company_id])
                    ->andWhere(['in', 'c.id', $accArr]);
                break;
            case 'web':
                $query = CompanyWeb::find()->alias('c')
                    ->select('a.web_name nickname')
                    ->leftJoin(AccountWebAll::tableName().' a','a.domain_sec=c.domain')
                    ->where(['c.company_id'=>$company_id])
                    ->andWhere(['in', 'c.id', $accArr]);
                break;
            default:
                $query = null;
                break;
        }

        if($res !== false){
            //+ 系统日志
            $accAll = $query->asArray()->all();
            $accAll = array_column($accAll, 'nickname');
            $acc_names = implode(',',$accAll);
            $content = '地区管理:用户对媒体账号:'.$acc_names.' 进行打标签操作';
            \Yii::$app->service->LogService->saveSysLog($content);
        }
        return $res;
    }
}
<?php


namespace webapi\services;


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
use webapi\models\MailList;
use webapi\models\MailListAccount;
use Yii;

/**
 * 媒体通讯录业务逻辑
 * Class ContactsService
 * @package webapi\services
 */
class ContactsService
{
    /**
     * 通讯录列表
     * @param $uid
     * @param $company_id
     * @param $search
     * @return array|void
     */
    public function getIndex($uid,$company_id,$search)
    {
        //+ 公司信息
        $info = Company::find()->where(['id' => $company_id])->one();
        if(empty($info)){
            return jsonErrorReturn('companyError');
        }

        $query = MailList::find()->select('id,user_name,first_word')
            ->where(['company_id'=>$company_id]);

        if($search){
            $query->andWhere(['like','user_name',$search]);
        }

        $list = $query->asArray()->all();

        $newList = [];
        if($list){
            $firstArr = array_column($list,'first_word');
            $firstArr = array_unique($firstArr);
            sort($firstArr);
            foreach ($firstArr as $first) {
                $child = ['first'=> $first, 'list'=> []];
                foreach ($list as $k => $v) {
                    if($v['first_word'] == $first){
                        $child['list'][] = [
                            'id'=> $v['id'],
                            'user_name'=> $v['user_name']
                        ];
                    }
                }
                $newList[] = $child;
            }
        }

        return $newList;
    }

    /**
     * 联系人详细信息
     * @param $uid
     * @param $company_id
     * @param $id
     * @return array|void
     */
    public function getInfo($uid,$company_id,$id)
    {
        $info = MailList::findOne($id);

        if(empty($info) || $info->company_id != $company_id){
            return jsonErrorReturn('paramsError', '联系人不存在或无此权限');
        }

        $user = $info;
        unset($user['company_id'],$user['company_index'],$user['first_word'],$user['status'],$user['created_time'],$user['updated_time']);

        //+ 关联账号
        $return = $this->getMediaList($uid,$company_id,'all',$id,'');
        $list = $return['check_list'];

        return [
            'user'=> $user,
            'list'=> $list
        ];
    }

    /**
     * 用户关联账号集合
     * @param $uid
     * @param $company_id
     * @param $type
     * @param $id
     * @param $search
     * @return array|void
     */
    public function getMediaList($uid,$company_id,$type,$id,$search)
    {
        $services = new PageAuthService();
        $tag = $services->getTagList();
        if($type != 'all') {
            //+ 判断该用户是否有权限
            $is_auth = 0;
            if (!empty($tag['media_tag'])) {
                foreach ($tag['media_tag'] as $m) {
                    if ($m['e_name'] == $type) {
                        $is_auth = 1;
                    }
                }
            }
            if ($is_auth == 0) {
                return jsonErrorReturn('authorityError');
            }
        }

        //+ 如果是全部，则查看该用户所拥有权限
        if($type == 'all'){
            $typeArr = !empty($tag['media_tag']) ? array_column($tag['media_tag'], 'e_name') : [];
            $list = [];
            if($typeArr) {
                foreach ($typeArr as $t) {
                    $ll = $this->getTypeAccList($t,$company_id,'');
                    $list = array_merge($list, $ll);
                }
            }
        }else{
            $list = $this->getTypeAccList($type,$company_id,'');
        }

        $check_list = [];
        if($id > 0){
            $mailMedia = MailListAccount::find()->where(['mail_id'=>$id])->asArray()->all();
            if($mailMedia && $list){
                foreach ($mailMedia as $media) {
                    foreach ($list as $k => $v) {
                        if($media['type'] == $v['type'] && $media['account_ids']){
                            if(in_array($v['account_id'], explode(',', $media['account_ids']))){
                                $check_list[] = $v;
                                unset($list[$k]);
                            }
                        }
                    }
                }
            }
        }

        //+ 搜索（只搜索为关联的账号）
        if($search && $list){
            foreach ($list as $k => $v) {
                if(strstr($v['nickname'], $search) === false){
                    unset($list[$k]);
                }
            }
        }

        $return = [
            'no_list'=> array_values($list),
            'check_list'=> $check_list
        ];

        return $return;
    }

    public function getTypeAccList($type,$company_id,$search)
    {
        switch ($type) {
            case 'wx':
                $query = CompanyWx::find()->alias('c')
                    ->select('c.id,a.nickname_id account_id,a.wx_nickname nickname')
                    ->leftJoin(AccountWxAll::tableName().' a','a.nickname_id=c.nickname_id')
                    ->where(['c.company_id'=>$company_id]);
                if($search){
                    $query->andWhere(['like','a.wx_nickname',$search]);
                }
                break;
            case 'weibo':
                $query = CompanyWeibo::find()->alias('c')
                    ->select('c.id,a.weibo_uid account_id,a.nickname')
                    ->leftJoin(AccountWeiboAll::tableName().' a','a.weibo_uid=c.weibo_uid')
                    ->where(['c.company_id'=>$company_id]);
                if($search){
                    $query->andWhere(['like','a.nickname',$search]);
                }
                break;
            case 'media_toutiao':
                $query = CompanyToutiao::find()->alias('c')
                    ->select('c.id,a.toutiao_user_id account_id,a.nickname')
                    ->leftJoin(AccountToutiaoAll::tableName().' a','a.toutiao_user_id=c.toutiao_user_id')
                    ->where(['c.company_id'=>$company_id]);
                if($search){
                    $query->andWhere(['like','a.nickname',$search]);
                }
                break;
            case 'douyin':
                $query = CompanyDouyin::find()->alias('c')
                    ->select('c.id,a.douyin_code account_id,a.douyin_name nickname')
                    ->leftJoin(AccountDouyinAll::tableName().' a','a.douyin_id=c.douyin_id')
                    ->where(['c.company_id'=>$company_id]);
                if($search){
                    $query->andWhere(['like','a.douyin_name',$search]);
                }
                break;
            case 'kuaishou':
                $query = CompanyKuaishou::find()->alias('c')
                    ->select('c.id,a.kuaishou_code account_id,a.kuaishou_name nickname')
                    ->leftJoin(AccountKuaishouAll::tableName().' a','a.kuaishou_id=c.kuaishou_id')
                    ->where(['c.company_id'=>$company_id]);
                if($search){
                    $query->andWhere(['like','a.kuaishou_name',$search]);
                }
                break;
            case 'web':
                $query = CompanyWeb::find()->alias('c')
                    ->select('c.id,a.domain_sec account_id,a.web_name nickname')
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

        $list = $query->asArray()->all();
        if($list){
            foreach ($list as $k => $v) {
                unset($list[$k]['id']);
                $list[$k]['type'] = $type;
                $list[$k]['icon'] = $type.'.icon';
            }
        }

        return $list;
    }

    /**
 * 新增/修改联系人
 * @param $uid
 * @param $company_id
 * @param $params
 * @return bool|void
 */
    public function getSave($uid,$company_id,$params)
    {
        //+ 公司信息
        $info = Company::find()->where(['id' => $company_id])->one();
        if(empty($info)){
            return jsonErrorReturn('companyError');
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            //+ 新增或修改联系人基本信息
            if ($params['id'] > 0) {
                $mailModel = MailList::findOne($params['id']);
                if (empty($mailModel)) {
                    return jsonErrorReturn('paramsError', '该联系人不存在');
                }
                $mailModel->updated_time = date('Y-m-d H:i:s');
            } else {
                $mailModel = new MailList();
            }
            $mailModel->company_id = $company_id;
            $mailModel->company_index = $info->company_index;
            $mailModel->first_word = getFirstCharter($params['user_name']);
            $mailModel->user_name = $params['user_name'];
            $mailModel->company_name = isset($params['company_name']) ? $params['company_name'] : '';
            $mailModel->department = isset($params['department']) ? $params['department'] : '';
            $mailModel->position = isset($params['position']) ? $params['position'] : '';
            $mailModel->mobile = isset($params['mobile']) ? $params['mobile'] : '';
            $mailModel->mobile_spare = isset($params['mobile_spare']) ? $params['mobile_spare'] : '';
            $mailModel->email = isset($params['email']) ? $params['email'] : '';
            $mailModel->email_spare = isset($params['email_spare']) ? $params['email'] : '';
            $mailModel->wx_name = isset($params['wx_name']) ? $params['wx_name'] : '';
            $mailModel->weibo_name = isset($params['weibo_name']) ? $params['weibo_name'] : '';
            $mailModel->address = isset($params['address']) ? $params['address'] : '';
            $mailModel->remark = isset($params['remark']) ? $params['remark'] : '';
            $res = $mailModel->save();
            $id = $params['id'] > 0 ? $params['id'] : $mailModel->id;
            if($res === false){
                $transaction->rollBack();
                return false;
            }

            //+ 保存关联账号信息
            if($params['id'] > 0){
                //+ ①修改时，先将之前的关联账号删除
                $res = MailListAccount::deleteAll(['mail_id'=> $params['id']]);
                if($res === false){
                    $transaction->rollBack();
                    return false;
                }
            }

            //+ ②新增关联账号
            $accountArr = isset($params['account']) ? $params['account'] : [];
            $accArr = [];
            if($accountArr){
                foreach ($accountArr as $acc) {
                    if(!isset($accArr[$acc['type']])){
                        $accArr[$acc['type']] = [];
                    }
                    $accArr[$acc['type']][] = $acc['account_id'];
                }
            }

            if(!empty($accArr)){
                $newList = [];
                foreach ($accArr as $k => $v) {
                    $newList[] = [
                        'mail_id'=> $id,
                        'type'=> $k,
                        'account_ids'=> implode(',',$v),
                    ];
                }

                $indexArr = ['mail_id','type','account_ids'];
                $res = Yii::$app->db->createCommand()->batchInsert(MailListAccount::tableName(),$indexArr,$newList)->execute();
                if($res === false){
                    $transaction->rollBack();
                    return false;
                }
            }

            //+ 系统日志
            $edit = $params['id'] > 0 ? '修改' : '新增';
            $content = '媒体通讯录:用户'.$edit.'联系人'.$params['user_name'].'。';
            \Yii::$app->service->LogService->saveSysLog($content);

            $transaction->commit();
            return $id;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    /**
     * 删除联系人
     */
    public function del($uid,$company_id,$params)
    {
        //+ 公司信息
        $info = Company::find()->where(['id' => $company_id])->one();
        if(empty($info)){
            return jsonErrorReturn('companyError');
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {

            $mailModel = MailList::findOne($params['id']);
            if (empty($mailModel)) {
                return jsonErrorReturn('paramsError', '该联系人不存在');
            }
            $mailModel->delete();
            MailListAccount::deleteAll(['mail_id'=>$params['id']]);

            //+ 系统日志
            $content = '媒体通讯录:用户'.$uid.'删除联系人'.$params['id'].'。';
            \Yii::$app->service->LogService->saveSysLog($content);
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    /**
     * 账号关联人列表
     * @param $uid
     * @param $company_id
     * @param $type
     * @param $account_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getMailList($uid,$company_id,$type,$account_id)
    {
        $list = MailListAccount::find()->alias('a')
            ->select('m.id,m.user_name,m.company_name,m.mobile')
            ->leftJoin(MailList::tableName().' m','m.id=a.mail_id')
            ->where(['m.company_id'=>$company_id])
            ->andWhere(['a.type'=>$type])
            ->andWhere("find_in_set('{$account_id}',a.account_ids)")
            ->asArray()->all();

        return $list;
    }
}
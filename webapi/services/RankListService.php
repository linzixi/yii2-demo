<?php


namespace webapi\services;


use webapi\extensions\ShudiEsBase;
use webapi\models\AccountDouyinAll;
use webapi\models\AccountKuaishouAll;
use webapi\models\AccountToutiaoAll;
use webapi\models\AccountWeiboAll;
use webapi\models\AccountWxAll;
use webapi\models\CompanyDouyin;
use webapi\models\CompanyKuaishou;
use webapi\models\CompanyRankGroup;
use webapi\models\CompanyToutiao;
use webapi\models\CompanyWeb;
use webapi\models\CompanyWeibo;
use webapi\models\CompanyWx;
use webapi\models\RankDate;
use webapi\models\RankDouyin;
use webapi\models\RankKuaishou;
use webapi\models\RankToutiao;
use webapi\models\RankWeibo;
use webapi\models\RankWx;
use webapi\models\TagRegion;
use webapi\models\TagTags;
use Yii;

/**
 * 媒体榜单日月周榜单列表业务逻辑
 * Class RankListService
 * @package webapi\services
 */
class RankListService
{
    /**
     * 微信榜单列表
     * @param $uid
     * @param $company_id
     * @param $page
     * @param $perpage
     * @param $type
     * @param $date
     * @param $region
     * @param $tags
     * @param $search
     * @return array|void
     */
    public function getWxRankList($uid,$company_id,$page,$perpage,$type,$date,$region,$tags,$search)
    {
        $services = new PageAuthService();
        $tag = $services->getTagList('tree');
        //+ 判断该用户是否有权限
        $is_auth = 0;
        if(!empty($tag['media_tag'])){
            foreach ($tag['media_tag'] as $m) {
                if($m['e_name'] == 'wx'){
                    $is_auth = 1;
                }
            }
        }
        if($is_auth == 0){
            return jsonErrorReturn('authorityError');
        }

        //+ 获取该公司所在榜单分组ID
        $groupArr = $this->getGroupArr($company_id);
        $group_id = 0;
        if(!empty($groupArr)){
            $group_id = $groupArr->wx_group_id;
        }

        //+ 榜单最新日期
        $rankDate = RankDate::findOne(['type'=>'wx']);

        $week = [];
        $result_rev = explode('_',$rankDate->week);
        $start_week = date("Ymd",strtotime($result_rev[0]));
        for($i=0;$i<4;$i++){
            $week[$i] = date("Ymd",strtotime("-".(($i+1)*6+$i)." day ".$start_week."")).'-'.date("Ymd",strtotime("-".($i*6+$i)." day ".$start_week.""));
        }

        $month = [];
        $start_month = $rankDate->month.'01';
        for ($i = 0; $i < 4; $i++) {
            $first = date('Ym01', strtotime("midnight first day of -{$i} month", strtotime($start_month)));
            $last = date('Ymt', strtotime("midnight first day of -{$i} month", strtotime($start_month)));
            $month[] = $first . '-' . $last;
        }
        $regionArr = !empty($tag['region_tag']) ? $tag['region_tag'] : [];
        $tagsArr = !empty($tag['tag']) ? $tag['tag'] : [];

        if(empty($date)){
            switch ($type) {
                case 'day':
                    $date = date('Y-m-d',strtotime($rankDate->day));
                    break;
                case 'week':
                    $date = $week[0];
                    break;
                case 'month':
                    $date = $month[0];
                    break;
                default:
                    $date = '';
                    break;
            }
        }
        //+ 下载地址
        $host =Yii::$app->request->hostInfo;
        $downloadUrl = $host.'/api/rank/export/export-wx?page='.$page.'&perpage='.$perpage.'&type='.$type.'&date='.$date.'&region='.$region.'&tags='.$tags.'&search='.$search;

        if($type == 'day'){
            $date = date('Ymd',strtotime($date));
            $days = 1;
        }
        if($type == 'week'){
            if (strpos($date, '~') !== false) {
                $dateArr = explode('~',$date);
            }else {
                $dateArr = explode('-', $date);
            }
            $date = date('Ymd', strtotime($dateArr[1])).'_'.date('Ymd', strtotime($dateArr[0]));
            $days = 7;
        }
        if($type == 'month'){
            if (strpos($date, '~') !== false) {
                $dateArr = explode('~',$date);
            }else {
                $dateArr = explode('-', $date);
            }
            $date = date('Ym',strtotime($dateArr[0]));
            $days = 30;
        }

//        $select = 'c.nickname_id,r.id,r.wx_nickname,r.wx_name,r.publish_news_num,r.readnum_all,r.toutiao_readnum,r.readnum_avg,r.likenum_all,r.wci,r.publish_num,r.likenum_avg,r.toutiao_likenum,r.readnum_max,r.likenum_max';
        $select = 'r.*,c.nickname_id';

        $select1 = 'sum(r.readnum_avg) readnum_avg,sum(r.likenum_avg) likenum_avg,sum(r.readnum_all) readnum_all,sum(r.likenum_all) likenum_all,sum(r.publish_news_num) publish_news_num,sum(r.publish_num) publish_num,sum(r.toutiao_readnum) toutiao_readnum,sum(r.toutiao_likenum) toutiao_likenum,max(r.likenum_max) likenum_max,max(r.readnum_max) readnum_max';

        $select2 = 'sum(r.wci) wci';

        $query = CompanyWx::find()->where(['company_id'=>$company_id])
            ->alias('c')
            ->leftJoin(RankWx::tableName().' r','c.nickname_id=r.nickname_id and r.group_id="'.$group_id.'" and r.rank_type="'.$type.'" and r.rank_date="'.$date.'"')
            //->where(['r.rank_type'=>$type,'r.rank_date'=>$date])
            ->orderBy('r.wci desc');

        //+ 地区筛选
        //| 地区筛选，若选择的地区有子级地区则子级地区所关联账号也筛选出现
        if($region){
            $regArr = $this->getRegionArr($region);
            $where = ['or'];
            foreach ($regArr as $reg) {
                $where[] = "find_in_set($reg,c.region_ids)";
            }
            $query->andWhere($where);
        }

        //+ 标签筛选
        //| 标签筛选，若选择的标签有子级标签则子级标签所关联账号也筛选出现
        if($tags){
            $regArr = $this->getRegionArr($tags, 2);
            $where = ['or'];
            foreach ($regArr as $reg) {
                $where[] = "find_in_set($reg,c.tags_ids)";
            }
            $query->andWhere($where);
        }

        //+ 搜索
        if($search){
            $query->andWhere(['like','r.wx_nickname',$search]);
        }

        //+ 总数
        $total = $query->count();

        //+ 计算综合传播指数
        //| 综合传播指数按 0.8*P1+0.2*P2
        //| 其中P1是 团体传播指数，P2是精英传播指数

        //+ 团体传播指数
        $w1 = $query->select($select1)->asArray()->one();
        $read_avg = round($w1['readnum_all'] / $days, 2);
        $like_avg = round($w1['likenum_all'] / $days, 2);
        $tt_read_avg = round($w1['toutiao_readnum'] / $days, 2);
        $tt_like_avg = round($w1['toutiao_likenum'] / $days, 2);
        $wci1 = round((0.3 * (0.85 * log($read_avg + 1)
                    + 0.15 * log(10 * $like_avg + 1))
                + 0.3 * (0.85 * log($w1['readnum_avg'] + 1)
                    + 0.15 * log(10 * $w1['likenum_avg'] + 1))
                + 0.3 * (0.85 * log($tt_read_avg + 1)
                    + 0.15 * log(10 * $tt_like_avg + 1))
                + 0.1 * (0.85 * log($w1['readnum_max'] + 1)
                    + 0.15 * log(10 * $w1['likenum_max'] + 1)))
            * (0.3 * (0.85 * log($read_avg + 1)
                    + 0.15 * log(10 * $like_avg + 1))
                + 0.3 * (0.85 * log($w1['readnum_avg'] + 1)
                    + 0.15 * log(10 * $w1['likenum_avg'] + 1))
                + 0.3 * (0.85 * log($tt_read_avg + 1)
                    + 0.15 * log(10 * $tt_like_avg + 1))
                + 0.1 * (0.85 * log($w1['readnum_max'] + 1)
                    + 0.15 * log(10 * $w1['likenum_max'] + 1))) * 10, 2);

        //+ 精英传播指数
        //| 取所有账号前20%账号的wci加和取平均
        $wci2 = 0;
        if($total>0) {
            $w2Count = ceil($total * 0.2);
            $w2 = $query->select($select2)->limit($w2Count)->asArray()->one();
            $wci2 = round($w2['wci'] / $w2Count, 2);
        }

        //+ 综合传播指数
        $wci = round(0.8*$wci1 + 0.2*$wci2, 2);

        $offset = ($page-1)*$perpage;
        $list = $query->select($select)->offset($offset)->limit($perpage)->asArray()->all();
        if($list){
            foreach ($list as $k => $v) {
                $v['readnum_all'] = $v['readnum_all'] ? fort_mat($v['readnum_all']) : 0;
                $v['toutiao_readnum'] = $v['toutiao_readnum'] ? fort_mat($v['toutiao_readnum']) : 0;
                $v['readnum_avg'] = $v['readnum_avg'] ? fort_mat($v['readnum_avg']) : 0;
                $v['likenum_all'] = $v['likenum_all'] ? fort_mat($v['likenum_all']) : 0;
                $v['old_like_num_all'] = $v['old_like_num_all'] ? fort_mat($v['old_like_num_all']) : 0;

                $account_info = AccountWxAll::getAccOne($v['nickname_id']);
                $v['wx_logo'] = $account_info->wx_logo;
                if(!$v['id']){
                    $v['wx_nickname'] = $account_info->wx_nickname;
                    $v['wx_name'] = $account_info->wx_name;
                }
                $v['id'] = $v['nickname_id'];

                //+ 是否有联系人
                $is_has_mail = 0;
                $hasMail = ContactsService()->getMailList($uid,$company_id,'wx',$v['nickname_id']);
                if(!empty($hasMail)){
                    if(count($hasMail) == 1){
                        $is_has_mail = $hasMail[0]['id'];
                    }else{
                        $is_has_mail = '-1';
                    }
                }
                $v['is_mail'] = $is_has_mail;

                $list[$k] = $this->checkData($v);
                $list[$k]['sort'] = $offset+$k+1;
            }
        }

        $data = [
            'total'=> $total,
            'list'=> $list,
            'wci'=> $wci,
            'downloadUrl'=> $downloadUrl,
            'day'=> date('Y-m-d',strtotime($rankDate->day)),
            'week'=> $this->transformTime($week),
            'month'=> $this->transformTime($month),
            'regionArr'=> $this->tagsInit($regionArr,explode(',',$region)),
            'tagsArr'=> $this->tagsInit($tagsArr,explode(',',$tags)),
        ];

        return $data;
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
     * 微博榜单列表
     * @param $uid
     * @param $company_id
     * @param $page
     * @param $perpage
     * @param $type
     * @param $date
     * @param $region
     * @param $tags
     * @param $search
     * @return array|void
     */
    public function getWeiboRankList($uid,$company_id,$page,$perpage,$type,$date,$region,$tags,$search)
    {
        $services = new PageAuthService();
        $tag = $services->getTagList('tree');
        //+ 判断该用户是否有权限
        $is_auth = 0;
        if(!empty($tag['media_tag'])){
            foreach ($tag['media_tag'] as $m) {
                if($m['e_name'] == 'weibo'){
                    $is_auth = 1;
                }
            }
        }
        if($is_auth == 0){
            return jsonErrorReturn('authorityError');
        }

        //+ 获取该公司所在榜单分组ID
        $groupArr = $this->getGroupArr($company_id);
        $group_id = 0;
        if(!empty($groupArr)){
            $group_id = $groupArr->weibo_group_id;
        }

        //+ 榜单最新日期
        $rankDate = RankDate::findOne(['type'=>'weibo']);

        $week = [];
        $result_rev = explode('_',$rankDate->week);
        $start_week = date("Ymd",strtotime($result_rev[0]));
        for($i=0;$i<4;$i++){
            $week[$i] = date("Ymd",strtotime("-".(($i+1)*6+$i)." day ".$start_week."")).'-'.date("Ymd",strtotime("-".($i*6+$i)." day ".$start_week.""));
        }

        $month = [];
        $start_month = $rankDate->month.'01';
        for ($i = 0; $i < 4; $i++) {
            $first = date('Ym01', strtotime("midnight first day of -{$i} month", strtotime($start_month)));
            $last = date('Ymt', strtotime("midnight first day of -{$i} month", strtotime($start_month)));
            $month[] = $first . '-' . $last;
        }

        //$week = getWeek();
        //$month = getMonth();
        //$regionArr = $tag['region_tag'];
        //$tagsArr = $tag['tag'];

        $regionArr = !empty($tag['region_tag']) ? $tag['region_tag'] : [];
        $tagsArr = !empty($tag['tag']) ? $tag['tag'] : [];

        if(empty($date)){
            switch ($type) {
                case 'day':
                    $date = date('Ymd', strtotime($rankDate->day));
                    break;
                case 'week':
                    $date = $week[0];
                    break;
                case 'month':
                    $date = $month[0];
                    break;
                default:
                    $date = '';
                    break;
            }
        }

        //+ 下载地址
        $host =Yii::$app->request->hostInfo;
        $downloadUrl = $host.'/api/rank/export/export-weibo?page='.$page.'&perpage='.$perpage.'&type='.$type.'&date='.$date.'&region='.$region.'&tags='.$tags.'&search='.$search;

        if($type == 'day'){
            $date = date('Ymd',strtotime($date));
        }
        if($type == 'week'){
            if (strpos($date, '~') !== false) {
                $dateArr = explode('~',$date);
            }else {
                $dateArr = explode('-', $date);
            }
            $date = date('Ymd', strtotime($dateArr[1])).'_'.date('Ymd', strtotime($dateArr[0]));
        }
        if($type == 'month'){
            if (strpos($date, '~') !== false) {
                $dateArr = explode('~',$date);
            }else {
                $dateArr = explode('-', $date);
            }
            $date = date('Ym',strtotime($dateArr[0]));
        }

//        $select = 'c.weibo_uid,r.id,r.weibo_nickname,r.publish_news_num,r.repost_count,r.comment_count,r.origin_repost_count,r.origin_comment_count,r.origin_news_num,r.like_count,r.bci';
        $select = 'r.*,c.weibo_uid';

        $select1 = 'sum(r.publish_news_num) publish_news_num,sum(r.repost_count) repost_count,sum(r.comment_count) comment_count,sum(r.origin_repost_count) origin_repost_count,sum(r.origin_comment_count) origin_comment_count,sum(r.like_count) like_count,sum(r.origin_news_num) origin_news_num';

        $select2 = 'sum(r.bci) bci';

        $query = CompanyWeibo::find()->where(['company_id'=>$company_id])
            ->alias('c')
            ->leftJoin(RankWeibo::tableName().' r','c.weibo_uid=r.weibo_uid and r.group_id="'.$group_id.'" and r.rank_type="'.$type.'" and r.rank_date="'.$date.'"')
            //->where(['r.rank_type'=>$type,'r.rank_date'=>$date])
            ->orderBy('r.bci desc');

        //+ 地区筛选
        //| 地区筛选，若选择的地区有子级地区则子级地区所关联账号也筛选出现
        if($region){
            $regArr = $this->getRegionArr($region);
            $where = ['or'];
            foreach ($regArr as $reg) {
                $where[] = "FIND_IN_SET($reg,c.region_ids)";
            }
            $query->andWhere($where);
        }

        //+ 标签筛选
        //| 标签筛选，若选择的标签有子级标签则子级标签所关联账号也筛选出现
        if($tags){
            $regArr = $this->getRegionArr($tags, 2);
            $where = ['or'];
            foreach ($regArr as $reg) {
                $where[] = "FIND_IN_SET($reg,c.tags_ids)";
            }
            $query->andWhere($where);
        }

        //+ 搜索
        if($search){
            $query->andWhere(['like','r.weibo_nickname',$search]);
        }

        //+ 总数
        $total = $query->count();

        //+ 计算综合传播指数
        //| 综合传播指数按 0.8*P1+0.2*P2
        //| 其中P1是 团体传播指数，P2是精英传播指数

        //+ 团体传播指数
        $w1 = $query->select($select1)->asArray()->one();
        $W1 = 0.3 * log($w1['publish_news_num'] + 1) + 0.7 * log($w1['origin_news_num'] + 1);
        $W2 = 0.2 * log($w1['repost_count'] + 1) + 0.2 * log($w1['comment_count'] + 1) + 0.25 * log($w1['origin_repost_count'] + 1) + 0.25 * log($w1['origin_comment_count'] + 1) + 0.1 * log($w1['like_count'] + 1);
        $bci1 = round((0.2 * $W1 + 0.8 * $W2) * 160, 2);

        //+ 精英传播指数
        //| 取所有账号前20%账号的wci加和取平均
        $bci2 = 0;
        if($total>0) {
            $w2Count = ceil($total * 0.2);
            $w2 = $query->select($select2)->limit($w2Count)->asArray()->one();
            $bci2 = round($w2['bci'] / $w2Count, 2);
        }

        //+ 综合传播指数
        $bci = round(0.8*$bci1 + 0.2*$bci2, 2);

        $offset = ($page-1)*$perpage;
        $list = $query->select($select)->offset($offset)->limit($perpage)->asArray()->all();
        if($list){
            foreach ($list as $k => $v) {
                $v['publish_news_num'] = $v['publish_news_num'].'/'.$v['origin_news_num'];
                $v['repost_count'] = $v['repost_count'] ? fort_mat($v['repost_count']) : 0;
                $v['comment_count'] = $v['comment_count'] ? fort_mat($v['comment_count']) : 0;
                $v['origin_repost_count'] = $v['origin_repost_count'] ? fort_mat($v['origin_repost_count']) : 0;
                $v['origin_comment_count'] = $v['origin_comment_count'] ? fort_mat($v['origin_comment_count']) : 0;
                $v['like_count'] = $v['like_count'] ? fort_mat($v['like_count']) : 0;

                $account_info = AccountWeiboAll::getAccOne($v['weibo_uid']);
                $v['weibo_logo'] = $account_info->avatar;
                if(!$v['id']){
                    $v['weibo_nickname'] = $account_info->nickname;
                }
                $v['id'] = $v['weibo_uid'];
//                unset($v['origin_news_num']);

                //+ 是否有联系人
                $is_has_mail = 0;
                $hasMail = ContactsService()->getMailList($uid,$company_id,'weibo',$v['weibo_uid']);
                if(!empty($hasMail)){
                    if(count($hasMail) == 1){
                        $is_has_mail = $hasMail[0]['id'];
                    }else{
                        $is_has_mail = '-1';
                    }
                }
                $v['is_mail'] = $is_has_mail;

                $list[$k] = $this->checkData($v);
                $list[$k]['sort'] = $offset+$k+1;
            }
        }

        $data = [
            'total'=> $total,
            'list'=> $list,
            'bci'=> $bci,
            'downloadUrl'=> $downloadUrl,
            'day'=> date('Y-m-d',strtotime($rankDate->day)),
            'week'=> $this->transformTime($week),
            'month'=> $this->transformTime($month),
            'regionArr'=> $this->tagsInit($regionArr,explode(',',$region)),
            'tagsArr'=> $this->tagsInit($tagsArr,explode(',',$tags)),
        ];

        return $data;
    }

    /**
     * 头条榜单列表
     * @param $uid
     * @param $company_id
     * @param $page
     * @param $perpage
     * @param $type
     * @param $date
     * @param $region
     * @param $tags
     * @param $search
     * @return array|void
     */
    public function getToutiaoRankList($uid,$company_id,$page,$perpage,$type,$date,$region,$tags,$search)
    {
        $services = new PageAuthService();
        $tag = $services->getTagList('tree');
        //+ 判断该用户是否有权限
        $is_auth = 0;
        if(!empty($tag['media_tag'])){
            foreach ($tag['media_tag'] as $m) {
                if($m['e_name'] == 'media_toutiao'){
                    $is_auth = 1;
                }
            }
        }
        if($is_auth == 0){
            return jsonErrorReturn('authorityError');
        }

        //+ 获取该公司所在榜单分组ID
        $groupArr = $this->getGroupArr($company_id);
        $group_id = 0;
        if(!empty($groupArr)){
            $group_id = $groupArr->toutiao_group_id;
        }

        //+ 榜单最新日期
        $rankDate = RankDate::findOne(['type'=>'toutiao']);

        $week = [];
        $result_rev = explode('_',$rankDate->week);
        $start_week = date("Ymd",strtotime($result_rev[0]));
        for($i=0;$i<4;$i++){
            $week[$i] = date("Ymd",strtotime("-".(($i+1)*6+$i)." day ".$start_week."")).'-'.date("Ymd",strtotime("-".($i*6+$i)." day ".$start_week.""));
        }

        $month = [];
        $start_month = $rankDate->month.'01';
        for ($i = 0; $i < 4; $i++) {
            $first = date('Ym01', strtotime("midnight first day of -{$i} month", strtotime($start_month)));
            $last = date('Ymt', strtotime("midnight first day of -{$i} month", strtotime($start_month)));
            $month[] = $first . '-' . $last;
        }

        //$week = getWeek();
        //$month = getMonth();
        //$regionArr = $tag['region_tag'];
        //$tagsArr = $tag['tag'];

        $regionArr = !empty($tag['region_tag']) ? $tag['region_tag'] : [];
        $tagsArr = !empty($tag['tag']) ? $tag['tag'] : [];

        if(empty($date)){
            switch ($type) {
                case 'day':
                    $date = date('Ymd', strtotime($rankDate->day));
                    break;
                case 'week':
                    $date = $week[0];
                    break;
                case 'month':
                    $date = $month[0];
                    break;
                default:
                    $date = '';
                    break;
            }
        }

        //+ 下载地址
        $host =Yii::$app->request->hostInfo;
        $downloadUrl = $host.'/api/rank/export/export-toutiao?page='.$page.'&perpage='.$perpage.'&type='.$type.'&date='.$date.'&region='.$region.'&tags='.$tags.'&search='.$search;

        if($type == 'day'){
            $date = date('Ymd',strtotime($date));
            $days = 1;
        }
        if($type == 'week'){
            if (strpos($date, '~') !== false) {
                $dateArr = explode('~',$date);
            }else {
                $dateArr = explode('-', $date);
            }
            $date = date('Ymd', strtotime($dateArr[1])).'_'.date('Ymd', strtotime($dateArr[0]));
            $days = 7;
        }
        if($type == 'month'){
            if (strpos($date, '~') !== false) {
                $dateArr = explode('~',$date);
            }else {
                $dateArr = explode('-', $date);
            }
            $date = date('Ym',strtotime($dateArr[0]));
            $days = 30;
        }

        $select = 'c.toutiao_user_id,r.id,r.toutiao_nickname,r.publish_news_num,r.read_count,r.read_avg,r.comment_count,r.comment_avg,r.tgi';

        $select1 = 'sum(r.publish_news_num) publish_news_num,sum(r.read_count) read_count,sum(r.read_avg) read_avg,sum(r.comment_count) comment_count,sum(r.comment_avg) comment_avg';

        $select2 = 'sum(r.tgi) tgi';

        $query = CompanyToutiao::find()->where(['company_id'=>$company_id])
            ->alias('c')
            ->leftJoin(RankToutiao::tableName().' r','c.toutiao_user_id=r.toutiao_user_id and r.group_id="'.$group_id.'" and r.rank_type="'.$type.'" and r.rank_date="'.$date.'"')
            //->where(['r.rank_type'=>$type,'r.rank_date'=>$date])
            ->orderBy('r.tgi desc');

        //+ 地区筛选
        //| 地区筛选，若选择的地区有子级地区则子级地区所关联账号也筛选出现
        if($region){
            $regArr = $this->getRegionArr($region);
            $where = ['or'];
            foreach ($regArr as $reg) {
                $where[] = "FIND_IN_SET($reg,c.region_ids)";
            }
            $query->andWhere($where);
        }

        //+ 标签筛选
        //| 标签筛选，若选择的标签有子级标签则子级标签所关联账号也筛选出现
        if($tags){
            $regArr = $this->getRegionArr($tags, 2);
            $where = ['or'];
            foreach ($regArr as $reg) {
                $where[] = "FIND_IN_SET($reg,c.tags_ids)";
            }
            $query->andWhere($where);
        }

        //+ 搜索
        if($search){
            $query->andWhere(['like','r.toutiao_nickname',$search]);
        }

        //+ 总数
        $total = $query->count();

        //+ 计算综合传播指数
        //| 综合传播指数按 0.8*P1+0.2*P2
        //| 其中P1是 团体传播指数，P2是精英传播指数

        //+ 团体传播指数
        $w1 = $query->select($select1)->asArray()->one();
        $x1 = round($w1['read_count'] / $days, 2);
        $x3 = round($w1['comment_count'] / $days, 2);
        $tgi1 = round((0.8 * (0.45 * log($x1 + 1) + 0.55 * log($w1['read_avg'] + 1))
                + 0.2 * (0.45 * log($x3 + 1) + 0.55 * log($w1['comment_avg'] + 1))) * 100, 2);

        //+ 精英传播指数
        //| 取所有账号前20%账号的wci加和取平均
        $tgi2 = 0;
        if($total>0) {
            $w2Count = ceil($total * 0.2);
            $w2 = $query->select($select2)->limit($w2Count)->asArray()->one();
            $tgi2 = round($w2['tgi'] / $w2Count, 2);
        }

        //+ 综合传播指数
        $tgi = round(0.8*$tgi1 + 0.2*$tgi2, 2);

        $offset = ($page-1)*$perpage;
        $list = $query->select($select)->offset($offset)->limit($perpage)->asArray()->all();
        if($list){
            foreach ($list as $k => $v) {
                $v['read_count'] = $v['read_count'] ? fort_mat($v['read_count']) : 0;
                $v['read_avg'] = $v['read_avg'] ? fort_mat($v['read_avg']) : 0;
                $v['comment_count'] = $v['comment_count'] ? fort_mat($v['comment_count']) : 0;
                $v['comment_avg'] = $v['comment_avg'] ? fort_mat($v['comment_avg']) : 0;

                if(!$v['id']){
                    $v['toutiao_nickname'] = AccountToutiaoAll::getAccOne($v['toutiao_user_id'])->nickname;
                }
                $v['id'] = $v['toutiao_user_id'];

                //+ 是否有联系人
                $is_has_mail = 0;
                $hasMail = ContactsService()->getMailList($uid,$company_id,'media_toutiao',$v['toutiao_user_id']);
                if(!empty($hasMail)){
                    if(count($hasMail) == 1){
                        $is_has_mail = $hasMail[0]['id'];
                    }else{
                        $is_has_mail = '-1';
                    }
                }
                $v['is_mail'] = $is_has_mail;

                $list[$k] = $this->checkData($v);
                $list[$k]['sort'] = $offset+$k+1;
            }
        }

        $data = [
            'total'=> $total,
            'list'=> $list,
            'tgi'=> $tgi,
            'downloadUrl'=> $downloadUrl,
            'day'=> date('Y-m-d',strtotime($rankDate->day)),
            'week'=> $week,
            'month'=> $month,
            'regionArr'=> $this->tagsInit($regionArr,explode(',',$region)),
            'tagsArr'=> $this->tagsInit($tagsArr,explode(',',$tags)),
        ];

        return $data;
    }

    /**
     * 抖音榜单列表
     * @param $uid
     * @param $company_id
     * @param $page
     * @param $perpage
     * @param $type
     * @param $date
     * @param $region
     * @param $tags
     * @param $search
     * @return array|void
     */
    public function getDouyinRankList($uid,$company_id,$page,$perpage,$type,$date,$region,$tags,$search)
    {
        $services = new PageAuthService();
        $tag = $services->getTagList('tree');
        //+ 判断该用户是否有权限
        $is_auth = 0;
        if(!empty($tag['media_tag'])){
            foreach ($tag['media_tag'] as $m) {
                if($m['e_name'] == 'douyin'){
                    $is_auth = 1;
                }
            }
        }
        if($is_auth == 0){
            return jsonErrorReturn('authorityError');
        }

        //+ 获取该公司所在榜单分组ID
        $groupArr = $this->getGroupArr($company_id);
        $group_id = 0;
        if(!empty($groupArr)){
            $group_id = $groupArr->douyin_group_id;
        }

        //+ 榜单最新日期
        $rankDate = RankDate::findOne(['type'=>'douyin']);

        $week = [];
        $result_rev = explode('_',$rankDate->week);
        $start_week = date("Ymd",strtotime($result_rev[1]));
        for($i=0;$i<4;$i++){
            $week[$i] = date("Ymd",strtotime("-".(($i+1)*6+$i)." day ".$start_week."")).'-'.date("Ymd",strtotime("-".($i*6+$i)." day ".$start_week.""));
        }

        $month = [];
        $start_month = $rankDate->month.'01';
        for ($i = 0; $i < 4; $i++) {
            $first = date('Ym01', strtotime("midnight first day of -{$i} month", strtotime($start_month)));
            $last = date('Ymt', strtotime("midnight first day of -{$i} month", strtotime($start_month)));
            $month[] = $first . '-' . $last;
        }

        //$week = getWeek();
        //$month = getMonth();
        //$regionArr = $tag['region_tag'];
        //$tagsArr = $tag['tag'];

        $regionArr = !empty($tag['region_tag']) ? $tag['region_tag'] : [];
        $tagsArr = !empty($tag['tag']) ? $tag['tag'] : [];

        if(empty($date)){
            switch ($type) {
                case 'day':
                    $date = date('Ymd', strtotime($rankDate->day));
                    break;
                case 'week':
                    $date = $week[0];
                    break;
                case 'month':
                    $date = $month[0];
                    break;
                default:
                    $date = '';
                    break;
            }
        }

        //+ 下载地址
        $host =Yii::$app->request->hostInfo;
        $downloadUrl = $host.'/api/rank/export/export-douyin?page='.$page.'&perpage='.$perpage.'&type='.$type.'&date='.$date.'&region='.$region.'&tags='.$tags.'&search='.$search;

        if($type == 'day'){
            $date = date('Ymd',strtotime($date));
        }
        if($type == 'week'){
            if (strpos($date, '~') !== false) {
                $dateArr = explode('~',$date);
            }else {
                $dateArr = explode('-', $date);
            }
//            $date = date('Ymd', strtotime($dateArr[1])).'_'.date('Ymd', strtotime($dateArr[0]));
            $date = date('Ymd', strtotime($dateArr[0])).'_'.date('Ymd', strtotime($dateArr[1]));
        }
        if($type == 'month'){
            if (strpos($date, '~') !== false) {
                $dateArr = explode('~',$date);
            }else {
                $dateArr = explode('-', $date);
            }
            $date = date('Ym',strtotime($dateArr[0]));
        }

        $select = 'c.douyin_id,r.id,r.douyin_name,r.douyin_code,r.video_count,r.fans_count,r.fans_count_up,r.like_count,r.share_count,r.comment_count,r.dci,r.like_num_up,r.share_num_up,r.comment_num_up';

        $select1 = 'sum(r.video_count) video_count,sum(r.fans_count) fans_count,sum(r.fans_count_up) fans_count_up,sum(r.like_count) like_count,sum(r.share_count) share_count,sum(r.comment_count) comment_count';

        $select2 = 'sum(r.dci) dci';

        $query = CompanyDouyin::find()->where(['company_id'=>$company_id])
            ->alias('c')
            ->leftJoin(RankDouyin::tableName().' r','c.douyin_id=r.douyin_id and r.group_id="'.$group_id.'" and r.rank_type="'.$type.'" and r.rank_date="'.$date.'"')
            //->where(['r.rank_type'=>$type,'r.rank_date'=>$date])
            ->orderBy('r.dci desc');

        //+ 地区筛选
        //| 地区筛选，若选择的地区有子级地区则子级地区所关联账号也筛选出现
        if($region){
            $regArr = $this->getRegionArr($region);
            $where = ['or'];
            foreach ($regArr as $reg) {
                $where[] = "FIND_IN_SET($reg,c.region_ids)";
            }
            $query->andWhere($where);
        }

        //+ 标签筛选
        //| 标签筛选，若选择的标签有子级标签则子级标签所关联账号也筛选出现
        if($tags){
            $regArr = $this->getRegionArr($tags, 2);
            $where = ['or'];
            foreach ($regArr as $reg) {
                $where[] = "FIND_IN_SET($reg,c.tags_ids)";
            }
            $query->andWhere($where);
        }

        //+ 搜索
        if($search){
            $query->andWhere(['like','r.douyin_name',$search]);
        }

        //+ 总数
        $total = $query->count();

        //+ 计算综合传播指数
        //| 综合传播指数按 0.8*P1+0.2*P2
        //| 其中P1是 团体传播指数，P2是精英传播指数

        //+ 团体传播指数
        $w1 = $query->select($select1)->asArray()->one();
        $fans_count_up = $w1['fans_count_up'] < 0 ? 0 : $w1['fans_count_up'];
        $dci1 = round((0.1 * log($w1['video_count'] + 1)
                + 0.76 * (0.17 * log($w1['like_count'] + 1)
                    + 0.37 * log($w1['comment_count'] + 1)
                    + 0.46 * log($w1['share_count'] + 1))
                + 0.14 * (0.89 * log($fans_count_up + 1)
                    + 0.11 * log($w1['fans_count'] + 1))) * 100, 2);

        //+ 精英传播指数
        //| 取所有账号前20%账号的wci加和取平均
        $dci2 = 0;
        if($total>0) {
            $w2Count = ceil($total * 0.2);
            $w2 = $query->select($select2)->limit($w2Count)->asArray()->one();
            $dci2 = round($w2['dci'] / $w2Count, 2);
        }

        //+ 综合传播指数
        $dci = round(0.8*$dci1 + 0.2*$dci2, 2);

        $offset = ($page-1)*$perpage;
        $list = $query->select($select)->offset($offset)->limit($perpage)->asArray()->all();
        if($list){
            foreach ($list as $k => $v) {
                $v['fans_count'] = $v['fans_count'] ? fort_mat($v['fans_count']) : 0;
                if($v['fans_count_up']<0){
                    $v['fans_count_up'] = '-'.fort_mat(abs($v['fans_count_up']));
                }else{
                    $v['fans_count_up'] = $v['fans_count_up'] ? fort_mat($v['fans_count_up']) : 0;
                }
                $v['like_count'] = $v['like_count'] ? fort_mat($v['like_count']) : 0;
                $v['share_count'] = $v['share_count'] ? fort_mat($v['share_count']) : 0;
                $v['comment_count'] = $v['comment_count'] ? fort_mat($v['comment_count']) : 0;

                $account_info = AccountDouyinAll::getAccOne($v['douyin_id']);
                $v['douyin_logo'] = $account_info->author_img;
                if(!$v['id']){
                    $v['douyin_name'] = $account_info->douyin_name;
                    $v['douyin_code'] = $account_info->douyin_code;
                }
                $v['id'] = $v['douyin_id'];

                //+ 是否有联系人
                $is_has_mail = 0;
                $hasMail = ContactsService()->getMailList($uid,$company_id,'douyin',$v['douyin_id']);
                if(!empty($hasMail)){
                    if(count($hasMail) == 1){
                        $is_has_mail = $hasMail[0]['id'];
                    }else{
                        $is_has_mail = '-1';
                    }
                }
                $v['is_mail'] = $is_has_mail;

                $list[$k] = $this->checkData($v);
                $list[$k]['sort'] = $offset+$k+1;
            }
        }

        $data = [
            'total'=> $total,
            'list'=> $list,
            'dci'=> $dci,
            'downloadUrl'=> $downloadUrl,
            'day'=> date('Y-m-d',strtotime($rankDate->day)),
            'week'=> $this->transformTime($week),
            'month'=> $this->transformTime($month),
            'regionArr'=> $this->tagsInit($regionArr,explode(',',$region)),
            'tagsArr'=> $this->tagsInit($tagsArr,explode(',',$tags)),
        ];

        return $data;
    }

    /**
     * 快手榜单列表
     * @param $uid
     * @param $company_id
     * @param $page
     * @param $perpage
     * @param $type
     * @param $date
     * @param $region
     * @param $tags
     * @param $search
     * @return array|void
     */
    public function getKuaishouRankList($uid,$company_id,$page,$perpage,$type,$date,$region,$tags,$search)
    {
        $services = new PageAuthService();
        $tag = $services->getTagList('tree');
        //+ 判断该用户是否有权限
        $is_auth = 0;
        if(!empty($tag['media_tag'])){
            foreach ($tag['media_tag'] as $m) {
                if($m['e_name'] == 'kuaishou'){
                    $is_auth = 1;
                }
            }
        }
        if($is_auth == 0){
            return jsonErrorReturn('authorityError');
        }

        //+ 获取该公司所在榜单分组ID
        $groupArr = $this->getGroupArr($company_id);
        $group_id = 0;
        if(!empty($groupArr)){
            $group_id = $groupArr->kuaishou_group_id;
        }

        //+ 榜单最新日期
        $rankDate = RankDate::findOne(['type'=>'kuaishou']);

        $week = [];
        $result_rev = explode('_',$rankDate->week);
        $start_week = date("Ymd",strtotime($result_rev[1]));
        for($i=0;$i<4;$i++){
            $week[$i] = date("Ymd",strtotime("-".(($i+1)*6+$i)." day ".$start_week."")).'-'.date("Ymd",strtotime("-".($i*6+$i)." day ".$start_week.""));
        }

        $month = [];
        $start_month = $rankDate->month.'01';
        for ($i = 0; $i < 4; $i++) {
            $first = date('Ym01', strtotime("midnight first day of -{$i} month", strtotime($start_month)));
            $last = date('Ymt', strtotime("midnight first day of -{$i} month", strtotime($start_month)));
            $month[] = $first . '-' . $last;
        }

        //$week = getWeek();
        //$month = getMonth();
        //$regionArr = $tag['region_tag'];
        //$tagsArr = $tag['tag'];

        $regionArr = !empty($tag['region_tag']) ? $tag['region_tag'] : [];
        $tagsArr = !empty($tag['tag']) ? $tag['tag'] : [];

        if(empty($date)){
            switch ($type) {
                case 'day':
                    $date = date('Ymd', strtotime($rankDate->day));
                    break;
                case 'week':
                    $date = $week[0];
                    break;
                case 'month':
                    $date = $month[0];
                    break;
                default:
                    $date = '';
                    break;
            }
        }

        //+ 下载地址
        $host =Yii::$app->request->hostInfo;
        $downloadUrl = $host.'/api/rank/export/export-kuaishou?page='.$page.'&perpage='.$perpage.'&type='.$type.'&date='.$date.'&region='.$region.'&tags='.$tags.'&search='.$search;

        if($type == 'day'){
            $date = date('Ymd',strtotime($date));
        }
        if($type == 'week'){
            if (strpos($date, '~') !== false) {
                $dateArr = explode('~',$date);
            }else {
                $dateArr = explode('-', $date);
            }
//            $date = date('Ymd', strtotime($dateArr[1])).'_'.date('Ymd', strtotime($dateArr[0]));
            $date = date('Ymd', strtotime($dateArr[0])).'_'.date('Ymd', strtotime($dateArr[1]));
        }
        if($type == 'month'){
            if (strpos($date, '~') !== false) {
                $dateArr = explode('~',$date);
            }else {
                $dateArr = explode('-', $date);
            }
            $date = date('Ym',strtotime($dateArr[0]));
        }

        $select = 'c.kuaishou_id,r.id,r.kuaishou_name,r.kuaishou_code,r.video_count,r.fans_count,r.fans_count_up,r.like_count,r.comment_count,r.kci,r.like_num_up,r.comment_num_up';

        $select1 = 'sum(r.video_count) video_count,sum(r.fans_count) fans_count,sum(r.fans_count_up) fans_count_up,sum(r.like_count) like_count,sum(r.comment_count) comment_count,sum(r.play_count) play_count';

        $select2 = 'sum(r.kci) kci';

        $query = CompanyKuaishou::find()->where(['company_id'=>$company_id])
            ->alias('c')
            ->leftJoin(RankKuaishou::tableName().' r','c.kuaishou_id=r.kuaishou_id and r.group_id="'.$group_id.'" and r.rank_type="'.$type.'" and r.rank_date="'.$date.'"')
            //->where(['r.rank_type'=>$type,'r.rank_date'=>$date])
            ->orderBy('r.kci desc');

        //+ 地区筛选
        //| 地区筛选，若选择的地区有子级地区则子级地区所关联账号也筛选出现
        if($region){
            $regArr = $this->getRegionArr($region);
            $where = ['or'];
            foreach ($regArr as $reg) {
                $where[] = "FIND_IN_SET($reg,c.region_ids)";
            }
            $query->andWhere($where);
        }

        //+ 标签筛选
        //| 标签筛选，若选择的标签有子级标签则子级标签所关联账号也筛选出现
        if($tags){
            $regArr = $this->getRegionArr($tags, 2);
            $where = ['or'];
            foreach ($regArr as $reg) {
                $where[] = "FIND_IN_SET($reg,c.tags_ids)";
            }
            $query->andWhere($where);
        }

        //+ 搜索
        if($search){
            $query->andWhere(['like','r.kuaishou_name',$search]);
        }

        //+ 总数
        $total = $query->count();

        //+ 计算综合传播指数
        //| 综合传播指数按 0.8*P1+0.2*P2
        //| 其中P1是 团体传播指数，P2是精英传播指数

        //+ 团体传播指数
        $w1 = $query->select($select1)->asArray()->one();
        $fans_count_add = $w1['fans_count_up'] > 0 ? $w1['fans_count_up'] : 0;
        $fans_count_cut = $w1['fans_count_up'] < 0 ? abs($w1['fans_count_up']) : 0;
        $kci1 = round((0.15 * log($w1['video_count']*1000 + 1)
            + 0.4 * log($w1['play_count'] + 1)
            + 0.2 * (0.45 * log($w1['like_count'] + 1)
            + 0.55 * log($w1['comment_count'] + 1))
        + 0.25* (log($w1['fans_count'] + 1) * 0.6
                + log($fans_count_add + 1) * 0.2
                - log($fans_count_cut + 1) *0.2)) * 100, 2);

        //+ 精英传播指数
        //| 取所有账号前20%账号的wci加和取平均
        $kci2 = 0;
        if($total>0) {
            $w2Count = ceil($total * 0.2);
            $w2 = $query->select($select2)->limit($w2Count)->asArray()->one();
            $kci2 = round($w2['kci'] / $w2Count, 2);
        }

        //+ 综合传播指数
        $kci = round(0.8*$kci1 + 0.2*$kci2, 2);

        $offset = ($page-1)*$perpage;
        $list = $query->select($select)->offset($offset)->limit($perpage)->asArray()->all();
        if($list){
            foreach ($list as $k => $v) {
                $v['fans_count'] = $v['fans_count'] ? fort_mat($v['fans_count']) : 0;
                if($v['fans_count_up']<0){
                    $v['fans_count_up'] = '-'.fort_mat(abs($v['fans_count_up']));
                }else{
                    $v['fans_count_up'] = $v['fans_count_up'] ? fort_mat($v['fans_count_up']) : 0;
                }
                $v['like_count'] = $v['like_count'] ? fort_mat($v['like_count']) : 0;
                $v['comment_count'] = $v['comment_count'] ? fort_mat($v['comment_count']) : 0;

                $account_info = AccountKuaishouAll::getAccOne($v['kuaishou_id']);
                $v['kuaishou_logo'] = $account_info->author_img;
                if(!$v['id']){
                    $v['kuaishou_name'] = $account_info->kuaishou_name;
                    $v['kuaishou_code'] = $account_info->kuaishou_code;
                }
                $v['id'] = $v['kuaishou_id'];

                //+ 是否有联系人
                $is_has_mail = 0;
                $hasMail = ContactsService()->getMailList($uid,$company_id,'kuaishou',$v['kuaishou_id']);
                if(!empty($hasMail)){
                    if(count($hasMail) == 1){
                        $is_has_mail = $hasMail[0]['id'];
                    }else{
                        $is_has_mail = '-1';
                    }
                }
                $v['is_mail'] = $is_has_mail;

                $list[$k] = $this->checkData($v);
                $list[$k]['sort'] = $offset+$k+1;
            }
        }

        $data = [
            'total'=> $total,
            'list'=> $list,
            'kci'=> $kci,
            'downloadUrl'=> $downloadUrl,
            'day'=> date('Y-m-d',strtotime($rankDate->day)),
            'week'=> $this->transformTime($week),
            'month'=> $this->transformTime($month),
            'regionArr'=> $this->tagsInit($regionArr,explode(',',$region)),
            'tagsArr'=> $this->tagsInit($tagsArr,explode(',',$tags)),
        ];

        return $data;
    }

    /**
     * 网站列表
     * @param $uid
     * @param $company_id
     * @param $page
     * @param $perpage
     * @param $region
     * @param $tags
     * @param $search
     * @return array|void
     */
    public function getWebRankList($uid,$company_id,$page,$perpage,$region,$tags,$status,$search)
    {
        $services = new PageAuthService();
        $tag = $services->getTagList('tree');
        //+ 判断该用户是否有权限
        $is_auth = 0;
        if(!empty($tag['media_tag'])){
            foreach ($tag['media_tag'] as $m) {
                if($m['e_name'] == 'web'){
                    $is_auth = 1;
                }
            }
        }
        if($is_auth == 0){
            return jsonErrorReturn('authorityError');
        }

        //$regionArr = $tag['region_tag'];
        //$tagsArr = $tag['tag'];

        $regionArr = !empty($tag['region_tag']) ? $tag['region_tag'] : [];
        $tagsArr = !empty($tag['tag']) ? $tag['tag'] : [];

        $select = 'c.id,r.web_name,r.domain_sec domain,r.create_time,r.staus,r.comment,r.web_url';
        $query = CompanyWeb::find()->where("FIND_IN_SET($company_id,r.company_ids)")
            ->alias('c')
            ->rightJoin('account_web_all r', 'r.domain_sec=c.domain');


        //+ 地区筛选
        //| 地区筛选，若选择的地区有子级地区则子级地区所关联账号也筛选出现
        if($region){
            $regArr = $this->getRegionArr($region);
            $where = ['or'];
            foreach ($regArr as $reg) {
                $where[] = "FIND_IN_SET($reg,c.region_ids)";
            }
            $query->andWhere($where);
        }

        //+ 标签筛选
        //| 标签筛选，若选择的标签有子级标签则子级标签所关联账号也筛选出现
        if($tags){
            $regArr = $this->getRegionArr($tags, 2);
            $where = ['or'];
            foreach ($regArr as $reg) {
                $where[] = "FIND_IN_SET($reg,c.tags_ids)";
            }
            $query->andWhere($where);
        }

        // 状态
        if($status!=-1){
            $query->andWhere(['r.staus' => $status]);
        } else {
            $query->andWhere(['<>', 'r.staus', 1]); // 过滤已删除数据
        }

        //+ 搜索
        if($search){
            $query->andWhere(['like','r.web_name',$search]);
        }

        //+ 总数
        $total = $query->count();

        $offset = ($page-1)*$perpage;
        $list = $query->select($select)->offset($offset)->limit($perpage)->asArray()->all();

        foreach ($list as &$item) {
            //+ 是否有联系人
            $is_has_mail = 0;
            $hasMail = ContactsService()->getMailList($uid,$company_id,'web',$item['domain']);
            if(!empty($hasMail)){
                if(count($hasMail) == 1){
                    $is_has_mail = $hasMail[0]['id'];
                }else{
                    $is_has_mail = '-1';
                }
            }
            $item['is_mail'] = $is_has_mail;
        }

        //+ 下载地址
        $host =Yii::$app->request->hostInfo;
        $downloadUrl = $host.'/api/rank/export/export-web?page='.$page.'&perpage='.$perpage.'&region='.$region.'&tags='.$tags.'&status='.$status.'&search='.$search;

        $data = [
            'total'=> $total,
            'list'=> $list,
            'downloadUrl'=> $downloadUrl,
            'regionArr'=> $this->tagsInit($regionArr,explode(',',$region)),
            'tagsArr'=> $this->tagsInit($tagsArr,explode(',',$tags)),
        ];

        return $data;
    }

    /**
     * 文章榜单
     * @param $uid
     * @param $company_id
     * @param $page
     * @param $perpage
     * @param $media_type
     * @param $type
     * @param $date
     * @param $region
     * @param $tags
     * @param $search
     * @return array|void
     */
    public function getArcRankList($uid,$company_id,$page,$perpage,$media_type,$type,$date,$region,$tags,$search, $emotion)
    {
        $services = new PageAuthService();
        $tag = $services->getTagList('normal');
        //+ 判断该用户是否有权限
        $is_auth = 0;
        if(!empty($tag['media_tag'])){
            foreach ($tag['media_tag'] as $m) {
                if($m['e_name'] == $media_type){
                    $is_auth = 1;
                }
            }
        }
        if($is_auth == 0){
            return jsonErrorReturn('authorityError');
        }

        $week = getWeek(3);
        $month = getMonth(3);
        //$regionArr = $tag['region_tag'];
        //$tagsArr = $tag['tag'];

        $regionArr = !empty($tag['region_tag']) ? $tag['region_tag'] : [];
        $tagsArr = !empty($tag['tag']) ? $tag['tag'] : [];

        //查询最新日期
        $baseEs = new ShudiEsBase();
        $index = Yii::$app->params['shudiEsPrefix'].CompanyService::$company_info['company_index'];
        $baseEs->index($index);
        $baseEs->select(['news_posttime']);
        $baseEs->platformType($media_type);
        //+ 排序
        $baseEs->sort('news_posttime')->from(1)->size(1);
        $res = $baseEs->query();
        if (isset($res['newsList']) && $res['newsList']) {
            $new_date = date('Ymd', strtotime($res['newsList'][0]['news_posttime']));
        }else {
            $new_date = date('Ymd', strtotime('-1 day'));
        }

        if(empty($date)){
            switch ($type) {
                case 'day':
                    $date = $new_date;
                    break;
                case 'week':
                    $date = $week[0];
                    break;
                case 'month':
                    $date = $month[0];
                    break;
                default:
                    $date = '';
                    break;
            }
        }

        //+ 下载地址
        $host =Yii::$app->request->hostInfo;
        $downloadUrl = $host.'/api/rank/export/export-arc?page='.$page.'&perpage='.$perpage.'&media_type='.$media_type.'&type='.$type.'&date='.$date.'&region='.$region.'&tags='.$tags.'&search='.$search.'&emotion='.$emotion;

        if($type == 'day'){
            $start = date('Y-m-d 00:00:00', strtotime($date));
            $end = date('Y-m-d 23:59:59', strtotime($date));
        }elseif($type == 'week'){
            if (strpos($date, '~') !== false) {
                $dateArr = explode('~',$date);
            }else {
                $dateArr = explode('-', $date);
            }
            $start = date('Y-m-d 00:00:00', strtotime($dateArr[0]));
            $end = date('Y-m-d 23:59:59', strtotime($dateArr[1]));
        }else{
            if (strpos($date, '~') !== false) {
                $dateArr = explode('~',$date);
            }else {
                $dateArr = explode('-', $date);
            }
            $start = date('Y-m-d 00:00:00', strtotime($dateArr[0]));
            $end = date('Y-m-d 23:59:59', strtotime($dateArr[1]));
        }

        $baseEs = new ShudiEsBase();
        $index = Yii::$app->params['shudiEsPrefix'].CompanyService::$company_info['company_index'];
        $baseEs->index($index);
        $baseEs->posttime($start,$end);
        $baseEs->select(['news_title','news_url','media_name','news_read_count','news_like_count','news_comment_count','account_area_tags','account_types_tags','platform','news_reposts_count','news_old_like_count','news_emotion','news_digest','news_url','news_posttime','news_position','news_is_origin','news_author','media_id','news_position']);
        $baseEs->platformType($media_type);
        //+ 地区筛选
        if($region){
            $regArr = $this->getRegionArr($region);
            $baseEs->regionTags($regArr);
        }
        //+ 标签筛选
        if($tags) {
            $regArr = $this->getRegionArr($tags, 2);
            $baseEs->tagTags($regArr);
        }
        //+ 排序
        $sort = 'news_read_count';
        if(in_array($media_type,['weibo'])){
            $sort = 'news_comment_count';
        }
        $baseEs->sort($sort);
        //+ 搜索
        if($search){
            $baseEs->keyword([$search],'title');
        }
        //+ 情感
        if ($emotion) {
            $baseEs->emotion($emotion); //文章情感属性
        }
        //+ 分页
        $offset = ($page-1)*$perpage;
        $baseEs->from($offset);
        $baseEs->size($perpage);
        $res = $baseEs->query();

        if($res['numFound'] > 0){
            foreach ($res['newsList'] as $k => $v) {
                $v['news_read_count'] = $v['news_read_count'] ? fort_mat($v['news_read_count']) : 0;
                $v['news_like_count'] = $v['news_like_count'] ? fort_mat($v['news_like_count']) : 0;
                $v['news_comment_count'] = $v['news_comment_count'] ? fort_mat($v['news_comment_count']) : 0;
                $v['news_title'] = $v['news_title'] ? mb_substr($v['news_title'],0,35) : '';
                $v['news_reposts_count'] = isset($v['news_reposts_count']) ? fort_mat($v['news_reposts_count']) : 0;
                //微信 news_old_like_count点赞  news_like_count再看  其它类型 news_like_count点赞
                if ($media_type == 'wx') {
                    $v['news_look_count'] = $v['news_like_count'];
                    $v['news_like_count'] = isset($v['news_old_like_count']) ? fort_mat($v['news_old_like_count']) : 0;
                }
                $v['news_emotion'] = isset($v['news_emotion']) ? $v['news_emotion'] : '';
                unset($v['news_old_like_count']);
                $res['newsList'][$k] = $this->checkData($v);
            }
        }

        $return = [
            'total'=> $res['numFound'],
            'list'=> $res['newsList'],
            'downloadUrl'=> $downloadUrl,
            'week'=> $this->transformTime($week),
            'day'=> date('Y-m-d',strtotime($new_date)),
            'month'=> $this->transformTime($month),
            'regionArr'=> $this->tagsInit($regionArr,explode(',',$region)),
            'tagsArr'=> $this->tagsInit($tagsArr,explode(',',$tags)),
        ];

        return $return;
    }

    // 添加榜单账号数量
    public function appendAccountNum($media_tag)
    {
        foreach ($media_tag as &$item) {
            $item['account_num'] = rand(50, 500);
        }

        return $media_tag;
    }

    /**
     * 获取该公司分组id
     * @param $company_id
     * @return array|CompanyRankGroup|null
     */
    private function getGroupArr($company_id)
    {
        $groupArr = CompanyRankGroup::findOne(['company_id'=>$company_id]);
        if(empty($groupArr)){
            $groupArr = [];
        }
        return $groupArr;
    }

    /**
     * 格式化数据
     * @param $arr
     * @return array|string
     */
    private function checkData($arr)
    {
        if(is_array($arr)){
            foreach($arr as $k=>$v){
                $arr[$k] = $v===null?'--':$v;
            }
            return $arr;
        }else{
            return '';
        }
    }

    /**
     * 地区标签条件集合
     * @param $region
     * @param int $type 1-地区 2-标签
     * @return array
     */
    private function getRegionArr($region,$type=1)
    {
        $regionArr = explode(',', $region);

        $query = $type == 1 ? TagRegion::find() : TagTags::find();

        $regArr = $regionArr;
        foreach ($regionArr as $id) {
            $ids = $query->select('id')
                ->where(['pid'=>$id,'status'=>1])
                ->asArray()->all();
            if(!empty($ids)){
                $ids = array_column($ids,'id');
                foreach ($ids as $v) {
                    $regArr[] = $v;
                    $ids1 = $query->select('id')
                        ->where(['pid' => $v, 'status' => 1])
                        ->asArray()->all();
                    if(!empty($ids1)){
                        $ids1 = array_column($ids1, 'id');
                        foreach ($ids1 as $v1) {
                            $regArr[] = $v1;
                        }
                    }
                }
            }
        }

        return $regArr;
    }

    /**
     * 转换时间
     */
    public function transformTime($date_arr)
    {
        if (!is_array($date_arr) && !$date_arr) {
            return $date_arr;
        }
        foreach ($date_arr as &$v) {
            $v = explode('-', $v);
            $v = date('Y-m-d', strtotime($v[0])) . '~' . date('Y-m-d', strtotime($v[1]));
        }
        unset($v);
        return $date_arr;
    }
}
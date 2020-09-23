<?php

namespace webapi\services;

use webapi\extensions\ShudiEsBase;
use webapi\models\CompanyContentCompliance;
use Yii;
/**
 * @Author:    Peimc<2676932973@qq.com>
 * @Date:      2020/6/16
 */
class ComplianceService
{
    public $params;
    public $baseEs;

    /**
     * 获取标签-地区-媒体 筛选权限
     * @return array
     */
    public function getTagsList(){
        $services = new PageAuthService();
        $tag = $services->getTagList('tree');
        $tag['tag'] = $this->tagsInit($tag['tag'],[]);
        $tag['region_tag'] = $this->tagsInit($tag['region_tag'],[]);
        return $tag;
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
     * 获取疑似违规内容文章列表
     * @return mixed
     */
    public function getMediaList(){
        $this->initParams();
        $this->buildEsCondition();
        $return = [
            "list"=>[],
            "count"=>0,
            "page"=>$this->params['page']
        ];
        $page = intval($this->params['page']);
        $limit = intval($this->params['prepage']);
        $limit = $limit > 100 ? 100 : $limit;
        $offset = ($page - 1) * $limit;
        $total =$weigui = $this->baseEs->groupCount("media_name")->query();
        $media_arr = $this->baseEs->group("media_name",null,$total)->query();
        $media_all = [];
        foreach ($media_arr as $k=>$v){
            $media_all[] = [
                'mediaName' => $k,
                'total'     => $v,
            ];
        }
        if(!empty($media_all)) {
            $media_return = array_slice($media_all, $offset, $limit);
            $num=0;
            foreach ($media_return as &$item) {
                $this->buildEsCondition();
                $hitNum = $this->baseEs->where('media_name', $item['mediaName'])
                    ->exists('text_compliance_type')->count()->query();

                $text_compliance_type = $this->baseEs->where('media_name', $item['mediaName'])
                    ->group('text_compliance_type')->query();
                $types = [];
                foreach ($text_compliance_type as $tk => $tv) {
                    $types[] = $tk;
                }
                if($this->params['platformType']!='all'){
                    $platform = $this->params['platformType'];
                }else{
                    $Es = new ShudiEsBase();
                    $index = Yii::$app->params['shudiEsPrefix'].CompanyService::$company_info['company_index'];
                    $Es->index($index);
                    $arc = $Es->size(1)->where('media_name', $item['mediaName'])->query();
                    $platform = isset($arc['newsList'][0]['platform'])?$arc['newsList'][0]['platform']:'other';
                }
                $item['accountAvatar'] = getMediaIcon($platform);
                $item['hitNum'] = $hitNum;
                $item['types'] = $types;
                if($hitNum>0){
                    $num++;
                }
            }

            $return["list"]=$media_return;
            $return["count"]=$total;

        }
        return $return;
    }

    /**
     * 获取饼图及统计数据
     */
    public function getPieData(){
        $this->initParams();
        $this->buildEsCondition();
        $compliance_type = ['广告禁用' => '', '新闻禁用' => '', '错词检测' => ''];
        if($this->params['match']==1){
            $count = $this->baseEs->groupCount("media_name")->query();
            $weigui = $this->baseEs->whereNot('text_compliance_keywords','{}')
                ->groupCount("media_name")->query();
            $percent = $count>0?(round($weigui/$count,4)*100)."%":"0.00";
            $data = $this->baseEs->groupGroup('text_compliance_type','media_name', $count, $count)->query();
            $bin_ = [];
            foreach ($compliance_type as $k=>$v){
                $bin_[] = [
                    'name'=>$k,
                    'value'=>isset($data[$k]) ? count($data[$k]) : 0,
                ];
            }
        }else{
            $count = $this->baseEs->count()->query();
            $weigui = $this->baseEs->whereNot('text_compliance_keywords','{}')
                ->count()->query();
            $percent = $count>0?(round($weigui/$count,4)*100)."%":"0.00";
            $data = $this->baseEs->group('text_compliance_type')->query();
            $bin_ = [];
            foreach ($compliance_type as $k=>$v){
                $bin_[] = [
                    'name'=>$k,
                    'value'=>isset($data[$k]) ? $data[$k] : 0,
                ];
            }
        }

        return $return = [
            'total'=>$count,
            'hitNum'=>$weigui,
            'percent'=>$percent,
            "pieData"=>$bin_
        ];
    }

    /**
     * 获取疑似违规内容文章列表
     * @return mixed
     */
    public function getArcList(){
        $this->initParams();
        $this->buildEsCondition();
        $arc = $this->baseEs->whereNot('text_compliance_keywords','{}')->query();
        $arc['list'] = camelCaseToSwitchData($arc['newsList']);
        $arc['count'] = $arc['numFound'];
        unset($arc['newsList']);
        foreach ($arc['list'] as &$item){
            $item['accountAvatar'] = getMediaIcon($item["platform"]);
        }
        return $arc;
    }

    /**
     * 获取疑文章详情
     * @return mixed
     */
    public function getArcDetail($newsUuid){
        $es = new ShudiEsBase();
        $index = Yii::$app->params['shudiEsPrefix'].CompanyService::$company_info['company_index'];
        $arc =$es->index($index)->where('news_uuid',$newsUuid)->appendFields(['news_local_url','text_compliance_keywords','text_compliance_type'])->query();
        $return = [];
        if(!empty($arc['newsList'][0])){
            $return = $arc['newsList'][0];
            $return['text_compliance_keywords'] = json_decode($return['text_compliance_keywords'],true);
            $return['content'] = @file_get_contents(Yii::$app->params['ossDomain'].$return['news_local_url']);
            $return['wrong_keywords'] = $this->formatKeywords($return['content'], $return['text_compliance_keywords']);
            $return['text_compliance_keywords'] = $this->formatArr($return['text_compliance_keywords']);
            if ($return['platform'] == 'wx') $return['content'] = str_replace("data-src", "src", $return['content']);
        }
        return $return;
    }


    public function initParams(){
        $params['prepage'] = Yii::$app->request->get('prepage','10');
        $params['page'] = Yii::$app->request->get('page','1');
        $params['platformType'] = Yii::$app->request->get('platformType','all');
        $params['mediaName'] = Yii::$app->request->get('mediaName','');
        $params['areaTag'] = Yii::$app->request->get('area','');
        $params['typeTag'] = Yii::$app->request->get('type','');
        $params['emotion'] = Yii::$app->request->get('emotion','');
        $params['startDate'] = Yii::$app->request->get('startDate',date("Y-m-d H:i:s",strtotime("-6 days")));
        $params['endDate'] = Yii::$app->request->get('endDate',date("Y-m-d H:i:s"));
        $params['keywords'] = Yii::$app->request->get('keywords','');
        $params['match'] = Yii::$app->request->get('match',1);
        $params['textCensorType'] = Yii::$app->request->get('textCensorType', '');
        return $this->params = $params;
    }

    protected function buildEsCondition(){
        $baseEs = new ShudiEsBase();
        $page = intval($this->params['page']);
        $limit = intval($this->params['prepage']);
        $limit = $limit > 100 ? 100 : $limit;
        $offset = ($page - 1) * $limit;
        $index = Yii::$app->params['shudiEsPrefix'].CompanyService::$company_info['company_index'];
       // $baseEs->index("new_sdxt_qingbo_test");
        $baseEs->index($index);
        $baseEs->from($offset);
        $baseEs->size($limit);
        if(!empty($this->params['platformType'])){
            if($this->params['platformType']=='all'){
                $media_tag = $this->getTagsList()['media_tag'];
                $platforms = [];
                foreach ($media_tag as $value){
                    $platforms[] = $value['e_name'];
                }
                $baseEs->platformType($platforms);
            }else{
                $baseEs->platformType($this->params['platformType']);
            }

        }
        if(!empty($this->params['mediaName'])){
            $baseEs->mediaName($this->params['mediaName']);
        }
        if(!empty($this->params['areaTag'])){
            $baseEs->regionTags($this->params['areaTag']);
        }
        if(!empty($this->params['typeTag'])){
            $baseEs->tagTags($this->params['typeTag']);
        }
        if(!empty($this->params['emotion'])){
            $baseEs->emotion($this->params['emotion']);
        }
        if(!empty($this->params['startDate']) || !empty($this->params['endDate'])){
            $baseEs->posttime($this->params['startDate'],$this->params['endDate']);
        }
        if(!empty($this->params['keywords'])){
            if($this->params['match']==1){
                $baseEs->keywordField([$this->params['keywords']], 'media_name.row');
            }else {
                $baseEs->keywordField([$this->params['keywords']], 'news_title');
            }
        }
        if(!empty($this->params['textCensorType'])) {
            $baseEs->keywordField([$this->params['textCensorType']], 'text_compliance_type');
        }
        $baseEs->whereNot('media_name','');
        $baseEs->appendFields(['text_compliance_keywords','text_compliance_type']);
        return $this->baseEs = $baseEs;
    }

    /**
     * @info 返回错词及出现次数的数组
     * @param $content
     * @param $keywords
     * @return array
     */
    protected function formatKeywords($content, $keywords)
    {
        if (empty($keywords['错词检测'])) return [];

        $wrong_keywords = $keywords['错词检测'];

        $wrong_keywords_arr = explode(',', $wrong_keywords);
        $new_keywords = [];
        foreach ($wrong_keywords_arr as $item) {
            if (strpos($item, '|') === false) { // 该错词都要高亮
                $single_row = ['name' => $item, 'value' => -1];
            } else {
                list($name, $bytes) = explode('|', $item);
                $value = $this->strPosNum($content, $name, $bytes);
                $single_row = compact('name', 'value');
            }
            $new_keywords[] = $single_row;
        }

        return $new_keywords;
    }

    /**
     * 根据错词字节数返回在正文中的次数
     * @param $str
     * @param $find
     * @param $begin_pos //到find为止前一个字符的总字节数
     * @return bool|int
     */
    protected function strPosNum($str, $find, $begin_pos)
    {
//        $str = strip_tags($str);
        $str = htmlspecialchars_decode($str);
        $str = preg_replace('/<\/?[^>]+>/','',$str);
        $str = str_replace([" ","　","\t","\n","\r"], ["","","","",""], $str);

        $new_str = mb_convert_encoding(substr(mb_convert_encoding($str, 'GBK', 'UTF-8'), 0, $begin_pos), 'UTF-8', 'GBK');

        return substr_count($new_str, $find) + 1;
    }

    /**
     * @info 去掉 | 和字节数
     * @param $keywords_arr
     * @return array
     */
    protected function formatArr($keywords_arr)
    {
        if (empty($keywords_arr)) return $keywords_arr;

        $new_arr = [];
        foreach ($keywords_arr as $key => $item) {
            $items = explode(',', $item);
            $name = '';
            foreach ($items as $row) {
                if (strpos($row, '|') !== false) {
                    $name .= explode('|', $row)[0] . ',';
                } else {
                    $name .= $row . ',';
                }
            }
            $new_arr[$key] = trim($name, ',');
        }

        return $new_arr;
    }

    // 获取账号列表
    public function getAccountList($uid, $company_id, $type, $search)
    {
        $services = new PageAuthService();
        $tag = $services->getTagList();
        if ($type != 'all') {
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
        $contact_service = new ContactsService();
        if ($type == 'all') {
            $typeArr = !empty($tag['media_tag']) ? array_column($tag['media_tag'], 'e_name') : [];
            $list = [];
            if ($typeArr) {
                foreach ($typeArr as $t) {
                    $ll = $contact_service->getTypeAccList($t,$company_id,'');
                    $list = array_merge($list, $ll);
                }
            }
        } else {
            $list = $contact_service->getTypeAccList($type,$company_id,'');
        }

        // 拆分为已加入和未加入
        $check_list = [];
        $censor_model = CompanyContentCompliance::find()->where(['company_id' => $company_id]);
        if ($type != 'all') {
            $censor_model->andWhere(['platform' => $type]);
        }
        $exist_list = $censor_model->asArray()->all();
        if (!empty($list) && !empty($exist_list)) {
            foreach ($exist_list as $item) {
                foreach ($list as $k => $v) {
                    if(($v['account_id'] == $item['account_id']) && ($item['platform'] == $v['type'])) {
                        $check_list[] = $v;
                        unset($list[$k]);
                    }
                }
            }
        }

        //+ 搜索（只搜索为关联的账号）
        if ($search && $list) {
            foreach ($list as $k => $v) {
                if (strstr($v['nickname'], $search) === false) {
                    unset($list[$k]);
                }
            }
        }
        $has_num = $censor_model->count();

        $return = [
            'no_list'=> array_values($list),
            'check_list'=> $check_list,
            'account_all_num' => 500,
            'account_has_num' => $has_num,
        ];

        return $return;
    }

    // 保存账号列表 先删除账号 再插入
    public function saveAccountList($company_id, $comapny_index, $accounts)
    {
        $transaction = \Yii::$app->db->beginTransaction();

        try {
            $del_res = CompanyContentCompliance::deleteAll(['company_id' => $company_id]);
            if ($del_res === false) {
                $transaction->rollBack();
                return false;
            }

            if (!empty($accounts)) {
                $new_data = [];
                foreach ($accounts as $item) {
                    $new_data[] = [
                        'platform' => $item['type'],
                        'account_id' => $item['account_id'],
                        'company_id' => $company_id,
                        'company_index' => $comapny_index
                    ];
                }

                $insert_res = Yii::$app->db->createCommand()->batchInsert(CompanyContentCompliance::tableName(), array_keys(current($new_data)), $new_data)->execute();
                if ($insert_res === false) {
                    $transaction->rollBack();
                    return false;
                }
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }

        return '保存成功!';
    }

    public function appendAccountNum(&$media_tag)
    {
        foreach ($media_tag as &$item) {
            $item['account_num'] = rand(50, 500);
        }
    }
}
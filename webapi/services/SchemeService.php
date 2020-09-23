<?php

namespace webapi\services;

use webapi\models\Scheme;
use Yii;

/**
 * @Author:    Peimc<2676932973@qq.com>
 * @Date:      2020/6/9
 */
class SchemeService
{
    /**
     * @param $name
     * @return array
     */
    public function getSchemeList($params) {
        $offset = ($params["page"]-1) * $params["prepage"];
        //读取该用户的所有的专题
        $uid = UserService::$uid;
        $base = Scheme::find()
            ->alias("s")
            ->select(["s.id", "s.name","u.user_name","s.create_time as createTime" ])
            ->leftJoin("sys_user as u","u.id=s.user_id")
            ->andWhere(["s.user_id" => $uid, "s.status" => Scheme::STATUS_NORMAL])
            ->orderBy(['s.create_time'=>SORT_DESC]);
        if ($params["name"]) $base =  $base->andWhere(["like", "name", $params["name"]]);
        $total = $base->count();
        $page = $params["page"];
        $prepage = $params["prepage"];
        $list =$base ->asArray() ->offset($offset)->limit($params["prepage"]) ->all();
        $return = [];
        if (!$list) return $return;
        return compact("list","total","page","prepage");
    }

    protected $skipCheck = false;
    protected $schemeInfo;
    /**
     * @param $data
     * @return array
     * 创建/更新专题数据
     */
    public function saveScheme($data) {
        $uid = UserService::$uid;
        $company_id = CompanyService::$company_id;
        $id = isset($data['id']) && $data['id'] ? (int)$data['id'] : 0;
        $begin_date = isset($data['begin_date'])?$data['begin_date']:date("Y-m-d");
        $end_date = isset($data['end_date'])?$data['end_date']:date("Y-m-d",strtotime("+6 days"));
        //把分析词之间的逗号改成+号
        $totalWords = 0;
        foreach ($data['keywords_final'] as $key => $keyword) {
            $keyword = $this->fitterKeywords($keyword);
            if (!$keyword || empty($keyword) ) {
                unset( $data['keywords_final'][$key] );
                continue;
            }
            //单组关键词不能超过10个
            if (count($keyword) > 10)  return ["msg" => "单个分析词不能超过10个"];
            $data['keywords_init'][$key]  = implode(",", $keyword);
            $data['keywords_final'][$key] = implode("+", $keyword);
            $totalWords = $totalWords + count($keyword);
        }
        if (count( $data['keywords_final'] ) > 10)  return ["msg" => "分析词组不能超过10个"];
        if (!$data['keywords_final'])  return ["msg" => "分析词不能为空"];
        if ($totalWords > 100 ) return ['msg'=>"单专题分析词数量不能超过100！"];
        $keywords_final = $data['keywords_final'];
        $keywords_init  = $data['keywords_init'];
        $keywords_exclude = isset($data['keywords_exclude'])?$data['keywords_exclude']:'';
        $keywords_exclude = $this->fitterKeywords($keywords_exclude);//过滤之后转成数组
        if (count($keywords_exclude) > 50 )  return ["msg" => "排除词不能超过50个"];
        //方案总数判断
        if ($id) {
            $model = Scheme::findOne(['user_id' => $uid, "id" => $id, "status" => 1]);
            if (!$model) return ["msg" => "该专题不存在！"];
        } else {
            $model = new Scheme();
            $model->create_time = date("Y-m-d H:i:s");
        }
        $model->company_id = $company_id;
        $model->user_id = $uid;
        $model->keywords_final = json_encode($keywords_final,JSON_UNESCAPED_UNICODE);
        $model->keywords_count = $totalWords;
        $model->keywords_exclude = !empty($keywords_exclude)?implode(",",$keywords_exclude):'';
        $model->keywords_init  =   json_encode($keywords_init,JSON_UNESCAPED_UNICODE);
        $model->name = $data['name'];
        $model->begin_date = $begin_date;
        $model->end_date = $end_date;
        $model->scheme_description = !empty($data['scheme_description'])?$data['scheme_description']:'';
        $model->update_time =  date("Y-m-d H:i:s");
        $result = $model->save();
        if ($result) {
            $content = $id > 0 ? ('专题监测:修改专题【'.$model->name.'】'):('专题检测:新增专题【'.$model->name.'】');
            //更新日志
            Yii::$app ->service->LogService->saveSysLog($content);
            return $model->toArray();
        }else{
            return ["msg" =>$model->errors];
        }
    }

    /**
     * @param $id
     * @return bool
     */
    public function deleteScheme($id) {
        $uid = UserService::$uid;
        $model = Scheme::findOne(['user_id' => $uid, "id" => (int)$id, "status" => Scheme::STATUS_NORMAL]);
        if (!$model) return false;
        $model->status = Scheme::STATUS_FAIL;
        $model->save();
        $content = '专题检测:删除专题【'.$model->name.'】';
        \Yii::$app->service->LogService->saveSysLog($content);
        return true;
    }

    /**
     * @param $id
     * @return array|bool
     */
    public function getSchemeInfo($id,$use = 1 ) {
        if ( isset($this->schemeInfo[$id]) ) return $this->schemeInfo[$id];
        $uid = UserService::$uid;
        if ($this->skipCheck){//包含删除的
            $model = Scheme::findOne(['id' => $id]);
        }else{
            $model = Scheme::findOne(['id' => $id, "user_id" => $uid, "status" => Scheme::STATUS_NORMAL]);
        }
        if (!$model) return false;

        $keyFinal = json_decode($model->keywords_final, true);
        sort($keyFinal);//处理一下数据
        $model->keywords_final = $keyFinal;
        if ($use && $model->keywords_exclude){
            $model->keywords_exclude = explode(",",$model->keywords_exclude);
        }
        $data = [];
        $data["id"] = $model->id;
        $data["name"] = $model->name;
        $data["beginDate"] = $model->begin_date;
        $data["endDate"] = $model->end_date;
        $data["schemeDescription"] =  $model->scheme_description ? $model->scheme_description : "";
        $data["createTime"] =  $model->create_time ? $model->create_time : "";
        $data["keywordsFinal"] =$keyFinal;
        $data["keywordsExclude"] =$model->keywords_exclude ? $model->keywords_exclude : "";
        $data["update_time"] = $model->update_time ? $model->update_time : "";;
        return $data;
    }


    /**
     * 过滤文字
     */
    protected function fitterKeywords($keywords) {
        if (!$keywords) return [];
        $replaceStr = str_replace(["：",":",";","；"," "],"",$keywords);
        $replaceStr = str_replace(["，",".","、"],",",$replaceStr);
        $arr = explode(",",$replaceStr);
        $res = [];
        if (is_array($arr) && !empty($arr)) {
            $arr = array_filter($arr);
            foreach ($arr as $k => $v) {
                $str = htmlspecialchars_decode($v);
                if (empty($v)) continue;
                array_push($res, $str);
            }
        }
        return $res;
    }
}
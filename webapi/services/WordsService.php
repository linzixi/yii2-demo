<?php

namespace webapi\services;

use Matrix\Exception;
use webapi\models\TextCensorKeywords;
use webapi\models\TextComplianceKeywords;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * @Author:    Peimc<2676932973@qq.com>
 * @Date:      2020/6/9
 */
class WordsService
{
    public function getInfo($type,$company_id){
        if($type==2){
            $data = Yii::$app->params['text_compliance_keywords_type'];
            $res = TextComplianceKeywords::find()->where(['company_id'=>$company_id])->asArray()->all();
        }else{
            $data = Yii::$app->params['text_censor_keywords_type'];
            $res = TextCensorKeywords::find()->where(['company_id'=>$company_id])->asArray()->all();
        }
        $res = ArrayHelper::index($res, 'type');
        foreach ($data as $key => $v) {
            $data[$key] = [
                'keywords' => !empty($res[$key]['keywords']) ? $res[$key]['keywords'] : "",
                'ambiguity_keywords' => !empty($res[$key]['ambiguity_keywords']) ? $res[$key]['ambiguity_keywords'] : "",
            ];
        }

        return $data;
    }

    public function saveKeywords($params){
        $company_id    = CompanyService::$company_id;
        $company_index = CompanyService::$company_info['company_index'];
        if($params['match']==2){
            $model = new TextComplianceKeywords();
            $types  = Yii::$app->params['text_compliance_keywords_type'];
        }else{
            $model = new TextCensorKeywords();
            $types  = Yii::$app->params['text_censor_keywords_type'];;
        }
        if(!in_array($params['type'],array_keys($types))){ // 验证类型是否符合定义的类型
            $err_msg = '词库类型不正确';
            return ["msg" => $err_msg];
        }

        $transaction = Yii::$app -> db -> beginTransaction();
        try {
            $find = $model->findOne(['company_id' => $company_id,'type'=>$params['type']]);
            if ($find) {
                $find->keywords = $params['keywords'];
                $find->ambiguity_keywords = $params['ambiguity_keywords'];
                $res = $find->save();
            } else {
                $model->company_id = $company_id;
                $model->company_index = $company_index;
                $model->type = $params['type'];
                $model->keywords = $params['keywords'];
                $model->ambiguity_keywords = $params['ambiguity_keywords'];
                $res = $model->save();
            }
            if(!$res) {
                $err_msg = $model->errors;
                $transaction -> rollBack();
                return ["msg" => $err_msg];
            }
        }catch (\Exception $e){
            $err_msg = $e->getMessage();
            $transaction -> rollBack();
            return ["msg" => $err_msg];
        }
        $transaction->commit();
        $name = $params['type']==1?'内容检查':'合规检测';
        $content = '补充词库:'.$name.'词库设置';
        \Yii::$app->service->LogService->saveSysLog($content);
        return $this->getInfo($params['match'],$company_id);
    }
}
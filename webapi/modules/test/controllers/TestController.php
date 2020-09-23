<?php
namespace webapi\modules\test\controllers;

use webapi\extensions\OpenController;
use Yii;
class TestController extends OpenController
{
    public $enableCsrfValidation = false;   //取消POST校验


    public function actionTest() {
        $token = Yii::$app->request->get("token");
        return $this->renderPartial("test",["token"=>$token]);
    }
}
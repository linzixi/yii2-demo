<?php
/**
 * User: Chen Bin
 * Date: 2020/6/16
 * Time: 9:49
 */

namespace webapi\modules\front\controllers;

use Yii;
use webapi\extensions\FrontController;

//千万要继承FrontController！！！！！
class TestController extends FrontController
{
    public $enableCsrfValidation = false;
    public $companyFlag = 'Testfront';


    public function actionTest()
    {
        return $this->renderPartial('test');
    }




}
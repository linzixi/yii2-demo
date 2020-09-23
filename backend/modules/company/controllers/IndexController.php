<?php
namespace backend\modules\company\controllers;

use backend\extensions\BaseController;
use Yii;

/**
 * Site controller
 */
class IndexController extends BaseController
{
    public $enableCsrfValidation = false;

    /**
     * Displays homepage.
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index', []);
    }

    /**
     * Displays login
     * @return mixed
     */
    public function actionLogin()
    {
        if(Yii::$app->request->isAjax){
            $username = Yii::$app->request->post('username');
            $password = Yii::$app->request->post('password');
            $return = Yii::$app->backService->UserService->login($username,$password);
            if (isset($return['msg'])) {
                return jsonErrorReturn("fail",$return['msg']);
            }
            return jsonSuccessReturn(10000,'登录成功');
        }else{
            return $this->renderPartial('login');
        }
    }

    /**
     *	查找当前企业
     */
    private function company($username){

    }

    /**
     *	错误返回
     */
    private function pageback($str, $back = 'login'){
        header("refresh:3;url=$back");

        echo $str . '，...<br>3秒后自动返回。';
        exit;
    }

    /**
     *	退出登录
     */
    public function actionLogout()
    {
        //	获取登录状态
        $isLogin = Yii::$app->session->get('user_id');
        if (!$isLogin) {
            return jsonErrorReturn("fail",'当前未登录');
        }
        Yii::$app->session->remove('user_id');
        return jsonSuccessReturn(10000,'操作成功');
    }
}

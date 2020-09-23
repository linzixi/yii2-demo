<?php

namespace backend\extensions;

use backend\services\ApiService;
use core\models\Company;
use core\models\User;
use Jasny\ValidationResult;
use Jasny\SSO;

class SsoServer extends SSO\Server{

    protected $userInfo;

    /**
     * Registered brokers
     * @var array
     */
    private static $brokers = [
        'frontend' => ['secret'=>'123456'],
        'custom' => ['secret'=>'123456']
    ];

    /**
     * Get the API secret of a broker and other info
     *
     * @param string $brokerId
     * @return array
     */
    protected function getBrokerInfo($brokerId)
    {
        return isset(self::$brokers[$brokerId]) ? self::$brokers[$brokerId] : null;
    }

    /**
     * Authenticate using user credentials
     *
     * @param string $username
     * @param string $password
     * @return ValidationResult
     */
    protected function authenticate($username, $password)
    {
        if (!isset($username)) {
            return ValidationResult::error("username isn't set");
        }
        if (!isset($password)) {
            return ValidationResult::error("password isn't set");
        }
        $company =  (new \yii\db\Query())
            ->select('*')
            ->from('company')
            ->where(['username'=>$username])
            ->one();
        if (!$company) {
           $msg = "公司信息不存在";
            return ValidationResult::error($msg);
        }
        //如果auto_sign匹配到了则代表是后台自动登陆
        if ( $company['auto_sign'] != $password &&  encryptPassword($password) != $company['password']) {
            $msg = "密码不正确";
            return ValidationResult::error($msg);
        }

        $this->userInfo = $company;
        return ValidationResult::success();
    }

    /**
     * Authenticate
     */
    public function login()
    {
        $this->startBrokerSession();

        if (empty($_POST['username'])) $this->fail("No username specified", 400);
        if (empty($_POST['password'])) $this->fail("No password specified", 400);

        $validation = $this->authenticate($_POST['username'], $_POST['password']);

        if ($validation->failed()) {
            return $this->fail($validation->getError(), 200);
        }

        $this->setSessionData('sso_user', $this->userInfo['id'] );
        $this->userInfo();
    }
    /**
     * Get the user information
     *
     * @return array
     */
    protected function getUserInfo($user_id)
    {
        $company = Company::find()->where(['id'=>$user_id])->asArray()->one();
        return $company;
    }

    public function userInfo() {
        $this->startBrokerSession();
        $user = null;
        $user_id = $this->getSessionData('sso_user');
        if ($user_id) {
            $user = $this->getUserInfo($user_id);
            if (!$user) return $this->fail("User not found", 200); // Shouldn't happen
        }
//        @file_put_contents("show.txt", $user_id);
        $this->returnJson($user);
    }

    public function userList(){
        $this->startBrokerSession();
        $user = null;
        $user_id = $this->getSessionData('sso_user');
        if (!$user_id) return $this->fail("User not found", 200); // Shouldn't happen
        $data = \Yii::$app->request->post();
        $data['company_id'] = $user_id ;
        $service = new ApiService();
        $res = $service->getUserList($data);
//        @file_put_contents("show.txt",$user_id);
        $this->returnJson($res);
    }

    /*
     * 返回数据格式
     */
    public function returnJson($res){
        header('Content-type: application/json; charset=UTF-8');
        echo json_encode($res);die();
    }
}
<?php

namespace webapi\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error','swagger','api','test'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index','swagger','api','test'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionSwagger()
    {
        if (  YII_ENV != "pro"  ){
            $projectRoot = Yii::getAlias('@webapi');
            $swagger = \OpenApi\scan($projectRoot."/modules");
            $swagger = json_encode($swagger) ;
            echo $swagger;exit;
        }
        return $this->redirect("/");
    }

    public function actionApi(){
        if (  YII_ENV != "pro" ){
            $host =Yii::$app->request->hostInfo;
            $host = $host."/api";
            return $this->renderPartial('api',['host'=>$host]);
        }
        return $this->redirect("/");
    }
    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionError()
    {
        echo "无权访问！";
        die;
    }

    /**
     * Login action.
     *
     * @return string
     */
}

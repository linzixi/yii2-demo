<?php

namespace backend\extensions;

use Yii;

class CrossController extends \yii\rest\Controller
{

    public function init()
    {
         header("Access-Control-Allow-Origin: *");
         header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
         header('Access-Control-Allow-Credentials: true');
         header("Access-Control-Allow-Headers: Authorization,Content-Type,Accept,Origin,User-Agent,DNT,Cache-Control,X-Mx-ReqToken,Keep-Alive,X-Requested-With,If-Modified-Since");
         if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
             \Yii::$app->response->setStatusCode(204);
             \Yii::$app->end(0);
         }
    }


}

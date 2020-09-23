<?php

namespace custom\modules\back\controllers;

use core\extensions\Oss;
use custom\extensions\BackController;
use custom\services\ApiService;
use custom\services\XingeService;
use Yii;

/**
 * Site controller
 */
class XingeController extends BackController {
    public $enableCsrfValidation = false;

    public $companyFlag = "Xinge";//加载auth配置参数

    /**
     *  专题列表
     * @return mixed
     */
    public function actionIndex() {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            $data = $request->post();
            $xingeService = new XingeService();
            $list = $xingeService->getList($data);
            return jsonSuccessReturn($list);
        } else {
            $data = [];
            return $this->renderPartial('index', $data);
        }
    }

    /**
     * 机会列表
     * @return mixed
     */
    public function actionChance() {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            $data = $request->post();
            $xingeService = new XingeService();
            $list = $xingeService->getChanceList($data);
            return jsonSuccessReturn($list);
        } else {
            $data = [];
            return $this->renderPartial('chance', $data);
        }
    }

    /**
     *  新增/编辑主题
     */
    public function actionAddTopic() {
        $request = Yii::$app->request;
        $data = $request->post();
        $oss = new Oss();
        $files = $oss->getImgName($_FILES);
        //图片上传的回调函数
        $callback = function () use ($oss, $files, $data) {
            $return = [];
            $img_list = ['img'];
            $new_data = [];
            foreach ($img_list as $v) {
                if (isset($data[$v])) {
                    $new_data[$v] = $data[$v];
                }
            }
            $oss = new Oss();
            foreach ($files as $k => $file) {
                $res = $oss->uploadImg($file['oss_name'], $file['tmp_name']);
                if ($res['status']) {
                    $return[$k] = $res['url'];
                }
            }
            return array_merge($new_data, $return);
        };
        $xingeService = new XingeService();
        $xingeService->callbackFunction = $callback;
        $res = $xingeService->editTopic($data);
        if ($res['success']) {
            return jsonSuccessReturn(10000, $res['msg']);
        } else {
            return jsonErrorReturn('fail', $res['msg']);
        }
    }

    /**
     *  新增/编辑机会
     */
    public function actionAddChance() {
        $request = Yii::$app->request;
        $data = $request->post();
        $xingeService = new XingeService();
        $res = $xingeService->editChance($data);
        if ($res['success']) {
            return jsonSuccessReturn(10000, $res['msg']);
        } else {
            return jsonErrorReturn('fail', $res['msg']);
        }
    }

    /**
     * 配置可见用户
     */
    public function actionSetVisible() {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            $data = $request->post();
            $xingeService = new XingeService();
            $data['company_id'] = $xingeService->getCompanyId();
            $list = $xingeService->visibleUser($data);
            return jsonSuccessReturn($list);
        } else {
            $data = [];
            return $this->renderPartial('visible', $data);
        }

    }

    /**
     * 设置用户可见/不可见
     * @return array
     */
    public function actionSwitch() {
        $request = Yii::$app->request;
        $data = $request->post();

        $xingeService = new XingeService();
        $res = $xingeService->switchUser($data);
        if ($res['success']) {
            return jsonSuccessReturn(10000, $res['msg']);
        } else {
            return jsonErrorReturn('fail', $res['msg']);
        }
    }

    /**
     *  删除专题/机会
     */
    public function actionDelete() {
        $request = Yii::$app->request;
        $data = $request->post();
        $xingeService = new XingeService();
        $res = $xingeService->deleteChance($data);
        if ($res['success']) {
            return jsonSuccessReturn(10000, $res['msg']);
        } else {
            return jsonErrorReturn('fail', $res['msg']);
        }
    }

    public function actionUserList() {

        $userList = Yii::$app->globalService->UserService->getMemberList();

        $return = [];
        foreach ($userList as $user) {
            $return_['username'] = $user['nickname'] . "(" . $user['tel'] . ")";
            $return_['id'] = $user['id'];
            $return_['checkStatus'] = false;
            $return[] = $return_;
        }
        return jsonSuccessReturn($return);
    }

    /**
     * 批量设置机会权限
     * @return \yii\console\Response|\yii\web\Response
     */
    public function actionAddAuth() {
        $userIds = Yii::$app->request->post("userIds");
        $chanceIds = Yii::$app->request->post("chanceIds");
        if (Yii::$app->request->isAjax) {
            $xingeService = new XingeService();
            if (!$chanceIds || !$userIds) {
                return jsonErrorReturn('fail', "请选择用户和日历/机会");
            }
            $data['chance_ids'] = implode(",", $chanceIds);
            $data['user_ids'] = implode(",", $userIds);
            $res = $xingeService->batchVisible($data);
            if ($res['success']) {
                return jsonSuccessReturn(10000, $res['msg']);
            } else {
                return jsonErrorReturn('fail', $res['msg']);
            }
        }

    }

    public function actionTest() {
        $xingeService = new XingeService();
        $data['chance_ids'] = "1,2,3,4";
        $data['user_ids'] = "1,2,3,4";
        $res = $xingeService->batchVisible($data);
    }
}

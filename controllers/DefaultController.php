<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace backend\gii\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\base\ViewContextInterface;
use yii\gii\controllers\DefaultController as GiiController;

/**
 * @inheritdoc
 */
class DefaultController extends GiiController implements ViewContextInterface
{
    /**
     * @inheritdoc
     */
    public $layout = '@backend/views/layouts/main.php';


    /**
     * @inheritdoc
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @inheritdoc
     */
    public function getViewPath()
    {
        return Yii::getAlias('@backend/gii/views/default');
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'diff'],
                        'allow' => true,
                        'roles' => ['dev'],
                    ],
                ],
            ],
        ];
    }
}

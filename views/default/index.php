<?php
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $generators \yii\gii\Generator[] */
/* @var $content string */

$generators = Yii::$app->controller->module->generators;
$this->title = 'Welcome to Gii';
$this->params['title_desc'] = 'a magical tool that can write code for you';
$this->params['breadcrumbs'][] = 'Gii';
?>
<div class="gii default-index">
    <div class="row">
        <?php $classes = ['primary', 'success', 'warning', 'danger', 'info', 'default']; ?>
        <?php $index = 0; ?>
        <?php foreach ($generators as $id => $generator): ?>
        <?php if ($index > 0 && $index % 4 === 0): ?>
    </div>
    <div class="row">
        <?php endif; ?>
        <div class="col-md-3">
            <div class="box box-<?= $classes[$index % count($classes)] ?>">
                <div class="box-header with-border">
                    <i class="fa fa-code"></i>
                    <h3 class="box-title"><?= Html::encode($generator->getName()) ?></h3>
                </div>
                <div class="box-body">
                    <p><?= $generator->getDescription() ?></p>
                    <p><?= Html::a('Start »', ['default/view', 'id' => $id], ['class' => 'btn btn-default']) ?></p>
                </div>
            </div>
        </div>
            <?php $index++; ?>
        <?php endforeach; ?>
    </div>

    <p><a class="btn btn-success" href="http://www.yiiframework.com/extensions/?tag=gii">Get More Generators</a></p>

</div>

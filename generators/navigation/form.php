<?php
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator yii\gii\generators\form\Generator */

echo $form->field($generator, 'ns');
echo $form->field($generator, 'defaultController');
echo $form->field($generator, 'layoutsPath');
echo $form->field($generator, 'enableI18N')->checkbox();
echo $form->field($generator, 'messageCategory');

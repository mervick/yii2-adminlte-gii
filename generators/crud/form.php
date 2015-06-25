<?php
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator yii\gii\generators\crud\Generator */

echo $form->field($generator, 'modelClass');
echo $form->field($generator, 'searchModelClass');
echo $form->field($generator, 'controllerClass');
echo $form->field($generator, 'viewPath');
echo $form->field($generator, 'baseControllerClass');
echo $form->field($generator, 'generateRelationsFields')->checkbox();
echo $form->field($generator, 'db');
echo $form->field($generator, 'enableI18N')->checkbox();
echo $form->field($generator, 'messageCategory');
echo $form->field($generator, 'addingI18NStrings')->checkbox();

$js = <<<JS
    // hide `adding I18N strings` field when I18N is disabled
    $('form #generator-enablei18n').change(function () {
        $('form .field-generator-addingi18nstrings').toggle($(this).is(':checked'));
    });
    // hide `db` field when `generateRelationsFields` is disabled
    $('form #generator-generaterelationsfields').change(function () {
        $('form .field-generator-db').toggle($(this).is(':checked'));
    });
    setTimeout(function() {
        $('form .field-generator-addingi18nstrings').toggle($('form #generator-enablei18n').is(':checked'));
        $('form .field-generator-db').toggle($('form #generator-generaterelationsfields').is(':checked'));
    }, 30);
JS;

$this->registerJs($js);

<?php
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator mervick\adminlte\gii\generators\model\Generator */

echo $form->field($generator, 'tableName');
echo $form->field($generator, 'useTablePrefix')->checkbox();
echo $form->field($generator, 'modelClass');
echo $form->field($generator, 'ns');
echo $form->field($generator, 'baseClass');
echo $form->field($generator, 'generateQuery')->checkbox();
echo $form->field($generator, 'queryClass');
echo $form->field($generator, 'queryNs');
echo $form->field($generator, 'queryBaseClass');
echo $form->field($generator, 'db');
echo $form->field($generator, 'generateRelations')->checkbox();
echo $form->field($generator, 'generateLabelsFromComments')->checkbox();
echo $form->field($generator, 'enableI18N')->checkbox();
echo $form->field($generator, 'messageCategory');
echo $form->field($generator, 'addingI18NStrings')->checkbox();
echo $form->field($generator, 'messagesPaths');
echo $form->field($generator, 'imagesPath');
echo $form->field($generator, 'imagesDomain');

$js = <<<JS
    // hide `adding I18N strings` field when I18N is disabled
    $('form #generator-enablei18n').change(function () {
        $('form .field-generator-addingi18nstrings').toggle($(this).is(':checked'));
        $('form .field-generator-messagespaths').toggle($(this).is(':checked') && $('form #generator-addingi18nstrings').is(':checked'));
    });
    $('form #generator-addingi18nstrings').change(function () {
        $('form .field-generator-messagespaths').toggle($(this).is(':checked') && $('form #generator-enablei18n').is(':checked'));
    });
    // hide `db` field when `generateRelationsFields` is disabled
    $('form #generator-generaterelationsfields').change(function () {
        $('form .field-generator-messagespaths').toggle($('form #generator-enablei18n').is(':checked') && $(this).is(':checked'));
    });
    setTimeout(function() {
        $('form .field-generator-addingi18nstrings').toggle($('form #generator-enablei18n').is(':checked'));
        $('form .field-generator-messagespaths').toggle($('form #generator-enablei18n').is(':checked') &&
            $('form #generator-addingi18nstrings').is(':checked'));
    }, 30);
JS;

$this->registerJs($js);

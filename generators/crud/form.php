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
echo $form->field($generator, 'icon');

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

        $("#generator-modelclass").on("change", function() {
            var o = $(this);
            if (o.closest("div").not(".has-error") && o.val() !== '') {
                var modelClass = o.val(),
                    searchModelClass = modelClass + 'Search',
                    modelClassName = modelClass.split('\\\\').pop(),
                    controllerClass = 'backend\\\\controllers\\\\' + modelClassName + 'Controller',
                    viewPath = '@backend/views/' + modelClassName.replace(/([A-Z])/g, '-$1').toLowerCase().replace(/^\-/, ''),
                    jQsearchModelClass = $("#generator-searchmodelclass"),
                    jQcontrollerClass = $("#generator-controllerclass"),
                    jQviewPath = $("#generator-viewpath");

                if (jQsearchModelClass.val() === '' || jQsearchModelClass.val() === jQsearchModelClass.data('generated')) {
                    jQsearchModelClass.val(searchModelClass).trigger("change");
                }
                if (jQcontrollerClass.val() === '' || jQcontrollerClass.val() === jQcontrollerClass.data('generated')) {
                    jQcontrollerClass.val(controllerClass).trigger("change");
                }
                if (jQviewPath.val() === '' || jQviewPath.val() === jQviewPath.data('generated')) {
                    jQviewPath.val(viewPath).trigger("change");
                }

                jQsearchModelClass.data('generated', searchModelClass);
                jQcontrollerClass.data('generated', controllerClass);
                jQviewPath.data('generated', viewPath);
            }
        });

        $("#generator-searchmodelclass").data('generated', $("#generator-searchmodelclass").val());
        $("#generator-controllerclass").data('generated', $("#generator-controllerclass").val());
        $("#generator-viewpath").data('generated', $("#generator-viewpath").val());

    }, 30);
JS;

$this->registerJs($js);

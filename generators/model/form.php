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

        // model generator: hide class name inputs when table name input contains *
        $('#generator-tablename').change(function () {
            var show = ($(this).val().indexOf('*') === -1);
            $('.field-generator-modelclass').toggle(show);
            if ($('#generator-generatequery').is(':checked')) {
                $('.field-generator-queryclass').toggle(show);
            }
        });

        var show = ($('#generator-tablename').val().indexOf('*') === -1);
        $('.field-generator-modelclass').toggle(show);
        if ($('#generator-generatequery').is(':checked')) {
            $('.field-generator-queryclass').toggle(show);
        }

        // model generator: translate table name to model class
        $('#generator-tablename').on('blur', function () {
            var tableName = $(this).val();
            if ($('#generator-modelclass').val() === '' && tableName && tableName.indexOf('*') === -1) {
                var modelClass = '';
                $.each(tableName.split('_'), function() {
                    if(this.length>0)
                        modelClass+=this.substring(0,1).toUpperCase()+this.substring(1);
                });
                $('#generator-modelclass').val(modelClass).blur();
            }
        });

        // model generator: translate model class to query class
        $('#generator-modelclass').on('blur', function () {
            var modelClass = $(this).val();
            if (modelClass !== '') {
                var queryClass = $('#generator-queryclass').val();
                if (queryClass === '') {
                    queryClass = modelClass + 'Query';
                    $('#generator-queryclass').val(queryClass);
                }
            }
        });

        // model generator: synchronize query namespace with model namespace
        $('#generator-ns').on('blur', function () {
            var stickyValue = $('.field-generator-queryns .sticky-value');
            var input = $('#generator-queryns');
            if (stickyValue.is(':visible') || !input.is(':visible')) {
                var ns = $(this).val();
                stickyValue.html(ns);
                input.val(ns);
            }
        });

    }, 30);
JS;

$this->registerJs($js);

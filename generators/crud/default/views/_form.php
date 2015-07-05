<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator mervick\adminlte\gii\generators\crud\Generator */

/* @var $model \yii\db\ActiveRecord */
$model = new $generator->modelClass();
$safeAttributes = $model->safeAttributes();
if (empty($safeAttributes)) {
    $safeAttributes = $model->attributes();
}

echo "<?php\n";
?>

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-form">

    <?= "<?php " ?>$form = ActiveForm::begin(<?= $generator->modelHasImages ? "['options' => ['enctype' => 'multipart/form-data']]" : '' ?>); ?>

<?php foreach ($generator->getColumnNames() as $attribute) {
    if (in_array($attribute, $safeAttributes)) {
        echo "    <?= " . $generator->generateActiveField($attribute) . " ?>\n\n";
    }
}
if (!empty($generator->relations)) { ?>
    <?= '<?=' ?> Html::tag('h4', <?= $generator->generateString('Related tables') ?>, ['class' => 'page-header']); ?>

<?php foreach ($generator->relations as $relation) { ?>
    <?= '<?=' ?> $form->field($model, '<?= $relation['property'] ?>')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(<?= $relation['many_many_class'] ?>::find()->orderBy('<?= $relation['many_many_title'] ?>')->asArray()->all(), '<?= $relation['many_many_id'] ?>', '<?= $relation['many_many_title'] ?>'),
        'options' => [
            'placeholder' => <?= $generator->generateString("Select {$relation['label']} ...") ?>,
            'multiple' => true,
        ],
        'pluginOptions' => [
            'allowClear' => true,
        ],
    ])->label(<?= $generator->generateString($relation['label']) ?>); ?>

<?php }
}    ?>
</div>
</div>
<div class="box-footer">

    <?= "<?= " ?>Html::submitButton(Html::tag('span', '', ['class' => 'glyphicon glyphicon-floppy-disk']) . ' ' .
        ($model->isNewRecord ? <?= $generator->generateString('Create') ?> : <?= $generator->generateString('Save') ?>),
        ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>

    <?= "<?php " ?>ActiveForm::end(); ?>

<?php if ($generator->modelHasImages): ?>
<?= '<?php' ?> ob_start(); ?>
<?php foreach (array_intersect($generator->imageAttributes, $generator->getColumnNames()) as $attribute): ?>
$("#<?= '<?=' ?> Html::getInputId($model, '<?= $attribute ?>') ?>").on("fileclear", function() {
    $(this).closest("form").find("input[type=hidden][name='<?= '<?=' ?> Html::getInputName($model, '<?= $attribute ?>') ?>']").val("");
});
<?php endforeach; ?>
<?= '<?php' ?>

$js = ob_get_clean();
$this->registerJs($js);
<?php endif; ?>
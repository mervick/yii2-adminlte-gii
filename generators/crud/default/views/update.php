<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();

echo "<?php\n";
?>

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

$this->title = <?= $generator->generateString('Edit '.Inflector::camel2words(StringHelper::basename($generator->modelClass))) ?> . ': ' . $model-><?= $generator->getNameAttribute() ?>;
$this->params['title'] = <?= $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>;
$this->params['title_desc'] = <?= $generator->generateString('Edit') ?>;
$this->params['breadcrumbs'][] = ['label' => <?= $model_many_string = $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model-><?= $generator->getNameAttribute() ?>, 'url' => ['view', <?= $urlParams ?>]];
$this->params['breadcrumbs'][] = <?= $generator->generateString('Update') ?>;
?>
<div class="box box-primary <?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-update">
    <div class="box-header with-border">
        <?= '<?php ' ?> $_model = '<?= ltrim($generator->modelClass, '\\') ?>'; ?>
        <?= '<?php ' ?> if (isset($_model::$icon)): ?>
        <i class="<?= '<?= ' ?> $_model::icon() ?>"></i>
        <?= '<?php ' ?> endif; ?>
        <h1 class="box-title"><?= '<?=' ?> $model-><?= $generator->getNameAttribute() ?> ?></h1>
        <div class="box-tools">
            <?= "<?= " ?>Html::a(Html::tag('span', '', ['class' => 'glyphicon glyphicon-eye-open']) . ' ' .
            <?= $generator->generateString('View') ?>, ['view', <?= $urlParams ?>], ['class' => 'btn btn-success btn-sm']) ?>
            <?= "<?= " ?>Html::a(Html::tag('span', '', ['class' => 'glyphicon glyphicon-list']) . ' ' .
            <?= $model_many_string ?>, ['index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>
    <div class="box-body">

        <?= "<?= " ?>$this->render('_form', [
        'model' => $model,
        ]) ?>

    </div>
</div>
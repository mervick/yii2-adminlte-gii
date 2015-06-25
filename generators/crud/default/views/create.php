<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

echo "<?php\n";
?>

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

$this->title = <?= $generator->generateString('Create ' . Inflector::camel2words(StringHelper::basename($generator->modelClass))) ?>;
$this->params['title'] = <?= $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>;
$this->params['title_desc'] = $this->title;
$this->params['breadcrumbs'][] = ['label' => <?= $model_many_string = $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box box-primary <?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-create">
    <div class="box-header with-border">
        <?= '<?php ' ?> $_model = '<?= ltrim($generator->modelClass, '\\') ?>'; ?>
        <?= '<?php ' ?> if (isset($_model::$icon)): ?>
        <i class="<?= '<?= ' ?> $_model::icon() ?>"></i>
        <?= '<?php ' ?> endif; ?>
        <h1 class="box-title"><?= '<?= ' ?><?= $generator->generateString('New ' . Inflector::camel2words(StringHelper::basename($generator->modelClass))) ?> ?></h1>
        <div class="box-tools">
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
<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator mervick\adminlte\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();

echo "<?php\n";
?>

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

$this->title = <?= $generator->generateString(Inflector::camel2words(StringHelper::basename($generator->modelClass))) ?> . ': ' . $model-><?= $generator->getNameAttribute() ?>;
$this->params['title'] = <?= $model_many_string = $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>;
$this->params['title_desc'] = <?= $generator->generateString('View') ?>;
$this->params['breadcrumbs'][] = ['label' => <?= $model_many_string ?>, 'url' => ['index']];
$this->params['breadcrumbs'][] = $model-><?= $generator->getNameAttribute() ?>;
?>
<div class="box box-primary <?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-view">
    <div class="box-header with-border">
        <?= '<?php ' ?> $_model = '<?= ltrim($generator->modelClass, '\\') ?>'; ?>
        <?= '<?php ' ?> if (isset($_model::$icon)): ?>
        <i class="<?= '<?= ' ?> $_model::icon() ?>"></i>
        <?= '<?php ' ?> endif; ?>
        <h1 class="box-title"><?= '<?=' ?> $model-><?= $generator->getNameAttribute() ?> ?></h1>
        <div class="box-tools">
            <?= "<?= " ?>Html::a(Html::tag('span', '', ['class' => 'glyphicon glyphicon-plus']) . ' ' .
            <?= $generator->generateString('Create ' . Inflector::camel2words(StringHelper::basename($generator->modelClass))) ?>,
                ['create'], ['class' => 'btn btn-success btn-sm']) ?>
            <?= "<?= " ?>Html::a(Html::tag('span', '', ['class' => 'glyphicon glyphicon-list']) . ' ' .
            <?= $model_many_string ?>, ['index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>
    <div class="box-body">

    <?= "<?= " ?>DetailView::widget([
        'model' => $model,
        'template' => function($attribute, $index, $widget) {
            if (is_callable($attribute['value'])) {
                $attribute['value'] = call_user_func($attribute['value'], $widget->model, $index, $widget);
            }
            return strtr("<tr><th>{label}</th><td>{value}</td></tr>", [
                '{label}' => $attribute['label'],
                '{value}' => $widget->formatter->format($attribute['value'], $attribute['format']),
            ]);
        },
        'attributes' => [
<?php
$columns = [];
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        $columns[] = $name;
    }
} else {
    foreach ($generator->getTableSchema()->columns as $column) {
        $columns[] = $column;
    }
}
foreach ($columns as $column) {
    $name = $tableSchema ? $column->name : $column;
    if ($model = $generator->isIdModel($name)) {
?>
            [
                'label' => <?= $generator->generateString($model['label']) ?>,
                'attribute' => '<?= $name ?>',
                'value' => function ($model, $index, $widget) {
                    /* @var $model <?= $generator->modelClass ?> */
                    return Html::a($model-><?= $model['attribute'] ?>->name,
                        Url::toRoute(['<?= $model['urlPath'] ?>/view', 'id' => $model-><?= $model['attribute'] ?>->id]),
                            ['title' => <?= $generator->generateString('View ' . $model['label'] . ' detail') ?>, 'data-toggle' => 'tooltip']);
                },
                'format' => 'raw',
            ],
<?php
    } elseif (in_array($name, array_merge($generator->datetimeAttributes, ['created_at', 'updated_at']))) { ?>
            [
                'attribute' => '<?= $name ?>',
                'value' => function ($model, $index, $widget) {
                    /* @var $model <?= $generator->modelClass ?> */
                    return date('d/m/Y H:i:s', $model-><?= $name ?>);
                },
                'format' => 'raw',
            ],
<?php
    } elseif (in_array($name, $generator->imageAttributes)) { ?>
            [
                'attribute' => '<?= $name ?>',
                'value' => function ($model, $index, $widget) {
                    /* @var $model <?= $generator->modelClass ?> */
                    return Html::tag('img', '', ['src' => $model-><?= $name ?>Url]);
                },
                'format' => 'raw',
            ],
<?php
    } elseif ($name === 'status') { ?>
            [
                'attribute' => '<?= $name ?>',
                'value' => function ($model, $index, $widget) {
                    /* @var $model <?= $generator->modelClass ?> */
                    return $model-><?= $name ?> ? '<span class="glyphicon glyphicon-ok text-success"></span>' :
                        '<span class="glyphicon glyphicon-remove text-danger"></span>';
                },
                'format' => 'raw',
            ],
<?php
    } elseif ($tableSchema && $column->phpType === 'double') {
        $decimals = $column->scale && is_int($column->scale) ? $column->scale : 4;
        ?>
            [
                'attribute' => '<?= $name ?>',
                'value' => function ($model, $index, $widget) {
                    /* @var $model <?= $generator->modelClass ?> */
                    return number_format($model-><?= $name ?>, <?= $decimals ?>);
                },
            ],
<?php
    } else { ?>
            '<?= $name ?>',
<?php
    }
}
    foreach($generator->relations as $relation) { ?>
            [
                'label' => <?= $generator->generateString($relation['label']) ?>,
                'attribute' => '<?= $relation['property'] ?>',
                'value' => function ($model, $index, $widget) {
                    /* @var $model <?= $generator->modelClass ?> */
                    $html = [];
                    $count = $model->get<?= $relation['relation'] ?>()->count();
                    $limit = ($show_counter = $count > 8) ? 5 : 8;
                    foreach (ArrayHelper::map($model->get<?= $relation['relation'] ?>()->orderBy('<?= $relation['many_many_title'] ?>')->limit($limit)->asArray()->all(), '<?= $relation['many_many_id'] ?>', '<?= $relation['many_many_title'] ?>') as $id => $name) {
                        $html[] = Html::a($name, Url::toRoute(['<?= lcfirst(array_reverse(explode('\\', $relation['many_many_class']))[0]) ?>/view', '<?= $relation['many_many_id'] ?>' => $id]),
                            ['title' => <?= $generator->generateString("View {$relation['single_label']}") ?>, 'data-toggle' => 'tooltip']);
                    }
                    return implode(', ', $html) . ($show_counter ? ' ' . Html::tag('span', <?= $generator->generateString("and {count} other {label}.", ['count' => 'php:$count - $limit', 'label' => 'php:' . $generator->generateString(strtolower($relation['label']))]) ?>) : '');
                },
                'format' => 'raw',
            ],
<?php } ?>
        ],
    ]); ?>

    </div>
    <div class="box-footer">
        <?= "<?= " ?>Html::a(Html::tag('span', '', ['class' => 'glyphicon glyphicon-pencil']) . ' ' .
            <?= $generator->generateString('Update') ?>, ['update', <?= $urlParams ?>], ['class' => 'btn btn-primary btn-sm']) ?>
        <?= "<?= " ?>Html::a(Html::tag('span', '', ['class' => 'glyphicon glyphicon-trash']) . ' ' .
            <?= $generator->generateString('Delete') ?>, ['delete', <?= $urlParams ?>], [
            'class' => 'btn btn-danger btn-sm',
            'data' => [
                'confirm' => <?= $generator->generateString('Are you sure you want to delete this item?') ?>,
                'method' => 'post',
            ],
        ]) ?>
    </div>
</div>
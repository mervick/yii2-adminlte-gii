<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator mervick\adminlte\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();
$nameAttribute = $generator->getNameAttribute();

echo "<?php\n";
?>

use yii\helpers\Html;
use \kartik\grid\GridView;

/* @var $this yii\web\View */
<?= !empty($generator->searchModelClass) ? "/* @var \$searchModel " . ltrim($generator->searchModelClass, '\\') . " */\n" : '' ?>
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = <?= $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>;
$this->params['breadcrumbs'][] = $this->title;

$gridColumns = [
    [
        'class' => 'kartik\grid\SerialColumn',
        'width' => '36px',
        'mergeHeader' => false,
    ],<?php
    $columns = [];
    $to_end = [];
    $end_sort = ['status', 'created_at', 'updated_at'];
    if (($tableSchema = $generator->getTableSchema()) === false) {
        foreach ($generator->getColumnNames() as $name) {
            if (in_array($name, $end_sort)) {
                $to_end[$name] = $name;
            } else {
                $columns[] = $name;
            }
        }
    } else {
        foreach ($tableSchema->columns as $column) {
            if (in_array($column->name, $end_sort)) {
                $to_end[$column->name] = $column;
            } else {
                $columns[] = $column;
            }
        }
    }
    $count = count($columns) + count($to_end);
    foreach ($columns as $index => $column) {
        echo $generator->generateColumn($tableSchema, $column, $index * 2 >= $count);
    }
    if (!empty($to_end)) {
        foreach ($end_sort as $name) {
            if (isset($to_end[$name])) {
                echo $generator->generateColumn($tableSchema, $to_end[$name], true);
            }
        }
    }
?>

    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdownOptions' => ['class'=>'pull-right'],
        'viewOptions' => ['title' => <?= $generator->generateString('View') ?>, 'data-toggle' => 'tooltip'],
        'updateOptions' => ['title' => <?= $generator->generateString('Edit') ?>, 'data-toggle' => 'tooltip'],
        'deleteOptions' => ['title' => <?= $generator->generateString('Delete') ?>, 'data-toggle' => 'tooltip'],
        'mergeHeader' => false,
    ],
    [
        'class' => '\kartik\grid\CheckboxColumn',
        'rowSelectedClass' => GridView::TYPE_INFO,
    ],
];
?>
<div class="box box-primary <?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-index">
    <div class="box-header with-border">
        <?= '<?php ' ?> $_model = '<?= ltrim($generator->modelClass, '\\') ?>'; ?>
        <?= '<?php ' ?> if (isset($_model::$icon)): ?>
        <i class="<?= '<?= ' ?> $_model::icon() ?>"></i>
        <?= '<?php ' ?> endif; ?>
        <h1 class="box-title"><?= '<?=' ?> $this->title ?></h1>
        <div class="box-tools">
            <?= "<?= " ?>Html::a(Html::tag('span', '', ['class' => 'glyphicon glyphicon-plus']) . ' ' .
                <?= $generator->generateString('Create ' . Inflector::camel2words(StringHelper::basename($generator->modelClass))) ?>,
                    ['create'], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
    </div>
    <div class="box-body">

    <?= "<?= " ?>GridView::widget([
        'dataProvider' => $dataProvider,
        <?= !empty($generator->searchModelClass) ? "'filterModel' => \$searchModel,\n" : ''; ?>
        'columns' => $gridColumns,
        'pjax' => true,
        'bordered' => true,
        'striped' => true,
        'condensed' => true,
        'responsive' => true,
        'hover' => false,
        'showPageSummary' => false,
        'persistResize' => false,
        'panel' => [
            'heading' => false,
        ],
        'panelTemplate' => '
            {panelBefore}
            {items}
            {panelFooter}
        ',
        'toggleDataOptions' => [
            'all' => [
                'icon' => 'resize-full',
                'class' => 'btn btn-default btn-sm',
            ],
            'page' => [
                'icon' => 'resize-small',
                'class' => 'btn btn-default btn-sm',
            ],
        ],
        'export' => [
            'options' => [
                'class' => 'btn btn-default btn-sm',
            ],
        ],
        'toolbar'=> [
            '{toggleData}',
            [
                'content' => Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['index'], ['data-pjax' => 0, 'class' => 'btn btn-default btn-sm', 'title' => <?= $generator->generateString('Reset Grid') ?>]),
            ],
            '{export}',
        ],
        'panelBeforeTemplate' => '
            <div class="pull-left">{summary}</div>
            <div class="pull-right">
                <div class="btn-toolbar kv-grid-toolbar" role="toolbar">{toolbar}</div>
            </div>
            <div class="clearfix"></div>',
    ]); ?>
    </div>
</div>
<?= '<?php' ?>

$js = <<<JS
    \$("[data-toggle='popover-x']").on('click', function() {
        var \$this = \$(this), href = \$this.attr('href'),
            dialog = \$this.attr('data-target') || (href && href.replace(/.*(?=#[^\s]+\$)/, ''));
        \$('.kv-editable-popover.popover.in').not(dialog).each(function() {
            var \$dialog = \$(this);
            \$dialog.popoverX('hide');
            \$dialog.removeClass("in");
            \$dialog.attr("aria-hidden", true);
        });
    });
JS;

$this->registerJs($js);
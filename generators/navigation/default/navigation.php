<?= "<?php\n"?>

$paths = [];
foreach (explode('/', \Yii::$app->request->pathInfo) as $path) {
    if (empty($paths)) {
        $paths[] = "/$path";
    } else {
        $paths[] = $paths[count($paths)-1] . "/$path";
    }
}
?>
<?php

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Inflector;

/** @var array $controllers */
/** @var mervick\adminlte\gii\generators\navigation\Generator $generator */

$items = [];
foreach ($controllers as $actions) {
    $html = [];
    $options = [];
    $path = explode('/', ltrim($actions[0]['url'], '/'))[0];
    if (count($actions) == 1) {
        $options['class'] = "<?= in_array('/$path', \$paths) ? 'active' : '' ?>";
        $html[] = Html::tag('a',
            Html::tag('i', '', ['class' => $actions[0]['icon']]) .
            Html::tag('span', $actions[0]['label']),
            ['href' => Url::toRoute([$actions[0]['url']])]);
    } else {
        $options['class'] = "treeview<?= in_array('/$path', \$paths) ? ' active' : '' ?>";
        $list = [];
        foreach ($actions as $action) {
            $list[] = Html::tag('li',
                Html::tag('a',
                    Html::tag('i', '', ['class' => 'fa fa-circle-o']) .
                    $action['label'],
                    ['href' => Url::toRoute([$action['url']])]),
                ['class' => "<?= in_array('{$action['url']}', \$paths) ? 'active' : '' ?>"]
            );
        }
        $html[] = Html::tag('a',
            Html::tag('i', '', ['class' => $actions[0]['icon']]) .
            Html::tag('span', $actions[0]['label']) .
            Html::tag('i', '', ['class' => 'fa fa-angle-left pull-right']),
            ['href' => Url::toRoute([$actions[0]['url']])]);
        $html[] = Html::tag('ul', implode("\n", $list), ['class' => 'treeview-menu']);
    }
    $items[] = Html::tag('li', implode("\n", $html), $options);
}


$dev_modules = array_intersect(['gii', 'debug'], array_keys(Yii::$app->modules));
if (!empty($dev_modules)) {
    $items[] = '<?php if (Yii::$app->user->can(\'dev\')): ?>';
    $items[] = Html::tag('li', '<?= ' . $generator->generateString('Development') . ' ?>', ['class' => 'header']);
    foreach ($dev_modules as $module) {
        $items[] = Html::tag('li',
            Html::tag('a',
                Html::tag('i', '', ['class' => 'fa fa-' . ($module == 'gii' ? 'code' : 'bug')]) .
                Html::tag('span', '<?= ' . $generator->generateString(ucfirst(Inflector::camel2id($module))) . ' ?>'),
                ['href' => Url::toRoute(["/$module"])]),
            ['class' => "<?= in_array('/$module', \$paths) ? 'active' : '' ?>"]);
    }
    $items[] = '<?php endif; ?>';
}

echo str_replace(['&#039;', '&lt;?=', '&lt;?php ', '?&gt;'], ['\'', '<?=', '<?php ', '?>'],
    Html::tag('ul', implode("\n", $items), [
        'class' => 'sidebar-menu',
    ])
);
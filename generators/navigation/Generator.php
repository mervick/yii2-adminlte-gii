<?php
namespace mervick\adminlte\gii\generators\navigation;

use Yii;
use yii\gii\CodeFile;
use yii\helpers\Inflector;

/**
 * This generator will generate AdminLTE main navigation template.
 *
 * @author Andrey Izman <izmanw@gmail.com>
 */
class Generator extends \yii\gii\Generator
{
    public $ns = 'backend\controllers';
    public $defaultController = 'AppController';
    public $layoutsPath = '@backend/views/layouts';
    public $enableI18N = true;
    public $messageCategory = 'app';

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'ALT Main Navigation Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator will generate AdminLTE main navigation template.';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['ns', 'layoutsPath', 'defaultController'], 'filter', 'filter' => 'trim'],
            [['ns'], 'match', 'pattern' => '/^[\w\\\\]+$/', 'message' => 'Only word characters and backslashes are allowed.'],
            [['defaultController'], 'match', 'pattern' => '/^\w+$/', 'message' => 'Only word characters are allowed.'],
            [['ns', 'layoutsPath', 'defaultController'], 'required'],
            [['ns'], 'filter', 'filter' => function($value) { return trim($value, '\\'); }],
            [['layoutsPath'], 'filter', 'filter' => function($value) { return trim($value, '/'); }],
            [['defaultController'], 'match', 'pattern' => '/Controller$/', 'message' => 'Controller class name must be suffixed with "Controller".'],
            [['defaultController'], 'match', 'pattern' => '/(^|\\\\)[A-Z][^\\\\]+Controller$/', 'message' => 'Controller class name must start with an uppercase letter.'],
            [['enableI18N'], 'boolean'],
            [['ns'], 'validateNamespace'],
            [['layoutsPath'], 'validateLayoutsPath'],
            [['defaultController'], 'validateDefaultController'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'ns' => 'Namespace',
            'defaultController' => 'Default Controller',
            'layoutsPath' => 'Layouts Path',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'ns' => 'This is the namespace of the controllers. If not set, it will default
                to <code>backend\\controllers</code>',
            'defaultController' => 'Specify the default controller, e.g. <code>SiteController</code>.',
            'layoutsPath' => 'Specify the directory for storing the view script for main layout. If not set, it will default
                to <code>@backend/views/layouts</code>',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates()
    {
        return ['navigation.php'];
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), ['ns', 'layoutsPath']);
    }

    /**
     * @return string the controller view path
     */
    public function getLayoutsPath()
    {
        return Yii::getAlias($this->layoutsPath);
    }

    /**
     * Read controller actions.
     * @param string $controllerName
     * @param string|null $name
     * @param string|null $icon
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function readControllerActions($controllerName, $name = null, $icon = null)
    {
        $controllerClass = "$this->ns\\$controllerName";
        $modelName = substr($controllerName, 0, -10);
        $basePath = strtolower(preg_replace('/([A-Z])/', '-\1', lcfirst($modelName)));
        /** @var \yii\web\Controller $controller */
        $controller = Yii::createObject($controllerClass, [$controllerName, $controllerName]);
        $actions = [[
            'label' => '<?= ' . $this->generateString($name ?: Inflector::pluralize(Inflector::camel2words($modelName))) . ' ?>',
            'icon' => $icon ?: (isset($controllerClass::$icon) ? $controllerClass::icon() : 'fa fa-folder'),
            'url' => "/$basePath/$controller->defaultAction",
        ]];
        foreach (get_class_methods($controller) as $action) {
            if (preg_match('/^action[A-Z]\w+$/', $action)) {
                $reflector = new \ReflectionClass($controllerClass);
                $parameters = $reflector->getMethod($action)->getParameters();
                if (empty($parameters)) {
                    $action = lcfirst(substr($action, 6));
                    if ($action !== $controller->defaultAction) {
                        $actions[] = [
                            'label' => '<?= ' . $this->generateString(Inflector::camel2words($action)) . ' ?>',
                            'icon' => 'fa fa-circle-o',
                            'url' => "/$basePath/$action",
                        ];
                    }
                }
            }
        }
        return $actions;
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $controllers = [
            $this->readControllerActions($this->defaultController, 'Dashboard', 'fa fa-dashboard')
        ];
        $path = Yii::getAlias('@' . str_replace('\\', '/', $this->ns));
        foreach (array_diff(scandir($path), ['.', '..', 'AuthController.php', "$this->defaultController.php"]) as $filename) {
            $controllers[] = $this->readControllerActions(explode('.', $filename)[0]);
        }
        return [new CodeFile(
            Yii::getAlias($this->layoutsPath) . '/main/navigation.php',
            $this->render('navigation.php', ['controllers' => $controllers])
        )];
    }

    /**
     * Validates the [[ns]] attribute.
     */
    public function validateNamespace()
    {
        $path = Yii::getAlias('@' . str_replace('\\', '/', $this->ns), false);
        if ($path === false) {
            $this->addError('ns', 'Namespace must be associated with an existing directory.');
        }
    }

    /**
     * Validates the [[viewPath]] attribute.
     */
    public function validateLayoutsPath()
    {
        $path = Yii::getAlias($this->layoutsPath, false);
        if ($path === false) {
            $this->addError('layoutsPath', 'Views path must be associated with an existing directory.');
        }
        elseif (is_dir("$path/layouts")) {
            $this->addError('layoutsPath', 'Non existing directory `layouts` in the views path.');
        }
    }

    /**
     * Validates the [[defaultController]] attribute.
     */
    public function validateDefaultController()
    {
        if ($path = Yii::getAlias('@' . str_replace('\\', '/', $this->ns), false)) {
            if (!file_exists("$path/{$this->defaultController}.php")) {
                $this->addError('defaultController', 'This controller does not exists.');
            }
        }
    }
}

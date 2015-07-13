<?php

namespace mervick\adminlte\gii\generators\crud;

use Yii;
use yii\base\ErrorException;
use yii\db\Schema;
use yii\gii\CodeFile;
use yii\db\Connection;
use yii\db\BaseActiveRecord;
use yii\base\Controller;
use yii\helpers\Inflector;
use yii\helpers\VarDumper;
use yii\helpers\StringHelper;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use mervick\adminlte\behaviors\ManyManyBehavior;
use mervick\adminlte\behaviors\ImageBehavior;

defined('PHP_INT_MIN') || define('PHP_INT_MIN', ~PHP_INT_MAX);

/**
 * AdminLTE CRUD Generator
 *
 * @property array $columnNames Model column names. This property is read-only.
 * @property string $controllerID The controller ID (without the module ID prefix). This property is
 * read-only.
 * @property array $searchAttributes Searchable attributes. This property is read-only.
 * @property boolean|\yii\db\TableSchema $tableSchema This property is read-only.
 * @property string $viewPath The controller view path. This property is read-only.
 * @property string $modelBaseName
 * @property bool $modelHasImages
 * @property string $modelNS
 * @property string $tableName
 *
 * @author Andrey Izman <izmanw@gmail.com>
 */
class Generator extends \yii\gii\generators\crud\Generator
{
    public $db = 'db';
    public $modelClass;
    public $controllerClass;
    public $viewPath;
    public $baseControllerClass = 'yii\web\Controller';
    public $searchModelClass = '';
    public $enableI18N = true;
    public $addingI18NStrings = true;
    public $generateRelationsFields = true;
    public $icon;

    protected $I18NStrings = [];
    protected $classNames = [];

    public $relations = [];

    const FIELD_TIMESTAMP_BEHAVIOR      = 'timestamp-behavior';
    const FIELD_IMAGE_BEHAVIOR          = 'image-behavior';
    const FIELD_MANY_MANY_BEHAVIOR      = 'many-many-behavior';

    const FIELD_FOREIGN_KEY             = 'foreign-key';
    const FIELD_PRIMARY                 = 'primary';
    const FIELD_DATETIME                = 'datetime';
    const FIELD_PASSWORD                = 'password'; #!
    const FIELD_TEXT                    = 'text'; #!
    const FIELD_HTML                    = 'html';
    const FIELD_FLOAT                   = 'float';
    const FIELD_INPUT                   = 'input';
    const FIELD_MANY                    = 'many';
    const FIELD_SELECT                  = 'select'; #!
    const FIELD_STATUS                  = 'status';
    const FIELD_INTEGER                 = 'integer';


    private function getLabelAttribute($class)
    {
        if (is_subclass_of($class, 'yii\db\ActiveRecord')) {
            /** @var $modelClass \yii\db\ActiveRecord */
            $columns = $modelClass::getTableSchema()->columns;
            $primary = null;
            $attributes = [];
            foreach ($columns as $column) {
                if (!$primary && $column->isPrimaryKey) {
                    $primary = $column->name;
                }
                if (!$column->allowNull && $column->phpType === 'string') {
                    $attributes[] = $column->name;
                }
            }
        } else {
            /* @var $model \yii\base\Model */
            $model = new $class();
            $attributes = $model->attributes();
        }

        $nameAttributes = array_intersect(['name', 'title', 'label'], $attributes);
        return empty($nameAttributes) ? empty($attributes) ? $primary : $attributes[0] : $nameAttributes[0];
    }

    public function getModelAttributes()
    {
        static $attributes;

        if (!isset($attributes)) {
            /** @var \yii\base\Model $model */
            $model = Yii::createObject($this->modelClass);
            $attributes = [];

            // read schema
            if (($tableSchema = $this->getTableSchema()) !== false) {
                foreach ($tableSchema->columns as $attribute => $column) {
                    $attributes[$attribute] = [
                        'type' => null,
                        'schema' => $column,
                    ];
                }
            } else {
                $attributes = array_fill_keys($model->attributes(), [
                    'type' => null,
                ]);
            }

            // read behaviors
            foreach ($model->behaviors() as $behavior) {
                if (!is_array($behavior)) {
                    $behavior = ['class' => $behavior];
                }

                if ($behavior['class'] === TimestampBehavior::className()) {
                    if (!empty($behavior['attributes'])) {
                        foreach ($behavior['attributes'] as $fields) {
                            if (is_array($fields)) {
                                foreach (array_values($fields) as $field) {
                                    $attributes[$field]['type'] = self::FIELD_TIMESTAMP_BEHAVIOR;
                                }
                            } else {
                                $attributes[$fields]['type'] = self::FIELD_TIMESTAMP_BEHAVIOR;
                            }
                        }
                    } else {
                        $attributes['created_at']['type'] = self::FIELD_TIMESTAMP_BEHAVIOR;
                        $attributes['updated_at']['type'] = self::FIELD_TIMESTAMP_BEHAVIOR;
                    }
                }
                elseif ($behavior['class'] === ManyManyBehavior::className()) {
                    if (!empty($behavior['relations'])) {
                        foreach ($behavior['relations'] as $id => $relation) {
                            $attributes[$id] = [
                                'type' => self::FIELD_MANY_MANY_BEHAVIOR,
                                'data' => $relation,
                            ];
                        }
                    }
                }
                elseif ($behavior['class'] === ImageBehavior::className()) {
                    if (!empty($behavior['attributes'])) {
                        foreach (array_keys($behavior['attributes']) as $id) {
                            $attributes[$id]['type'] = self::FIELD_IMAGE_BEHAVIOR;
                        }
                    }
                }
            }

            if ($tableSchema) {
                foreach ($tableSchema->foreignKeys as $fk) {
                    $table = $fk[0];
                    unset($fk[0]);
                    $id = array_keys($fk)[0];
                    $className = $this->generateClassName($table);
                    $class = "$this->modelNS\\$className";
                    $attributes[$id] = [
                        'type' => self::FIELD_FOREIGN_KEY,
                        'data' => [
                            'class' => $class,
                            'table' => $table,
                            'key' => $fk[$id],
                            'orderBy' => $this->getLabelAttribute($class),
                            'label' => Inflector::camel2words($className),
                        ],
                    ];
                }
            }

            // types
            foreach ($attributes as $name => &$data) {
                $column = null;
                if ($tableSchema) {
                    /** @var \yii\db\ColumnSchema $column */
                    $column = $data['schema'];
                }
                if ($column && $column->isPrimaryKey) {
                    $data['type'] = self::FIELD_PRIMARY;
                }
                elseif (empty($data['type']))
                {
                    $type = $column ? $column->phpType : null;

                    if ((!$column || in_array($type, ['integer', 'string'])) &&
                        in_array($name, ['date', 'datetime', 'time', 'timestamp'])) {
                        $data['type'] = self::FIELD_DATETIME;
                    } elseif ($name === 'status') {
                        $data['type'] = self::FIELD_STATUS;
                    } elseif ($type === 'integer') {
                        $min = $column->unsigned ? 0 : PHP_INT_MIN;
                        $max = PHP_INT_MAX;
                        if ($column->size && is_int($column->size)) {
                            $max = pow(10, $column->size) - 1;
                            if ($min === PHP_INT_MIN) {
                                $min = -$max+1;
                            }
                        }
                        $data['type'] = self::FIELD_INTEGER;
                        $data['data'] = [
                            'min' => $min,
                            'max' => $max,
                        ];
                    } elseif ($type === 'double') {
                        $min = $column->unsigned ? 0 : PHP_INT_MIN;
                        $max = PHP_INT_MAX;
                        $step = 0.0001;
                        $decimals = 4;
                        if ($column->size && is_int($column->size) && is_int($column->scale)) {
                            $max = pow(10, $column->size - $column->scale) - 1;
                            if ($min === PHP_INT_MIN) {
                                $min = -$max+1;
                            }
                            $step = pow(10, -1 * $column->scale);
                            $decimals = $column->scale;
                        }
                        $data['type'] = self::FIELD_FLOAT;
                        $data['data'] = [
                            'min' => $min,
                            'max' => $max,
                            'step' => $step,
                            'decimals' => $decimals,
                        ];
                    } elseif ((!$column || $type === 'string') && preg_match('/_html$/i', $name)) {
                        $data['type'] = self::FIELD_HTML;
                    } else {
                        $data['type'] = self::FIELD_INPUT;
                    }
                }
            }

            // pull right
            $keys = array_keys($attributes);
            $count = count($keys);
            foreach ($keys as $index => $attribute) {
                $attributes[$attribute]['pull_right'] = $index * 2 >= $count;
            }

        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'AdminLTE CRUD Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator generates a controller and views that implement CRUD (Create, Read, Update, Delete)
            operations for the specified data model.';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['controllerClass', 'modelClass', 'searchModelClass', 'baseControllerClass'], 'filter', 'filter' => 'trim'],
            [['modelClass', 'controllerClass', 'baseControllerClass', 'indexWidgetType'], 'required'],
            [['searchModelClass'], 'compare', 'compareAttribute' => 'modelClass', 'operator' => '!==', 'message' => 'Search Model Class must not be equal to Model Class.'],
            [['modelClass', 'controllerClass', 'baseControllerClass', 'searchModelClass'], 'match', 'pattern' => '/^[\w\\\\]*$/', 'message' => 'Only word characters and backslashes are allowed.'],
            [['modelClass'], 'validateClass', 'params' => ['extends' => BaseActiveRecord::className()]],
            [['modelClass'], 'match', 'pattern' => '/^(?:[a-zA-Z][a-zA-Z0-9]+\\\\)+[A-Z][a-zA-Z0-9]+$/'],
            [['baseControllerClass'], 'validateClass', 'params' => ['extends' => Controller::className()]],
            [['controllerClass'], 'match', 'pattern' => '/Controller$/', 'message' => 'Controller class name must be suffixed with "Controller".'],
            [['controllerClass'], 'match', 'pattern' => '/(^|\\\\)[A-Z][^\\\\]+Controller$/', 'message' => 'Controller class name must start with an uppercase letter.'],
            [['controllerClass', 'searchModelClass'], 'validateNewClass'],
            [['indexWidgetType'], 'in', 'range' => ['grid', 'list']],
            [['modelClass'], 'validateModelClass'],
            [['enableI18N'], 'boolean'],
            [['messageCategory'], 'validateMessageCategory', 'skipOnEmpty' => false],
            [['viewPath'], 'safe'],
            [['icon'], 'match', 'pattern' => '/^(?:[0-9a-z\-]+)?$/i', 'message' => 'No valid image class.'],
            [['addingI18NStrings', 'generateRelationsFields'], 'boolean'],
            [['db'], 'filter', 'filter' => 'trim'],
            [['db'], 'required'],
            [['db'], 'match', 'pattern' => '/^\w+$/', 'message' => 'Only word characters are allowed.'],
            [['db'], 'validateDb'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'modelClass' => 'Model Class',
            'controllerClass' => 'Controller Class',
            'viewPath' => 'View Path',
            'baseControllerClass' => 'Base Controller Class',
            'indexWidgetType' => 'Widget Used in Index Page',
            'searchModelClass' => 'Search Model Class',
            'addingI18NStrings' => 'Adding I18N Strings',
            'generateRelationsFields' => 'Generate Relations Fields',
            'icon' => 'Icon css class',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'modelClass' => 'This is the ActiveRecord class associated with the table that CRUD will be built upon.
                You should provide a fully qualified class name, e.g., <code>app\models\Post</code>.',
            'controllerClass' => 'This is the name of the controller class to be generated. You should
                provide a fully qualified namespaced class (e.g. <code>app\controllers\PostController</code>),
                and class name should be in CamelCase with an uppercase first letter. Make sure the class
                is using the same namespace as specified by your application\'s controllerNamespace property.',
            'viewPath' => 'Specify the directory for storing the view scripts for the controller. You may use path alias here, e.g.,
                <code>/var/www/basic/controllers/views/post</code>, <code>@app/views/post</code>. If not set, it will default
                to <code>@app/views/ControllerID</code>',
            'baseControllerClass' => 'This is the class that the new CRUD controller class will extend from.
                You should provide a fully qualified class name, e.g., <code>yii\web\Controller</code>.',
            'indexWidgetType' => 'This is the widget type to be used in the index page to display list of the models.
                You may choose either <code>GridView</code> or <code>ListView</code>',
            'searchModelClass' => 'This is the name of the search model class to be generated. You should provide a fully
                qualified namespaced class name, e.g., <code>app\models\PostSearch</code>.',
            'addingI18NStrings' => 'Enables the adding non existing I18N strings to the message category files.',
            'generateRelationsFields' => 'Enable to generate relations fields',
            'db' => 'This is the ID of the DB application component.',
            'icon' => 'Icon css class, e.g. <code>glyphicon glyphicon-user</code> render to <i class="glyphicon glyphicon-user"></i>',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates()
    {
        return ['controller.php'];
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), [
            'baseControllerClass',
            'indexWidgetType',
            'addingI18NStrings',
            'generateRelationsFields',
            'db',
        ]);
    }

    /**
     * Checks if model class is valid
     */
    public function validateModelClass()
    {
        /* @var $class ActiveRecord */
        $class = $this->modelClass;
        $pk = $class::primaryKey();
        if (empty($pk)) {
            $this->addError('modelClass', "The table associated with $class must have primary key(s).");
        }
    }

    /**
     * Validates the [[db]] attribute.
     */
    public function validateDb()
    {
        if (!Yii::$app->has($this->db)) {
            $this->addError('db', 'There is no application component named "db".');
        } elseif (!Yii::$app->get($this->db) instanceof Connection) {
            $this->addError('db', 'The "db" application component must be a DB connection instance.');
        }
    }

    /**
     * @return Connection the DB connection as specified by [[db]].
     */
    protected function getDbConnection()
    {
        return Yii::$app->get($this->db, false);
    }

    /**
     * @return string the controller view path
     */
    public function getViewPath()
    {
        if (empty($this->viewPath)) {
            return Yii::getAlias('@vendor/mervick/yii2-adminlte-gii/views/' . $this->getControllerID());
        } else {
            return Yii::getAlias($this->viewPath);
        }
    }

    public function getNameAttribute()
    {
        foreach ($this->getColumnNames() as $name) {
            if (!strcasecmp($name, 'name') || !strcasecmp($name, 'title')) {
                return $name;
            }
        }
        /* @var $class \yii\db\ActiveRecord */
        $class = $this->modelClass;
        $pk = $class::primaryKey();

        return $pk[0];
    }

    /**
     * @return string the controller ID (without the module ID prefix)
     */
    public function getControllerID()
    {
        $pos = strrpos($this->controllerClass, '\\');
        $class = substr(substr($this->controllerClass, $pos + 1), 0, -10);

        return Inflector::camel2id($class);
    }

    /**
     * Get model base name
     * @return string
     */
    public function getModelBaseName()
    {
        return StringHelper::basename($this->modelClass);
    }

    /**
     * Returns true when model has attributes what will be render with image widget.
     * @return bool
     */
    public function getModelHasImages()
    {
        static $hasImages;
        if (!isset($hasImages)) {
            return $hasImages = count(array_intersect($this->imageAttributes, $this->getColumnNames())) > 0;
        }
        return $hasImages;
    }

    /**
     * Returns true when model has attributes what will be render with datetime widget.
     * @return bool
     */
    public function getModelHasDates()
    {
        static $hasDates;
        if (!isset($hasDates)) {
            return $hasDates = count(array_intersect($this->datetimeAttributes, $this->getColumnNames())) > 0;
        }
        return $hasDates;
    }

    /**
     * Checks whatever attribute is foreign key.
     * @param string $attribute
     * @return array|bool
     */
    public function isIdModel($attribute)
    {
        if (substr($attribute, 0, 3) == 'id_') {
            $atBegin = true;
            $table = substr($attribute, 3);
        } elseif (substr($attribute, -3) == '_id') {
            $table = substr($attribute, 0, -3);
        }
        if (!empty($table)) {
            $name = explode('_', $table);
            foreach ($name as &$n) $n = ucfirst($n);
            $class = implode('', $name);
            $modelClass = "{$this->modelNS}\\$class";
            $columns = $this->getClassColumns($modelClass);
            $namedAttributes = array_intersect(['name', 'title', 'label'], $columns);
            $orderBy = !empty($namedAttributes) ? $namedAttributes[0] : array_diff([$attribute], $columns)[0];
            $lname = lcfirst($class);
            return [
                'class' => $modelClass,
                'label' => Inflector::camel2words($class),
                'name' => $lname,
                'attribute' => !empty($atBegin) ? "id$class" : $lname . 'Id',
                'table' => $table,
                'orderBy' => $orderBy,
                'urlPath' => strtolower(preg_replace('/([A-Z])/', '-\1', $lname)),
            ];
        }
        return false;
    }

    /**
     * Get model namespace
     * @return string
     */
    public function getModelNS()
    {
        $modelClass = explode('\\', $this->modelClass);
        array_pop($modelClass);
        return implode('\\', $modelClass);
    }

    /**
     * Get table columns or class attributes
     * @param string $class
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function getClassColumns($class)
    {
        if (is_subclass_of($class, 'yii\db\ActiveRecord')) {
            /** @var $class \yii\db\ActiveRecord */
            return $class::getTableSchema()->getColumnNames();
        } else {
            /* @var $model \yii\base\Model */
            $model = new $class();
            return $model->attributes();
        }
    }

    /**
     * Generates a grid column
     * @param $tableSchema \yii\db\TableSchema
     * @param $column \yii\db\ColumnSchema
     * @param bool $pull_right
     * @return string
     */
    public function generateColumn($tableSchema, $column, $pull_right=false)
    {
        $attribute = !$tableSchema ? $column : $column->name;
        if ($attribute == 'id') return '';
        if ($model = $this->isIdModel($attribute)) {
            return "
    [
        'class' => '\\\\kartik\\\\grid\\\\EditableColumn',
        'attribute' => '$attribute',
        'vAlign' => 'middle',
        'value' => function (\$model, \$key, \$index, \$widget) {
            return \$model->{$model['attribute']}->name;
        },
        'filterType' => GridView::FILTER_SELECT2,
        'filter' => \\yii\\helpers\\ArrayHelper::map({$model['class']}::find()->orderBy('{$model['orderBy']}')->asArray()->all(), 'id', '{$model['orderBy']}'),
        'filterWidgetOptions' => [
            'pluginOptions' => ['allowClear' => true],
        ],
        'editableOptions' => [
            'inputType' => \\kartik\\editable\\Editable::INPUT_SELECT2,
            'options' => [
                'data' => \\yii\\helpers\\ArrayHelper::map({$model['class']}::find()->orderBy('{$model['orderBy']}')->asArray()->all(), 'id', '{$model['orderBy']}'),
            ]," . ($pull_right ? "
            'placement' => 'left'," : '') . "
        ],
        'filterInputOptions' => [
            'placeholder' => " . $this->generateString($model['label']) . "
        ],
        'format' => 'raw',
    ],";
        }
        if (in_array($attribute, $this->datetimeAttributes)) {
            return "
    [
        'class' => '\\\\kartik\\\\grid\\\\EditableColumn',
        'attribute' => '$attribute',
        'hAlign' => 'center',
        'vAlign' => 'middle',
        'width' => '10%',
        'filterType' => GridView::FILTER_DATE_RANGE,
        'filterWidgetOptions' => [
            'pluginOptions' => [
                'format' => 'DD/MM/YYYY',
                'autoclose' => true,
            ],
        ]," . ($pull_right ? "
        'filterInputOptions' => [
            'class' => 'form-control pull-right',
        ],
        " : '') . "
        'value' => function (\$model, \$key, \$index, \$widget) {
            return date('d.m.Y, H:i', \$model->$attribute);
        },
        'editableOptions' => [
            'inputType' => \\kartik\\editable\\Editable::INPUT_WIDGET,
            'widgetClass' => '\\\\kartik\\\\datecontrol\\\\DateControl',
            'options' => [
                'type' => 'datetime',
                'displayFormat' => 'php:d.m.Y, H:i:s',
                'saveFormat' => 'php:U',
                'saveTimezone' => Yii::\$app->timeZone,
                'displayTimezone' => Yii::\$app->timeZone,
                'options' => [
                    'pluginOptions' => [
                        'autoclose' => true,
                    ],
                ],
            ]," . ($pull_right ? "
            'placement' => 'left'," : '') . "
        ],
    ],";
        }
        if (in_array($attribute, ['created_at', 'updated_at'])) {
            return "
    [
        'class' => '\\\\kartik\\\\grid\\\\EditableColumn',
        'attribute' => '$attribute',
        'hAlign' => 'center',
        'vAlign' => 'middle',
        'width' => '10%',
        'filterType' => GridView::FILTER_DATE_RANGE,
        'filterWidgetOptions' => [
            'pluginOptions' => [
                'format' => 'DD/MM/YYYY',
                'autoclose' => true,
            ],
        ]," . ($pull_right ? "
        'filterInputOptions' => [
            'class' => 'form-control pull-right',
        ],
        " : '') . "
        'value' => function (\$model, \$key, \$index, \$widget) {
            return date('d.m.Y, H:i', \$model->$attribute);
        },
        'readonly' => true,
    ],";
        }
        if (in_array($attribute, $this->imageAttributes)) {
            return "
    [
        'class' => '\\\\kartik\\\\grid\\\\DataColumn',
        'attribute' => '$attribute',
        'vAlign' => 'middle',
        'hAlign' => 'center',
        'value' => function (\$model, \$index, \$widget) {
            return !empty(\$model->$attribute) ? '<"."img src=\"'.\$model->{$attribute}Url.'\" />' : '';
        },
        'format' => 'raw',
        'filter' => false,
        'enableSorting' => false,
        'mergeHeader' => false,
    ],";
        }
        if ($attribute === 'status') {
            return "
    [
        'class' => '\\\\kartik\\\\grid\\\\BooleanColumn',
        'attribute' => '$attribute',
        'vAlign' => 'middle',
        'hAlign' => 'center',
        'width' => '5%',
    ],";
        }
        if ($column && $column->phpType === 'integer') {
            if ($column->unsigned) {
                $min = 0;
            }
            if ($column->size && is_int($column->size)) {
                $max = pow(10, $column->size) - 1;
                if (!isset($min)) {
                    $min = -$max;
                }
            }
            return "
    [
        'class' => '\\\\kartik\\\\grid\\\\EditableColumn',
        'attribute' => '$attribute',
        'vAlign' => 'middle',
        'editableOptions' => [
            'inputType' => \\kartik\\editable\\Editable::INPUT_SPIN,
            'options' => [
                'pluginOptions' => [
                    'verticalbuttons' => true," . (isset($min) ? "
                    'min' => $min," : '' ) . (isset($max) ? "
                    'max' => $max," : '' ) . "
                ],
            ]," . ($pull_right ? "
            'placement' => 'left'," : '') . "
        ],
    ],";
        }
        if ($column && $column->phpType === 'double') {
            if ($column->unsigned) {
                $min = 0;
            }
            if ($column->size && is_int($column->size) && is_int($column->scale)) {
                $max = pow(10, $column->size - $column->scale) - 1;
                if (!isset($min)) {
                    $min = -$max;
                }
                $step = pow(10, -1 * $column->scale);
                $decimals = $column->scale;
            } else {
                $step = 0.0001;
                $decimals = 4;
            }
            return "
    [
        'class' => '\\\\kartik\\\\grid\\\\EditableColumn',
        'attribute' => '$attribute',
        'vAlign' => 'middle',
        'value' => function (\$model, \$key, \$index, \$widget) {
            return number_format(\$model->$attribute, $decimals);
        },
        'editableOptions' => [
            'inputType' => \\kartik\\editable\\Editable::INPUT_SPIN,
            'options' => [
                'pluginOptions' => [
                    'verticalbuttons' => true," . (isset($min) ? "
                    'min' => $min," : '' ) . (isset($max) ? "
                    'max' => $max," : '' ) . "
                    'step' => $step,
                    'decimals' => $decimals,
                ],
            ]," . ($pull_right ? "
            'placement' => 'left'," : '') . "
        ],
    ],";
        }
        return "
    [
        'class' => '\\\\kartik\\\\grid\\\\EditableColumn',
        'attribute' => '$attribute',
        'vAlign' => 'middle'," . ($pull_right ? "
        'editableOptions' => ['placement' => 'left']," : '') . "
    ],";
    }

    /**
     * Generates "kartik" active field.
     * @param string $attribute
     * @param null|\yii\db\ColumnSchema $column
     * @return bool|string
     */
    protected function generateKartikActiveField($attribute, $column=null)
    {
        if ($model = $this->isIdModel($attribute)) {
            return "\$form->field(\$model, '$attribute')->widget(\\kartik\\widgets\\Select2::classname(), [
        'data' => \\yii\\helpers\\ArrayHelper::map({$model['class']}::find()->orderBy('{$model['orderBy']}')->asArray()->all(), 'id', '{$model['orderBy']}'),
        'options' => ['placeholder' => " . $this->generateString("Select a {$model['label']} ...") . "],
        'pluginOptions' => [
            'allowClear' => true,
        ],
    ]);";
        } else {
            if (in_array($attribute, $this->datetimeAttributes)) {
                return "\$form->field(\$model, '$attribute')->widget(\\kartik\\datecontrol\\DateControl::classname(), [
        'type' => 'datetime',
        'displayFormat' => 'php:d/m/Y H:i:s',
        'saveFormat' => 'php:U',
        'saveTimezone' => Yii::\$app->timeZone,
        'displayTimezone' => Yii::\$app->timeZone,
    ]);";
            }
            if (in_array($attribute, $this->imageAttributes)) {
                return "\$form->field(\$model, '$attribute')->widget(\\kartik\\widgets\\FileInput::className(), [
        'pluginOptions' => [
            'language' => 'ru',
            'showUpload' => false,
            'maxFileCount' => 1,
            'initialPreviewShowDelete' => false,
            'initialPreview' => \$model->{$attribute}Url ? [\"<"."img class=\\\"file-preview-image\\\" src=\\\"{\$model->{$attribute}Url}\\\">\"] : [],
        ],
        'options' => ['accept' => 'image/*'],
    ]),
    Html::hiddenInput(Html::getInputName(\$model, '$attribute'), \$model->$attribute)";
            }
            switch ($attribute) {
                case 'updated_at':
                case 'created_at':
                    return "\$form->field(\$model, '$attribute')->widget(\\kartik\\datecontrol\\DateControl::classname(), [
        'type' => 'datetime',
        'displayFormat' => 'php:d/m/Y H:i:s',
        'saveFormat' => 'php:U',
        'saveTimezone' => Yii::\$app->timeZone,
        'displayTimezone' => Yii::\$app->timeZone,
        'options' => ['disabled' => true],
    ]);";
                case 'status':
                    return "\$form->field(\$model, '$attribute')->dropDownList([0 => ".$this->generateString('Inactive').", 1 => ".$this->generateString('Active')."])";
            }
        }
        if ($column && $column->phpType === 'integer') {
            if ($column->unsigned) {
                $min = 0;
            }
            if ($column->size && is_int($column->size)) {
                $max = pow(10, $column->size) - 1;
                if (!isset($min)) {
                    $min = -$max;
                }
            }
            return "\$form->field(\$model, '$attribute')->widget(\\kartik\\widgets\\TouchSpin::classname(), [
        'pluginOptions' => [
            'verticalbuttons' => true," . (isset($min) ? "
            'min' => $min," : '' ) . (isset($max) ? "
            'max' => $max," : '' ) . "
        ]
    ]);";
        }
        if ($column && $column->phpType === 'double') {
            if ($column->unsigned) {
                $min = 0;
            }
            if ($column->size && is_int($column->size) && is_int($column->scale)) {
                $max = pow(10, $column->size - $column->scale) - 1;
                if (!isset($min)) {
                    $min = -$max;
                }
                $step = pow(10, -1 * $column->scale);
                $decimals = $column->scale;
            } else {
                $step = 0.0001;
                $decimals = 4;
            }
            return "\$form->field(\$model, '$attribute')->widget(\\kartik\\widgets\\TouchSpin::classname(), [
        'pluginOptions' => [
            'verticalbuttons' => true," . (isset($min) ? "
            'min' => $min," : '' ) . (isset($max) ? "
            'max' => $max," : '' ) . "
            'step' => $step,
            'decimals' => $decimals,
        ]
    ]);";
        }
        return false;
    }

    /**
     * Generates code for active field
     * @param string $attribute
     * @return string
     */
    public function generateActiveField($attribute)
    {
        $tableSchema = $this->getTableSchema();
        if ($tableSchema === false || !isset($tableSchema->columns[$attribute])) {
            if (preg_match('/^(password|pass|passwd|passcode)$/i', $attribute)) {
                return "\$form->field(\$model, '$attribute')->passwordInput()";
            } elseif ($field = $this->generateKartikActiveField($attribute)) {
                return $field;
            } else {
                return "\$form->field(\$model, '$attribute')";
            }
        }
        $column = $tableSchema->columns[$attribute];
        if ($column->phpType === 'boolean') {
            return "\$form->field(\$model, '$attribute')->checkbox()";
        } elseif ($column->type === 'text') {
            return "\$form->field(\$model, '$attribute')->textarea(['rows' => 6])";
        } else {
            if (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name)) {
                $input = 'passwordInput';
            } else {
                $input = 'textInput';
            }
            if ($field = $this->generateKartikActiveField($attribute, $column)) {
                return $field;
            }
            if (is_array($column->enumValues) && count($column->enumValues) > 0) {
                $dropDownOptions = [];
                foreach ($column->enumValues as $enumValue) {
                    $dropDownOptions[$enumValue] = Inflector::humanize($enumValue);
                }
                return "\$form->field(\$model, '$attribute')->dropDownList("
                . preg_replace("/\n\s*/", ' ', VarDumper::export($dropDownOptions)).", ['prompt' => ''])";
            } elseif ($column->phpType !== 'string' || $column->size === null) {
                return "\$form->field(\$model, '$attribute')->$input()";
            } else {
                return "\$form->field(\$model, '$attribute')->$input(['maxlength' => true])";
            }
        }
    }

    /**
     * Generates validation rules for the search model.
     * @return array the generated validation rules
     */
    public function generateSearchRules()
    {
        $datetimeAttributes = array_merge($this->datetimeAttributes, ['created_at', 'updated_at']);
        if (($table = $this->getTableSchema()) === false) {
            $columns = $this->getColumnNames();
            $_columns = array_diff($columns, $datetimeAttributes);
            $rules = [];
            if (!empty($_columns)) {
                $rules[] = "[['" . implode("', '", $_columns) . "'], 'safe']";
            }
            if (count($_columns) != count($columns)) {
                $_columns = array_intersect($columns, $datetimeAttributes);
                $rules[] = "[['" . implode("', '", $_columns) . "'], 'filter', 'filter' => 'trim'],";
                $rules[] = "[['" . implode("', '", $_columns) . "'], 'date', 'format' => 'dd/MM/YYYY - dd/MM/YYYY', 'message' => " .
                    $this->generateString('Invalid date range format.') . "],";
            }
            return $rules;
        }

        $types = [];
        foreach ($table->columns as $column) {
            if (in_array($column->name, $datetimeAttributes)) {
                $types["filter', 'filter' => 'trim"][] = $column->name;
                $types['datetime'][] = $column->name;
                continue;
            }
            switch ($column->type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                    $types['integer'][] = $column->name;
                    break;
                case Schema::TYPE_BOOLEAN:
                    $types['boolean'][] = $column->name;
                    break;
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DOUBLE:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $types['number'][] = $column->name;
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                default:
                    $types['safe'][] = $column->name;
                    break;
            }
        }

        $rules = [];
        foreach ($types as $type => $columns) {
            if ($type == 'datetime') {
                $rules[] = "[['" . implode("', '", $columns) . "'], 'date', 'format' => 'dd/MM/YYYY - dd/MM/YYYY', 'message' => " .
                    $this->generateString('Invalid date range format.') . "]";
            } else {
                $rules[] = "[['" . implode("', '", $columns) . "'], '$type']";
            }
        }

        return $rules;
    }

    /**
     * Generates search conditions
     * @return array
     */
    public function generateSearchConditions()
    {
        $columns = [];
        if (($table = $this->getTableSchema()) === false) {
            $class = $this->modelClass;
            /* @var $model \yii\base\Model */
            $model = new $class();
            foreach ($model->attributes() as $attribute) {
                $columns[$attribute] = 'unknown';
            }
        } else {
            foreach ($table->columns as $column) {
                $columns[$column->name] = $column->type;
            }
        }

        $datetimeAttributes = array_merge($this->datetimeAttributes, ['created_at', 'updated_at']);
        $dateAttributes = [];
        $likeConditions = [];
        $hashConditions = [];
        foreach ($columns as $column => $type) {
            switch ($type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                case Schema::TYPE_BOOLEAN:
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DOUBLE:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                    if (in_array($column, $datetimeAttributes)) {
                        $dateAttributes[] = $column;//"'{$column}' => !empty(\$this->{$column}) ? date_create_from_format('d/m/Y H:i:s', \$this->{$column})->format('U') : null,";
                    } else {
                        $hashConditions[] = "'{$column}' => \$this->{$column},";
                    }
                    break;
                default:
                    $likeConditions[] = "->andFilterWhere(['like', '{$column}', \$this->{$column}])";
                    break;
            }
        }

        $conditions = [];

        if (!empty($dateAttributes)) {
            $dateAttributes = implode("', '", $dateAttributes);
            $conditions[] = "foreach (['$dateAttributes'] as \$attribute) {
            if (!empty(\$this->\$attribute)) {
                \$date = explode('-', \$this->\$attribute);
                \$query->andWhere(['>=', \$attribute, date_create_from_format('d/m/Y', trim(\$date[0]))->format('U')])
                    ->andWhere(['<=', \$attribute, date_create_from_format('d/m/Y', trim(\$date[1]))->format('U') + 86400]);
            }
        }";
        }

        if (!empty($hashConditions)) {
            $conditions[] = "\$query->andFilterWhere([\n"
                . str_repeat(' ', 12) . implode("\n" . str_repeat(' ', 12), $hashConditions)
                . "\n" . str_repeat(' ', 8) . "]);\n";
        }
        if (!empty($likeConditions)) {
            $conditions[] = "\$query" . implode("\n" . str_repeat(' ', 12), $likeConditions) . ";\n";
        }

        return $conditions;
    }

    /**
     * @inheritdoc
     */
    public function generateString($string = '', $placeholders = [], $tabs = 0, $tab = '    ')
    {
        if ($this->enableI18N && $this->addingI18NStrings && !isset($this->I18NStrings[$string])) {
            $this->I18NStrings[$string] = $string;
        }
        $string = addslashes($string);
        if ($this->enableI18N) {
            // If there are placeholders, use them
            if (!empty($placeholders)) {
                $ph = ', ' . str_replace('{%__PRIME__%}', "'", preg_replace('/\'php:([^\']+)\'/', '\1',
                        str_replace("\\'", '{%__PRIME__%}', str_replace("\n", "\n" . str_repeat($tab, $tabs), VarDumper::export($placeholders)))));
            } else {
                $ph = '';
            }
            $str = "Yii::t('" . $this->messageCategory . "', '" . $string . "'" . $ph . ")";
        } else {
            // No I18N, replace placeholders by real words, if any
            if (!empty($placeholders)) {
                $phKeys = array_map(function($word) {
                    return '{' . $word . '}';
                }, array_keys($placeholders));
                $phValues = array_values($placeholders);
                $str = "'" . str_replace($phKeys, $phValues, $string) . "'";
            } else {
                // No placeholders, just the given string
                $str = "'" . $string . "'";
            }
        }
        return $str;
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $this->getModelAttributes();
        return;
        //$this->readModel($this->modelClass);
        $this->relationsFields();
        $files = parent::generate();
        if ($this->enableI18N && $this->addingI18NStrings && !empty($this->I18NStrings)) {
            if (($pos = strpos($this->controllerClass, '\\controllers\\')) !== false) {
                $path = rtrim(Yii::getAlias('@' . str_replace('\\', '/',
                            ltrim(substr($this->controllerClass, 0, $pos), '\\'))), '/') . '/messages';
                if (is_dir($path)) {
                    foreach (array_diff(scandir($path), ['.', '..']) as $language) {
                        $filename = "$path/$language/{$this->messageCategory}.php";
                        $messages = file_exists($filename) ? require($filename) : [];
                        $messages = array_merge($messages, array_diff_key($this->I18NStrings, $messages));
                        $files[] = new CodeFile($filename, "<?php\nreturn " . VarDumper::export($messages) . ";");
                    }
                }
            }
        }
        return $files;
    }

    /**
     * Returns table name of {{modelClass}}.
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getTableName()
    {
        static $name;
        if (!isset($name)) {
            /** @var $model \yii\db\ActiveRecord */
            $model = Yii::createObject($this->modelClass);
            $name = rtrim(ltrim($model->tableName(), '{%'), '}');
        }
        return $name;
    }

    /**
     * Generates a class name from the specified table name.
     * @param string $tableName the table name (which may contain schema prefix)
     * @param boolean $useSchemaName should schema name be included in the class name, if present
     * @return string the generated class name
     */
    protected function generateClassName($tableName, $useSchemaName = null)
    {
        if (isset($this->classNames[$tableName])) {
            return $this->classNames[$tableName];
        }

        $schemaName = '';
        $fullTableName = $tableName;
        if (($pos = strrpos($tableName, '.')) !== false) {
            if (($useSchemaName === null && true) || $useSchemaName) {
                $schemaName = substr($tableName, 0, $pos) . '_';
            }
            $tableName = substr($tableName, $pos + 1);
        }

        $db = $this->getDbConnection();
        $patterns = [];
        $patterns[] = "/^{$db->tablePrefix}(.*?)$/";
        $patterns[] = "/^(.*?){$db->tablePrefix}$/";
        if (strpos($this->tableName, '*') !== false) {
            $pattern = $this->tableName;
            if (($pos = strrpos($pattern, '.')) !== false) {
                $pattern = substr($pattern, $pos + 1);
            }
            $patterns[] = '/^' . str_replace('*', '(\w+)', $pattern) . '$/';
        }
        $className = $tableName;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $tableName, $matches)) {
                $className = $matches[1];
                break;
            }
        }

        return $this->classNames[$fullTableName] = Inflector::id2camel($schemaName.$className, '_');
    }

    /**
     * @return array the generated relation declarations
     */
    protected function relationsFields()
    {
        if (!$this->generateRelationsFields) {
            return;
        }

        $db = $this->getDbConnection();

        $schema = $db->getSchema();
        if ($schema->hasMethod('getSchemaNames')) { // keep BC to Yii versions < 2.0.4
            try {
                $schemaNames = $schema->getSchemaNames();
            } catch (NotSupportedException $e) {
                // schema names are not supported by schema
            }
        }
        if (!isset($schemaNames)) {
            if (($pos = strpos($this->tableName, '.')) !== false) {
                $schemaNames = [substr($this->tableName, 0, $pos)];
            } else {
                $schemaNames = [''];
            }
        }

        \ChromePhp::log($this->tableName);
        $relations = [];
        foreach ($schemaNames as $schemaName) {
            foreach ($db->getSchema()->getTableSchemas($schemaName) as $table) {
                $className = $this->generateClassName($table->fullName);
                foreach ($table->foreignKeys as $refs) {
                    $refTable = $refs[0];
                    $refTableSchema = $db->getTableSchema($refTable);
                    unset($refs[0]);
                    $fks = array_keys($refs);

                    $relationName = $this->generateRelationName($relations, $table, $fks[0], false);
                    $relations[$table->fullName][$relationName] = true;

                    $uniqueKeys = [$table->primaryKey];
                    try {
                        $uniqueKeys = array_merge($uniqueKeys, $db->getSchema()->findUniqueIndexes($table));
                    } catch (NotSupportedException $e) {
                        // ignore
                    }
                    $hasMany = true;
                    foreach ($uniqueKeys as $uniqueKey) {
                        if (count(array_diff(array_merge($uniqueKey, $fks), array_intersect($uniqueKey, $fks))) === 0) {
                            $hasMany = false;
                            break;
                        }
                    }
                    $relationName = $this->generateRelationName($relations, $refTableSchema, $className, $hasMany);
                    $relations[$refTableSchema->fullName][$relationName] = true;
                }

                if (($fks = $this->checkPivotTable($table)) === false) {
                    continue;
                }

                $relations = $this->generateManyManyRelations($table, $fks, $relations);
            }
        }
    }

    /**
     * Checks if the given table is a junction table.
     * For simplicity, this method only deals with the case where the pivot contains two PK columns,
     * each referencing a column in a different table.
     * @param $table \yii\db\TableSchema the table being checked
     * @return array|boolean the relevant foreign key constraint information if the table is a junction table,
     * or false if the table is not a junction table.
     */
    protected function checkPivotTable($table)
    {
        $pk = $table->primaryKey;
        if (count($pk) !== 2) {
            return false;
        }
        $fks = [];
        foreach ($table->foreignKeys as $refs) {
            if (count($refs) === 2) {
                if (isset($refs[$pk[0]])) {
                    $fks[$pk[0]] = [$refs[0], $refs[$pk[0]]];
                } elseif (isset($refs[$pk[1]])) {
                    $fks[$pk[1]] = [$refs[0], $refs[$pk[1]]];
                }
            }
        }
        if (count($fks) === 2 && $fks[$pk[0]][0] !== $fks[$pk[1]][0]) {
            return $fks;
        } else {
            return false;
        }
    }

    /**
     * Generate a relation name for the specified table and a base name.
     * @param array $relations the relations being generated currently.
     * @param \yii\db\TableSchema $table the table schema
     * @param string $key a base name that the relation name may be generated from
     * @param boolean $multiple whether this is a has-many relation
     * @return string the relation name
     */
    protected function generateRelationName($relations, $table, $key, $multiple)
    {
        if (!empty($key) && substr_compare($key, 'id', -2, 2, true) === 0 && strcasecmp($key, 'id')) {
            $key = rtrim(substr($key, 0, -2), '_');
        }
        if ($multiple) {
            $key = Inflector::pluralize($key);
        }
        $name = $rawName = Inflector::id2camel($key, '_');
        $i = 0;
        while (isset($table->columns[lcfirst($name)])) {
            $name = $rawName . ($i++);
        }
        while (isset($relations[$table->fullName][$name])) {
            $name = $rawName . ($i++);
        }

        return $name;
    }

    /**
     * Generates relations using a junction table by adding an extra viaTable().
     * @param \yii\db\TableSchema the table being checked
     * @param array $fks obtained from the checkPivotTable() method
     * @param array $relations
     * @return array modified $relations
     */
    private function generateManyManyRelations($table, $fks, $relations)
    {
        $db = $this->getDbConnection();
        $tables = $className = $tableSchema = [];

        foreach ([0, 1] as $id) {
            $tables[$id] = $fks[$table->primaryKey[$id]][0];
            $className[$id] = $this->generateClassName($tables[$id]);
            $tableSchema[$id] = $db->getTableSchema($tables[$id]);
        }

        foreach ([0, 1] as $id) {
            $n_id = $id == 0 ? 1 : 0;
            if ($this->tableName === $tableSchema[$id]->name) {
                $columns = $tableSchema[$id]->getColumnNames();
                $namedAttributes = array_intersect(['name', 'title', 'label'], $columns);
                $relationName = $this->generateRelationName($relations, $tableSchema[$id], $table->primaryKey[$n_id], true);
                $this->relations[] = [
                    'relation' => $relationName,
                    'property' => lcfirst($relationName),
                    'many_class' => '\\' . trim($this->modelNS, '\\') . '\\' . $this->generateClassName($table->name),
                    'many_table' => $table->name,
                    'many_fk' => $table->primaryKey[$id],
                    'many_id' => $fks[$table->primaryKey[$id]][1],
                    'many_many_class' => '\\' . trim($this->modelNS, '\\') . '\\' . $className[$n_id],
                    'many_many_table' => $tables[$id],
                    'many_many_fk' => $table->primaryKey[$n_id],
                    'many_many_id' => $fks[$table->primaryKey[$n_id]][1],
                    'many_many_title' => !empty($namedAttributes) ? $namedAttributes[0] : $columns[0],
                    'label' => Inflector::pluralize($label = Inflector::camel2words($className[$n_id])),
                    'single_label' => $label,
                ];
            }
        }

        return $relations;

        $db = $this->getDbConnection();

        $table0 = $fks[$table->primaryKey[0]][0];
        $table1 = $fks[$table->primaryKey[1]][0];
        $table0Schema = $db->getTableSchema($table0);
        $table1Schema = $db->getTableSchema($table1);

        $relationName0 = $this->generateRelationName($relations, $table0Schema, $table->primaryKey[1], true);
        $relations[$table0Schema->fullName][$relationName0] = true;
        $relationName1 = $this->generateRelationName($relations, $table1Schema, $table->primaryKey[0], true);
        $relations[$table1Schema->fullName][$relationName1] = true;

        if (count($table->foreignKeys) == 2) {
            if (count($db->getTableSchema($table->name)->getColumnNames()) == 2) {
                foreach ($table->foreignKeys as $index => $fk) {
                    \ChromePhp::log("fk: {$fk[0]}");
                    if ($fk[0] == $this->tableName) {
                        \ChromePhp::log("fk: {$fk[0]}");
                        $key = ($index+1) % 2;
                        $many_fk = array_values(array_diff(array_keys($table->foreignKeys[$index]), [0]))[0];
                        $many_many_fk = array_values(array_diff(array_keys($table->foreignKeys[$key]), [0]))[0];
                        $columns = $db->getTableSchema($table->foreignKeys[$key][0])->getColumnNames();
                        $namedAttributes = array_intersect(['name', 'title', 'label'], $columns);
                        $label = Inflector::camel2words($table->foreignKeys[$key][0]);
                        $this->relations[] = [
                            'relation' => $relationName0,
                            'property' => lcfirst($relationName0),
                            'many_class' => '\\' . trim($this->modelNS, '\\') . '\\' . $this->generateClassName($table->name),
                            'many_table' => $table->name,
                            'many_fk' => $many_fk,
                            'many_id' => $table->foreignKeys[$index][$many_fk],
                            'many_many_class' => '\\' . trim($this->modelNS, '\\') . '\\' . $this->generateClassName($table->foreignKeys[$key][0]),
                            'many_many_table' => $table->foreignKeys[$key][0],
                            'many_many_fk' => $many_many_fk,
                            'many_many_id' => $table->foreignKeys[$key][$many_many_fk],
                            'many_many_title' => !empty($namedAttributes) ? $namedAttributes[0] : $columns[0],
                            'label' => Inflector::pluralize($label),
                            'single_label' => $label,
                        ];
                        break;
                    }
                }
            }
        }
        return $relations;
    }
}

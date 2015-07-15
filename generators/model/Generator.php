<?php

namespace mervick\adminlte\gii\generators\model;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Connection;
use yii\db\Schema;
use yii\gii\CodeFile;
use yii\helpers\Inflector;
use yii\helpers\VarDumper;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\BaseActiveRecord;

/**
 * This generator will generate one or multiple ActiveRecord classes for the specified database table.
 *
 * @author Andrey Izman <izmanw@gmail.com>
 */
class Generator extends \yii\gii\Generator
{
    public $db = 'db';
    public $ns = 'common\models';
    public $tableName;
    public $modelClass;
    public $baseClass = 'yii\db\ActiveRecord';
    public $queryNs = 'common\models';
    public $queryClass;
    public $queryBaseClass = 'yii\db\ActiveQuery';
    public $generateRelations = true;
    public $generateLabelsFromComments = true;
    public $useTablePrefix = true;
    public $useSchemaName = true;
    public $generateQuery = true;
    public $enableI18N = true;
    public $imagesPath = '@images';
    public $imagesDomain = 'img.{$domain}';
    public $enableTimestampBehavior = false;
    public $addingI18NStrings = true;
    public $messagesPaths = '@backend/messages';

    public $imageAttributes = ['img', 'image', 'logo', 'avatar', 'picture'];
    public $relationsSetters = [];

    protected $I18NStrings = [];


    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'AdminLTE Model Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator generates an ActiveRecord class for the specified database table.';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['db', 'ns', 'tableName', 'modelClass', 'baseClass', 'queryNs', 'queryClass', 'queryBaseClass'], 'filter', 'filter' => 'trim'],
            [['ns', 'queryNs'], 'filter', 'filter' => function($value) { return trim($value, '\\'); }],
            [['db', 'ns', 'tableName', 'baseClass', 'queryNs', 'queryBaseClass'], 'required'],
            [['db', 'modelClass', 'queryClass'], 'match', 'pattern' => '/^\w+$/', 'message' => 'Only word characters are allowed.'],
            [['ns', 'baseClass', 'queryNs', 'queryBaseClass'], 'match', 'pattern' => '/^[\w\\\\]+$/', 'message' => 'Only word characters and backslashes are allowed.'],
            [['tableName'], 'match', 'pattern' => '/^(\w+\.)?([\w\*]+)$/', 'message' => 'Only word characters, and optionally an asterisk and/or a dot are allowed.'],
            [['db'], 'validateDb'],
            [['ns', 'queryNs'], 'validateNamespace'],
            [['tableName'], 'validateTableName'],
            [['modelClass'], 'validateModelClass', 'skipOnEmpty' => false],
            [['baseClass'], 'validateClass', 'params' => ['extends' => ActiveRecord::className()]],
            [['queryBaseClass'], 'validateClass', 'params' => ['extends' => ActiveQuery::className()]],
            [['generateRelations', 'generateLabelsFromComments', 'useTablePrefix', 'useSchemaName', 'generateQuery'], 'boolean'],
            [['enableI18N'], 'boolean'],
            [['messageCategory'], 'validateMessageCategory', 'skipOnEmpty' => false],
            [['imagesDomain', 'imagesPath'], 'filter', 'filter' => 'trim'],
            [['imagesPath'], 'filter', 'filter' => function($value) { return trim($value, '/'); }],
            [['imagesDomain', 'imagesPath'], 'required'],
            [['addingI18NStrings'], 'boolean'],
            [['imagesDomain'], 'match', 'pattern' => '/^(?:[\w](?:[\w-]+[\w])?\.(?:{\$domain})|(?:[\w](?:[0-9\w\-\.]+)?[\w]\.[\w]+))|(?:@[\w_-]+)$/', 'message' => 'No valid images domain.'],
            [['messagesPaths'], 'validateMessagesPaths'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'ns' => 'Model Namespace',
            'db' => 'Database Connection ID',
            'tableName' => 'Table Name',
            'modelClass' => 'Model Class',
            'baseClass' => 'Model Base Class',
            'generateRelations' => 'Generate Relations',
            'generateLabelsFromComments' => 'Generate Labels from DB Comments',
            'generateQuery' => 'Generate ActiveQuery',
            'queryNs' => 'ActiveQuery Namespace',
            'queryClass' => 'ActiveQuery Class',
            'queryBaseClass' => 'ActiveQuery Base Class',
            'addingI18NStrings' => 'Adding I18N Strings',
            'messagesPaths' => 'I18N Messages Path',
            'imagesDomain' => 'Images Domain',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'ns' => 'This is the namespace of the ActiveRecord class to be generated, e.g., <code>app\models</code>',
            'db' => 'This is the ID of the DB application component.',
            'tableName' => 'This is the name of the DB table that the new ActiveRecord class is associated with, e.g. <code>post</code>.
                The table name may consist of the DB schema part if needed, e.g. <code>public.post</code>.
                The table name may end with asterisk to match multiple table names, e.g. <code>tbl_*</code>
                will match tables who name starts with <code>tbl_</code>. In this case, multiple ActiveRecord classes
                will be generated, one for each matching table name; and the class names will be generated from
                the matching characters. For example, table <code>tbl_post</code> will generate <code>Post</code>
                class.',
            'modelClass' => 'This is the name of the ActiveRecord class to be generated. The class name should not contain
                the namespace part as it is specified in "Namespace". You do not need to specify the class name
                if "Table Name" ends with asterisk, in which case multiple ActiveRecord classes will be generated.',
            'baseClass' => 'This is the base class of the new ActiveRecord class. It should be a fully qualified namespaced class name.',
            'generateRelations' => 'This indicates whether the generator should generate relations based on
                foreign key constraints it detects in the database. Note that if your database contains too many tables,
                you may want to uncheck this option to accelerate the code generation process.',
            'generateLabelsFromComments' => 'This indicates whether the generator should generate attribute labels
                by using the comments of the corresponding DB columns.',
            'useTablePrefix' => 'This indicates whether the table name returned by the generated ActiveRecord class
                should consider the <code>tablePrefix</code> setting of the DB connection. For example, if the
                table name is <code>tbl_post</code> and <code>tablePrefix=tbl_</code>, the ActiveRecord class
                will return the table name as <code>{{%post}}</code>.',
            'useSchemaName' => 'This indicates whether to include the schema name in the ActiveRecord class
                when it\'s auto generated. Only non default schema would be used.',
            'generateQuery' => 'This indicates whether to generate ActiveQuery for the ActiveRecord class.',
            'queryNs' => 'This is the namespace of the ActiveQuery class to be generated, e.g., <code>app\models</code>',
            'queryClass' => 'This is the name of the ActiveQuery class to be generated. The class name should not contain
                the namespace part as it is specified in "ActiveQuery Namespace". You do not need to specify the class name
                if "Table Name" ends with asterisk, in which case multiple ActiveQuery classes will be generated.',
            'queryBaseClass' => 'This is the base class of the new ActiveQuery class. It should be a fully qualified namespaced class name.',
            'imagesDomain' => 'Images sub-domain pattern, e.g., <code>img.{$domain}</code> on domain <code>test.com</code>
                will be render as <code>img.test.com</code>, also you can set the full domain name, e.g., <code>images.example.com</code>.
                Be sure that the web root of this domain linked to your <code>imagesPath</code>.',
//            'modelIcon' => 'This is a model icon, e.g., <code>glyphicon-asterisk</code>',
            'imagesPath' => 'This is a path to upload images. May be path alias use this, e.g., <code>@app/web/img</code>',
            'addingI18NStrings' => 'Enables the adding non existing I18N strings to the message category files.',
            'messagesPaths' => 'This is a path to I18N messages, ability to set multiple directories separated by commas, e.g.
                <code>@backend/messages,@frontend/messages</code>',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function autoCompleteData()
    {
        $db = $this->getDbConnection();
        if ($db !== null) {
            return [
                'tableName' => function () use ($db) {
                    return $db->getSchema()->getTableNames();
                },
            ];
        } else {
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates()
    {
        // @todo make 'query.php' to be required before 2.1 release
        return ['model.php'/*, 'query.php'*/];
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), [
            'ns', 'db', 'baseClass', 'generateRelations', 'generateLabelsFromComments', 'queryNs', 'queryBaseClass',
            'addingI18NStrings', 'messagesPaths', 'imagesPath', 'imagesDomain',
        ]);
    }

    /**
     * Generates the attribute labels for the specified table.
     * @param \yii\db\TableSchema $tableSchema the table schema
     * @param string $tableName
     * @return array the generated attribute labels (name => label)
     */
    public function generateLabels($tableSchema, $tableName)
    {
        $labels = [];

        $refs = $this->foreignAttributes($tableSchema, $tableName);

        foreach ($tableSchema->columns as $column) {
            if ($this->generateLabelsFromComments && !empty($column->comment)) {
                $labels[$column->name] = $column->comment;
            } elseif (!strcasecmp($column->name, 'id')) {
                $labels[$column->name] = 'ID';
            } elseif (isset($refs[$column->name])) {
                $labels[$column->name] = Inflector::camel2words($refs[$column->name]['table']);
            } else {
                $labels[$column->name] = Inflector::camel2words($column->name);
            }
        }

        if (!empty($this->relationsSetters[$tableName])) {
            foreach ($this->relationsSetters[$tableName] as $relationSetter) {
                $labels[$relationSetter['property']] = $relationSetter['label'];
            }
        }

        return $labels;
    }

    /**
     * Get statuses constants to begin at model
     * @param \yii\db\TableSchema $tableSchema
     * @return string
     */
    public function statusConstants($tableSchema)
    {
        foreach ($tableSchema->columns as $column) {
            if ($column->name == 'status') {
                return "    const STATUS_INACTIVE = 0;\n    const STATUS_ACTIVE = 1;\n";
            }
        }
        return '';
    }

    /**
     * Returns timestamp attributes which will be auto updating.
     * @param \yii\db\TableSchema $tableSchema the table schema
     * @param string $tableName
     * @return array
     */
    public function timestampAttributes($tableSchema, $tableName)
    {
        static $attributes;
        if (!isset($attributes)) {
            $attributes = [];
        }

        if (!isset($attributes[$tableName])) {
            $attributes[$tableName] = [];
            foreach ($tableSchema->columns as $column) {
                if ($column->name == 'created_at' || $column->name == 'updated_at') {
                    $attributes[$tableName][] = $column->name;
                }
            }
        }

        return $attributes[$tableName];
    }

    /**
     * Returns the models image attributes.
     * @param \yii\db\TableSchema $tableSchema the table schema
     * @param string $tableName
     * @return array
     */
    public function imageAttributes($tableSchema, $tableName)
    {
        static $attributes;
        if (!isset($attributes)) {
            $attributes = [];
        }

        if (!isset($attributes[$tableName])) {
            $attributes[$tableName] = [];
            foreach ($tableSchema->columns as $column) {
                if (in_array($column->name, $this->imageAttributes)) {
                    $attributes[$tableName][] = $column->name;
                }
            }
        }

        return $attributes[$tableName];
    }

    /**
     * Exports var with php code and inserts the tabs
     * @param mixed $var
     * @param int $insert_tabs
     * @param string $tab_string
     * @return string
     */
    public function exportVar($var, $insert_tabs = 0, $tab_string = '    ')
    {
        if (empty($tab_string)) {
            $tab_string = '    ';
        }

        $var = preg_replace('~\'`([^\'`]+)`\'~', '$1', VarDumper::export($var));
        $var = $this->formatCode($var, $insert_tabs, $tab_string);

        return $var;
    }

    /**
     * Format code
     * @param array|string $var
     * @param int $insert_tabs
     * @param string $tab_string
     * @param mixed $validate
     * @return string
     */
    public function formatCode($var, $insert_tabs = 0, $tab_string = '    ', $validate = true)
    {
        if (empty($var) || empty($validate)) {
            return '';
        }
        if (empty($tab_string)) {
            $tab_string = '    ';
        }

        $tabs = str_repeat($tab_string, $insert_tabs);

        if (is_array($var)) {
            foreach ($var as &$item) {
                if (is_array($item)) {
                    $item = $this->exportVar($item, $insert_tabs, $tab_string);
                }
            }
            return $tabs . implode("\n$tabs", $var) . "\n";
        } else {
            if ($insert_tabs > 0) {
                return str_replace("\n", "\n$tabs", $var);
            }
        }

        return $var;
    }

    /**
     * Get model behaviors
     * @param \yii\db\TableSchema $tableSchema the table schema
     * @param string $tableName
     * @return string
     */
    public function modelBehaviors($tableSchema, $tableName)
    {
        $behaviors = [];

        $timestampAttributes = $this->timestampAttributes($tableSchema, $tableName);
        if (!empty($timestampAttributes)) {
            if (count($timestampAttributes) == 2) {
                $behaviors[] = '`TimestampBehavior::className()`';
            } else {
                $behavior = [
                    'class' => '`TimestampBehavior::className()`'
                ];

                if (in_array('created_at', $timestampAttributes)) {
                    $behavior['attributes'] = [
                        '`BaseActiveRecord::EVENT_BEFORE_INSERT`' => 'created_at',
                    ];
                } else {
                    $behavior['attributes'] = [
                        '`BaseActiveRecord::EVENT_BEFORE_INSERT`' => 'updated_at',
                        '`BaseActiveRecord::EVENT_BEFORE_UPDATE`' => 'updated_at',
                    ];
                }

                $behaviors[] = $behavior;
            }
        }

        $imageAttributes = $this->imageAttributes($tableSchema, $tableName);
        if (!empty($imageAttributes)) {
            $behavior = [
                'class' => '`ImageBehavior::className()`',
                'domain' => $this->imagesDomain,
                'upload_dir' => $this->imagesPath,
                'schema' =>  "{\$path}/{$tableName}/{\$attribute}/{\$size}",
                'attributes' => [],
            ];

            foreach ($imageAttributes as $attribute) {
                $behavior['attributes'][$attribute] = [
                    'sizes' => [
                        'default' => [
                            'size' => '200x200',
                            'format' => 'jpg',
                            'master' => 'adapt',
                        ],
                    ],
                ];
            }

            $behaviors[] = $behavior;
        }

        if (!empty($this->relationsSetters[$tableName])) {
            $behavior = [
                'class' => '`ManyManyBehavior::className()`',
                'relations' => [],
            ];

            foreach ($this->relationsSetters[$tableName] as $rs) {
                $behavior['relations'][$rs['property']] = [
                    'label' => $rs['label'],
                    'class' => $rs['many_class'],
                    'refs' => [
                        $rs['many_id'],
                        $rs['many_fk'],
                        $rs['many_many_fk'],
                    ],
                ];
            }

            $behaviors[] = $behavior;
        }

        return $this->formatCode([
            '',
            '/** @inheritdoc */',
            'public function behaviors()',
            '{',
            '    return ' . rtrim($this->exportVar($behaviors, 2)) . ';',
            '}'
        ], 1, null, $behaviors);
    }

    /**
     * Get model namespaces
     * @param \yii\db\TableSchema $tableSchema the table schema
     * @param string $tableName
     * @return string
     */
    public function modelNS($tableSchema, $tableName)
    {
        $ns = ['Yii;'];
        $ns[] = ltrim($this->baseClass, '\\') .';';

        if (!empty($this->relationsSetters[$tableName])) {
            foreach ($this->relationsSetters[$tableName] as $rs) {
                $ns[] = ltrim($rs['many_class'], '\\') .';';
            }
        }

        $timestampAttributes = $this->timestampAttributes($tableSchema, $tableName);
        if (!empty($timestampAttributes)) {
            $ns[] = 'yii\\behaviors\\TimestampBehavior;';
            if (count($timestampAttributes) < 2) {
                $ns[] = 'yii\\db\\BaseActiveRecord;';
            }
        }

        $imageAttributes = $this->imageAttributes($tableSchema, $tableName);
        if (!empty($imageAttributes)) {
            $ns[] = 'mervick\\adminlte\\behaviors\\ImageBehavior;';
        }

        if (!empty($this->relationsSetters[$tableName])) {
            $ns[] = 'mervick\\adminlte\\behaviors\\ManyManyBehavior;';
        }

        return $this->formatCode($ns, 1, 'use ');
    }

    /**
     * Get model properties
     * @param \yii\db\TableSchema $tableSchema the table schema
     * @param string $tableName
     * @param array $relations
     * @return string
     */
    public function modelPhpDocs($tableSchema, $tableName, $relations)
    {
        $docs = [];

        if (!empty($tableSchema->columns)) {
            $docs[] = 'Attributes:';
            foreach ($tableSchema->columns as $column) {
                $docs[] = "@property {$column->phpType} \${$column->name}";
            }
        }

        if (!empty($relations)) {
            $docs[] = '';
            $docs[] = 'Relations:';
            foreach ($relations as $name => $relation) {
                $docs[] = '@property ' . $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name);
            }
        }

        $timestampAttributes = $this->timestampAttributes($tableSchema, $tableName);
        if (!empty($timestampAttributes)) {
            $docs[] = '';
            $docs[] = 'Inherited from TimestampBehavior:';
            $docs[] = '@method touch(string $attribute)';
        }

        $imageAttributes = $this->imageAttributes($tableSchema, $tableName);
        if (!empty($imageAttributes)) {
            $docs[] = '';
            $docs[] = 'Inherited from ImageBehavior:';
            foreach ($imageAttributes as $attribute) {
                $docs[] = "@property string {$attribute}Url";
            }
            foreach ($imageAttributes as $attribute) {
                $docs[] = '@method string get' . ucfirst($attribute) .'Url(string $size)';
            }
        }

        if (!empty($this->relationsSetters[$tableName])) {
            $docs[] = '';
            $docs[] = 'Inherited from ManyManyBehavior:';
            $docs[] = '@method validateManyMany(string $attribute)';
            foreach ($this->relationsSetters[$tableName] as $rs) {
                $docs[] = "@method set{$rs['relation']}(array \$value)";
            }
        }

        $fullTableName = $this->generateTableName($tableName);
        return $this->formatCode([
            '/**',
            " * This is the model class for table $fullTableName",
            ' *',
            rtrim($this->formatCode($docs, 1, ' * ', $docs)),
            ' */',
        ]);
    }

    /**
     * Validate {{messagesPaths}} attribute.
     */
    public function validateMessagesPaths()
    {
        if ($this->enableI18N && $this->addingI18NStrings) {
            if (empty($this->messagesPaths)) {
                $this->addError('messagesPaths', 'This field is required.');
            } else {
                foreach (explode(',', $this->messagesPaths) as $alias) {
                    if (!($path = Yii::getAlias($alias, false))) {
                        $this->addError('messagesPaths', "Invalid path alias: $alias");
                        break;
                    } elseif (!is_dir($path)) {
                        $this->addError('messagesPaths', "Enable to read path '$path'.");
                        break;
                    }
                }
            }
        }
    }

    /**
     * Generates validation rules for the specified table.
     * @param \yii\db\TableSchema $tableSchema the table schema
     * @param string $tableName
     * @return array the generated validation rules
     */
    public function generateRules($tableSchema, $tableName)
    {
        $rules = $types = $lengths = $extra = [];
        foreach ($tableSchema->columns as $column) {
            if ($column->autoIncrement) {
                continue;
            }
            if (!$column->allowNull && $column->defaultValue === null &&
                    (!in_array($column->name, array_merge(['created_at', 'updated_at'], $this->imageAttributes)))) {
                $types['required'][] = $column->name;
            }
            if ($column->name == 'status') {
                $extra[] = "[['{$column->name}'], 'default', 'value' => self::STATUS_ACTIVE]";
                $extra[] = "[['{$column->name}'], 'in', 'range' => [self::STATUS_INACTIVE, self::STATUS_ACTIVE]]";
            }
            if (in_array($column->name, $this->imageAttributes)) {
                $types['safe'][] = $column->name;
                $types["file', 'extensions' => 'jpg, jpeg, gif, png"][] = $column->name;
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
                case 'double': // Schema::TYPE_DOUBLE, which is available since Yii 2.0.3
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $types['number'][] = $column->name;
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                    $types['safe'][] = $column->name;
                    break;
                default: // strings
                    if ($column->size > 0) {
                        $lengths[$column->size][] = $column->name;
                    } else {
                        $types['string'][] = $column->name;
                    }
            }
        }

        if (!empty($this->relationsSetters[$tableName])) {
            foreach ($this->relationsSetters[$tableName] as $rs) {
                $types["validateManyMany"][] = $rs['property'];
            }
        }

        foreach ($types as $type => $columns) {
            $rules[] = "[['" . implode("', '", $columns) . "'], '$type']";
        }
        foreach ($lengths as $length => $columns) {
            $rules[] = "[['" . implode("', '", $columns) . "'], 'string', 'max' => $length]";
        }
        $rules = array_merge($rules, $extra);

        // Unique indexes rules
        try {
            $db = $this->getDbConnection();
            $uniqueIndexes = $db->getSchema()->findUniqueIndexes($tableSchema);
            foreach ($uniqueIndexes as $uniqueColumns) {
                // Avoid validating auto incremental columns
                if (!$this->isColumnAutoIncremental($tableSchema, $uniqueColumns)) {
                    $attributesCount = count($uniqueColumns);

                    if ($attributesCount == 1) {
                        $rules[] = "[['" . $uniqueColumns[0] . "'], 'unique']";
                    } elseif ($attributesCount > 1) {
                        $labels = array_intersect_key($this->generateLabels($tableSchema, $tableName), array_flip($uniqueColumns));
                        $lastLabel = array_pop($labels);
                        $columnsList = implode("', '", $uniqueColumns);
                        $rules[] = "[['" . $columnsList . "'], 'unique', 'targetAttribute' => ['" . $columnsList . "'], 'message' => 'The combination of " . implode(', ', $labels) . " and " . $lastLabel . " has already been taken.']";
                    }
                }
            }
        } catch (NotSupportedException $e) {
            // doesn't support unique indexes information...do nothing
        }

        return $rules;
    }

    /**
     * Get foreign attributes.
     * @param \yii\db\TableSchema $tableSchema the table schema
     * @param string $tableName
     * @return array
     */
    public function foreignAttributes($tableSchema, $tableName)
    {
        static $result;
        if (!isset($result)) {
            $result = [];
        }
        if (!isset($result[$tableName])) {
            foreach ($tableSchema->foreignKeys as $refs) {
                $refTableName = $refs[0];
                $attribute = array_keys(array_diff_key($refs, [0]))[0];
                $refKey = $refs[$attribute];
//                $refTableSchema = $db->getTableSchema($refTableName, true);
//                foreach ($refTableSchema->columns as $column) {
//
//                }
                $result[$tableName][$attribute] = [
                    'table' => $refTableName,
                    'key' => $refKey,
                ];
            }
        } else {
            return $result[$tableName];
        }
        return $result[$tableName] = [];
    }

    /**
     * @return array the generated relation declarations
     */
    protected function generateRelations()
    {
        static $result;

        if (isset($result)) {
            return $result;
        }

        if (!$this->generateRelations) {
            return $result = [];
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

        $relations = [];
        foreach ($schemaNames as $schemaName) {
            foreach ($db->getSchema()->getTableSchemas($schemaName) as $table) {
                $className = $this->generateClassName($table->fullName);
                foreach ($table->foreignKeys as $refs) {
                    $refTable = $refs[0];
                    $refTableSchema = $db->getTableSchema($refTable);
                    unset($refs[0]);
                    $fks = array_keys($refs);
                    $refClassName = $this->generateClassName($refTable);

                    // Add relation for this table
                    $link = $this->generateRelationLink(array_flip($refs));
                    $relationName = $this->generateRelationName($relations, $table, $fks[0], false);
                    $relations[$table->fullName][$relationName] = [
                        "return \$this->hasOne($refClassName::className(), $link);",
                        $refClassName,
                        false,
                    ];

                    // Add relation for the referenced table
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
                    $link = $this->generateRelationLink($refs);
                    $relationName = $this->generateRelationName($relations, $refTableSchema, $className, $hasMany);
                    $relations[$refTableSchema->fullName][$relationName] = [
                        "return \$this->" . ($hasMany ? 'hasMany' : 'hasOne') . "($className::className(), $link);",
                        $className,
                        $hasMany,
                    ];
                }

                if (($fks = $this->checkPivotTable($table)) === false) {
                    continue;
                }

                $relations = $this->generateManyManyRelations($table, $fks, $relations);
            }
        }

        return $result = $relations;
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
            $link = $this->generateRelationLink([$fks[$table->primaryKey[$n_id]][1] => $table->primaryKey[$n_id]]);
            $viaLink = $this->generateRelationLink([$table->primaryKey[$id] => $fks[$table->primaryKey[$id]][1]]);
            $relationName = $this->generateRelationName($relations, $tableSchema[$id], $table->primaryKey[$n_id], true);
            $relations[$tableSchema[$id]->fullName][$relationName] = [
                "return \$this->hasMany({$className[$n_id]}::className(), $link)->viaTable('"
                . $this->generateTableName($table->name) . "', $viaLink);",
                $className[$n_id],
                true,
            ];
            if ($this->tableName === $tableSchema[$id]->name || $this->tableName === '*') {
                /** @var $schema \yii\db\TableSchema */
                $schema = $tableSchema[$id];
                $columns = $schema->getColumnNames();
                $namedAttributes = array_intersect(['name', 'title', 'label'], $columns);
                $this->relationsSetters[$tableSchema[$id]->name][] = [
                    'relation' => $relationName,
                    'property' => lcfirst($relationName),
                    'many_class' => '\\' . trim($this->ns, '\\') . '\\' . $this->generateClassName($table->name),
                    'many_table' => $table->name,
                    'many_fk' => $table->primaryKey[$id],
                    'many_id' => $fks[$table->primaryKey[$id]][1],
                    'many_many_class' => '\\' . trim($this->ns, '\\') . '\\' . $className[$n_id],
                    'many_many_table' => $tables[$id],
                    'many_many_fk' => $table->primaryKey[$n_id],
                    'many_many_id' => $fks[$table->primaryKey[$n_id]][1],
                    'many_many_title' => !empty($namedAttributes) ? $namedAttributes[0] : $columns[0],
                    'label' => Inflector::pluralize(Inflector::camel2words($className[$n_id])),
                    'single_label' => Inflector::camel2words($className[$n_id]),
                ];
            }
        }

        return $relations;
    }

    /**
     * Generates the link parameter to be used in generating the relation declaration.
     * @param array $refs reference constraint
     * @return string the generated link parameter.
     */
    protected function generateRelationLink($refs)
    {
        $pairs = [];
        foreach ($refs as $a => $b) {
            $pairs[] = "'$a' => '$b'";
        }

        return '[' . implode(', ', $pairs) . ']';
    }

    /**
     * Checks if the given table is a junction table.
     * For simplicity, this method only deals with the case where the pivot contains two PK columns,
     * each referencing a column in a different table.
     * @param \yii\db\TableSchema the table being checked
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
     * Validates the [[ns]] attribute.
     */
    public function validateNamespace()
    {
        $this->ns = ltrim($this->ns, '\\');
        $path = Yii::getAlias('@' . str_replace('\\', '/', $this->ns), false);
        if ($path === false) {
            $this->addError('ns', 'Namespace must be associated with an existing directory.');
        }
    }

    /**
     * Validates the [[modelClass]] attribute.
     */
    public function validateModelClass()
    {
        if ($this->isReservedKeyword($this->modelClass)) {
            $this->addError('modelClass', 'Class name cannot be a reserved PHP keyword.');
        }
        if ((empty($this->tableName) || substr_compare($this->tableName, '*', -1, 1)) && $this->modelClass == '') {
            $this->addError('modelClass', 'Model Class cannot be blank if table name does not end with asterisk.');
        }
    }

    /**
     * Validates the [[tableName]] attribute.
     */
    public function validateTableName()
    {
        if (strpos($this->tableName, '*') !== false && substr_compare($this->tableName, '*', -1, 1)) {
            $this->addError('tableName', 'Asterisk is only allowed as the last character.');

            return;
        }
        $tables = $this->getTableNames();
        if (empty($tables)) {
            $this->addError('tableName', "Table '{$this->tableName}' does not exist.");
        } else {
            foreach ($tables as $table) {
                $class = $this->generateClassName($table);
                if ($this->isReservedKeyword($class)) {
                    $this->addError('tableName', "Table '$table' will generate a class which is a reserved PHP keyword.");
                    break;
                }
            }
        }
    }

    protected $tableNames;
    protected $classNames;

    /**
     * @return array the table names that match the pattern specified by [[tableName]].
     */
    protected function getTableNames()
    {
        if ($this->tableNames !== null) {
            return $this->tableNames;
        }
        $db = $this->getDbConnection();
        if ($db === null) {
            return [];
        }
        $tableNames = [];
        if (strpos($this->tableName, '*') !== false) {
            if (($pos = strrpos($this->tableName, '.')) !== false) {
                $schema = substr($this->tableName, 0, $pos);
                $pattern = '/^' . str_replace('*', '\w+', substr($this->tableName, $pos + 1)) . '$/';
            } else {
                $schema = '';
                $pattern = '/^' . str_replace('*', '\w+', $this->tableName) . '$/';
            }

            foreach ($db->schema->getTableNames($schema) as $table) {
                if (preg_match($pattern, $table)) {
                    $tableNames[] = $schema === '' ? $table : ($schema . '.' . $table);
                }
            }
        } elseif (($table = $db->getTableSchema($this->tableName, true)) !== null) {
            $tableNames[] = $this->tableName;
            $this->classNames[$this->tableName] = $this->modelClass;
        }

        return $this->tableNames = $tableNames;
    }

    /**
     * Generates the table name by considering table prefix.
     * If [[useTablePrefix]] is false, the table name will be returned without change.
     * @param string $tableName the table name (which may contain schema prefix)
     * @return string the generated table name
     */
    public function generateTableName($tableName)
    {
        if (!$this->useTablePrefix) {
            return $tableName;
        }

        $db = $this->getDbConnection();
        if (preg_match("/^{$db->tablePrefix}(.*?)$/", $tableName, $matches)) {
            $tableName = '{{%' . $matches[1] . '}}';
        } elseif (preg_match("/^(.*?){$db->tablePrefix}$/", $tableName, $matches)) {
            $tableName = '{{' . $matches[1] . '%}}';
        }
        return $tableName;
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
            if (($useSchemaName === null && $this->useSchemaName) || $useSchemaName) {
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
     * Generates a query class name from the specified model class name.
     * @param string $modelClassName model class name
     * @return string generated class name
     */
    protected function generateQueryClassName($modelClassName)
    {
        $queryClassName = $this->queryClass;
        if (empty($queryClassName) || strpos($this->tableName, '*') !== false) {
            $queryClassName = $modelClassName . 'Query';
        }
        return $queryClassName;
    }

    /**
     * @return Connection the DB connection as specified by [[db]].
     */
    protected function getDbConnection()
    {
        return Yii::$app->get($this->db, false);
    }

    /**
     * Checks if any of the specified columns is auto incremental.
     * @param \yii\db\TableSchema $tableSchema the table schema
     * @param array $columns columns to check for autoIncrement property
     * @return boolean whether any of the specified columns is auto incremental.
     */
    protected function isColumnAutoIncremental($tableSchema, $columns)
    {
        foreach ($columns as $column) {
            if (isset($tableSchema->columns[$column]) && $tableSchema->columns[$column]->autoIncrement) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function generateString($string = '', $placeholders = [])
    {
        if ($this->enableI18N && $this->addingI18NStrings && !isset($this->I18NStrings[$string])) {
            $this->I18NStrings[$string] = $string;
        }
        $string = addslashes($string);
        if ($this->enableI18N) {
            // If there are placeholders, use them
            if (!empty($placeholders)) {
                $ph = ', ' . str_replace('{%__PRIME__%}', "'", preg_replace('/\'php:([^\']+)\'/', '\1',
                        str_replace("\\'", '{%__PRIME__%}', VarDumper::export($placeholders))));
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
        $files = [];

        $relations = $this->generateRelations();
        $db = $this->getDbConnection();

        foreach ($this->getTableNames() as $tableName) {
            // model :
            $modelClassName = $this->generateClassName($tableName);
            $queryClassName = ($this->generateQuery) ? $this->generateQueryClassName($modelClassName) : false;
            $tableSchema = $db->getTableSchema($tableName);
            $params = [
                'tableName' => $tableName,
                'className' => $modelClassName,
                'queryClassName' => $queryClassName,
                'tableSchema' => $tableSchema,
                'labels' => $this->generateLabels($tableSchema, $tableName),
                'rules' => $this->generateRules($tableSchema, $tableName),
                'relations' => isset($relations[$tableName]) ? $relations[$tableName] : [],
            ];
            $files[] = new CodeFile(
                Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $modelClassName . '.php',
                $this->render('model.php', $params)
            );

            // query :
            if ($queryClassName) {
                $params = [
                    'className' => $queryClassName,
                    'modelClassName' => $modelClassName,
                ];
                $files[] = new CodeFile(
                    Yii::getAlias('@' . str_replace('\\', '/', $this->queryNs)) . '/' . $queryClassName . '.php',
                    $this->render('query.php', $params)
                );
            }
        }

        if ($this->enableI18N && $this->addingI18NStrings && !empty($this->I18NStrings)) {
            foreach (explode(',', $this->messagesPaths) as $alias) {
                $path = Yii::getAlias($alias);
                foreach (array_diff(scandir($path), ['.', '..']) as $language) {
                    $filename = "$path/$language/{$this->messageCategory}.php";
                    $messages = file_exists($filename) ? require($filename) : [];
                    $messages = array_merge($messages, array_diff_key($this->I18NStrings, $messages));
                    $files[] = new CodeFile($filename, "<?php\nreturn " . VarDumper::export($messages) . ";");
                }
            }
        }

        return $files;
    }
}

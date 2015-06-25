<?php
namespace backend\gii\generators\model;

use Yii;
use yii\db\Schema;
use yii\gii\CodeFile;
use yii\helpers\Inflector;
use yii\helpers\VarDumper;
use yii\base\NotSupportedException;

/**
 * This generator will generate one or multiple ActiveRecord classes for the specified database table.
 *
 * @author Andrey Izman <izmanw@gmail.com>
 */
class Generator extends \yii\gii\generators\model\Generator
{
    public $generateRelations = true;
    public $generateLabelsFromComments = true;
    public $useTablePrefix = true;
    public $useSchemaName = true;
    public $generateQuery = true;
    public $enableI18N = true;
    public $modelIcon;
    public $imagesPath = '@backend/web/img';
    public $enableTimestampBehavior = false;
    public $addingI18NStrings = true;
    public $messagesPaths = '@backend/messages';

    public $imageAttributes = ['img', 'image', 'logo', 'avatar', 'picture', 'preview'];
    public $relationsSetters = [];

    protected $I18NStrings = [];


    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'ALT Model Generator';
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
            [['modelIcon', 'imagesPath'], 'filter', 'filter' => 'trim'],
            ['imagesPath', 'filter', 'filter' => function($value) { return trim($value, '/'); }],
            [['modelIcon', 'imagesPath'], 'required'],
            ['modelIcon', 'match', 'pattern' => '/^\w+\-\w+(?:[0-9\w\-]+)?$/', 'message' => 'No valid image class.'],
            [['addingI18NStrings'], 'boolean'],
            ['messagesPaths', 'validateMessagesPaths'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'addingI18NStrings' => 'Adding I18N Strings',
            'messagesPaths' => 'I18N Messages Paths',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'modelIcon' => 'This is a model icon, e.g., <code>glyphicon-asterisk</code>',
            'imagesPath' => 'Path to upload images. May be path alias use this, e.g., <code>@app/web/img</code>',
            'addingI18NStrings' => 'Enables the adding non existing I18N strings to the message category files.',
            'messagesPaths' => 'Paths to I18N messages, ability to set multiple directories separated by commas, e.g. <code>@backend/messages,@frontend/messages</code>',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), [
            'addingI18NStrings',
            'messagesPaths',
        ]);
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
                return "\n    const STATUS_INACTIVE = 0;\n    const STATUS_ACTIVE = 1;\n\n";
            }
        }
        return '';
    }

    /**
     * Get namespace for TimestampBehavior
     * @param \yii\db\TableSchema $tableSchema
     * @return string
     */
    public function timestampBehaviorNs($tableSchema)
    {
        foreach ($tableSchema->columns as $column) {
            if ($column->name == 'created_at' || $column->name == 'updated_at') {
                $this->enableTimestampBehavior = true;
                return "use yii\\behaviors\\TimestampBehavior;\n";
            }
        }
        return '';
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
     * Get timestamp setters for fields whose are not present in table schema
     * @param \yii\db\TableSchema $tableSchema
     * @return string
     */
    public function timestampSetters($tableSchema)
    {
        if ($this->enableTimestampBehavior) {
            $columns = [];
            foreach ($tableSchema->columns as $column) {
                if ($column->name == 'created_at' || $column->name == 'updated_at') {
                    $columns[] = $column->name;
                }
            }
            $columns = array_diff(['created_at', 'updated_at'], $columns);
            if (!empty($columns)) {
                $content = [];
                foreach ($columns as $column) {
                    $content[] = implode("\n    ", [ "",
                            "/**",
                            " * Setter for `$column` used in TimestampBehavior",
                            " */",
                            "public function set" . ucfirst($column) . "(\$v) {}",
                        ]) . "\n";
                }
                return implode('', $content);
            }
        }
        return '';
    }

    /**
     * Get images properties
     * @param \yii\db\TableSchema $tableSchema
     * @param string $tableName
     * @return string
     */
    public function imageUploadProperties($tableSchema, $tableName)
    {
        $attributes = [];
        foreach ($tableSchema->columns as $column) {
            if (in_array($column->name, $this->imageAttributes)) {
//                $attributes[] = implode("\n    ", [ "",
//                        "/**",
//                        " * @var mixed image the attribute for rendering",
//                        " * the file input widget for upload on the form",
//                        " */",
//                        "public \${$column->name}_upload;"
//                    ]) . "\n";
                $attributes[] = implode("\n    ", [ "",
                        "/**",
                        " * @var mixed image the attribute for rendering",
                        " * the file input widget for upload on the form",
                        " */",
                        "public \${$column->name}_sizes = ['200x200'];"
                    ]) . "\n";
                $attributes[] = implode("\n    ", [ "",
                        "/**",
                        " * @var mixed image the attribute for rendering",
                        " * the file input widget for upload on the form",
                        " */",
                        "public \${$column->name}_upload_dir = '@backend/web/img/$tableName/{$column->name}';"
                    ]) . "\n";
            }
        }
        return implode('', $attributes);
    }

    /**
     * Generates validation rules for the specified table.
     * @param \yii\db\TableSchema $table the table schema
     * @return array the generated validation rules
     */
    public function generateRules($table)
    {
        $rules = $types = $lengths = $extra = [];
        foreach ($table->columns as $column) {
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

        if (!empty($this->relationsSetters)) {
            foreach ($this->relationsSetters as $rs) {
                $types["validate{$rs['relation']}"][] = $rs['property'];
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
            $uniqueIndexes = $db->getSchema()->findUniqueIndexes($table);
            foreach ($uniqueIndexes as $uniqueColumns) {
                // Avoid validating auto incremental columns
                if (!$this->isColumnAutoIncremental($table, $uniqueColumns)) {
                    $attributesCount = count($uniqueColumns);

                    if ($attributesCount == 1) {
                        $rules[] = "[['" . $uniqueColumns[0] . "'], 'unique']";
                    } elseif ($attributesCount > 1) {
                        $labels = array_intersect_key($this->generateLabels($table), array_flip($uniqueColumns));
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
     * @inheritdoc
     */
    public function generateLabels($table)
    {
        $labels = parent::generateLabels($table);

        foreach ($labels as $attribute => $label) {
            if (substr($attribute, 0, 3) === 'id_') {
                $labels[$attribute] = Inflector::camel2words(substr($attribute, 3));
            } elseif (substr($attribute, -3) === '_id') {
                $labels[$attribute] = Inflector::camel2words(substr($attribute, 0, -3));
            }
        }

        if (!empty($this->relationsSetters)) {
            foreach ($this->relationsSetters as $relationSetter) {
                $labels[$relationSetter['property']] = $relationSetter['label'];
            }
        }

        return $labels;
    }

    /**
     * @return array the generated relation declarations
     */
    protected function generateRelations()
    {
        if (!$this->generateRelations) {
            return [];
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

        return $relations;
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
            if ($this->tableName === $tableSchema[$id]->name) {
                $columns = $tableSchema[$id]->getColumnNames();
                $namedAttributes = array_intersect(['name', 'title', 'label'], $columns);
                $this->relationsSetters[] = [
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
        $files = parent::generate();
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

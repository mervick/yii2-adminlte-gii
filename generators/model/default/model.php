<?php
/**
 * This is the template for generating the model class of a specified table.
 */

/* @var $this yii\web\View */
/* @var $generator mervick\adminlte\gii\generators\model\Generator */
/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $queryClassName string query class name */
/* @var $tableSchema yii\db\TableSchema */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $rules string[] list of validation rules */
/* @var $relations array list of relations (name => relation declaration) */

echo "<?php\n";
?>
namespace <?= $generator->ns ?>;

<?= $generator->modelNS($tableSchema, $tableName) ?>

<?= $generator->modelPhpDocs($tableSchema, $tableName, $relations) ?>
class <?= $className ?> extends <?= array_reverse(explode('\\', $generator->baseClass))[0] . "\n" ?>
{
<?= $generator->statusConstants($tableSchema) ?>

    /** @inheritdoc */
    public static function tableName()
    {
        return '<?= $generator->generateTableName($tableName) ?>';
    }
<?= $generator->modelBehaviors($tableSchema, $tableName) ?>
<?php if ($generator->db !== 'db'): ?>

    /** @return \yii\db\Connection the database connection used by this AR class. */
    public static function getDb()
    {
        return Yii::$app->get('<?= $generator->db ?>');
    }
<?php endif; ?>

    /** @inheritdoc */
    public function rules()
    {
        return [<?= "\n            " . implode(",\n            ", $rules) . "\n        " ?>];
    }

    /** @inheritdoc */
    public function attributeLabels()
    {
        return [
<?php foreach ($labels as $name => $label): ?>
            <?= "'$name' => " . $generator->generateString($label) . ",\n" ?>
<?php endforeach; ?>
        ];
    }
<?php foreach ($relations as $name => $relation): ?>

    /** @return \yii\db\ActiveQuery */
    public function get<?= $name ?>()
    {
        <?= $relation[0] . "\n" ?>
    }
<?php endforeach; ?>
<?php if ($queryClassName): ?>
<?php
    $queryClassFullName = ($generator->ns === $generator->queryNs) ? $queryClassName : '\\' . $generator->queryNs . '\\' . $queryClassName;
    echo "\n";
?>
    /**
     * @inheritdoc
     * @return <?= $queryClassFullName ?> the active query used by this AR class.
     */
    public static function find()
    {
        return new <?= $queryClassFullName ?>(get_called_class());
    }
<?php endif; ?>
}
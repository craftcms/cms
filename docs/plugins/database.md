# Database Queries

Craft provides two ways of interacting with the database: The [Query Builder](#query-builder) and [Active Record](#active-record). They are both based on concepts that are core to Yii, but Craft adds a few features of its own on top of both.

## Query Builder

Craft’s query builder is an extension of Yii’s. If you haven’t worked with Yii before, take a few minutes to read through their [Query Builder](http://www.yiiframework.com/doc/guide/1.1/en/database.query-builder) documentation, so you can get a grasp of how it works and what it’s capable of.

In Craft, when you type:

```php
$query = craft()->db->createCommand();
```

you’re going to get a “DbCommand” object back, which extends Yii’s [CDbCommand](http://www.yiiframework.com/doc/api/1.1/CDbCommand) class with a few enhancements. Let’s go over what those are.

### General DbCommand Enhancements

Craft’s DbCommand enhances Yii’s CDbCommand in the following ways:

- It automatically adds the prefix to table names, without requiring you to wrap the table name in `{{` and `}}`:

    ```php
    // with Yii's CDbCommand:
    ->from('{{users}}')

    // with Craft's DbCommand:
    ->from('users')
    ```

- It allows you to specify `AND` conditions as key/value arrays, with automatic parameter cleansing:

    ```php
    // with Yii's CDbCommand:
    ->where(
        array('and', 'foo = :foo', 'bar = :bar'),
        array(':foo' => $foo, ':bar' => $bar)
    )

    // with Craft's DbCommand:
    ->where(array(
        'foo' => $foo,
        'bar' => $bar
    ))
    ```

- It allows you to specify “`$type`” attributes using an array syntax:

    ```php
    // with Yii's CDbCommand:
    ->alterColumn('assetfiles', 'sourceId', 'INT(11) NULL')

    // with Craft's DbCommand:
    ->alterColumn('assetfiles', 'sourceId', array(
        'column' => ColumnType::Int,
        'null' => true
    ))
    ```


- The `id`, `dateCreated`, `dateUpdated`, and `uid` columns are automatically accounted for in `createTable()`, `insert()`, `insertAll()`, `insertOrUpdate()`, and `update()`, so you don’t need to worry about them.

### Additional Methods in DbCommand

DbCommand also has the following methods, which aren’t available to CDbCommand:

#### `addSelect( $columns = '*' )`

Adds additional columns to the query, without overwriting the existing ones (unlike `select()`, which will clear any previously defined columns).

```php
craft()->db->createCommand()
    ->select('foo')
    ->addSelect('bar')
    // ...
```

#### `andWhere( $conditions, $params = array() )`

Adds additional conditions to the query, without overwriting the existing ones (unlike `where()`, which will clear any previously defined conditions).

```php
craft()->db->createCommand()
    ->where('foo = 1')
    ->andWhere('bar = 1')
    // ...
```

#### `insertAll( $table, $columns, $rows, $includeAuditColumns = true )`

Batch-inserts multiple rows at once.

```php
$rows[] = array('apples');
$rows[] = array('oranges');
$rows[] = array('pears');

craft()->db->createCommand()->insertAll('fruit', array('name'), $rows);
```

#### `addColumnFirst( $table, $column, $type )`

Adds a new column to the beginning of a table.

#### `addColumnBefore( $table, $column, $type, $before )`

Adds a new column before another one.

#### `addColumnAfter( $table, $column, $type, $after )`

Adds a new column after another one.


## Active Record

Active Record is Craft/Yii’s ORM feature. It allows you to define [models](models.md) that represent rows of data in a database table, and use those models to fetch, update, and delete the corresponding database rows.

Craft calls these specialized models “records”. The class your records will extend is BaseRecord, which itself extends [CActiveRecord](http://www.yiiframework.com/doc/api/1.1/CActiveRecord).

While Yii expects your database schema to already be established by the time the application is running, Craft’s BaseRecord is actually capable of _creating_ your database tables when your plugin is installed.

### Creating a Record

To create a record, first create a new records/ subfolder within your plugin’s folder. Then create a new file in that folder, named with this format:

```
[PluginHandle]_[RecordName]Record.php
```

If your plugin name is “Cocktail Recipes”, and your record name is “Ingredient”, the file would be named “CocktailRecipes_IngredientRecord.php”.

Create a new class in that file, with the same name as the filename:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'cocktailrecipes_ingredients';
    }

    protected function defineAttributes()
    {
        return array(
            'name' => AttributeType::String,
            'type' => array(AttributeType::Enum, 'values' => "alcohol,mixer,other"),
        );
    }
}
```

**`getTableName()`** returns the name of the database table the model is associated with (sans table prefix). By convention, tables created by plugins should be prefixed with the plugin name and an underscore.

**`defineAttributes()`** returns an array of attributes which map back to columns in the database table.

### Defining Relations

If your record should have any relationships with other tables, you can specify them with the `defineRelations()` function:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientRecord extends BaseRecord
{
    // ...

    public function defineRelations()
    {
        return array(
            'drinks' => array(static::HAS_MANY, 'CocktailRecipes_DrinkRecord', 'ingredientId'),
        );
    }
}
```

**`defineRelations()`** works basically the same as [`CActiveRecord::relations()`](http://www.yiiframework.com/doc/api/1.1/CActiveRecord#relations-detail) (see Yii’s [Relational Active Record](http://www.yiiframework.com/doc/guide/1.1/en/database.arr) documentation), with two differences:

- You don’t need to specify the namespace of the related record class in the second argument (defaults to the Craft namespace)
- You don’t need to specify the foreign key column name in `BELONGS_TO` relations (defaults to the relation name appended with “`Id`”)


### Defining Indexes

If you want to define any indexes on your table, you can do it with the `defineIndexes()` function:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientRecord extends BaseRecord
{
    // ...

    public function defineIndexes()
    {
        return array(
            array('columns' => array('name', 'type'), 'unique' => true),
        );
    }
}
```

### Further Reading

BaseRecord is an instance of Yii’s [CActiveRecord](http://www.yiiframework.com/doc/api/1.1/CActiveRecord) class, so everything CActiveRecord can do, BaseRecord can do as well.

::: tip
Records’ ability to modify the database means that they should never be used to transport data throughout the system. Their instances should be contained to [services](services.md) only, so that services remain the one and only place where system state changes ever occur.
:::

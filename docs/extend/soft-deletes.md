# Soft Deletes

Modules and plugins can add soft delete support to their components by following this guide.

::: tip
All element types support soft deletes out of the box. See [Element Types](element-types.md#restore-action) for information on how to make them restorable.
:::

## Prepare the Database Table

Components that are soft-deletable must have a `dateDeleted` column in their database table. Rows that have a `dateDeleted` value will be considered soft-deleted.

```php
// New table migration
$this->createTale('{{%tablename}}', [
    // other columns...
    'dateDeleted' => $this->dateTime()->null(),
]);

// Existing table migration
$this->addColumn('{{%tablename}}', 'dateDeleted',
    $this->dateTime()->null()->after('dateUpdated'));
```

Tables containing soft-deletable component data should not enforce any unique constraints (besides a primary key). If yours does, you’ll need to remove them.

```php
use craft\helpers\MigrationHelper;

// Stop enforcing unique handles at the database level
MigrationHelper::dropIndexIfExists('{{%tablename}}', ['handle'], true, $this);
$this->createIndex(null, '{{%tablename}}', ['handle'], false);
```

## Hard-Delete Rows When Their Time Is Up

Table rows that have been soft-deleted should only stick around as long as the <config:softDeleteDuration> config setting wants them to, and then be hard-deleted.

Rather than check for stale rows on every request, we can make this a part of Craft’s [garbage collection](../gc.md) routines.

<api:craft\services\Gc> will fire a `run` event each time that it is running. You can tap into that from your module/plugin’s `init()` method. 

```php
use craft\services\Gc;
use yii\base\Event;

public function init()
{
    paren::init();
    
    Event::on(Gc::class, Gc::EVENT_RUN, function() {
        Craft::$app->gc->hardDelete('{{%tablename}}');
    }
}
```

[hardDelete()](api:craft\services\Gc::hardDelete()) method will delete any rows with a `dateDeleted` value set to a timestamp that’s older than the <config:softDeleteDuration> config setting. 

::: tip
If you need to check multiple tables for stale rows, you can pass an array of table names into [hardDelete()](api:craft\services\Gc::hardDelete()) instead.
:::

## Update the Active Record Class

If the component has a corresponding [Active Record](https://www.yiiframework.com/doc/guide/2.0/en/db-active-record) class, you can add soft delete support to it by importing <api:craft\db\SoftDeleteTrait>:

```php
use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

class MyRecord extends ActiveRecord
{
    use SoftDeleteTrait;
    
    // ...
}
```

That trait will give your class the following features:

- [find()](api:craft\db\SoftDeleteTrait::find()) will only return rows that haven’t been soft-deleted (where the `dateDeleted` column is still `null`).
- A [findWithTrashed()](api:craft\db\SoftDeleteTrait::findWithTrashed()) static method will be added for finding rows regardless of whether they’ve been soft-deleted.
- A [findTrashed()](api:craft\db\SoftDeleteTrait::findTrashed()) static method will be added for finding rows that have been soft-deleted (where the `dateDeleted` column is not `null`).
- A `softDelete()` method will be added that should be called instead of [delete()](api:yii\db\ActiveRecord::delete()), which will update the row’s `dateDeleted` column to a current timestamp, rather than deleting the row.
- A `restore()` method will be added for restoring a soft-deleted row by removing its `dateDeleted` value.

Internally, the trait uses the [ActiveRecord Soft Delete Extension](https://github.com/yii2tech/ar-softdelete) for Yii 2, which is implemented as a [behavior](https://www.yiiframework.com/doc/guide/2.0/en/concept-behaviors).

If your class already defines its own behaviors, you will need to rename the trait’s [behaviors()](api:craft\db\SoftDeleteTrait::behaviors()) method on import, and manually call it from your `behaviors()` method:

```php
use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

class MyRecord extends ActiveRecord
{
    use SoftDeleteTrait {
        behaviors as softDeleteBehaviors;
    }

    public function behaviors()
    {
        $behaviors = $this->softDeleteBehaviors();
        $behaviors['myBehavior'] = MyBehavior::class;
        return $behaviors;
    }

    // ...
}
```

If your class is overriding <api:yii\db\ActiveRecord::find()>, you will need to add a `dateDeleted` condition to the resulting query yourself:

```php{5}
public static function find()
{
    // @var MyActiveQuery $query
    $query = Craft::createObject(MyActiveQuery::class, [static::class]);
    $query->where(['dateDeleted' => null]);
    return $query;
}
```

## Update the Rest of Your Code

Check your code for any database queries that involve your component’s table. They will need to be updated as well.

- When selecting data from your table, make sure that you’re ignoring rows with a `dateDeleted` value.

  ```php{4}
  $results = (new \craft\db\Query())
      ->select(['...'])
      ->from(['{{%tableName}}'])
      ->where(['dateDeleted' => null])
      ->all();
  ```

- When deleting rows from your table using your Active Record class, call its new `softDelete()` method rather than [delete()](api:yii\db\ActiveRecord::delete()).

  ```php
  $record->softDelete();
  ```

- When deleting rows from your table using a query command, call <api:craft\db\Command::softDelete()> rather than [delete()](api:yii\db\Command::delete()).

  ```php
  \Craft::$app->db->createCommand()
      ->softDelete('{{%tablename}}', ['id' => $id])
      ->execute(); 
  ```

## Restoring Soft-Deleted Rows

There are two ways to restore soft-deleted rows that haven’t been hard-deleted by garbage collection yet:

- With your Active Record class, by calling its `restore()` method.

  ```php
  $record = MyRecord::findTrashed()
      ->where(['id' => $id])
      ->one();

  $record->restore();
  ```

- With a query command, by calling <api:craft\db\Command::restore()>.

  ```php
  \Craft::$app->db->createCommand()
      ->restore('{{%tablename}}', ['id' => $id])
      ->execute();
  ```

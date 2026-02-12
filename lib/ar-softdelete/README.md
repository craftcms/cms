<p align="center">
    <a href="https://github.com/yii2tech" target="_blank">
        <img src="https://avatars2.githubusercontent.com/u/12951949" height="100px">
    </a>
    <h1 align="center">ActiveRecord Soft Delete Extension for Yii2</h1>
    <br>
</p>

This extension provides support for ActiveRecord soft delete.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://img.shields.io/packagist/v/yii2tech/ar-softdelete.svg)](https://packagist.org/packages/yii2tech/ar-softdelete)
[![Total Downloads](https://img.shields.io/packagist/dt/yii2tech/ar-softdelete.svg)](https://packagist.org/packages/yii2tech/ar-softdelete)
[![Build Status](https://travis-ci.org/yii2tech/ar-softdelete.svg?branch=master)](https://travis-ci.org/yii2tech/ar-softdelete)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2tech/ar-softdelete
```

or add

```json
"yii2tech/ar-softdelete": "*"
```

to the require section of your composer.json.


Usage
-----

This extension provides support for so called "soft" deletion of the ActiveRecord, which means record is not deleted
from database, but marked with some flag or status, which indicates it is no longer active, instead.

This extension provides [[\yii2tech\ar\softdelete\SoftDeleteBehavior]] ActiveRecord behavior for such solution
support in Yii2. You may attach it to your model class in the following way:

```php
<?php

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

class Item extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'isDeleted' => true
                ],
            ],
        ];
    }
}
```

There are 2 ways of "soft" delete applying:
 - using `softDelete()` separated method
 - mutating regular `delete()` method

Usage of `softDelete()` is recommended, since it allows marking the record as "deleted", while leaving regular `delete()`
method intact, which allows you to perform "hard" delete if necessary. For example:

```php
<?php

$id = 17;
$item = Item::findOne($id);
$item->softDelete(); // mark record as "deleted"

$item = Item::findOne($id);
var_dump($item->isDeleted); // outputs "true"

$item->delete(); // perform actual deleting of the record
$item = Item::findOne($id);
var_dump($item); // outputs "null"
```

However, you may want to mutate regular ActiveRecord `delete()` method in the way it performs "soft" deleting instead
of actual removing of the record. It is a common solution in such cases as applying "soft" delete functionality for
existing code. For such functionality you should enable [[\yii2tech\ar\softdelete\SoftDeleteBehavior::$replaceRegularDelete]]
option in behavior configuration:

```php
<?php

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

class Item extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'isDeleted' => true
                ],
                'replaceRegularDelete' => true // mutate native `delete()` method
            ],
        ];
    }
}
```

Now invocation of the `delete()` method will mark record as "deleted" instead of removing it:

```php
<?php

$id = 17;
$item = Item::findOne($id);
$item->delete(); // no record removal, mark record as "deleted" instead

$item = Item::findOne($id);
var_dump($item->isDeleted); // outputs "true"
```

**Heads up!** In case you mutate regular ActiveRecord `delete()` method, it will be unable to function with ActiveRecord
transactions feature, e.g. scenarios with [[\yii\db\ActiveRecord::OP_DELETE]] or [[\yii\db\ActiveRecord::OP_ALL]]
transaction levels:

```php
<?php

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

class Item extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'replaceRegularDelete' => true // mutate native `delete()` method
            ],
        ];
    }

    public function transactions()
    {
        return [
            'some' => self::OP_DELETE,
        ];
    }
}

$item = Item::findOne($id);
$item->setScenario('some');
$item->delete(); // nothing happens!
```


## Querying "soft" deleted records <span id="querying-soft-deleted-records"></span>

Obviously, in order to find only "deleted" or only "active" records you should add corresponding condition to your search query:

```php
<?php

// returns only not "deleted" records
$notDeletedItems = Item::find()
    ->where(['isDeleted' => false])
    ->all();

// returns "deleted" records
$deletedItems = Item::find()
    ->where(['isDeleted' => true])
    ->all();
```

However, you can use [[yii2tech\ar\softdelete\SoftDeleteQueryBehavior]] to facilitate composition of such queries.
The easiest way to apply this behavior is its manual attachment to the query instance at [[\yii\db\BaseActiveRecord::find()]]
method. For example:

```php
<?php

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

class Item extends ActiveRecord
{
    // ...
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                // ...
            ],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery|SoftDeleteQueryBehavior
     */
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query;
    }
}
```

In case you already define custom query class for your active record, you can move behavior attachment there.
For example:

```php
<?php

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

class Item extends ActiveRecord
{
    // ...
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                // ...
            ],
        ];
    }

    /**
     * @return ItemQuery|SoftDeleteQueryBehavior
     */
    public static function find()
    {
        return new ItemQuery(get_called_class());
    }
}

class ItemQuery extends \yii\db\ActiveQuery
{
    public function behaviors()
    {
        return [
            'softDelete' => [
                'class' => SoftDeleteQueryBehavior::className(),
            ],
        ];
    }
}
```

Once being attached [[yii2tech\ar\softdelete\SoftDeleteQueryBehavior]] provides named scopes for the records filtering using
"soft" deleted criteria. For example:

```php
<?php

// Find all "deleted" records:
$deletedItems = Item::find()->deleted()->all();

// Find all "active" records:
$notDeletedItems = Item::find()->notDeleted()->all();

// find all comments for not "deleted" items:
$comments = Comment::find()
    ->innerJoinWith(['item' => function ($query) {
        $query->notDeleted();
    }])
    ->all();
```

You may easily create listing filter for "deleted" records using `filterDeleted()` method:

```php
<?php

// Filter records by "soft" deleted criteria:
$items = Item::find()
    ->filterDeleted(Yii::$app->request->get('filter_deleted'))
    ->all();
```

This method applies `notDeleted()` scope on empty filter value, `deleted()` - on positive filter value, and no scope (e.g.
show both "deleted" and "active" records) on negative (zero) value.

> Note: [[yii2tech\ar\softdelete\SoftDeleteQueryBehavior]] has been designed to properly handle joins and avoid ambiguous
  column errors, however, there still can be cases, which it will be unable to handle properly. Be prepared to specify
  "soft deleted" conditions manually in case you are writing complex query, involving several tables with "soft delete" feature.

By default [[yii2tech\ar\softdelete\SoftDeleteQueryBehavior]] composes filter criteria for its scopes using the information from
[[yii2tech\ar\softdelete\SoftDeleteBehavior::$softDeleteAttributeValues]]. Thus you may need to manually configure filter conditions
in case you are using sophisticated logic for "soft" deleted records marking. For example:

```php
<?php

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

class Item extends ActiveRecord
{
    // ...
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'statusId' => 'deleted',
                ],
            ],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery|SoftDeleteQueryBehavior
     */
    public static function find()
    {
        $query = parent::find();
        
        $query->attachBehavior('softDelete', [
            'class' => SoftDeleteQueryBehavior::className(),
            'deletedCondition' => [
                'statusId' => 'deleted',
            ],
            'notDeletedCondition' => [
                'statusId' => 'active',
            ],
        ]);
        
        return $query;
    }
}
```

> Tip: you may apply a condition, which filters "not deleted" records, to the ActiveQuery as default scope, overriding
  `find()` method. Also remember, that you may reset such default scope using `onCondition()`  and `where()` methods
  with empty condition.

```php
<?php

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

class Item extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'isDeleted' => true
                ],
            ],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery|SoftDeleteQueryBehavior
     */
    public static function find()
    {
        $query = parent::find();
        
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        
        return $query->notDeleted();
    }
}

$notDeletedItems = Item::find()->all(); // returns only not "deleted" records

$allItems = Item::find()
    ->onCondition([]) // resets "not deleted" scope for relational databases
    ->all(); // returns all records

$allItems = Item::find()
    ->where([]) // resets "not deleted" scope for NOSQL databases
    ->all(); // returns all records
```


## Smart deletion <span id="smart-deletion"></span>

Usually "soft" deleting feature is used to prevent the database history loss, ensuring data, which been in use and
perhaps have a references or dependencies, is kept in the system. However sometimes actual deleting is allowed for
such data as well.
For example: usually user account records should not be deleted but only marked as "inactive", however if you browse
through users list and found accounts, which has been registered long ago, but don't have at least single log-in in the
system, these records have no value for the history and can be removed from database to save disk space.

You can make "soft" deletion to be "smart" and detect, if the record can be removed from the database or only marked as "deleted".
This can be done via [[\yii2tech\ar\softdelete\SoftDeleteBehavior::$allowDeleteCallback]]. For example:

```php
<?php
 
use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

class User extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'isDeleted' => true
                ],
                'allowDeleteCallback' => function ($user) {
                    return $user->lastLoginDate === null; // allow delete user, if he has never logged in
                }
            ],
        ];
    }
}

$user = User::find()->where(['lastLoginDate' => null])->limit(1)->one();
$user->softDelete(); // removes the record!!!

$user = User::find()->where(['not' =>['lastLoginDate' => null]])->limit(1)->one();
$user->softDelete(); // marks record as "deleted"
```

[[\yii2tech\ar\softdelete\SoftDeleteBehavior::$allowDeleteCallback]] logic is applied in case [[\yii2tech\ar\softdelete\SoftDeleteBehavior::$replaceRegularDelete]]
is enabled as well.


## Handling foreign key constraints <span id="handling-foreign-key-constraints"></span>

In case of usage of the relational database, which supports foreign keys, like MySQL, PostgreSQL etc., "soft" deletion
is widely used for keeping foreign keys consistence. For example: if user performs a purchase at the online shop, information
about this purchase should remain in the system for the future bookkeeping. The DDL for such data structure may look like
following one:

```sql
CREATE TABLE `Customer`
(
   `id` integer NOT NULL AUTO_INCREMENT,
   `name` varchar(64) NOT NULL,
   `address` varchar(64) NOT NULL,
   `phone` varchar(20) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE InnoDB;

CREATE TABLE `Purchase`
(
   `id` integer NOT NULL AUTO_INCREMENT,
   `customerId` integer NOT NULL,
   `itemId` integer NOT NULL,
   `amount` integer NOT NULL,
    PRIMARY KEY (`id`)
    FOREIGN KEY (`customerId`) REFERENCES `Customer` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`itemId`) REFERENCES `Item` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
) ENGINE InnoDB;
```

Thus, while set up a foreign key from 'purchase' to 'user', 'ON DELETE RESTRICT' mode is used. So on attempt to delete
a user record, which have at least one purchase, a database error will occur. However, if user record have no external
reference, it can be deleted.

Usage of [[\yii2tech\ar\softdelete\SoftDeleteBehavior::$allowDeleteCallback]] for such use case is not very practical.
It will require performing extra queries to determine, if external references exist or not, eliminating the benefits of
the foreign keys database feature.

Method [\yii2tech\ar\softdelete\SoftDeleteBehavior::safeDelete()]] attempts to invoke regular [[\yii\db\BaseActiveRecord::delete()]]
method, and, if it fails with exception, falls back to [[yii2tech\ar\softdelete\SoftDeleteBehavior::softDelete()]].

```php
<?php

// if there is a foreign key reference :
$customer = Customer::findOne(15);
var_dump(count($customer->purchases)); // outputs; "1"
$customer->safeDelete(); // performs "soft" delete!
var_dump($customer->isDeleted) // outputs: "true"

// if there is NO foreign key reference :
$customer = Customer::findOne(53);
var_dump(count($customer->purchases)); // outputs; "0"
$customer->safeDelete(); // performs actual delete!
$customer = Customer::findOne(53);
var_dump($customer); // outputs: "null"
```

By default `safeDelete()` method catches [[\yii\db\IntegrityException]] exception, which means soft deleting will be
performed on foreign constraint violation DB exception. You may specify another exception class here to customize fallback
error level. For example: usage of [[\Throwable]] will cause soft-delete fallback on any error during regular deleting.


## Record restoration <span id="record-restoration"></span>

At some point you may want to "restore" records, which have been marked as "deleted" in the past.
You may use `restore()` method for this:

```php
<?php

$id = 17;
$item = Item::findOne($id);
$item->softDelete(); // mark record as "deleted"

$item = Item::findOne($id);
$item->restore(); // restore record
var_dump($item->isDeleted); // outputs "false"
```

By default attribute values, which should be applied for record restoration are automatically detected from [[\yii2tech\ar\softdelete\SoftDeleteBehavior::$softDeleteAttributeValues]],
however it is better you specify them explicitly via [[\yii2tech\ar\softdelete\SoftDeleteBehavior::$restoreAttributeValues]].

> Tip: if you enable [[\yii2tech\ar\softdelete\SoftDeleteBehavior::$useRestoreAttributeValuesAsDefaults]], attribute values,
  which marks restored record, will be automatically applied at new record insertion.


## Events <span id="events"></span>

By default [[\yii2tech\ar\softdelete\SoftDeleteBehavior::softDelete()]] triggers [[\yii\db\BaseActiveRecord::EVENT_BEFORE_DELETE]]
and [[\yii\db\BaseActiveRecord::EVENT_AFTER_DELETE]] events in the same way they are triggered at regular `delete()`.

Also [[\yii2tech\ar\softdelete\SoftDeleteBehavior]] triggers several additional events in the scope of the owner ActiveRecord:

 - [[\yii2tech\ar\softdelete\SoftDeleteBehavior::EVENT_BEFORE_SOFT_DELETE]] - triggered before "soft" delete is made.
 - [[\yii2tech\ar\softdelete\SoftDeleteBehavior::EVENT_AFTER_SOFT_DELETE]] - triggered after "soft" delete is made.
 - [[\yii2tech\ar\softdelete\SoftDeleteBehavior::EVENT_BEFORE_RESTORE]] - triggered before record is restored from "deleted" state.
 - [[\yii2tech\ar\softdelete\SoftDeleteBehavior::EVENT_AFTER_RESTORE]] - triggered after record is restored from "deleted" state.

You may attach the event handlers for these events to your ActiveRecord object:

```php
<?php

$item = Item::findOne($id);
$item->on(SoftDeleteBehavior::EVENT_BEFORE_SOFT_DELETE, function($event) {
    $event->isValid = false; // prevent "soft" delete to be performed
});
```

You may also handle these events inside your ActiveRecord class by declaring the corresponding methods:

```php
<?php

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

class Item extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                // ...
            ],
        ];
    }

    public function beforeSoftDelete()
    {
        $this->deletedAt = time(); // log the deletion date
        return true;
    }

    public function beforeRestore()
    {
        return $this->deletedAt > (time() - 3600); // allow restoration only for the records, being deleted during last hour
    }
}
```


## Transactional operations <span id="transactional-operations"></span>

You can explicitly enclose [[\yii2tech\ar\softdelete\SoftDeleteBehavior::softDelete()]] method call in a transactional block, like following:

```php
<?php

$item = Item::findOne($id);

$transaction = $item->getDb()->beginTransaction();
try {
    $item->softDelete();
    // ...other DB operations...
    $transaction->commit();
} catch (\Exception $e) { // PHP < 7.0
    $transaction->rollBack();
    throw $e;
} catch (\Throwable $e) { // PHP >= 7.0
    $transaction->rollBack();
    throw $e;
}
```

Alternatively you can use [[\yii\db\ActiveRecord::transactions()]] method to specify the list of operations, which should be performed inside the transaction block.
Method [[\yii2tech\ar\softdelete\SoftDeleteBehavior::softDelete()]] responds both to [[\yii\db\ActiveRecord::OP_UPDATE]] and [[\yii\db\ActiveRecord::OP_DELETE]].
In case current model scenario includes at least of those constants, soft-delete will be performed inside the transaction block.

> Note: method [[\yii2tech\ar\softdelete\SoftDeleteBehavior::safeDelete()]] uses its own internal transaction logic, which may
  conflict with automatic transactional operations. Make sure you do not run this method in the scenario, which is affected by
  [[\yii\db\ActiveRecord::transactions()]].


## Optimistic locks <span id="optimistic-locks"></span>

Soft-delete supports optimistic lock in the same way as regular [[\yii\db\ActiveRecord::save()]] method.
In case you have specified version attribute via [[\yii\db\ActiveRecord::optimisticLock()]], [[\yii2tech\ar\softdelete\SoftDeleteBehavior::softDelete()]]
will throw [[\yii\db\StaleObjectException]] exception in case of version number outdated.
For example, in case you ActiveRecord is defined as following:

```php
<?php

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

class Item extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'softDelete' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'isDeleted' => true
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function optimisticLock()
    {
        return 'version';
    }
}
```

You can create delete link in following way:

```php
<?php
use yii\helpers\Html;

/* @var $model Item */
?>
...
<?= Html::a('delete', ['delete', 'id' => $model->id, 'version' => $model->version], ['data-method' => 'post']) ?>
...
```

Then you can catch [[\yii\db\StaleObjectException]] exception inside controller action code to resolve the conflict:

```php
<?php

use yii\db\StaleObjectException;
use yii\web\Controller;

class ItemController extends Controller
{
    public function delete($id, $version)
    {
        $model = $this->findModel($id);
        $model->version = $version;
        
        try {
            $model->softDelete();
            return $this->redirect(['index']);
        } catch (StaleObjectException $e) {
            // logic to resolve the conflict
        }
    }
    
    // ...
}
```

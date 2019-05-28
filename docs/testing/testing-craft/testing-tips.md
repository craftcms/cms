# Tips

## Maintain your database
The Craft module provides a `cleanup` and `transaction` option for the `codeception.yml` file. 
It is recomended that you set this option to `true`. 

### `transactions`
The transaction option ensures that changes made to the database during your test 
are rolled back using a Yii2 
[transaction](https://www.yiiframework.com/doc/api/2.0/yii-db-transaction). This means that if you,
for example, save a `craft\db\ActiveRecord` instance before the next test that database row is removed.
This prevents collisions and prevens you from spending hours debugging your tests. 

::: warning
If you are running MySQL the `[[%searchindex]]` table may be running the 
MyISAM Database driver. If this is the case transactions are 
[not-supported](https://dev.mysql.com/doc/refman/5.6/en/myisam-storage-engine.html).

If you are creating new elements in your tests using:
`Craft::$app->getElements()->saveElement()` and the element you are saving has content 
in the `[[%searchindex]]` table - this `[[%searchindex]]` content will not be removed. It is recommended to 
manually delete clear the search index or use an [element fixture](framework/fixtures.md#element-fixtures)
:::

### `Cleaup`
The cleanup option ensures that fixtures are removed after a test. This cleans any fixture
data inserted during your test from the database. 
Before the next test the new fixtures will be added again. 

## Use .gitignore
Through the getting started guide you will have setup a `_craft` folder which contains various directories for testing. 
One of these directories is the `storage` directory. During testing Craft will create alot of temporary files and logs in this folder. 
Use a [.gitignore](https://git-scm.com/docs/gitignore) file to not commit these files into your version control system (I.E. GIT). 
The same policy should apply to the `tests/_output/` directory that Codeception creates for tests

TODO: More....

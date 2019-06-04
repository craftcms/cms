# Tips

## Maintain your database
The Craft module provides a `cleanup` and `transaction` option for the `codeception.yml` file. 
It is recommended that you set this option to `true`. 

### `transactions`
The transaction option ensures that changes made to the database during your test 
are rolled back using a Yii2 
[transaction](https://www.yiiframework.com/doc/api/2.0/yii-db-transaction). This means that if you,
for example, save a `craft\db\ActiveRecord` instance before the next test that database row is removed.
This prevents collisions and prevents you from spending hours debugging your tests. 

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
One of these directories is the `storage` directory. During testing Craft will create a lot of temporary files and logs in this folder. 
Use a [.gitignore](https://git-scm.com/docs/gitignore) file to not commit these files into your version control system (I.E. GIT). 
The same policy should apply to the `tests/_output/` directory that Codeception creates for tests

## Namespacing
Craft namespaces it's tests under one separate root namespace and then expands per test subject. I.E. Unit tests are namespaced
under `crafttests\unit` while functional tests are namespaced under `crafttests\functional`. It is advised to apply
this same convention to your tests. If you are testing a 
module or plugin you may want to provide support resources for testing, it is advised to namespace these using
`my/plugin/namespace/test` - this is exactly how Craft does it as well. See the
[element fixtures](../testing-craft/fixtures.md) as an example. 

## Quickly setup tests using the dedicated console command
If you have a general understanding of the typical Craft testing setup you can use the
`tests/setup-tests` console command which will do all of the important setup work for you. 
It will copy from Craft's `src/test/internal/example-test-suite` folder to either your project's root directory.  
or a directory path of your choosing. All you then have to do is: 

- Composer require codeception. 
- Run `codecept build`
- Add a `.env` file. 

TODO: More....

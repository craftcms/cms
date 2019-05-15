# Fixtures
## General
Fixtures are used to setup data in a test suite that is predictable and the same 
for each test run (so assertions can be conducted based on this data).
They can be defined in the `fixturesMethod` [defined](config-options.md) in the `codeception.yml` file. 
In the [Yii2 docs](https://www.yiiframework.com/doc/guide/2.0/en/test-fixtures#defining-a-fixture)
you can read about fixture classes and fixture data as well as how these can be setup/used for testing.

To setup fixtures Create a folder called `fixtures` in your `tests` folder. 
In this folder we will put our fixture classes and fixture data 

## Element fixtures
As Craft's element types are quite complicated some heavy lifting is required in order to set them for usage in 
accordance to what the Yii2 docs describe. For this reason support is provided 
out of the box for setting up various element types. 

::: tip
Craft's element fixtures are based on the excellent team over at [robuust](https://robuust.digital/) 
and their `craft-fixtures` plugin which can be found [here](https://github.com/robuust/craft-fixtures). 
:::

### `Asset fixtures`

If you want to add fixtures for assets. Extend `craft\test\fixtures\elements\AssetFixture` 

The fixture data file could look like this:

```php
<?php

// We need a copy because Asset will move the temp file
copy(__DIR__.'/assets/product.jpg', 'product.jpg');

return [
    [
        'tempFilePath' => 'product.jpg',
        'filename' => 'product.jpg',
        'volumeId' => $this->volumeIds['products'],
        'folderId' => $this->folderIds['clothes'],
    ],
];
```

This will upload and link product.jpg as an asset.

`AssetFixture` will define `$this->volumeIds` and `$this->folderIds` with their handles as key.

The primary keys are: `volumeId`, `folderId`, `filename` and `title`.

### `Category fixtures`

Extend `craft\test\fixtures\elements\CategoryFixture` to add categories. 

The fixture data file could look like this:

```php
<?php

return [
    [
        'groupId' => $this->groupIds['categories'],
        'title' => 'Category',
    ],
];
```

`CategoryFixture` will define `$this->groupIds` with all category group handles as key.

The primary keys are: `siteId`, `groupId` and `title`.

### `Entry fixtures`

Extend `craft\test\fixtures\elements\EntryFixture` to add entries.
 
The fixture data file could look like this:

```php
<?php

return [
    [
        'sectionId' => $this->sectionIds['home'],
        'typeId' => $this->typeIds['home']['home'],
        'title' => 'Home',
    ],
];
```

`EntryFixture`EntryFixture will define `$this->sectionIds` with all section handles as key. It will also define `$this->typeIds` with all section handles as the first key and the entry type handles as the second key.

The primary keys are: `siteId`, `sectionId`, `typeId` and `title`.


// TODO: Globals

### `Tag fixtures`

Extend `craft\test\fixtures\elements\TagFixture` to add tags. 

The fixture data file could look like this:

```php
<?php

return [
    [
        'groupId' => $this->groupIds['tags'],
        'title' => 'Tag',
    ],
];
```

`TagFixture` will define `$this->groupIds` with all tag group handles as key.

The primary keys are: `siteId`, `groupId` and `title`.

### `User fixtures`

Extend `craft\test\fixtures\elements\UserFixture` to add users. 

The fixture data file could look like this:

```php
<?php

return [
    [
        'username' => 'User',
        'email' => 'info@example.com',
    ],
];
```

The primary keys are: `siteId`, `username` and `email`.

## Field layout fixtures
// TODO Explain field layout fixtures

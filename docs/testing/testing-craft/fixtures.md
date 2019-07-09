# Fixtures
Fixtures are used to setup data in a test suite that is predictable and the same 
for each test run (so assertions can be conducted based on this data).
They can be defined in the `fixturesMethod` [defined](config-options.md) in the `codeception.yml` file. 
In the [Yii2 docs](https://www.yiiframework.com/doc/guide/2.0/en/test-fixtures#defining-a-fixture)
you can read about fixture classes and fixture data as well as how these can be setup/used for testing.

To setup fixtures Create a folder called `fixtures` in your `tests` folder. 
In this folder we will put our fixture classes and fixture data 

## Craft specific data
For traditional database rows regular fixtures will suffice. However Craft 
introduces several concepts of its own, these come attached to some complicated 
linked data structures that are very difficult to manage with regular fixtures. 

### Element fixtures
Element types are one of Craft's main selling points for developers. They yield a lot of power.
That power is courtesy of a complicated data structure and set of api's. 
A by-product of this is that some heavy lifting is required if element types are to be 
defined in a single fixture and data file. For this reason support is provided 
out of the box for setting up various element types. 

Provide your own custom element type? 
You can extend `craft\test\fixtures\elements\ElementFixture` to provide developers using your module/plugin support 
for their testing code - or just provide yourself support for testing your own module/plugin.  

::: tip
Craft's element fixtures are based on the excellent team over at [robuust](https://robuust.digital/) 
and their `craft-fixtures`  [plugin](https://github.com/robuust/craft-fixtures).
:::

### `Asset fixtures`

If you want to add fixtures for assets. Extend `craft\test\fixtures\elements\AssetFixture` 

The fixture data file could look like this:

```php
<?php

return [
    [
        'tempFilePath' => 'path/to/_craft/storage/runtime/temp/product.jpg',
        'filename' => 'product.jpg',
        'volumeId' => $this->volumeIds['products'],
        'folderId' => $this->folderIds['clothes'],
    ],
];
```

`product.jpg` (and any of your other testing assets) should live in the `tests/_craft/assets` folder.

This will upload and link product.jpg as an asset.

`AssetFixture` will define `$this->volumeIds` and `$this->folderIds` with their handles as key.

The primary keys are: `volumeId`, `folderId`, `filename` and `title`.

::: warning
The `AssetFixture` class will automatically copy your assets into the `tests/_craft/storage/runtime/temp` folder. 
Please ensure that the `tempFilePath` points to a filename this directory. 
:::

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


### `Global set fixture`

Extend `craft\test\fixtures\elements\GlobalSetFixture` to add Global Sets. 

The fixture data file could look like this:

```php
<?php

return [
    [
        'handle' => 'aHandle',
    ],
];
```

The primary keys are: `handle`.

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

### Element fixture field layout and content. 

If you pass a `fieldLayoutType` into any class that extends the base `ElementFixture` class Craft 
will automatically try to find that the field layout associated with the type you passed in and link this field 
layout to the new Element you are creating. 

If you want to set custom field values you can simply include those into your fixture data file (the examples as shown above). 
Here's an example of a fixture data file that is creating an entry with a title and some custom fields: 

````php
<?php
return [
    [
        // Standard `craft\elements\Entry` fields. 
        'authorId' => '1',
        'sectionId' => '1000',
        'typeId' => '1000',
        'title' => 'Theories of matrix',
        
        // Set a  field layout
        'fieldLayoutType' => 'field_layout_with_matrix_and_normal_fields',
        
        // Set field values
        'aPlainTextFieldHandle' => 'value of text field',
        'anotherPlainTextFieldHandle' => 'another valu',
        
        // If your field layout defines matrix fields - you can set those too!
        'matrixFirst' => [
            'new1' => [
                'type' => 'aBlock',
                'fields' => [
                    'firstSubfield' => 'Some text'
    
                ],
            ],
            'new2' => [
                'type' => 'aBlock',
                'fields' => [
                    'firstSubfield' => 'Some text'
                ],
            ],
        ],
    ]
];

````

### Field layout fixtures
Another Craft specific concept is field layouts. Field layouts consist 
of 
- The layouts themselves
- Tabs
- Fields

All of which are linked together, and would be very difficult to manage in a 
test environment with ordinary fixtures. For this reason Craft provides a special 
`craft\test\fixtures\FieldLayoutFixture` class that provides all the required support 
to setup field layouts, the tabs and the underlying fields, including creating the fields in the `content` table.

All you have to do is create an ordinary fixture class and extend `craft\test\fixtures\FieldLayoutFixture`. 

Then add the `public $dataFile = 'path/to/datafile/` property that points to a datafile
containing at least the following keys (including the nested position)

```php
<?php
use craft\fields\Matrix;

return [
    [
        'type' => 'craft\test\Craft', // Required - can be set to whatever you want. 
        'tabs' => [ // Required - Value can be set to an empty array[]
            [
                'name' => 'Tab 1', // Required
                'fields' => [ // Required - Value can be set to an empty array[]
                    [
                        'layout-link' => [ // Required
                            'required' => true // Required
                        ],
                        'field' => [
                            'name' => 'Test field', // Required
                            'handle' => 'testField2', // Required
                            'fieldType' => SomeField::class, // Required
                        ]
                    ],
                    // Even matrix fields are supported in the following config: 
                    [
                        'layout-link' => [
                            'required' => false
                        ],
                        'field' => [
                            'name' => 'Matrix 1',
                            'handle' => 'matrixFirst',
                            'fieldType' => Matrix::class,
                            'blockTypes' => [
                                'new1' => [
                                    'name' => "A Block",
                                    'handle' => "aBlock",
                                    'fields' => [
                                        'new1' => [
                                            'type' => SomeField::class,
                                            'name' => 'First Subfield',
                                            'handle' => 'firstSubfield',
                                            'instructions' => '',
                                            'required' => false,
                                            'typesettings' => [
                                                'multiline' => ''
                                            ]
                                        ]
                                    ]
                                ],
                                'new2' => [
                                    'name' => "Another Block",
                                    'handle' => "another Block",
                                    'fields' => [
                                        'new1' => [
                                            'type' => SomeField::class,
                                            'name' => 'Another Subfield',
                                            'handle' => 'anotherSubfield',
                                            'instructions' => '',
                                            'required' => false,
                                            'typesettings' => [
                                                'multiline' => ''
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                ]
            ]
        ]
    ]
];
```

::: tip
The value of the key-value pairs can be set to whatever is required
for your project. What is crucial 
is that the array keys are set with __any__ string value. You can add your own parameters to 
the array value of the `field` key - as long as they correspond to `public` properties in 
the class defined in the `fieldType` key. 
:::

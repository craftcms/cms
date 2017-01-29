Services
========

- [What are Services](#what-are-services)
- [Creating a Service](#creating-a-service)
- [Model Operation Methods](#model-operation-methods)
  - [Class-Oriented Methods](#class-oriented-methods)
  - [Interface-Oriented Methods](#interface-oriented-methods)

## What are Services?

Services are [singleton](https://en.wikipedia.org/wiki/Singleton_pattern) classes that get attached to your primary plugin class as [components](http://www.yiiframework.com/doc-2.0/guide-structure-application-components.html) (e.g. `MyPlugin::getInstance()->serviceName`).

They have two jobs:

- They contain most of your plugin’s business logic.
- They define your plugin’s API, which your plugin (and other plugins) can access.

For example, Craft’s field management code is located in `craft\services\Fields`, which is available at `Craft::$app->fields`. It has a `getFieldByHandle()` method that returns a field model by its handle. If that’s something you want to do, you can call `Craft::$app->fields->getFieldByHandle('foo')`.

## Creating a Service

To create a service class for your plugin, create a `services/` subdirectory within your plugin’s `src/` directory, and create a file within it named after the class name you want to give your service. If you want to name your service class `Bacon` then name the file `Bacon.php`.

Open the file in your text editor and use this template as its starting point:

```php
<?php

namespace ns\prefix\services;

use Craft;
use yii\base\Component;

class Bacon extends Component
{
    // ...
}
```

Once the service class exists, you can register it as a component on your primary plugin class by calling [`setComponents()`](http://www.yiiframework.com/doc-2.0/yii-di-servicelocator.html#setComponents()-detail) from its [`init()`](http://www.yiiframework.com/doc-2.0/yii-base-object.html#init()-detail) method:

```php
public function init()
{
    parent::init();

    $this->setComponents([
        'bacon' => \vendor\pluginhandle\services\Bacon::class),
    ]);

    // ...
}
```

With that in place, you will now be able to access your service via `MyPlugin::getInstance()->bacon`.

## Model Operation Methods

Many service methods perform some sort of operation for a given model, such as a CRUD operation.

There are two common types of model operation methods in Craft:

1. Methods that accept a *specific model class* (e.g. `craft\services\Categories::saveGroup()`, which saves a category group represented by the given `craft\models\CategoryGroup` model). We call these **class-oriented methods**.

2. Methods that accept any class so long as it implements an *interface* (e.g. `craft\services\Fields::deleteField()`, which deletes a field represented by the given `craft\base\FieldInterface` instance, regardless of its actual class). We call these **interface-oriented methods**.

Both types of methods should follow the same general control flow, with one difference: interface-oriented methods should trigger callback methods on the model before and after the action is performed, giving the model a chance to run its own custom logic.

Here’s an example: `craft\services\Elements::saveElement()` will call `beforeSave()` and `afterSave()` methods on the element model before and after it saves a record of the element to the `elements` database table. Entry elements (`craft\elements\Entry`) use their `afterSave()` method as an opportunity to save a row in the entry-specific `entries` database table.

### Class-Oriented Methods

Here’s a control flow diagram for class-oriented methods:

```
╔════════════════════════════╗
║ saveRecipe(Recipe $recipe) ║
╚════════════════════════════╝
               │
               ▼
               Λ
              ╱ ╲
             ╱   ╲              ┏━━━━━━━━━━━━━━┓
          validates? ─── no ───▶┃ return false ┃
             ╲   ╱              ┗━━━━━━━━━━━━━━┛
              ╲ ╱
               V
               │
              yes
               │
               ▼
  ┌────────────────────────┐
  │ beforeSaveRecipe event │
  └────────────────────────┘
               │
               ▼
     ┌───────────────────┐
     │ begin transaction │
     │      (maybe)      │
     └───────────────────┘
               │
               ▼
      ┌─────────────────┐
      │ save the recipe │
      └─────────────────┘
               │
               ▼
      ┌─────────────────┐
      │ end transaction │
      │     (maybe)     │
      └─────────────────┘
               │
               ▼
   ┌───────────────────────┐
   │ afterSaveRecipe event │
   └───────────────────────┘
               │
               ▼
        ┏━━━━━━━━━━━━━┓
        ┃ return true ┃
        ┗━━━━━━━━━━━━━┛
```

> {note} It’s only necessary to wrap the operation in a database transaction if the operation encompasses multiple database changes.

Here’s a complete code example of what that looks like:

```php
public function saveRecipe(Recipe $recipe, $runValidation = true)
{
    if ($runValidation && !$recipe->validate()) {
        Craft::info('Recipe not saved due to validation error.', __METHOD__);

        return false;
    }

    $isNewRecipe = !$recipe->id;

    // Fire a 'beforeSaveRecipe' event
    $this->trigger(self::EVENT_BEFORE_SAVE_RECIPE, new RecipeEvent([
        'recipe' => $recipe,
        'isNew' => $isNewRecipe,
    ]));

    // ... Save the recipe here ...

    // Fire an 'afterSaveRecipe' event
    $this->trigger(self::EVENT_AFTER_SAVE_RECIPE, new RecipeEvent([
        'recipe' => $recipe,
        'isNew' => $isNewRecipe,
    ]));

    return true;
}
```

### Interface-Oriented Methods

Here’s a control flow diagram for interface-oriented methods:

```
╔═════════════════════════════════════════════════╗
║ saveIngredient(IngredientInterface $ingredient) ║
╚═════════════════════════════════════════════════╝
                         │
                         ▼
                         Λ
                        ╱ ╲
                       ╱   ╲              ┏━━━━━━━━━━━━━━┓
                    validates? ─── no ───▶┃ return false ┃
                       ╲   ╱              ┗━━━━━━━━━━━━━━┛
                        ╲ ╱
                         V
                         │
                        yes
                         │
                         ▼
          ┌────────────────────────────┐
          │ beforeSaveIngredient event │
          └────────────────────────────┘
                         │
                         ▼
               ┌───────────────────┐
               │ begin transaction │
               └───────────────────┘
                         │
                         ▼
                         Λ
                        ╱ ╲
                       ╱   ╲                        ┌──────────────────────┐
             $ingredient->beforeSave() ── false ───▶│ rollback transaction │
                       ╲   ╱                        └──────────────────────┘
                        ╲ ╱                                     │
                         V                                      ▼
                         │                              ┏━━━━━━━━━━━━━━┓
                        true                            ┃ return false ┃
                         │                              ┗━━━━━━━━━━━━━━┛
                         ▼
              ┌─────────────────────┐
              │ save the ingredient │
              └─────────────────────┘
                         │
                         ▼
           ┌──────────────────────────┐
           │ $ingredient->afterSave() │
           └──────────────────────────┘
                         │
                         ▼
                ┌─────────────────┐
                │ end transaction │
                └─────────────────┘
                         │
                         ▼
           ┌───────────────────────────┐
           │ afterSaveIngredient event │
           └───────────────────────────┘
                         │
                         ▼
                  ┏━━━━━━━━━━━━━┓
                  ┃ return true ┃
                  ┗━━━━━━━━━━━━━┛
```

Here’s a complete code example of what that looks like:

```php
public function saveIngredient(IngredientInterface $ingredient, $runValidation = true)
{
    /** @var Ingredient $ingredient */

    if ($runValidation && !$ingredient->validate()) {
        Craft::info('Ingredient not saved due to validation error.', __METHOD__);

        return false;
    }

    $isNewIngredient = !$ingredient->id;

    // Fire a 'beforeSaveIngredient' event
    $this->trigger(self::EVENT_BEFORE_SAVE_INGREDIENT, new IngredientEvent([
        'ingredient' => $ingredient,
        'isNew' => $isNewIngredient,
    ]));

    $transaction = Craft::$app->getDb()->beginTransaction();

    try {
        if (!$ingredient->beforeSave()) {
            $transaction->rollback();

            return false;
        }

        // ... Save the ingredient here ...

        $ingredient->afterSave();

        $transaction->commit();
    } catch (\Exception $e) {
        $transaction->rollBack();

        throw $e;
    }

    // Fire an 'afterSaveIngredient' event
    $this->trigger(self::EVENT_AFTER_SAVE_INGREDIENT, new IngredientEvent([
        'ingredient' => $ingredient,
        'isNew' => $isNewIngredient,
    ]));

    return true;
}
```



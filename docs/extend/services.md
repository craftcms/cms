# Services

[[toc]]

## What are Services?

Services are [singleton](https://en.wikipedia.org/wiki/Singleton_pattern) classes that get attached to your primary plugin class as [components](https://www.yiiframework.com/doc/guide/2.0/en/structure-application-components) (e.g. `MyPlugin::getInstance()->serviceName`).

They have two jobs:

- They contain most of your plugin’s business logic.
- They define your plugin’s API, which your plugin (and other plugins) can access.

For example, Craft’s field management code is located in <api:craft\services\Fields>, which is available at `Craft::$app->fields`. It has a `getFieldByHandle()` method that returns a field model by its handle. If that’s something you want to do, you can call `Craft::$app->fields->getFieldByHandle('foo')`.

## Creating a Service

To create a service class for your plugin, create a `services/` subdirectory within your plugin’s `src/` directory, and create a file within it named after the class name you want to give your service. If you want to name your service class `Foo` then name the file `Foo.php`.

Open the file in your text editor and use this template as its starting point:

```php
<?php
namespace ns\prefix\services;

use yii\base\Component;

class Foo extends Component
{
    // ...
}
```

Once the service class exists, you can register it as a component on your primary plugin class by calling [setComponents()](api:yii\di\ServiceLocator::setComponents()) from its [init()](api:yii\base\BaseObject::init()) method:

```php
public function init()
{
    parent::init();

    $this->setComponents([
        'foo' => \ns\prefix\services\Foo::class,
    ]);

    // ...
}
```

## Calling Service Methods

You can access your service from anywhere in the codebase using `MyPlugin::getInstance()->serviceName`. So if your service name is `foo` and it has a method named `bar()`, you could call it like this:

```php
MyPlugin::getInstance()->foo->bar()
```

If you need to call a service method directly from your primary Plugin class, you can skip `MyPlugin::getInstance()` and just use `$this`:

```php
$this->foo->bar()
```

## Model Operation Methods

Many service methods perform some sort of operation for a given model, such as a CRUD operation.

There are two common types of model operation methods in Craft:

1. Methods that accept a *specific model class* (e.g. <api:craft\services\Categories::saveGroup()>, which saves a category group represented by the given <api:craft\models\CategoryGroup> model). We call these **class-oriented methods**.

2. Methods that accept any class so long as it implements an *interface* (e.g. <api:craft\services\Fields::deleteField()>, which deletes a field represented by the given <api:craft\base\FieldInterface> instance, regardless of its actual class). We call these **interface-oriented methods**.

Both types of methods should follow the same general control flow, with one difference: interface-oriented methods should trigger callback methods on the model before and after the action is performed, giving the model a chance to run its own custom logic.

Here’s an example: <api:craft\services\Elements::saveElement()> will call `beforeSave()` and `afterSave()` methods on the element model before and after it saves a record of the element to the `elements` database table. Entry elements (<api:craft\elements\Entry>) use their `afterSave()` method as an opportunity to save a row in the entry-specific `entries` database table.

### Class-Oriented Methods

Here’s a control flow diagram for class-oriented methods:

![An example flow for a saveRecipe() method.](../images/save-component--class.png =612x1176)

::: tip
It’s only necessary to wrap the operation in a database transaction if the operation encompasses multiple database changes.
:::

Here’s a complete code example of what that looks like:

```php
public function saveRecipe(Recipe $recipe, $runValidation = true)
{
    // Fire a 'beforeSaveRecipe' event
    $this->trigger(self::EVENT_BEFORE_SAVE_RECIPE, new RecipeEvent([
        'recipe' => $recipe,
        'isNew' => $isNewRecipe,
    ]));

    if ($runValidation && !$recipe->validate()) {
        \Craft::info('Recipe not saved due to validation error.', __METHOD__);
        return false;
    }

    $isNewRecipe = !$recipe->id;

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

![An example flow for a saveIngredient() method.](../images/save-component--interface.png =660x1488)

Here’s a complete code example of what that looks like:

```php
public function saveIngredient(IngredientInterface $ingredient, $runValidation = true)
{
    /** @var Ingredient $ingredient */

    // Fire a 'beforeSaveIngredient' event
    $this->trigger(self::EVENT_BEFORE_SAVE_INGREDIENT, new IngredientEvent([
        'ingredient' => $ingredient,
        'isNew' => $isNewIngredient,
    ]));

    if (!$ingredient->beforeSave()) {
        return false;
    }

    if ($runValidation && !$ingredient->validate()) {
        \Craft::info('Ingredient not saved due to validation error.', __METHOD__);
        return false;
    }

    $isNewIngredient = !$ingredient->id;

    $transaction = \Craft::$app->getDb()->beginTransaction();
    try {
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



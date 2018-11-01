# Models

Models are containers for data. Just about every time information is passed between [services](services.md), [controllers](controllers.md), and templates in Craft, it’s passed via a model.

To create a model, first create a new `models/` subfolder within your plugin’s folder. Then create a new file in that folder, named with this format:

```
[PluginHandle]_[ModelName]Model.php
```

If your plugin name is “Cocktail Recipes”, and your model name is `Ingredient`, the file would be named `CocktailRecipes_IngredientModel.php`.

Create a new class in that file, with the same name as the filename:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientModel extends BaseModel
{
    protected function defineAttributes()
    {
        return array(
            'name' => AttributeType::String,
            'type' => array(AttributeType::Enum, 'values' => "alcohol,mixer,other"),
        );
    }
}
```

That’s it! You will now be able to create new instances of the model, and run validation on them.

## Instantiating your Model

You can create new instances of your model just like any other PHP class:

```php
<?php
namespace Craft;

$ingredient = new CocktailRecipes_IngredientModel();
$ingredient->name = "Tonic Water";
$ingredient->type = "mixer";
```

In the event that you already have an array of key/value pairs that maps to a model’s attributes, you can use the `populateModel()` static method instead, provided by BaseModel:

```php
<?php
namespace Craft;

$ingredient = CocktailRecipes_IngredientModel::populateModel($data);
```

There’s also a corresponding `populateModels()` static method if you have an array of arrays of key/value pairs:

```php
<?php
namespace Craft;

$ingredients = CocktailRecipes_IngredientModel::populateModels($data);
```

These functions also accept instances of BaseModel and [BaseRecord](database.md) rather than arrays.


## Validation

After filling up your model instance with values, validating it is quite simple:

```php
<?php
namespace Craft;

if ($ingredient->validate())
{
    // It validates!
}
else
{
    // Here's a list of all the errors, grouped by attribute:
    $ingredient->getErrors();

    // Here's a list of errors for a single attribute:
    $ingredient->getErrors('name');
}
```

## Further Reading

BaseModel is an instance of Yii’s [CModel](https://www.yiiframework.com/doc/api/1.1/CModel) class, so everything CModel can do, BaseModel can do as well.

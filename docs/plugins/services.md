# Services

All of your plugin’s business logic should go in **services**, including saving data, retrieving data, etc. They provide APIs that your [controllers](controllers.md), [template variables](variables.md), and other plugins can interact with.

To create a service, first create a new `services/` subfolder within your plugin’s folder. Then create a new file in that folder, named with this format:

```
[PluginHandle]_[ServiceName]Service.php
```

If your plugin name is “Cocktail Recipes”, and you’re writing a service to handle ingredient actions, the file would be named `CocktailRecipes_IngredientsService.php`.

Create a new class in that file, with the same name as the filename:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientsService extends BaseApplicationComponent
{
    public function saveIngredient(CocktailRecipes_IngredientModel $ingredient)
    {
        ...
    }

    public function deleteIngredientById($id)
    {
        ...
    }
}
```

The specific functions that go in your services are entirely up to you.

Instances of your services will be available to the entire system via `craft()->pluginName_serviceName`. For example:

```php
craft()->cocktailRecipes_ingredients->saveIngredient($ingredient);
```

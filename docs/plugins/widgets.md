# Dashboard Widgets

Adding new types of widgets to the dashboard couldn’t be easier in Craft.

First, create a new subfolder within your plugin’s folder, called `widgets/`. Create a new file in that folder, named with this format:

```
[PluginHandle]_[WidgetHandle]Widget.php
```

If your plugin name is “Cocktail Recipes”, and your widget name is “Recent Cocktails”, the file would be named `CocktailRecipes_RecentCocktailsWidget.php`.

Create a new class in that file, with the same name as the filename:

```php
<?php
namespace Craft;

class CocktailRecipes_RecentCocktailsWidget extends BaseWidget
{
    public function getName()
    {
        return Craft::t('Recent Cocktails');
    }

    public function getBodyHtml()
    {
        $cocktails = craft()->cocktailRecipes->getRecentCocktails();

        return craft()->templates->render('cocktailrecipes/_widgets/recentcocktails/body', array(
            'cocktails' => $cocktails
        ));
    }
}
```

That `getName()` method should look familiar – your primary plugin class has the same method. Difference is, this time it’s returning the name of your widget, rather than the name of your entire plugin.

`getBodyHtml()` does just what it says: it returns your widget’s body HTML. We recommend that you store the actual HTML in a template, and load it via `craft()->templates->render()`.

::: tip
To make sense of that template path, see [Plugin Template Paths, Explained](templates.md#plugin-template-paths-explained).
:::


## Getting your Widget to Span Multiple Columns

If you want your widget to span across multiple columns in the Dashboard, you just need to override the protected `$colspan` property:

```php
<?php
namespace Craft;

class CocktailRecipes_RecentCocktailsWidget extends BaseWidget
{
    protected $colspan = 2;

    // ...
}
```

Alternatively, if the colspan might be different depending on your widget’s settings or other factors, you can set it from a `getColspan()` function:

```php
<?php
namespace Craft;

class CocktailRecipes_RecentCocktailsWidget extends BaseWidget
{
    public function getColspan()
    {
        return $this->getSettings()->colspan;
    }

    // ...
}
```

## Giving your Widget Some Settings

If your widget requires settings, you first must tell Craft which settings it has. You do that with the aptly named `defineSettings()` method. This method returns an array whose keys define the setting names, and values define the parameters (the type of value, etc.).

```php
<?php
namespace Craft;

class CocktailRecipes_RecentCocktailsWidget extends BaseWidget
{
    protected function defineSettings()
    {
        return array(
           'limit' => array(AttributeType::Number, 'min' => 0),
        );
    }
}
```

With that in place, you can call `$this->getSettings()` from any method within your widget, and get a [model](models.md) class back, prepopulated with your widget’s settings.

Next you need to add a `getSettingsHtml()` method which returns the HTML for displaying your settings. Like `getBodyHtml()`, we recommend that you create a template for the actual settings HTML, and load it up with `craft()->templates->render()`.

```php
<?php
namespace Craft;

class CocktailRecipes_RecentCocktailsWidget extends BaseWidget
{
    // ...

    public function getSettingsHtml()
    {
        return craft()->templates->render('cocktailrecipes/_widgets/recentcocktails/settings', array(
           'settings' => $this->getSettings()
        ));
    }
}
```

::: tip
To make sense of that template path, see [Plugin Template Paths, Explained](templates.md#plugin-template-paths-explained).
:::

If you need to do any processing on your settings’ post data before they’re saved to the database, you can do it with the `prepSettings()` method:

```php
<?php
namespace Craft;

class CocktailRecipes_RecentCocktailsWidget extends BaseWidget
{
    // ...

    public function prepSettings($settings)
    {
        // Modify $settings here...

        return $settings;
    }
}
```

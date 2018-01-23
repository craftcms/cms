# Field Types

{intro} Whenever someone creates a new [field](../fields.md) in Craft, they must specify what type of field it is. The system comes with a handful of field types baked in, and we’ve made it extremely easy for plugins to add new ones.

First, create a new subfolder within your plugin’s folder, called fieldtypes/. Create a new file in that folder, named with this format:

```
[PluginHandle]_[FieldTypeName]FieldType.php
```

If your plugin name is “Cocktail Recipes”, and your fieldtype name is “Ingredient List”, the file would be named CocktailRecipes_IngredientListFieldType.php.

Create a new class in that file, with the same name as the filename:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientListFieldType extends BaseFieldType
{
    public function getName()
    {
        return Craft::t('Ingredient List');
    }

    public function getInputHtml($name, $value)
    {
        return craft()->templates->render('cocktailrecipes/ingredientlist/input', array(
            'name'  => $name,
            'value' => $value
        ));
    }
}
```

That `getName()` method should look familiar – your primary plugin class has the same method. Difference is, this time it’s returning the name of your fieldtype, rather than the name of your entire plugin.

`getInputHtml()` does just what it says: it returns your fieldtype’s input HTML. It accepts two arguments: `$name` and `$value`. `$name` is the name you should assign your HTML input’s `name=` attribute, and `$value` is the field’s current value (either from the DB, or the POST data if there was a validation error).

You’ll notice that `getInputHtml()` is simply passing those two arguments along to a template. We recommend you do the same, so you can keep all of your plugin’s HTML views together in one place.

Here’s an example input template for a textarea fieldtype:

```twig
<textarea name="{{ name }}">{{ value }}</textarea>
```

> {tip} To make sense of that “cocktailrecipes/ingredientlist/input” template path, see [Plugin Template Paths, Explained](templates.md#plugin-template-paths-explained).

## Binding Javascript

It’s easy to bind some Javascript to your field type’s input. You just need to keep in mind that there’s a good chance the HTML returned by `getInputHtml()` is going to be run through [namespaceInputs()](/classreference/services/TemplatesService#namespaceInputs-detail) before being output to the page. Which means that any `name=` and `id=` HTML attributes will get prefixed with something.

For example, when editing an entry, fields get namespaced with “field”. So an input with the name “cocktails” becomes “fields[cocktails]”, and an element with the ID “cocktails” would become “fields-cocktails”.

If your Javascript code relies on knowing an ID within your field’s HTML, all you have to do is pass the ID through [namespaceInputId()](/classreference/services/TemplatesService#namespaceInputId-detail) before placing it in your Javascript code:

```php
public function getInputHtml($name, $value)
{
    // Reformat the input name into something that looks more like an ID
    $id = craft()->templates->formatInputId($name);

    // Figure out what that ID is going to look like once it has been namespaced
    $namespacedId = craft()->templates->namespaceInputId($id);

    // Include our Javascript
    craft()->templates->includeJsResource('cocktails/js/input.js');
    craft()->templates->includeJs("$('#{$namespacedId}').cocktail();");

    // Render the HTML
    return craft()->templates->render('cocktails/input', array(
        'name'  => $name,
        'id'    => $id,
        'value' => $value
    ));
}
```

## Accessing Contextual Data

Usually when your fieldtype’s functions are called, it’s done within the context of a particular field and a particular element (entry, asset, global set, tag, or user). You can access the field’s info via `$this->model`, which will be a FieldModel instance. And you can access the element’s info via `$this->element`, which will be an instance of a subclass of BaseElementModel.


## Giving your Fieldtype Some Settings

If your fieldtype requires settings, you first must tell Craft which settings it has. You do that with the aptly named `defineSettings()` method. This method returns an array whose keys define the setting names, and values define the parameters (the type of value, etc.).

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientListFieldType extends BaseFieldType
{
    // ...

    protected function defineSettings()
    {
        return array(
            'initialSlots' => array(AttributeType::Number, 'min' => 0)
        );
    }
}
```

With that in place, you can call `$this->getSettings()` from any method within your widget, and get a [model](models.md) class back, prepopulated with your widget’s settings.

Next you need to add a `getSettingsHtml()` method which returns the HTML for displaying your settings. Like `getInputHtml()`, we recommend that you create a template for the actual settings HTML, and load it up with `craft()->templates->render()`.

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientListFieldType extends BaseFieldType
{
    // ...

    public function getSettingsHtml()
    {
        return craft()->templates->render('cocktailrecipes/ingredientlist/settings', array(
            'settings' => $this->getSettings()
        ));
    }
}
```

> {tip} To make sense of that template path, see [Plugin Template Paths, Explained](templates.md#plugin-template-paths-explained).

If you need to do any processing on your settings’ post data before they’re saved to the database’s `content` table, you can do it with the `prepSettings()` method:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientListFieldType extends BaseFieldType
{
    // ...

    public function prepSettings($settings)
    {
        // Modify $settings here...

        return $settings;
    }
}
```

## Customizing the Database Column Type

When someone creates a new field using your fieldtype, your fieldtype can choose what type of column it gets within the `content` database table. By default, BaseFieldType sets the column to `VARCHAR(255)`, but you can override that with `defineContentAttribute()`:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientListFieldType extends BaseFieldType
{
    // ...

    public function defineContentAttribute()
    {
        return AttributeType::Number;
    }
}
```

If your fieldtype is storing data in its own table, and doesn’t have any use for a column within the main `content` table, you may also set `defineContentAttribute()` to return `false`.


## Modifying your Input’s Post Data

If you need to do any processing on your input’s post data before it’s saved to the database, you can do it with the `prepValueFromPost()` function:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientListFieldType extends BaseFieldType
{
    // ...

    public function prepValueFromPost($value)
    {
        // Modify $value here...

        return $value;
    }
}
```

## Modifying your Fieldtype’s Stored Data for Use

If you need to do any processing on your fieldtype’s stored data before it can be used by the templates and `getInputHtml()`, you can do it with the `prepValue()` function.

If your fieldtype is storing data in a different database table, `prepValue()` is where you fetch it.

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientListFieldType extends BaseFieldType
{
    // ...

    public function prepValue($value)
    {
        // Modify $value here...

        return $value;
    }
}
```


## Events

BaseFieldType provides three events that you can latch code onto:

### `onBeforeSave()`

Called right before a field is saved.

### `onAfterSave()`

Called right after a field is saved, and `$this->model->id` is set.

### `onAfterElementSave()`

Called right after an element is saved, and `$this->element->id` is set.

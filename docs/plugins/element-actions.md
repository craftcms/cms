# Element Actions

When you select elements on their index pages, the regular toolbar options get replaced with new ones that will trigger various actions, like batch-deleting all of the selected elements.

That functionality is provided by Element Action classes.

If your plugin has a custom Element Type that needs custom actions, or you want to add new actions to the built-in Element Types’ index pages, you can do that by giving your plugin its own Element Action classes.

## Requirements

On a technical level, Element Action classes must meet the following requirements:

* They must live within an `elementactions/` subfolder in your plugin’s folder.
* Their filenames and class names must have this format:
  ```
  [PluginHandle]_[ActionHandle]ElementAction.php
  ```
* Their files must have the `Craft` namespace.
* They must implement the [IElementAction](https://docs.craftcms.com/api/v2/elementactions/IElementAction.html) interface.

::: tip
To keep your Element Action class lean and mean, it can inherit from [BaseElementAction](https://docs.craftcms.com/api/v2/elementactions/BaseElementAction.html) instead of implementing the whole IElementAction interface on its own.
:::

## UI Modes

There are two ways your Element Action can manifest itself on the page:

* As a custom trigger in the toolbar
* As an option tucked inside the menu at the end of the toolbar

The choice is up to the Element Action class. If [getTriggerHtml()](https://docs.craftcms.com/api/v2/elementactions/IElementAction.html#getTriggerHtml-detail) returns anything, then whatever it returns will show up the toolbar; otherwise a new option will be added to the menu for you, labeled with whatever [getName()](https://docs.craftcms.com/api/v2/etc/components/IComponentType.html#getName-detail) returns.


## Example 1: Custom Trigger

Here’s a sample Element Action whose job is to add an ingredient to all of the selected elements on the index page. The user is going to need to choose _which_ ingredient to add, so it makes sense to give this Element Action its own toolbar button, which opens a menu with all the possible ingredient options. Clicking on an ingredient should trigger the action.

(For the purposes of this example, it doesn’t matter what an “ingredient” is. It could be an entry from an “Ingredients” channel, a custom Element Type, etc.)

```php
<?php
namespace Craft;

class CocktailRecipes_AddIngredientElementAction extends BaseElementAction
{
    public function getName()
    {
        return Craft::t('Add Ingredient');
    }

    public function getTriggerHtml()
    {
        // Render the trigger menu template with all the available ingredients    
        $ingredients = craft()->cocktailRecipes->getAllIngredients();

        return craft()->templates->render('cocktailRecipes/_addIngredientsTrigger', array(
            'ingredients' => $ingredients
        ));
    }

    public function performAction(ElementCriteriaModel $criteria)
    {
        // Get the selected ingredient    
        $ingredientId = $this->getParams()->ingredient;    
        $ingredient = craft()->cocktailRecipes->getIngredientById($ingredientId);

        // Make sure it's a valid one    
        if (!$ingredient)
        {
            $this->setMessage(Craft::t('The selected ingredient could not be found.'));
            return false;        
        }

        // Add the ingredient to the selected elements
        $elements = $criteria->find();

        foreach ($elements as $element)
        {
            craft()->cocktailRecipes->addIngredientToElement($element, $ingredient);
        }

        // Success!
        $this->setMessage(Craft::t('Ingredient added successfully.'));
        return true;
    }

    protected function defineParams()
    {
        return array(
            'ingredient' => array(AttributeType::Number, 'required' => true),
        );
    }
}
```

And here’s what that `cocktailRecipes/_addIngredientsTrigger` template looks like:

```twig
<div class="btn menubtn" role="button">{{ "Add Ingredient"|t }}</div>
<div class="menu">
    <ul>
        {% for ingredient in ingredients %}
            <li><a class="formsubmit" data-param="ingredient" data-value="{{ ingredient.id }}">{{ ingredient.name }}</a></li>
        {% endfor %}
    </ul>
</div>
```

::: tip
To make sense of that template path, see [Plugin Template Paths, Explained](templates.md#plugin-template-paths-explained).
:::

### Breaking it Down

Here’s what’s going on in this example:

1. Our [getTriggerHtml()](https://docs.craftcms.com/api/v2/elementactions/IElementAction.html#getTriggerHtml-detail) method fetches all the ingredients from our plugin’s imaginary [service](services.md), passes them to a `cocktailRecipes/_addIngredientsTrigger` template, and returns the template’s rendered HTML.
2. Our `cocktailRecipes/_addIngredientsTrigger` template defines a menu button labeled “Add Ingredient”. When clicked, the button will open a menu that lists all of the ingredients we fetched. (We get this UI functionality for free in Craft thanks to those `menubtn` and `menu` classes.)
3. When one of the menu options are clicked on, a form that contains our trigger will automatically get submitted thanks to that `formsubmit` class. Additionally, a new hidden input will added to the form with the name “ingredient” and its value will be whatever the ingredient’s ID is, thanks to those `data-param` and `data-value` attributes.
4. On the back end, when an action request comes in, Craft will instantiate a new `CocktailRecipes_AddIngredientElementAction` object. Craft knows that it’s expecting an `ingredients` parameter because [defineParams()](https://docs.craftcms.com/api/v2/elementactions/BaseElementAction.html#defineParams-detail) has defined it, so Craft will check the POST data for it.
5. Once any parameters have been assigned, Craft will call the [performAction()](https://docs.craftcms.com/api/v2/elementactions/IElementAction.html#performAction-detail) method, passing an [ElementCriteriaModel](https://docs.craftcms.com/api/v2/models/ElementCriteriaModel.html) to it that represents all of the selected elements.
6. `performAction()` then loops through all of the selected elements, adding the selected ingredient to them, with the help of one of its service methods. It responds with either `true` or `false` depending on whether it was successful.
7. The index page is notified of the action’s result, the elements are refreshed, and the message set by [setMessage()](https://docs.craftcms.com/api/v2/elementactions/BaseElementAction.html#setMessage-detail) is displayed to the user.


## Example 2: Menu Option

Here’s a sample Element Action whose job is to remove all ingredients from the selected elements on the index page. In this case, there is no need for the user to set any parameters for the action, so rather than giving the action its own trigger in the toolbar, a menu option will do just fine.

```php
<?php
namespace Craft;

class CocktailRecipes_RemoveAllIngredientsElementAction extends BaseElementAction
{
    public function getName()
    {
        return Craft::t('Remove all ingredients');
    }

    public function isDestructive()
    {
        return true;
    }

    public function performAction(ElementCriteriaModel $criteria)
    {
        // Remove all the ingredients from the selected elements
        $elements = $criteria->find();

        foreach ($elements as $element)
        {
            craft()->cocktailRecipes->removeAllIngredientsFromElement($element);
        }

        // Success!
        $this->setMessage(Craft::t('Ingredients removed successfully.'));
        return true;
    }
}
```

### Breaking it Down

Here’s what’s going on in this example:

1. Our Element Action doesn’t have a [getTriggerHtml()](https://docs.craftcms.com/api/v2/elementactions/IElementAction.html#getTriggerHtml-detail) method, so instead of getting its own trigger in the toolbar, Craft simply gives it an option in the actions menu, labelled “Remove all ingredients” because that’s what [getName()](https://docs.craftcms.com/api/v2/etc/components/IComponentType.html#getName-detail) returned. It will show up below a horizontal rule in the option menu, because [isDestructive()](https://docs.craftcms.com/api/v2/elementactions/IElementAction.html#isDestructive-detail) returned `true`.
2. When the user clicks on the “Remove all ingredients” menu option, the browser will send off a request to the back end informing it to run that action.
3. On the back end, when the action request comes in, Craft will instantiate a new CocktailRecipes_RemoveAllIngredientsElementAction object, and call its [performAction()](https://docs.craftcms.com/api/v2/elementactions/IElementAction.html#performAction-detail) method, passing an [ElementCriteriaModel](https://docs.craftcms.com/api/v2/models/ElementCriteriaModel.html) to it that represents all of the selected elements.
4. performAction() then loops through all of the selected elements, adding the selected ingredient to them, with the help of one of its service methods. It responds with either `true` or `false` depending on whether it was successful.
5. The index page is notified of the action’s result, the elements are refreshed, and the message set by [setMessage()](https://docs.craftcms.com/api/v2/elementactions/BaseElementAction.html#setMessage-detail) is displayed to the user.


## Binding JavaScript to Element Action Triggers

Craft provides a JavaScript class that makes it easy to bind JavaScript to your triggers.

To get started, add this to your [getTriggerHtml()](https://docs.craftcms.com/api/v2/elementactions/IElementAction.html#getTriggerHtml-detail) method:

```php
public function getTriggerHtml()
{
    $js = 'new Craft.ElementActionTrigger({' .
               'handle: '.JsonHelper::encode($this->getClassHandle()).', ' .
               'batch: true, ' .
               'validateSelection: function($selectedElements){ return true; }, ' .
               'activate: function($selectedElements){ alert("Made you click"); }' .
          '});';

    craft()->templates->includeJs($js);
}
```

::: tip
You might find it easier to write the JavaScript using PHP’s [Heredoc string syntax](http://php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc). Alternatively you could write it in a resource file and include it using [TemplatesService::includeJsFile()](https://docs.craftcms.com/api/v2/services/TemplatesService.html#includeJsFile-detail).
:::

Craft.ElementActionTrigger supports the following settings:

* **handle** – The Element Action class’s handle. In our examples above, that would either be “CocktailRecipes_AddIngredient” or “CocktailRecipes_RemoveAllIngredients”, but if you just pass in `$this->getClassHandle()` you won’t have to worry about it.
* **batch** – A boolean indicating whether the trigger should be enabled when more than one element is selected. (Defaults to `true`.)
* **validateSelection** – Can be set to a function which determines whether or not the trigger should be enabled for the currently-selected elements. The function should return `true` or `false` depending on whether the selection is valid or not. (This setting is optional.)
* **activate** – Can be set to a function which will completely override what happens when the trigger is activated. (This setting is optional.)

::: tip
You can bind JavaScript to your Element Action trigger regardless of whether it has a custom button in the toolbar or it lives within the action menu.
:::

## Adding Actions to Index Pages

Once you’ve created an Element Action class, you need to add it to the appropriate element index page(s.html).

If your Element Action is intended for your own custom Element Type, you can do that by implementing the [getAvailableActions()](https://docs.craftcms.com/api/v2/elementtypes/IElementType.html#getAvailableActions-detail) method on your Element Type class.

If you want to add your Element Action to a third party’s Element Type, you will need to use its corresponding plugin hook (assuming the Element Type provides one). The following hooks are provided by Craft’s built-in Element Types:

* [addEntryActions](hooks-reference.md#addEntryActions)
* [addCategoryActions](hooks-reference.md#addCategoryActions)
* [addAssetActions](hooks-reference.md#addAssetActions)
* [addUserActions](hooks-reference.md#addUserActions)

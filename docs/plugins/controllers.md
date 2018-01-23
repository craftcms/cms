# Controllers

Generally speaking, controllers are the middlemen between the front end of the CP/website and your plugin’s [services](services.md). They contain **action methods** which handle individual tasks.

A common pattern used throughout Craft involves a controller action gathering post data, saving it on a [model](models.md), passing the model off to a service, and then responding to the request appropriately depending on the service method’s response.

Action methods begin with the prefix “action”, followed by a description of what the method does (for example, `actionSaveIngredient()`).

To create a controller, first create a new controllers/ subfolder within your plugin’s folder. Then create a new file in that folder, named with this format:

```
[PluginHandle]_[ControllerName]Controller.php
```

If your plugin name is “Cocktail Recipes”, and you’re writing a controller to handle ingredient actions, the file would be named “CocktailRecipes_IngredientsController.php”.

Create a new class in that file, with the same name as the filename:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientsController extends BaseController
{
    public function actionSaveIngredient()
    {
        // ...
    }

    public function actionDeleteIngredient()
    {
        // ...
    }
}
```

## How Controller Actions fit into Routing

When a request comes in, the first thing Craft does after determining that it’s not a resource request is check if it’s a controller action request. **Action requests** will either have a URI which starts with “actions/” (a word that is customizable via the “actionTrigger” config setting in craft/config/general.php), or an “action” param in POST or the query string.

If it is an action request, the next thing Craft will do is attempt to match the **action path** (whatever comes after “actions/” in the URL, or whatever the “action” param is set to) to an actual controller action. In the context of plugins, action paths look like this::

```
[PluginHandle]/[ControllerName]/[ActionName]
```

If your plugin class is “CocktailRecipesPlugin”, your controller class name is “CocktailRecipes_IngredientsController”, and your action method name is “actionSaveIngredient”, the action path would be “cocktailRecipes/ingredients/saveIngredient”.

If Craft is able to match the action path to a controller action, that action method will be called. (If not, Craft responds with a 404.) From there, it’s up to the controller how to respond to the request. (See below for a couple examples of response functions.) However if the controller doesn’t call a response function, Craft will continue on with its request routing, as if nothing had just happened.


## Posting to Controller Actions

Most of the time, you’ll want to access controller actions via HTML forms over POST. To do this, we recommend that you leave your form’s ‘action’ attribute blank, and specify your action via a hidden input named “action” instead:

```twig
<form method="post" action="" accept-charset="UTF-8">
    <input type="hidden" name="action" value="cocktailRecipes/ingredients/saveIngredient">
    <input type="hidden" name="redirect" value="cocktailRecipes/ingredients">

    <!-- ...-->

    <input class="btn submit" type="submit" value="{{ 'Submit'|t }}">
</form>
```

When you leave your form’s ‘action’ attribute blank, browsers will default to the current request’s URL. Which is great in the event that your controller needs to reload the previous page without a redirect. A common example of this is when the user’s input didn’t validate, and you want to pass the errors back to your template (ideally tucked away within a [model](models.md)).

You can queue variables up to be passed to the requested template by passing them to `craft()->urlManager->setRouteVariables()`:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientsController extends BaseController
{
    public function actionSaveIngredient()
    {
        $this->requirePostRequest();

        $ingredient = new CocktailRecipes_IngredientModel();

        // ...

        if (craft()->cocktailRecipes_ingredients->saveIngredient($ingredient))
        {
            craft()->userSession->setNotice(Craft::t('Ingredient saved.'));
            $this->redirectToPostedUrl();
        }
        else
        {
            // Prepare a flash error message for the user.
            craft()->userSession->setError(Craft::t('Couldn’t save ingredient.'));

            // Make the ingredient model available to the template as an 'ingredient' variable,
            // since it contains the user's dumb input as well as the validation errors.
            craft()->urlManager->setRouteVariables(array(
               'ingredient' => $ingredient
            ));
        }
    }

    // ...
}
```


## Posting to Controller Actions with JavaScript

Craft provides a JavaScript function that makes it very easy to post to your controller actions as well:

```javascript
var data = {
    // ...
};

Craft.postActionRequest('cocktailRecipes/ingredients/saveIngredient', data, function(response) {
    // ...
});
```


## Linking Directly to Controller Actions

If you have a reason to link directly to a controller action, opposed to posting data to it, you can do that using the `UrlHelper::getActionUrl()` function:

```php
<?php
namespace Craft;

$url = UrlHelper::getActionUrl('cocktailRecipes/ingredients/deleteIngredient', array('id' => 10));
```

A similar `actionUrl()` function is available to your templates:

```twig
<a href="{{ actionUrl('cocktailRecipes/ingredients/saveIngredient', { id: 10 }) }}">
```

And then there’s `Craft.getActionUrl()` for Javascript:

```javascript
var url = Craft.getActionUrl('cocktailRecipes/ingredients/saveIngredient', { id: 10 });
```

> {warning} You’ll notice that action URLs begin with “actions/”. Don’t be tempted to skip these action URL functions and just type “actions/” yourself though, as that trigger segment is configurable.

## Allowing Anonymous Access to Actions

By default, controller actions are only accessible to logged-in users. You can override that by changing the protected `$allowAnonymous` property on your controller class. To allow anonymous access to all of your controller’s actions, set it to `true`:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientsController extends BaseController
{
    protected $allowAnonymous = true;

    // ...
}
```

Or if you only want to allow anonymous access to specific actions, you can set it to an array of those actions’ method names:

```php
<?php
namespace Craft;

class CocktailRecipes_IngredientsController extends BaseController
{
    protected $allowAnonymous = array('actionSaveIngredient');

    // ...
}
```

## BaseController Methods

BaseController makes several methods available to your action methods:

### `$this->renderTemplate($template, $variables)`

This is a shortcut for [TemplatesService::render()](/classreference/services/TemplatesService#render-detail), but rather than returning the rendered template, it will output it to the browser and end the request. It will also include any CSS and JS that is queued up via [includeCssFile()](/classreference/services/TemplatesService#includeCssFile-detail), [includeJsFile()](/classreference/services/TemplatesService#includeJsFile-detail), et al., and it will set the appropriate MIME type header based on the template file’s extension.

### `$this->requireLogin()`

Requires the user to be logged in. Useful if most of your actions should allow anonymous access, with a couple of exceptions. Set `protected $allowAnonymous = true;` and call `$this->requireLogin()` at the beginning of any action methods that should require login.

### `$this->requireAdmin()`

Requires the user to be logged in with an Admin account.

### `$this->requirePostRequest()`

Requires the current request to be sent over POST. We recommend you use this for all actions that change the system state in any way.

### `$this->requireAjaxRequest()`

Requires the current request to be sent over AJAX.

### `$this->redirect($url, $terminate = true, $statusCode = 302)`

Redirects the request to a different URL. ``$url`` can be either an absolute URL or just a URI.

### `$this->redirectToPostedUrl($variables = array())`

Redirects to the URL defined by the “redirect” POST parameter. If you pass any variables, they will be swapped out first. For example, `$this->redirectToPostedUrl(array('drinkId' => $drink->id));` would swap any instances of `{drinkId}` in the posted URL with `$drink->id`.

### `$this->returnJson($var)`

JSON-encodes ``$var`` and responds with it, with the appropriate JSON headers.

### `$this->returnErrorJson($error)`

Responds with JSON, where `response.error` is set to `$error`.
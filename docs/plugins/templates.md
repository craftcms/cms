# Templates

In Craft, all HTML rendering is done via [templates](../templating-overview.md) – even for the CP itself. There are no PHP-based view files.

If your plugin needs its own templates, you can place them in a templates/ folder within your plugin’s folder (ex: craft/plugins/cocktailrecipes/templates/).

## Plugin Template Paths, Explained

To manually render a template from your plugin’s PHP code, call [TemplatesService::render()](https://docs.craftcms.com/api/v2/services/TemplatesService.html#render-detail):

```php
craft()->templates->render('pluginHandle/path/to/template')
```

The first segment of the path you pass into TemplatesService::render() is your plugin handle; everything after it is the path to your template, relative from your plugin’s templates/ folder.

So, if you were to call `craft()->templates->render('cocktailrecipes/settings')`, for example, Craft would check the following locations, in this order:

1. craft/app/templates/cocktailrecipes/settings.html
2. craft/app/templates/cocktailrecipes/settings/index.html
3. craft/plugins/cocktailrecipes/templates/settings.html
4. craft/plugins/cocktailrecipes/templates/settings/index.html

As you can see, the “templates/” folder segment is assumed, so there’s no need to include it when calling `render()`.

> {tip} Craft will only automatically look for templates within the plugin folders for CP requests. If you are going to be calling TemplatesService::render() from a front end site request, you will need to manually tell Craft where to find your template using [PathService::setTemplatesPath()](https://docs.craftcms.com/api/v2/services/PathService.html#setTemplatesPath-detail) first:
>
> ```php
> $oldPath = craft()->path->getTemplatesPath();
> $newPath = craft()->path->getPluginsPath().'pluginHandle/templates';
> craft()->path->setTemplatesPath($newPath);
> $html = craft()->templates->render('path/to/template');
> craft()->path->setTemplatesPath($oldPath);
> ```
>
> If you do this, you won’t be needing to begin the template path with your plugin handle.

## Giving your Plugin its own CP Section

If you want to give your plugin its own CP section, add this to your primary plugin class:

```php
<?php
namespace Craft;

class CocktailRecipesPlugin extends BasePlugin
{
    // ...

    public function hasCpSection()
    {
        return true;
    }
}
```

With that set, your plugin will show up in the CP nav. Clicking on it will take you to admin/PluginHandle, which will route to index.html within your plugin’s templates/ folder.

Your plugin’s CP section can have as many pages as you’d like. To link to other pages, use the `{{ url() }}` template function just like you would in your site’s templates (ex: `href="{{ url('cocktailrecipes/all') }}`).

## Extending the CP layout

Most of the time you’ll want your plugin’s templates to look like the rest of the CP. To do that, they must extend the ``_layouts/cp`` layout.

The `_layouts/cp` layout expects two variables to be set: `title` and `content`. `title` is used to set the page’s `<title>` and `<h1>` tag values, and `content` defines the HTML that should show up in the `#main` div.

```twig
{% extends "_layouts/cp" %}

{% set title = "Cocktail Recipes"|t %}

{% set content %}
    <p>Hello!</p>
{% endset %}
```

If your plugin’s CP section has its own sub-navigation, you can define that by setting the `tabs` variable in your template:

```twig
{% set tabs = {
    recent: { label: "Recent"|t, url: url('cocktailrecipes') },
    new:    { label: "Add a New Recipe"|t, url: url('cocktailrecipes/new') }
} %}
```

Tell the CP template which tab should be selected by setting the `selectedTab` variable:

```twig
{% set selectedTab = 'recent' %}
```

You can also add breadcrumbs to your pages by setting the `crumbs` variable:

```twig
{% set crumbs = [
    { label: "Cocktail Recipes"|t, url: url('cocktailrecipes') },
    { label: recipe.groupName|t, url: url('cocktailrecipes/recipes/'~recipe.groupHandle) }
] %}
```

## Dynamic URL Routing

By default, incoming requests are routed to a template with the same path as the request URI (possibly with “.html” or “/index.html” appended to it). Most of the time this works well, but it falls short for dynamic URLs, such as URLs where one of the segments is an ID or a slug. For example, you might want to route URLs that look like “admin/cocktailrecipes/123” to templates/\_edit.html.

You can accomplish this by registering **routes**. Craft gives plugins a chance to register new CP routes via the registerCpRoutes hook. Simply add a new method to your plugin called `registerCpRoutes()`:

```php
<?php
namespace Craft;

class CocktailRecipesPlugin extends BasePlugin
{
    // ...

    public function registerCpRoutes()
    {
        return array(
            'cocktailrecipes/(?P<recipeId>\d+)' => 'cocktailrecipes/_edit',
       );
    }
}
```

As you can see, the method returns an array of routes. The keys are regular expressions that the request URI will be matched against, and the values are template paths to be loaded when a successful match occurs.

If your regular expression includes any named subpatterns, e.g. `(?P<recipeId>\d+)`, their match values will become available to the template as variables of the same name. So in this example, if the URI was “admin/cocktailrecipes/123”, the cocktailrecipes/_edit template would get loaded, and a `{{ recipeId }}` variable would be available to it, with the value “123”.
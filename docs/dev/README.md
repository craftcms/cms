# Front-End Development

In Craft, you define your site’s HTML output with templates.

Templates are files that live within your `templates/` folder. The structure of your templates is completely up to you – you can put templates at the root of that folder, within subdirectories, or within subdirectories’ subdirectories (and on and on). Whatever works for your site’s needs.

Craft uses [Twig](https://twig.symfony.com/) to parse your templates. Twig is elegant, powerful, and blazing fast. If you’re new to Twig, be sure to read the [Twig Primer](twig-primer.md) to familiarize yourself with its syntax.

::: tip
PHP code isn’t allowed in your templates, but Craft provides various ways to [extend Twig](../extend/extending-twig.md) to suit your needs.
:::

## Template Paths

There are several times when you’ll need to enter a path to one of your templates:

* When choosing which template [entry](../sections-and-entries.md) and [category](../categories.md) URLs should load
* When assigning a template to a [route](../routing.md#dynamic-routes)
* Within [include](https://twig.symfony.com/doc/tags/include.html), [extends](https://twig.symfony.com/doc/tags/extends.html), and [embed](https://twig.symfony.com/doc/tags/embed.html) template tags

Craft has a standard template path format that applies to each of these cases: a Unix-style file system path to the template file, relative from your `templates/` folder.

For example, if you have a template located at `templates/recipes/entry.twig`, the following template paths would point to it:

* `recipes/entry`
* `recipes/entry.twig`

### Index Templates

If you name your template `index.twig`, you don’t need to specify it in the template path.

For example, if you have a template located at `templates/recipes/ingredients/index.twig`, the following template paths would point to it:

* `recipes/ingredients`
* `recipes/ingredients/index`
* `recipes/ingredients/index.twig`

If you have templates located at both `templates/recipes/ingredients.twig` *and* `templates/recipes/ingredients/index.twig`, the template path `recipes/ingredients` will match `ingredients.twig`.


### Hidden Templates

Craft treats templates with names prefixed with an underscore, for example `recipes/_entry.twig`, as hidden templates that are not directly accessible.

If we have a recipe entry that is available at the entry URL `http://mysite.com/recipes/gin-tonic`, which uses the template located at `recipes/entry`, someone could access the template directly at `http://mysite.com/recipes/entry`.

In this example there is no reason to access the template directly because it's only ever used as part of an entry URL. We change its file name to `_entry.twig` so it is considered hidden by Craft and update the settings in our Section.

Now when we try to access `http://mysite.com/recipes/entry` Craft returns a 404 error instead of attempting to render the template.

## Template Localization

If you’re running multiple sites with Craft, you can create site-specific subfolders in your `templates/` folder, which contain templates that will only be available to a specific site.

For example, if you want to create a special template welcoming your German customers, but there’s no need for it on your English site, then you could save it in `templates/de/welcome.twig`. That template would be available from `http://example.de/welcome`.

Craft will look for localized templates _before_ it looks for templates in the normal location, so you can use them to override non-localized templates. See our [Localization Guide](../localization.md) for more details.

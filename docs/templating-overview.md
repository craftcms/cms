# Templating Overview

In Craft, you define your site’s HTML output with templates.

Templates are files that live within your craft/templates folder. The structure of your templates is completely up to you – you can put templates at the root of that folder, within subdirectories, or within subdirectories’ subdirectories (and on and on). Whatever works for your site’s needs.

Craft uses [Twig](https://twig.symfony.com/) to parse your templates. Twig is elegant, powerful, and blazing fast. If you’re new to Twig, be sure to read through our [twig-primer](twig-primer.md) to familiarize yourself with its syntax.

::: tip
PHP code isn’t allowed in your templates. If you have a need to do something that is not possible out-of-the-box with Craft or Twig, you can create a [plugin](plugins/introduction.md) that provides a new [template variable](plugins/variables.md).
:::

## Template Paths

There are several times when you’ll need to enter a path to one of your templates:

* When choosing which template [entry](sections-and-entries.md) and [category](categories.md) URLs should load
* When assigning a template to a [route](routing.md#dynamic-routes)
* Within [include](https://twig.symfony.com/doc/tags/include.html), [extends](https://twig.symfony.com/doc/tags/extends.html), and [embed](https://twig.symfony.com/doc/tags/embed.html) template tags

Craft has a standard template path format that applies to each of these cases: a Unix-style file system path to the template file, relative from your craft/templates folder.

If you have a template located at craft/templates/foo/bar.html, the following template paths would point to it:

* `foo/bar`
* `foo/bar.html`

### Index Templates

If you name your template “index.html”, you don’t need to specify it in the template path.

For example, if you have a template located at craft/templates/foo/bar/index.html, the following template paths would point to it:

* `foo/bar`
* `foo/bar/index`
* `foo/bar/index.html`

::: tip
If you have templates located at both craft/templates/foo/bar.html *and* craft/templates/foo/bar/index.html, the template path “foo/bar” will match bar.html.
:::

## Template Localization

If you’re running a localized website with Craft Pro, you can create locale-specific subfolders in your `craft/templates/` folder, which contain templates that will only be available to a specific locale. For example, if you want to create a special template welcoming your German customers, but there’s no need for it on your English site, then you could save it in `craft/templates/de/welcome.html`. That template would be available from `http://example.de/welcome`.

Craft will look for localized templates _before_ it looks for templates in the normal location, so they can also be used to override non-localized templates. See our [Localization Guide](localization-guide.md) for more details.

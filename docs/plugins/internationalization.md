# Internationalization

Craft makes it extremely easy to define translatable strings. From PHP, just wrap the string in `Craft::t()`, and from the templates, just run the string through the `|t` filter. Craft will take the string and check if it has been translated into the user’s preferred language.

If your string has a dynamic portion to it, such as “Ingredients in a {cocktail}”, where “{cocktail}” should be replaced by the name of the cocktail you’re currently viewing, just pass a second parameter to `Craft::t`:

```php
<?php
namespace Craft;

$str = Craft::t('Ingredients in a {cocktail}', array(
    'cocktail' => $cocktail->name
));
```

You can do the same thing within your templates with the `|t` filter:

```twig
{{ "Ingredients in a {cocktail}"|t({
    cocktail: cocktail.name
}) }}
```

Once your plugin’s strings have been made translatable, users will be able to supply the translations by creating files within their craft/translations folder. See [Translating Static Text](https://craftcms.com/support/static-translations) for the details.
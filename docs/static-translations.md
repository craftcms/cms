# Static Message Translations

Most websites and apps will have some UI messages that are hard-coded into the templates or PHP files. These are called “static messages”, because they aren’t being dynamically defined by content in the CMS.

If you’re building a multilingual site or app, then these messages will need to be translatable just like your CMS-driven content.

To do that, Craft employs Yii’s [Message Translations](https://www.yiiframework.com/doc/guide/2.0/en/tutorial-i18n#message-translation) feature, and pre-defines a special translation category, `site`, for front-end messages. 

## Prep Your Messages

The first step is to run all of your static messages through the translator. If you’re working on a template, use the [translate](dev/filters.md#translate-or-t) filter (`|t`). If you’re working in PHP code, use [Craft::t()](api:yii\BaseYii::t()).

::: code
```twig
{# old #}
<a href="/contact">Contact us</a>

{# new #}
<a href="/contact">{{ 'Contact us'|t }}</a>
```
```php
// old
$label = 'Contact us';

// new
$label = Craft::t('site', 'Contact us');
```
:::

## Provide the Translations

Once you’ve prepped a message for translations, you need to supply the actual translation.

To do that, create a new folder in your project’s base directory called `translations/`, and within that, create a new folder named after the target language’s ID. Within that, create a file called `site.php`.

For example, if you want to translate your site into German, this is what your project’s directory structure should look like:

```
my-project.test/
├── config/
├── ...
└── translations/
    └── de/
        └── site.php
```

Now open `site.php` in a text editor, and have it return an array that maps the source messages to their translated messages.

```php
<?php

return [
    'Contact us' => 'Kontaktiere uns',
];
``` 

Now, when Craft is processing the message translation for a German site, “Contact us” will be replaced with  “Kontaktiere uns”.

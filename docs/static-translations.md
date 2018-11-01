# Static Message Translations

Craft Pro’s localization features are great for translating your dynamic content. But a lot of the copy on your site probably doesn't come from custom fields. Sometimes it comes from a non-translatable field in the CP (such as the Site Name), and other times it’s hard-coded right into the templates. So how do you translate _that stuff?_

You can translate all of that static copy using **translation files**.

Translation files are PHP files that live in `craft/translations/` (a folder you must create yourself). They simply return an array which maps the source language’s strings to the target language.

For example, if you have a section called “News” in your English site, and you want it to be called “Noticias” in your Spanish site, create a file in `craft/translations/` called `es.php`:
 
```
craft/
├── config/
├── ...
└── translations/
    └── es.php
```

Now open `es.php` in a text editor, and add this:

```php
<?php

return array(
    'News' => 'Noticias',
);
```

Add a new line to that array with anything else you wish to translate.

Once your translations are in place, you have to update your template to utilize them.

In this case you would perhaps open up your news/index.html template, and find the News heading:

```twig
<h1>News</h1>
```

Replace the static text with a Twig tag that passes the text through Craft’s [translate](templating/filters.md#translate-or-t) filter (`|t`):

```twig
<h1>{{ "News"|t }}</h1>
```

The next time you view that template in Spanish, you will find that rather than “News”, the heading will read “Noticias”. Just like that!

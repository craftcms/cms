# Globals

Globals store content that is available globally throughout your templates, as the name suggests.

You can create multiple sets of global content, called Global Sets, from Settings → Globals. Each Global Set has its own [field layout](fields.md#field-layouts).

If you have at least one Global Set, the CP will get a new “Globals” item added to its primary nav, which will take you to a page that lists all of your Global Sets in a sidebar, as well as all of the fields associated with the selected Global Set in the main content area.

Unlike [entries](sections-and-entries.md#entries), Global Sets don’t have the Live Preview feature, since they aren’t associated with any one particular URL.

## Templating

You can access your Global Sets from any template via their handles. For example, if you have a Global Set with the handle “companyInfo”, and it has a field with the handle “yearEstablished”, you can access that field anywhere using this code:

```twig
{{ companyInfo.yearEstablished }}
```

There are a few additional Global Set properties you can grab besides your custom fields. See [GlobalSetModel](templating/globalsetmodel.md) for a full reference.
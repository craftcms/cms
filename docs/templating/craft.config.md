# `craft.config`

You can access your config settings from your templates with `craft.config`.

## Properties

You can access any of your config settings in `craft/config/general.php` by treating them as properties of `craft.config`:

```twig
{% if craft.config.devMode %}
    <p>Craft is running in Dev Mode.</p>
{% endif %}
```

## Methods

The following methods are available:

### `get( name, file )`

If you want to access config values from any config file besides `general.php`, you can use `get()`:

```twig
{{ craft.config.get('someConfigSetting', 'someConfigFile') }}
```

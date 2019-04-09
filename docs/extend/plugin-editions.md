# Plugin Editions

The Plugin Store will soon add **limited** support for multi-edition plugins, which will work similarly to Craft’s two editions (Solo and Pro).

- Plugins that support mulitple editions are still comprised of a single Composer package.
- Plugins’ active edition is recorded in the [project config](../project-config.md).
- Plugins can implement feature toggles by checking their active edition.

::: warning
Not every plugin can or should support editions. [Contact](https://craftcms.com/contact) Pixel & Tonic before you begin adding edition support to make sure it will be allowed for your plugin.
:::

## Define the Editions

To add edition support to a plugin, begin by defining the available editions (in ascending order), by overriding <api:craft\base\Plugin::editions()>.

```php
class Plugin extends \craft\base\Plugin;
{
    const EDITION_LITE = 'lite';
    const EDITION_PRO = 'pro';

    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    // ...
}
```

## Add Feature Toggles

Your feature toggles can call your plugin’s [is()](api:craft\base\Plugin::is()) method.

::: code

```php
if (Plugin::getInstance()->is(Plugin::EDITION_PRO) {
    // Pro edition-only code goes here...
}
```

```twig
{% if plugin('plugin-handle').is('pro') %}
    {# Pro edition-only code goes here... #}
{% endif %}
```

:::

`is()` accepts two arguments, `$edition` and `$operator`.

`$edition` is the name of the edition you’re concerned with.

`$operator` is how you wish to compare that edition with the installed edition. By default it is set to `=`, which tests for version equality.

The following operators are also supported:

Operator | Tests if the active edition is ____ the given edition
- | -
`<` or `lt` | …less than…
`<=` or `le` | …less than or equal to…
`>` or `gt` | …greater than…
`>=` or `ge` | …greater than or equal to…
`==` or `eq` | …equal to… (same as default behavior)
`!=`, `<>`, or `ne` | …not equal to…

::: tip
Changing editions should always be a lossless operation; no plugin data should change as a result of the edition change. Editions can change back and forth at any time, and plugins should have no problem rolling with it.
:::

## Testing

You can toggle the active edition by changing the `plugins.<plugin-handle>.edition` property in `config/project.yaml`.

::: tip
If you don’t have a `config/project.yaml` file, you need to enable the <config:useProjectConfigFile> config setting.
:::

After changing the value to a valid edition handle (one returned by your plugin’s `editions()` method), Craft will prompt you to sync your `project.yaml` changes into the loaded project config. Once that’s done, your plugin’s active edition will be set to the new edition, and feature toggles should start behaving accordingly. 

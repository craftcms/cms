# Updating Plugins for Craft 3

Craft 3 is a complete rewrite of the CMS, built on Yii 2. Due to the scope of changes in Yii 2, there was no feasible way to port Craft to it without breaking every plugin in the process. So we took it as an [opportunity](https://www.urbandictionary.com/define.php?term=double%20transgression%20theory) to refactor several major areas of the system.

The primary goals of the refactoring were:

- Establish new [coding guidelines and best practices](coding-guidelines.md), optimizing for performance, clarity, and maintainability.
- Identify areas where Craft was needlessly reinventing the wheel, and stop doing that.
- Support modern development toolkits (Composer, PostgreSQL, etc.).

The end result is a faster, leaner, and much more elegant codebase for core development and plugin development alike. We hope you enjoy it.

::: tip
If you think something is missing, please [create an issue](https://github.com/craftcms/docs/issues/new).
:::

[[toc]]

## High Level Notes

- Craft is now built on Yii 2.
- The main application instance is available via `Craft::$app` now, rather than `craft()`.
- Plugins must now have a `composer.json` file that defines some basic info about the plugin.
- Plugins now get their own root namespace, rather than sharing a `Craft\` namespace with all of Craft and other plugins, and all Craft and plugin code must follow the [PSR-4](https://www.php-fig.org/psr/psr-4/) specification.
- Plugins are now an extension of [Yii modules](https://www.yiiframework.com/doc/guide/2.0/en/structure-modules).

## Changelogs

Craft 3 plugins should include a changelog named `CHANGELOG.md`, rather than a `releases.json` file (see [Changelogs and Updates](changelogs-and-updates.md)).

If you have an existing `releases.json` file, you can quickly convert it to a changelog using the following command in your terminal:

```bash
# go to the plugin directory
cd /path/to/my-plugin

# create a CHANGELOG.md from its releases.json 
curl https://api.craftcms.com/v1/utils/releases-2-changelog --data-binary @releases.json > CHANGELOG.md
``` 

## Yii 2

Yii, the framework Craft is built on, was completely rewritten for 2.0. See its comprehensive [upgrade guide](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1) to learn about how things have changed under the hood.

Relevant sections:

- [Namespace](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#namespace)
- [Component and Object](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#component-and-object)
- [Object Configuration](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#object-configuration)
- [Events](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#events)
- [Path Aliases](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#path-aliases)
- [Models](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#models)
- [Controllers](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#controllers)
- [Console Applications](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#console-applications)
- [I18N](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#i18n)
- [Assets](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#assets)
- [Helpers](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#helpers)
- [Query Builder](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#query-builder)
- [Active Record](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#active-record)
- [Active Record Behaviors](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#active-record-behaviors)
- [User and IdentityInterface](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#user-and-identityinterface)

## Service Names

The following core service names have changed:

| Old             | New
| --------------- | ----------------
| `assetSources`  | `volumes`
| `email`         | `mailer`
| `templateCache` | `templateCaches`
| `templates`     | `view`
| `userSession`   | `user`

## Components 

Component classes (element types, field types, widget types, etc.) follow a new design pattern in Craft 3.

In Craft 2, each component was represented by two classes: a Model (e.g. `FieldModel`) and a Type (e.g. `PlainTextFieldType`). The Model was the main representation of the component, and defined the common properties that were always going to be there, regardless of the component’s type (e.g. `id`, `name`, and `handle`); whereas the Type was responsible for defining the things that made the particular component type unique (e.g. its input UI).

In Craft 3, component types no longer act as separate, peripheral classes to the Model; they now are one and the same class as the model.

Here’s how it works:

- Any required component methods such as `getInputHtml()` are defined by an interface (e.g. <api:craft\base\FieldInterface>).
- Common properties such as `$handle` are defined by a trait (e.g. <api:craft\base\FieldTrait>).
- A base implementation of the component type is provided by an abstract base class (e.g. <api:craft\base\Field>).
- The base class is extended by the various component classes (e.g. <api:craft\fields\PlainText>).



## Translations

<api:Craft::t()> requires a `$category` argument now, which should be set to one of these translation categories:

- `yii` for Yii translation messages
- `app` for Craft translation messages
- `site` for front-end translation messages
- a plugin handle for plugin-specific translation messages

```php
\Craft::t('app', 'Entries')
```

In addition to front-end translation messages, the `site` category should be used for admin-defined labels in the Control Panel:

```php
\Craft::t('app', 'Post a new {section} entry', [
    'section' => \Craft::t('site', $section->name)
])
```

To keep front-end Twig code looking clean, the `|t` and `|translate` filters don’t require that you specify the category, and will default to `site`. So these two tags will give you the same output:

```twig
{{ "News"|t }}
{{ "News"|t('site') }}
```

## DB Queries

### Table Names

Craft no longer auto-prepends the DB table prefix to table names, so you must write table names in Yii’s `{{%tablename}}` syntax.

### Select Queries

Select queries are defined by <api:craft\db\Query> classes now.

```php
use craft\db\Query;

$results = (new Query())
    ->select(['column1', 'column2'])
    ->from(['{{%tablename}}'])
    ->where(['foo' => 'bar'])
    ->all();
```

### Operational Queries

Operational queries can be built from the helper methods on <api:craft\db\Command> (accessed via `Craft::$app->db->createCommand()`), much like the [`DbCommand`](https://docs.craftcms.com/api/v2/craft-dbcommand.html) class in Craft 2.

One notable difference is that the helper methods no longer automatically execute the query, so you must chain a call to `execute()`.

```php
$result = \Craft::$app->db->createCommand()
    ->insert('{{%tablename}}', $rowData)
    ->execute();
```

## Element Queries

`ElementCriteriaModel` has been replaced with [Element Queries](../dev/element-queries/README.md) in Craft 3:

```php
// Old:
$criteria = craft()->elements->getCriteria(ElementType::Entry);
$criteria->section = 'news';
$entries = $criteria->find();

// New:
use craft\elements\Entry;

$entries = Entry::find()
    ->section('news')
    ->all(); 
```

## Craft Config Settings

All of Craft’s config settings have been moved to actual properties on a few config classes, located in `vendor/craftcms/cms/src/config/`. The new Config service (<api:craft\services\Config>) provides getter methods/properties that will return those classes:

```php
// Old:
$devMode = craft()->config->get('devMode');
$tablePrefix = craft()->config->get('tablePrefix', ConfigFile::Db);

// New:
$devMode = Craft::$app->config->general->devMode;
$tablePrefix = Craft::$app->config->db->tablePrefix;
```

## Files

- `IOHelper` has been replaced with <api:craft\helpers\FileHelper>, which extends Yii’s <api:yii\helpers\BaseFileHelper>.
- Directory paths returned by <api:craft\helpers\FileHelper> and <api:craft\services\Path> methods no longer include a trailing slash.
- File system paths in Craft now use the `DIRECTORY_SEPARATOR` PHP constant (which is set to either `/` or `\` depending on the environment) rather than hard-coded forward slashes (`/`).

## Events

The traditional way of registering event handlers in Craft 2/Yii 1 was:

```php
$component->onEventName = $callback;
```

This would directly register the event listener on the component.

In Craft 3/Yii 2, use <api:yii\base\Component::on()> instead:

```php
$component->on('eventName', $callback);
```

Craft 2 also provided a `craft()->on()` method, which could be used to register event handlers on a service:

```php
craft()->on('elements.beforeSaveElement', $callback);
```

There is no direct equivalent in Craft 3, but generally event handlers that used `craft()->on()` in Craft 2 should use [class-level event handlers](https://www.yiiframework.com/doc/guide/2.0/en/concept-events#class-level-event-handlers) in Craft 3.

```php
use craft\services\Elements;
use yii\base\Event;

Event::on(Elements::class, Elements::EVENT_BEFORE_SAVE_ELEMENT, $callback);
```

In addition to services, you can use class-level event handlers for components that may not be initialized yet, or where tracking down a reference to them is not straightforward.

For example, if you want to be notified every time a Matrix field is saved, you could do this:

```php
use craft\events\ModelEvent;
use craft\fields\Matrix;
use yii\base\Event;

Event::on(Matrix::class, Matrix::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    // ...
});
```

## Plugin Hooks

The concept of “plugin hooks” has been removed in Craft 3. Here’s a list of the previously-supported hooks and how you should accomplish the same things in Craft 3:

### General Hooks

#### `addRichTextLinkOptions`

```php
// Old:
public function addRichTextLinkOptions()
{
    return [
        [
            'optionTitle' => Craft::t('Link to a product'),
            'elementType' => 'Commerce_Product',
        ],
    ];
}

// New:
use craft\events\RegisterRichTextLinkOptionsEvent;
use craft\fields\RichText;
use yii\base\Event;

Event::on(RichText::class, RichText::EVENT_REGISTER_LINK_OPTIONS, function(RegisterRichTextLinkOptionsEvent $event) {
    $event->linkOptions[] = [
        'optionTitle' => \Craft::t('plugin-handle', 'Link to a product'),
        'elementType' => Product::class,
    ];
});
```

#### `addTwigExtension`

```php
// Old:
public function addTwigExtension()
{
    Craft::import('plugins.cocktailrecipes.twigextensions.MyExtension');
    return new MyExtension();
}

// New:
\Craft::$app->view->registerTwigExtension($extension);
```

#### `addUserAdministrationOptions`

```php
// Old:
public function addUserAdministrationOptions(UserModel $user)
{
    if (!$user->isCurrent()) {
        return [
            [
                'label'  => Craft::t('Send Bacon'),
                'action' => 'baconater/sendBacon'
            ],
        ];
    }
}

// New:
use craft\controllers\UsersController;
use craft\events\RegisterUserActionsEvent;
use yii\base\Event;

Event::on(UsersController::class, UsersController::EVENT_REGISTER_USER_ACTIONS, function(RegisterUserActionsEvent $event) {
    if ($event->user->isCurrent) {
        $event->miscActions[] = [
            'label' => \Craft::t('plugin-handle', 'Send Bacon'),
            'action' => 'baconater/send-bacon'
        ];
    }
});
```

#### `getResourcePath`

```php
// Old:
public function getResourcePath($path)
{
    if (strpos($path, 'myplugin/') === 0) {
        return craft()->path->getStoragePath().'myplugin/'.substr($path, 9);
    }
}
```

::: warning NOTE
There is no direct Craft 3 equivalent for this hook, which allowed plugins to handle resource requests, because the concept of resource requests has been removed in Craft 3. See [Asset Bundles](asset-bundles.md) to learn how plugins can serve resources in Craft 3.  
:::

#### `modifyCpNav`

```php
// Old:
public function modifyCpNav(&$nav)
{
    if (craft()->userSession->isAdmin()) {
        $nav['foo'] = [
            'label' => Craft::t('Foo'),
            'url' => 'foo'
        ];
    }
}

// New:
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;
use yii\base\Event;

Event::on(Cp::class, Cp::EVENT_REGISTER_CP_NAV_ITEMS, function(RegisterCpNavItemsEvent $event) {
    if (\Craft::$app->user->identity->admin) {
        $event->navItems['foo'] = [
            'label' => \Craft::t('plugin-handle', 'Utils'),
            'url' => 'utils'
        ];
    }
});
```

#### `registerCachePaths`

```php
// Old:
public function registerCachePaths()
{
    return [
        craft()->path->getStoragePath().'drinks/' => Craft::t('Drink images'),
    ];
}

// New:
use craft\events\RegisterCacheOptionsEvent;
use craft\utilities\ClearCaches;
use yii\base\Event;

Event::on(ClearCaches::class, ClearCaches::EVENT_REGISTER_CACHE_OPTIONS, function(RegisterCacheOptionsEvent $event) {
    $event->options[] = [
        'key' => 'drink-images',
        'label' => \Craft::t('plugin-handle', 'Drink images'),
        'action' => \Craft::$app->path->getStoragePath().'/drinks'
    ];
});
```

#### `registerEmailMessages`

```php
// Old:
public function registerEmailMessages()
{
    return ['my_message_key'];
}

// New:
use craft\events\RegisterEmailMessagesEvent;
use craft\services\SystemMessages;
use yii\base\Event;

Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, function(RegisterEmailMessagesEvent $event) {
    $event->messages[] = [
        'key' => 'my_message_key',
        'heading' => Craft::t('plugin-handle', 'Email Heading'),
        'subject' => Craft::t('plugin-handle', 'Email Subject'),
        'body' => Craft::t('plugin-handle', 'The plain text email body...'),
    ];
});
```

::: tip
Rather than defining the full message heading/subject/body right within the <api:Craft::t()> call, you can pass placeholder strings (e.g. `'email_heading'`) and define the actual string in your plugin’s translation file.
:::

#### `registerUserPermissions`

```php
// Old:
public function registerUserPermissions()
{
    return [
        'drinkAlcohol' => ['label' => Craft::t('Drink alcohol')],
        'stayUpLate' => ['label' => Craft::t('Stay up late')],
    ];
}

// New:
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use yii\base\Event;

Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
    $event->permissions[\Craft::t('plugin-handle', 'Vices')] = [
        'drinkAlcohol' => ['label' => \Craft::t('plugin-handle', 'Drink alcohol')],
        'stayUpLate' => ['label' => \Craft::t('plugin-handle', 'Stay up late')],
    ];
});
```

#### `getCpAlerts`

```php
// Old:
public function getCpAlerts($path, $fetch)
{
    if (craft()->config->devMode) {
        return [Craft::t('Dev Mode is enabled!')];
    }
}

// New:
use craft\events\RegisterCpAlertsEvent;
use craft\helpers\Cp;
use yii\base\Event;

Event::on(Cp::class, Cp::EVENT_REGISTER_ALERTS, function(RegisterCpAlertsEvent $event) {
    if (\Craft::$app->config->general->devMode) {
        $event->alerts[] = \Craft::t('plugin-handle', 'Dev Mode is enabled!');
    }
});
```

#### `modifyAssetFilename`

```php
// Old:
public function modifyAssetFilename($filename)
{
    return 'KittensRule-'.$filename;
}

// New:
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\helpers\Assets;
use yii\base\Event;

Event::on(Assets::class, Assets::EVENT_SET_FILENAME, function(SetElementTableAttributeHtmlEvent $event) {
    $event->filename = 'KittensRule-'.$event->filename;

    // Prevent other event listeners from getting invoked
    $event->handled = true;
});
```

### Routing Hooks

#### `registerCpRoutes`

```php
// Old:
public function registerCpRoutes()
{
    return [
        'cocktails/new' => 'cocktails/_edit',
        'cocktails/(?P<widgetId>\d+)' => ['action' => 'cocktails/editCocktail'],
    ];
}

// New:
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
    $event->rules['cocktails/new'] = ['template' => 'cocktails/_edit'];
    $event->rules['cocktails/<widgetId:\d+>'] = 'cocktails/edit-cocktail';
});
```

#### `registerSiteRoutes`

```php
// Old:
public function registerSiteRoutes()
{
    return [
        'cocktails/new' => 'cocktails/_edit',
        'cocktails/(?P<widgetId>\d+)' => ['action' => 'cocktails/editCocktail'],
    ];
}

// New:
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
    $event->rules['cocktails/new'] = ['template' => 'cocktails/_edit'];
    $event->rules['cocktails/<widgetId:\d+>'] = 'cocktails/edit-cocktail';
});
```

#### `getElementRoute`

```php
// Old:
public function getElementRoute(BaseElementModel $element)
{
    if (
        $element->getElementType() === ElementType::Entry &&
        $element->getSection()->handle === 'products'
    ) {
        return ['action' => 'products/viewEntry'];
    }
}

// New:
use craft\base\Element;
use craft\elements\Entry;
use craft\events\SetElementRouteEvent;
use yii\base\Event;

Event::on(Entry::class, Element::EVENT_SET_ROUTE, function(SetElementRouteEvent $event) {
    /** @var Entry $entry */
    $entry = $event->sender;

    if ($entry->section->handle === 'products') {
        $event->route = 'products/view-entry';

        // Prevent other event listeners from getting invoked
        $event->handled = true;
    }
});
```

### Element Hooks

The following sets of hooks have been combined into single events that are shared across all element types.

For each of these, you could either pass <api:craft\base\Element::class> to the first argument of `yii\base\Event::on()` (registering the event listener for *all* element types), or a specific element type class (registering the event listener for just that one element type).

#### `addEntryActions`, `addCategoryActions`, `addAssetActions`, & `addUserActions`

```php
// Old:
public function addEntryActions($source)
{
    return [new MyElementAction()];
}

// New:
use craft\elements\Entry;
use craft\events\RegisterElementActionsEvent;
use yii\base\Event;

Event::on(Entry::class, Element::EVENT_REGISTER_ACTIONS, function(RegisterElementActionsEvent $event) {
    $event->actions[] = new MyElementAction();
});
```

#### `modifyEntrySortableAttributes`, `modifyCategorySortableAttributes`, `modifyAssetSortableAttributes`, & `modifyUserSortableAttributes`

```php
// Old:
public function modifyEntrySortableAttributes(&$attributes)
{
    $attributes['id'] = Craft::t('ID');
}

// New:
use craft\base\Element;
use craft\elements\Entry;
use craft\events\RegisterElementSortOptionsEvent;
use yii\base\Event;

Event::on(Entry::class, Element::EVENT_REGISTER_SORT_OPTIONS, function(RegisterElementSortOptionsEvent $event) {
    $event->sortOptions['id'] = \Craft::t('app', 'ID');
});
```

#### `modifyEntrySources`, `modifyCategorySources`, `modifyAssetSources`, & `modifyUserSources`

```php
// Old:
public function modifyEntrySources(&$sources, $context)
{
    if ($context == 'index') {
        $sources[] = [
            'heading' => Craft::t('Statuses'),
        ];

        $statuses = craft()->elements->getElementType(ElementType::Entry)
            ->getStatuses();
        foreach ($statuses as $status => $label) {
            $sources['status:'.$status] = [
                'label' => $label,
                'criteria' => ['status' => $status]
            ];
        }
    }
}

// New:
use craft\base\Element;
use craft\elements\Entry;
use craft\events\RegisterElementSourcesEvent;
use yii\base\Event;

Event::on(Entry::class, Element::EVENT_REGISTER_SOURCES, function(RegisterElementSourcesEvent $event) {
    if ($event->context === 'index') {
        $event->sources[] = [
            'heading' => \Craft::t('plugin-handle', 'Statuses'),
        ];

        foreach (Entry::statuses() as $status => $label) {
            $event->sources[] = [
                'key' => 'status:'.$status,
                'label' => $label,
                'criteria' => ['status' => $status]
            ];
        }
    }
});
```

#### `defineAdditionalEntryTableAttributes`, `defineAdditionalCategoryTableAttributes`, `defineAdditionalAssetTableAttributes`, & `defineAdditionalUserTableAttributes`

```php
// Old:
public function defineAdditionalEntryTableAttributes()
{
    return [
        'foo' => Craft::t('Foo'),
        'bar' => Craft::t('Bar'),
    ];
}

// New:
use craft\elements\Entry;
use craft\events\RegisterElementTableAttributesEvent;
use yii\base\Event;

Event::on(Entry::class, Element::EVENT_REGISTER_TABLE_ATTRIBUTES, function(RegisterElementTableAttributesEvent $event) {
    $event->tableAttributes['foo'] = ['label' => \Craft::t('plugin-handle', 'Foo')];
    $event->tableAttributes['bar'] = ['label' => \Craft::t('plugin-handle', 'Bar')];
});
```

#### `getEntryTableAttributeHtml`, `getCategoryTableAttributeHtml`, `getAssetTableAttributeHtml`, & `getUserTableAttributeHtml`

```php
// Old:
public function getEntryTableAttributeHtml(EntryModel $entry, $attribute)
{
    if ($attribute === 'price') {
        return '$'.$entry->price;
    }
}

// New:
use craft\base\Element;
use craft\elements\Entry;
use craft\events\SetElementTableAttributeHtmlEvent;
use yii\base\Event;

Event::on(Entry::class, Element::EVENT_SET_TABLE_ATTRIBUTE_HTML, function(SetElementTableAttributeHtmlEvent $event) {
    if ($event->attribute === 'price') {
        /** @var Entry $entry */
        $entry = $event->sender;

        $event->html = '$'.$entry->price;

        // Prevent other event listeners from getting invoked
        $event->handled = true;
    }
});
```

#### `getTableAttributesForSource`

```php
// Old:
public function getTableAttributesForSource($elementType, $sourceKey)
{
    if ($sourceKey == 'foo') {
        return craft()->elementIndexes->getTableAttributes($elementType, 'bar');
    }
}
```

::: warning NOTE
There is no direct Craft 3 equivalent for this hook, which allowed plugins to completely change the table attributes for an element type right before the element index view was rendered. The closest thing in Craft 3 is the <api:craft\base\Element::EVENT_REGISTER_TABLE_ATTRIBUTES> event, which can be used to change the available table attributes for an element type when an admin is customizing the element index sources.
:::

## Template Variables

Template variables are no longer a thing in Craft 3, however plugins can still register custom services on the global `craft` variable by listening to its `init` event:

```php
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;

Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
    /** @var CraftVariable $variable */
    $variable = $event->sender;
    $variable->set('componentName', YourVariableClass::class);
});
```

(Replace `componentName` with whatever you want your variable’s name to be off of the `craft` object. For backwards-compatibility, you might want to go with your old `camelCased` plugin handle.)

## Rendering Templates

The TemplatesService has been replaced with a View component.

```php
// Old:
craft()->templates->render('pluginHandle/path/to/template', $variables);

// New:
\Craft::$app->view->renderTemplate('plugin-handle/path/to/template', $variables);
```

### Controller Action Templates

Controllers’ `renderTemplate()` method hasn’t changed much. The only difference is that it used to output the template and end the request for you, whereas now it returns the rendered template, which your controller action should return.

```php
// Old:
$this->renderTemplate('pluginHandle/path/to/template', $variables);

// New:
return $this->renderTemplate('plugin-handle/path/to/template', $variables);
```

### Rendering Plugin Templates on Front End Requests

If you want to render a plugin-supplied template on a front-end request, you need to set the View component to the CP’s template mode:

```php
// Old:
$oldPath = craft()->templates->getTemplatesPath();
$newPath = craft()->path->getPluginsPath().'pluginhandle/templates/';
craft()->templates->setTemplatesPath($newPath);
$html = craft()->templates->render('path/to/template');
craft()->templates->setTemplatesPath($oldPath);

// New:
use craft\web\View;

$oldMode = \Craft::$app->view->getTemplateMode();
\Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);
$html = \Craft::$app->view->renderTemplate('plugin-handle/path/to/template');
\Craft::$app->view->setTemplateMode($oldMode);
```

## Control Panel Templates

If your plugin has any templates that extend Craft’s `_layouts/cp.html` Control Panel layout template, there are a few things that might need to be updated.

### `extraPageHeaderHtml`

Support for the `extraPageHeaderHtml` variable has been removed. To create a primary action button in the page header, use the new `actionButton` block.

```twig
{# Old: #}
{% set extraPageHeaderHtml %}
    <a href="{{ url('recipes/new') }}" class="btn submit">{{ 'New recipe'|t('app') }}</a>
{% endset %}

{# New: #}
{% block actionButton %}
    <a href="{{ url('recipes/new') }}" class="btn submit">{{ 'New recipe'|t('app') }}</a>
{% endblock %}
```

### Full-Page Grids

If you had a template that overrode the `main` block, and defined a full-page grid inside it, you should divide the grid items’ contents into the new `content` and `details` blocks.

Additionally, any `<div class="pane">`s you had should generally lose their `pane` classes.

```twig
{# Old: #}
{% block main %}
    <div class="grid first" data-max-cols="3">
        <div class="item" data-position="left" data-colspan="2">
            <div id="recipe-fields" class="pane">
                <!-- Primary Content -->
            </div>
        </div>
        <div class="item" data-position="right">
            <div class="pane meta">
                <!-- Secondary Content -->
            </div>
        </div>
    </div>
{% endblock %}

{# New: #}
{% block content %}
    <div id="recipe-fields">
        <!-- Primary Content -->
    </div>
{% endblock %}

{% block details %}
    <div class="meta">
        <!-- Secondary Content -->
    </div>
{% endblock %}
```

### Control Panel Template Hooks

The following Control Panel [template hooks](template-hooks.md) have been renamed:

| Old                              | New
| -------------------------------- | ----------------------------
| `cp.categories.edit.right-pane`  | `cp.categories.edit.details`
| `cp.entries.edit.right-pane`     | `cp.entries.edit.details`
| `cp.users.edit.right-pane`       | `cp.users.edit.details`

## Resource Requests

Craft 3 doesn’t have the concept of resource requests. See [Asset Bundles](asset-bundles.md) for information about working with front end resources.

## Registering Arbitrary HTML

If you need to include arbitrary HTML somewhere on the page, use the `beginBody` or `endBody` events on the View component:

```php
// Old:
craft()->templates->includeFootHtml($html);

// New:
use craft\web\View;
use yii\base\Event;

Event::on(View::class, View::EVENT_END_BODY, function(Event $event) {
    // $html = ...
    echo $html;
});
```

## Background Tasks

Craft’s Tasks service has been replaced with a job queue, powered by the [Yii 2 Queue Extension](https://github.com/yiisoft/yii2-queue).

If your plugin provides any custom task types, they will need to be converted to jobs:

```php
// Old:
class MyTask extends BaseTask
{
    public function getDescription()
    {
        return 'Default description';
    }
    
    public function getTotalSteps()
    {
        return 5;
    }
    
    public function runStep($step)
    {
        // do something...
        return true;
    }
}

// New:
use craft\queue\BaseJob;

class MyJob extends BaseJob
{
    public function execute($queue)
    {
        $totalSteps = 5;
        for ($step = 0; $step < $steps; $step++)
        {
            $this->setProgress($queue, $step / $totalSteps); 
            // do something...
        } 
    }
    
    protected function defaultDescription()
    {
        return 'Default description';
    }
}
```

Adding jobs to the queue is a little different as well:

```php
// Old:
craft()->tasks->createTask('MyTask', 'Custom description', array(
    'mySetting' => 'value', 
));

// New:
Craft::$app->queue->push(new MyJob([
    'description' => 'Custom description',
    'mySetting' => 'value',
]));
```

## Writing an Upgrade Migration

You may need to give your plugin a migration path for Craft 2 installations, so they don’t get stranded.

First you must determine whether Craft is going to consider your plugin to be an **update** or a **new installation**. If your plugin handle hasn’t changed (besides going from `UpperCamelCase` to `kebab-case`), Craft will see your new version as an **update**. But if your handle did change in a more significant way, Craft isn’t going to recognize it, and will consider it a completely new plugin.


If the handle (basically) stayed the same, create a new [migration](migrations.md) named something like “`craft3_upgrade`”. Your upgrade code will go in its `safeUp()` method just like any other migration.

If the handle has changed, you’ll need to put your upgrade code in your [Install migration](migrations.md#plugin-install-migrations) instead. Use this as a starting point:

```php
<?php
namespace ns\prefix\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp()
    {
        if ($this->_upgradeFromCraft2()) {
            return;
        }
        
        // Fresh install code goes here...
    }

    private function _upgradeFromCraft2()
    {
        // Fetch the old plugin row, if it was installed
        $row = (new \craft\db\Query())
            ->select(['id', 'settings'])
            ->from(['{{%plugins}}'])
            ->where(['in', 'handle', ['old-handle', 'oldhandle']])
            ->one();
        
        if (!$row) {
            return false;
        }

        // Update this one's settings to old values
        $this->update('{{%plugins}}', [
            'settings' => $row['settings']
        ], ['handle' => 'new-handle']);

        // Delete the old row
        $this->delete('{{%plugins}}', ['id' => $row['id']]);

        // Any additional upgrade code goes here...

        return true;
    }

    public function safeDown()
    {
        // ...
    }
}
```

Replace `old-handle` and `oldhandle` with your plugin’s previous handle (in `kebab-case` and `onewordalllowercase`), and put any additional upgrade code at the end of the `_upgradeFromCraft2()` method (before the `return` statement). Your normal install migration code (for fresh installations of your plugin) should go at the end of `safeUp()`.

### Component Class Names

If your plugin provides any custom element types, field types, or widget types, you will need to update the `type` column in the appropriate tables to match their new class names.

#### Elements

```php
$this->update('{{%elements}}', [
    'type' => MyElement::class
], ['type' => 'OldPlugin_ElementType']);
```

#### Fields

```php
$this->update('{{%fields}}', [
    'type' => MyField::class
], ['type' => 'OldPlugin_FieldType']);
```

#### Widgets

```php
$this->update('{{%widgets}}', [
    'type' => MyWidget::class
], ['type' => 'OldPlugin_WidgetType']);
```

### Locale FKs

If your plugin created any custom foreign keys to the `locales` table in Craft 2, the Craft 3 upgrade will have automatically added new columns alongside them, with foreign keys to the `sites` table instead, as the `locales` table is no longer with us.

The data should be good to go, but you will probably want to drop the old column, and rename the new one Craft created for you.

```php
// Drop the old locale FK column
$this->dropColumn('{{%tablename}}', 'oldName');

// Rename the new siteId FK column
MigrationHelper::renameColumn('{{%tablename}}', 'oldName__siteId', 'newName', $this);
```

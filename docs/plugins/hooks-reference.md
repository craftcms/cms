# Hooks Reference

Craft provides several hooks that give plugins the opportunity to get involved in various areas of the system.

::: tip
See [hooks-and-events](hooks-and-events.md) for an explanation of how hooks work in Craft, and how they differ from events.
:::

## General Hooks

### `addRichTextLinkOptions`

Called by

:   <api:Craft\RichTextFieldType::getInputHtml()>

Return

:   An array of additional options that should be available to Rich Text fields’ Link menus.

Gives plugins a chance to add additional options to Rich Text fields’ Link menus.

```php
public function addRichTextLinkOptions()
{
    return array(
        array(
            'optionTitle' => Craft::t('Link to a product'),
            'elementType' => 'Commerce_Product',
        ),
    );
}
```

Each sub-array can contain the following keys:

* **`'optionTitle'`** _(required)_ – The user-facing option label
* **`'elementType'`** _(required)_ – The element type class that the option represents
* **`'sources'`** _(optional)_ – An array of source keys (matching the keys returned by the element type’s <api:Craft\getSources()> method) which the element selector modal should be restricted to
* **`'criteria'`** _(optional)_ – Any element criteria params that should be applied to filter which elements can be selected
* **`'storageKey'`** _(optional)_ – The localStorage key that should be used to store the element selector modal state (defaults to RichTextFieldType.LinkTo[ElementType])

### `addTwigExtension`

Called by

:   <api:Craft\TemplatesService::getTwig()>, <api:Craft\TemplatesService::onPluginsLoaded()>

Return

:   A new [\Twig_Extension](https://twig.symfony.com/api/master/Twig_Extension.html) object

Gives plugins a chance to add a new [Twig extension](https://twig.symfony.com/doc/api.html#using-extensions).

```php
public function addTwigExtension()
{
    Craft::import('plugins.cocktailrecipes.twigextensions.CocktailRecipesTwigExtension');
    return new CocktailRecipesTwigExtension();
}
```

### `addUserAdministrationOptions`

Called by

:   <api:Craft\UsersController::actionEditUser()>

Return

:   An array of additional administration options for the Edit User page.

Gives plugins a chance to add additional administration options to the Edit User page, which will show up when clicking on the gear icon menu at the top-right of the page.

The hook should return an array where each item is a sub-array with the following keys:

* **`'label'`** – The user-facing option label
* **`'action'`** – The controller action that should get loaded when the option is selected (optional)
* **`'id'`** – The option’s `id` attribute value (optional)

If you include the `'action'` key, the corresponding controller action can expect to find a `userId` POST parameter identifying the ID of the user that was being edited.

```php
public function addUserAdministrationOptions(UserModel $user)
{
    if (!$user->isCurrent())
    {
        return array(
            array(
                'label'  => Craft::t('Pull finger'),
                'action' => 'fartbook/pullFinger'
            ),
        );
    }    
}
```

### `getResourcePath`

Called by

:   <api:Craft\ResourcesService::getResourcePath()>

Return

:   A string identifying the server path to the requested resource, or `null` if your plugin isn’t sure.

Gives plugins a chance to map an incoming resource request’s path with the path to an actual file on the server.

```php
public function getResourcePath($path)
{
    // Does this path start with "myplugin/"?
    if (strncmp($path, 'myplugin/', 9) == 0)
    {
        // Return the path to the file in craft/storage/
        return craft()->path->getStoragePath().'myplugin/'.substr($path, 9);
    }
}
```

### `modifyCpNav`

Called by

:   <api:Craft\CpVariable::nav()>

Gives plugins a chance to modify the Control Panel navigation.

```php
public function modifyCpNav(&$nav)
{
    if (craft()->userSession->isAdmin())
    {
        $nav['utils'] = array('label' => 'Utils', 'url' => 'utils');
    }    
}
```

The config arrays can be set to the following:

- A string representing the label of the nav item. (The array key will be used to generate the URL.)
- An array with `'label'` and `'url'` keys, if you want to define the URL explicitly.

### `registerCachePaths`

Called by

:   <api:Craft\ClearCachesTool::performAction()>

Return

:   An array where the keys are paths and the values are the labels.

Gives plugins a chance to register new cache paths for the Clear Caches tool.

```php
public function registerCachePaths()
{
    return array(
        craft()->path->getStoragePath().'drinks/' => Craft::t('Drink images'),
    );
}
```

### `registerEmailMessages`

Called by

:   <api:Craft\EmailMessagesService::getAllMessages()>, <api:Craft\EmailMessagesService::getMessage()>, <api:Craft\EmailMessagesService::saveMessage()>

Return

:   An array of email messages.

Gives plugins a chance to register their own email messages.  

Craft will look for the email keys and messages in a translation file matching the plugins [source language](setting-things-up.md).

For example, if the plugin source language is en_us and a message key is `myPluginMessageKey1`, Craft will look in `pluginHandle/translations/en_us.php` for the following keys to get the appropriate email messages:

* `myPluginMessageKey1_heading`
* `myPluginMessageKey1_subject`
* `myPluginMessageKey1_body`

```php
public function registerEmailMessages()
{
    return array(
        'myPluginMessageKey1',
        'myPluginMessageKey2',
    );
}
```

### `registerUserPermissions`

Called by

:   <api:Craft\UserPermissionsService::getAllPermissions()>

Return

:   An array of user permissions.

Gives plugins a chance to register new user permissions.

```php
public function registerUserPermissions()
{
    return array(
        'drinkAlcohol' => array('label' => Craft::t('Drink alcohol')),
        'stayUpLate' => array('label' => Craft::t('Stay up late')),
    );
}
```

### `getCpAlerts`

Called by

:   <api:Craft\CpHelper::getAlerts()>

Return

:   An array of CP alerts, or `null` if there are no alerts to display.

Gives plugins a chance to register new Control Panel alerts.

```php
public function getCpAlerts($path, $fetch)
{
    if (craft()->config->devMode)
    {
        return array('Dev Mode is enabled!');
    }
}
```

The hook is passed the following arguments:

* **`$path`** – The path to the main CP page that the alert will be displayed on. (This won’t nceessarily be the same as <api:Craft\HttpRequestService::getPath()> because getCpAlerts() is often called over Ajax after the main page has loaded.)
* **`$fetch`** – Whether the method can create HTTP requests when determining if it should display alerts. When this is `false`, if the alerts can not be determined without an HTTP request, `null` should be returned, so the method does not significantly impact CP page load times.

## Routing Hooks

### `registerCpRoutes`

Called by

:   <api:Craft\UrlManager::parseUrl()>

Return

:   An array of CP routes.

Gives plugins a chance to register their own CP routes.

```php
public function registerCpRoutes()
{
    return array(
        'cocktails/new'               => 'cocktails/_edit',
        'cocktails/(?P<widgetId>\d+)' => array('action' => 'cocktails/editCocktail'),
    );
}
```

### `registerSiteRoutes`

Called by

:   <api:Craft\UrlManager::parseUrl()>

Return

:   An array of site routes.

Gives plugins a chance to register their own front-end site routes.

```php
public function registerSiteRoutes()
{
    return array(
        'cocktails/(?P<widgetId>\d+)' => array('action' => 'cocktails/editCocktail'),
    );
}
```

::: tip
It’s a good practice to make any URL segments in your site routes configurable by the site administrator, so you’re not forcing a particular URL scheme on sites using your plugin.
:::

### `getElementRoute`

Called by

:   <api:Craft\UrlManager::parseUrl()>

Return

:   A route, or `null`

Gives plugins a chance to override the default site route for a given element.

```php
public function getElementRoute(BaseElementModel $element)
{
    if (
        $element->getElementType() == ElementType::Entry &&
        $element->getSection()->handle == 'products'
    )
    {
        return array('action' => 'products/viewEntry');
    }
}
```

## Element Hooks

### `addEntryActions`

Called by

:   <api:Craft\EntryElementType::getAvailableActions()>

Return

:   An array of element actions.

Gives plugins a chance to add additional actions to the entry index page. Each item in the array can either be an element action’s class handle or an instantiated <api:Craft\IElementAction> object.

```php
public function addEntryActions($source)
{
    return array(
        'Foo',
        new BarElementAction(),
    );
}
```

### `addCategoryActions`

Called by

:   <api:Craft\CategoryElementType::getAvailableActions()>

Return

:   An array of element actions.

Gives plugins a chance to add additional actions to the category index page. Each item in the array can either be an element action’s class handle or an instantiated <api:Craft\IElementAction> object.

```php
public function addCategoryActions($source)
{
    return array(
        'Foo',
        new BarElementAction(),
    );
}
```

### `addAssetActions`

Called by

:   <api:Craft\AssetElementType::getAvailableActions()>

Return

:   An array of element actions.

Gives plugins a chance to add additional actions to the asset index page. Each item in the array can either be an element action’s class handle or an instantiated <api:Craft\IElementAction> object.

```php
public function addAssetActions($source)
{
    return array(
        'Foo',
        new BarElementAction(),
    );
}
```

### `addUserActions`

Called by

:   <api:Craft\UserElementType::getAvailableActions()>

Return

:   An array of element actions.

Gives plugins a chance to add additional actions to the user index page. Each item in the array can either be an element action’s class handle or an instantiated <api:Craft\IElementAction> object.

```php
public function addUserActions($source)
{
    return array(
        'Foo',
        new BarElementAction(),
    );
}
```

### `modifyAssetFilename`

Called by

:   <api:Craft\AssetsHelper::cleanAssetName()>

Return

:   The modified asset filename.

Gives plugins an opportunity to customize asset filenames as they are being cleansed.

```php
public function modifyAssetFilename($filename)
{
    return 'KittensRule-'.$filename;
}
```

### `modifyEntrySortableAttributes`

Called by

:   <api:Craft\EntryElementType::defineSortableAttributes()>

Gives plugins a chance to modify the attributes that entries can be sorted by in the Control Panel.

```php
public function modifyEntrySortableAttributes(&$attributes)
{
    $attributes['id'] = Craft::t('ID');
}
```

### `modifyCategorySortableAttributes`

Called by

:   <api:Craft\CategoryElementType::defineSortableAttributes()>

Gives plugins a chance to modify the attributes that categories can be sorted by in the Control Panel.

```php
public function modifyCategorySortableAttributes(&$attributes)
{
    $attributes['id'] = Craft::t('ID');
}
```

### `modifyAssetSortableAttributes`

Called by

:   <api:Craft\AssetElementType::defineSortableAttributes()>

Gives plugins a chance to modify the attributes that assets can be sorted by in the Control Panel.

```php
public function modifyAssetSortableAttributes(&$attributes)
{
    $attributes['id'] = Craft::t('ID');
}
```

### `modifyUserSortableAttributes`

Called by

:   <api:Craft\UserElementType::defineSortableAttributes()>

Gives plugins a chance to modify the attributes that users can be sorted by in the Control Panel.

```php
public function modifyUserSortableAttributes(&$attributes)
{
    $attributes['id'] = Craft::t('ID');
}
```

### `modifyEntrySources`

Called by

:   <api:Craft\EntryElementType::getSources()>

Gives plugins a chance to modify the available sources for entries.

```php
public function modifyEntrySources(&$sources, $context)
{
    if ($context == 'index')
    {
        $sources[] = array('heading' => 'Statuses');

        $statuses = craft()->elements->getElementType(ElementType::Entry)->getStatuses();
        foreach ($statuses as $status => $label)
        {
            $sources['status:'.$status] = array(
                'label' => $label,
                'criteria' => array('status' => $status)
            );
        }
    }
}
```

### `modifyCategorySources`

Called by

:   <api:Craft\CategoryElementType::getSources()>

Gives plugins a chance to modify the available sources for categories.

```php
public function modifyCategorySources(&$sources, $context)
{
    if ($context == 'index')
    {
        $sources[] = array('heading' => 'Statuses');

        $statuses = craft()->elements->getElementType(ElementType::Category)->getStatuses();
        foreach ($statuses as $status => $label)
        {
            $sources['status:'.$status] = array(
                'label' => $label,
                'criteria' => array('status' => $status)
            );
        }
    }
}
```

### `modifyAssetSources`

Called by

:   <api:Craft\AssetElementType::getSources()>

Gives plugins a chance to modify the available sources for assets.

```php
public function modifyAssetSources(&$sources, $context)
{
    if ($context == 'index')
    {
        $sources[] = array('heading' => 'File Kinds');

        foreach (IOHelper::getFileKinds() as $kind => $info)
        {
            $sources['kind:'.$kind] = array('label' => $info['label'], 'criteria' => array('kind' => $kind));
        }
    }
}
```

### `modifyUserSources`

Called by

:   <api:Craft\UserElementType::getSources()>

Gives plugins a chance to modify the available sources for users.

```php
public function modifyUserSources(&$sources, $context)
{
    if ($context == 'index')
    {
        $sources[] = array('heading' => 'Statuses');

        $statuses = craft()->elements->getElementType(ElementType::User)->getStatuses();
        foreach ($statuses as $status => $label)
        {
            $sources['status:'.$status] = array(
                'label' => $label,
                'criteria' => array('status' => $status)
            );
        }
    }
}
```

### `defineAdditionalEntryTableAttributes`

Called by

:   <api:Craft\EntryElementType::defineAvailableTableAttributes()>

Gives plugins a chance to make additional table columns available to entry indexes.

```php
public function defineAdditionalEntryTableAttributes()
{
    return array(
        'foo' => "Foo",
        'bar' => "Bar",
    );
}
```

### `defineAdditionalCategoryTableAttributes`

Called by

:   <api:Craft\CategoryElementType::defineAvailableTableAttributes()>

Gives plugins a chance to make additional table columns available to category indexes.

```php
public function defineAdditionalCategoryTableAttributes()
{
    return array(
        'foo' => "Foo",
        'bar' => "Bar",
    );
}
```

### `defineAdditionalAssetTableAttributes`

Called by

:   <api:Craft\AssetElementType::defineAvailableTableAttributes()>

Gives plugins a chance to make additional table columns available to asset indexes.

```php
public function defineAdditionalAssetTableAttributes()
{
    return array(
        'foo' => "Foo",
        'bar' => "Bar",
    );
}
```

### `defineAdditionalUserTableAttributes`

Called by

:   <api:Craft\UserAssetType::defineAvailableTableAttributes()>

Gives plugins a chance to make additional table columns available to user indexes.

```php
public function defineAdditionalUserTableAttributes()
{
    return array(
        'foo' => "Foo",
        'bar' => "Bar",
    );
}
```

### `getEntryTableAttributeHtml`

Called by

:   <api:Craft\EntryElementType::getTableAttributeHtml()>

Return

:   The HTML that should be displayed in the table cell for the given attribute.

Gives plugins a chance to customize the HTML of the table cells on the entry index page.

```php
public function getEntryTableAttributeHtml(EntryModel $entry, $attribute)
{
    if ($attribute == 'price')
    {
        return '$'.$entry->price;
    }
}
```

### `getCategoryTableAttributeHtml`

Called by

:   <api:Craft\CategoryElementType::getTableAttributeHtml()>

Return

:   The HTML that should be displayed in the table cell for the given attribute.

Gives plugins a chance to customize the HTML of the table cells on the category index page.

```php
public function getCategoryTableAttributeHtml(CategoryModel $category, $attribute)
{
    if ($attribute == 'color' && $category->color)
    {
        return '<div class="colorbox" style="background-color: '.$category->color.';"></div>';
    }
}
```

### `getAssetTableAttributeHtml`

Called by

:   <api:Craft\AssetElementType::getTableAttributeHtml()>

Return

:   The HTML that should be displayed in the table cell for the given attribute.

Gives plugins a chance to customize the HTML of the table cells on the asset index page.

```php
public function getAssetTableAttributeHtml(AssetFileModel $asset, $attribute)
{
    if ($attribute == 'smallScreenImage' || $attribute == 'largeScreenImage')
    {
        $altImage = $asset->$attribute->first();

        if ($altImage)
        {
            return craft()->templates->render('_elements/element', array(
                'element' => $altImg
            ));
        }

        return '';
    }
}
```

### `getUserTableAttributeHtml`

Called by

:   <api:Craft\UserElementType::getTableAttributeHtml()>

Return

:   The HTML that should be displayed in the table cell for the given attribute.

Gives plugins a chance to customize the HTML of the table cells on the user index page.

```php
public function getUserTableAttributeHtml(UserModel $user, $attribute)
{
    if ($attribute == 'twitter' && $user->twitter)
    {
        return '<a href="https://twitter.com/'.$user->twitter.'">@'.$user->twitter.'</a>';
    }
}
```

### `getTableAttributesForSource`

Called by

:   <api:Craft\BaseElementType::getTableAttributesForSource()>

Return

:   An array of table attributes, or `null`.

Gives plugins a chance to customize the visible table attributes for a given element index source.

```php
public function getTableAttributesForSource($elementType, $sourceKey)
{
    if ($sourceKey == 'foo')
    {
        return craft()->elementIndexes->getTableAttributes($elementType, 'bar');
    }
}
```

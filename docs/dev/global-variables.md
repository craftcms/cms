# Global Variables

Every single template is going to get loaded with the following variables:

## `craft`

A <api:craft\web\twig\variables\CraftVariable> object, which provides access points to various helper functions and objects for templates.

### `craft.app`

A reference to the main <api:craft\web\Application> instance (the thing you get when you type `Craft::$app` in PHP code) is also available to templates via `craft.app`.

::: warning
Accessing things via `craft.app` is considered advanced. There are more security implications than other Twig-specific variables and functions, and your templates will be more susceptible to breaking changes during major Craft version bumps.
:::

```twig
{% if craft.app.config.general.devMode %}
    <p>This site is running in Dev Mode.</p>
{% endif %}
```  

## `currentSite`

The requested site, represented by a <api:craft\models\Site> object.

```twig
{{ currentSite.name }}
```

You can access all of the sites in the same group as the current site via `currentSite.group.sites`:

```twig
<nav>
    <ul>
        {% for site in currentSite.group.sites %}
            <li><a href="{{ alias(site.baseUrl) }}">{{ site.name }}</a></li> 
        {% endfor %}
    </ul>
</nav>
```

## `currentUser`

The currently-logged-in user, represented by a <api:craft\elements\User> object, or `null` if no one is logged in.

```twig
{% if currentUser %}
    Welcome, {{ currentUser.friendlyName }}!
{% endif %}
```

## `loginUrl`

The URL to your site’s login page, based on the <config:loginPath> config setting.

```twig
{% if not currentUser %}
    <a href="{{ loginUrl }}">Login</a>
{% endif %}
```

## `logoutUrl`

The URL Craft uses to log users out, based on the <config:logoutPath> config setting. Note that Craft will automatically redirect users to your homepage after going here; there’s no such thing as a “logout _page_”.

```twig
{% if currentUser %}
    <a href="{{ logoutUrl }}">Logout</a>
{% endif %}
```

## `now`

A [DateTime](http://php.net/manual/en/class.datetime.php) object set to the current date and time.

```twig
Today is {{ now|date('M j, Y') }}.
```

## `POS_BEGIN`

Twig-facing copy of the [craft\web\View::POS_BEGIN](api:craft\web\View#constants) constant.

## `POS_END`

Twig-facing copy of the [craft\web\View::POS_END](api:craft\web\View#constants) constant.

## `POS_HEAD`

Twig-facing copy of the [craft\web\View::POS_HEAD](api:craft\web\View#constants) constant.

## `POS_LOAD`

Twig-facing copy of the [craft\web\View::POS_LOAD](api:craft\web\View#constants) constant.

## `POS_READY`

Twig-facing copy of the [craft\web\View::POS_READY](api:craft\web\View#constants) constant.

## `siteName`

The name of your site, as defined in Settings → Sites.

```twig
<h1>{{ siteName }}</h1>
```

## `siteUrl`

The URL of your site

```twig
<link rel="home" href="{{ siteUrl }}">
```

## `SORT_ASC`

Twig-facing copy of the `SORT_ASC` PHP constant.

## `SORT_DESC`

Twig-facing copy of the `SORT_DESC` PHP constant.

## `SORT_FLAG_CASE`

Twig-facing copy of the `SORT_FLAG_CASE` PHP constant.

## `SORT_LOCALE_STRING`

Twig-facing copy of the `SORT_LOCALE_STRING` PHP constant.

## `SORT_NATURAL`

Twig-facing copy of the `SORT_NATURAL` PHP constant.

## `SORT_NUMERIC`

Twig-facing copy of the `SORT_NUMERIC` PHP constant.

## `SORT_REGULAR`

Twig-facing copy of the `SORT_REGULAR` PHP constant.

## `SORT_STRING`

Twig-facing copy of the `SORT_STRING` PHP constant.

## `systemName`

The System Name, as defined in Settings → General.

## `view`

A reference to the <api:craft\web\View> instance that is driving the template.

## Global Set Variables

Each of your site’s [global sets](../globals.md) will be available to your template as global variables, named after their handle.

They will be represented as <api:craft\elements\GlobalSet> objects.

```twig
<p>{{ companyInfo.companyName }} was established in {{ companyInfo.yearEstablished }}.</p>
```

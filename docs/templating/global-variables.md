# Global Variables

Every single template is going to get loaded with the following variables:

## `now`

A [datetime](datetime.md) object set to the current date and time.

```twig
Today is {{ now|date('M j, Y') }}.
```

## `siteName`

The name of your site, as defined in Settings → General.

```twig
<h1>{{ siteName }}</h1>
```

## `siteUrl`

The URL of your site. (See [How Craft Determines the Site URL](https://craftcms.com/support/site-url))

```twig
<link rel="home" href="{{ siteUrl }}">
```

## `currentUser`

A [UserModel](usermodel.md) object set to the currently logged-in user (if there is one).

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

## Global Set Variables

Each of your site’s [global sets](../globals.md) get [GlobalSetModel](globalsetmodel.md) object to represent them.

```twig
<p>{{ companyInfo.companyName }} was established in {{ companyInfo.yearEstablished }}.</p>
```

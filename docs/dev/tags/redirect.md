# `{% redirect %}` Tags

This tag will redirect the browser to a different URL.

```twig
{% if not user or not user.isInGroup('members') %}
    {% redirect "pricing" %}
{% endif %}
```

## Parameters

The `{% redirect %}` tag has the following parameter:

### The URL

Immediately after typing “`{% redirect`”, you need to tell the tag where to redirect the browser. You can either give it a full URL, or just the path.

### The Status Code

By default, redirects will have `302` status codes, which tells the browser that the requested URL has only been moved to the redirected URL _temporarily_.

You can customize which status code accompanies your redirect response by typing it right after the URL. For example, the following code would return a `301` redirect (permanent):

```twig
{% redirect "pricing" 301 %}
```

### Flash Messages

You can optionally set flash messages that will show up for the user on the next request using the `with notice` and/or `with error` params:

```twig
{% if not currentUser.isInGroup('members') %}
    {% redirect "pricing" 301 with notice "You have to be a member to access that!" %}
{% endif %}
```

# `{% header %}` Tags

This tag will set a new HTTP header on the response.

```twig
{# Tell the browser to cache this page for 30 days #}
{% set expiry = now|date_modify('+30 days') %}

{% header "Cache-Control: max-age=" ~ (expiry.timestamp - now.timestamp) %}
{% header "Pragma: cache" %}
{% header "Expires: " ~ expiry|date('D, d M Y H:i:s', 'GMT') ~ " GMT" %}
```

## Parameters

The `{% header %}` tag supports the following parameter:

### Header

You specify the actual header that should be set by typing it as a string after the word `header`. This parameter is required.

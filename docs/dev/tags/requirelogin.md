# `{% requireLogin %}` Tags

This tag will ensure that the user is logged in. If they aren’t, they will be redirected to the Login page, and returned to the original page after successfully logging in.

```twig
{% requireLogin %}
```

You can place this tag anywhere in your template, including within a conditional. If/when Twig gets to it, the login enforcement will take place.

The URL that the logged-out users get redirected to is based on your <config:loginPath> config setting.


# `{% requireLogin %}` Tags

This tag will ensure that the user is logged in. If they aren’t, they’ll be redirected to a Login page and returned to the original page after successfully logging in.

```twig
{% requireLogin %}
```

Place this tag anywhere in your template, including within a conditional. If/when Twig gets to it, the login enforcement will take place.

The Login page location is based on your <config:loginPath> config setting. If you do not set <config:loginPath>, it defaults to `login`. That will throw a `404` error if you have not handled the `/login` route with a custom template. To use the Control Panel’s Login form, set it to `admin/login` or `[your cpTrigger]/login`.
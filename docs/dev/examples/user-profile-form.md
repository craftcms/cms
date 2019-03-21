# User Profile Form

You can create a front-end form to let users edit their profiles without granting them access to the Control Panel. To do this, you can point your form to the same controller that the Control Panel uses for its profile form. (Jump down to [Form Action](#form-action) for more about forms and controllers.)

We’ll provide two examples: The simplest possible profile form and a full-featured profile form.

## Simple Profile

The following fields don't require any validation.
 
- first name
- last name
- photo

If those are all you need, then the form can be quite simple.

```twig
{% requireLogin %}

<form id="profile-form" class="profile-form" method="post" accept-charset="UTF-8" enctype="multipart/form-data">
  <input type="hidden" name="action" value="users/save-user">

  {{ csrfInput() }}

  <input type="hidden" name="userId" value="{{ currentUser.id }}">

  <div>
    <label for="first-name">First Name</label>
    <input type="text" id="first-name" name="firstName" value="{{ currentUser.firstName }}">
  </div>

  <div>
    <label for="last-name">Last Name</label>
    <input type="text" id="last-name" name="lastName" value="{{ currentUser.lastName }}">
  </div>

  <div>
    <label for="photo">Photo</label>

    {% if currentUser.photo %}
      <div>
        <img id="user-photo" src="{{ currentUser.photo.url() }}" alt="">
      </div>
    {% endif %}
  </div>

  <div>
    {# This file field takes precedence over the ”Delete photo” checkbox #}
    <label for="photo">Select photo</label>
    <input id="photo" type="file" name="photo" accept="image/png,image/jpeg">
  </div>

  <div>
    {# If a file has been selected, this has no effect #}
    <label for="deletePhoto">
      <input id="deletePhoto" type="checkbox" name="deletePhoto"> Delete photo
    </label>
  </div>

  <div>
    <input type="submit" value="Save Profile">
    <a href="{{ craft.request.url }}">Reset</a>
  </div>
</form>
```

The [Breaking it down](#breaking-it-down) section will cover these fields as they appear in the advanced profile example below.

## Advanced Profile

This example adds everything including:

- first name
- last name
- photo
- username
- email
- password
- a custom field
- validation

See the [Breakdown](#breakdown) section for details. See the [Extras](#extras) section for some example styles for this form.

Keep in mind that there is a custom Bio field included in this example, so if you don’t have a Bio field, then delete that section after you copy and paste into your template.

```twig
{% requireLogin %}

<form id="profile" method="post" accept-charset="UTF-8" enctype="multipart/form-data">

  {% set notice = craft.app.session.getFlash('notice') %}
  {% if notice %}
    <p>{{ notice }}</p>
  {% endif %}

  {% set formUser = user is defined ? user : currentUser  %}

  {% if formUser.hasErrors() %}
    <div class="error-list">
      <p>Unable to save user. Please check for errors.</p>

      <ul>
        {% for error in formUser.getFirstErrors() %}
          <li>{{ error }}</li>
        {% endfor %}
      </ul>
    </div>
  {% endif %}

  {{ csrfInput() }}

  {# {{ redirectInput('users/'~currentUser.username) }} #}

  <input type="hidden" name="action" value="users/save-user">

  <input type="hidden" name="userId" value="{{ formUser.id }}">

  <div class="group">
    <label for="first-name">First Name</label>
    <input type="text" id="first-name" name="firstName" value="{{ formUser.firstName }}">
  </div>

  <div class="group">
    <label for="last-name">Last Name</label>
    <input type="text" id="last-name" name="lastName" value="{{ formUser.lastName }}">
  </div>

  {% if formUser.photo %}
  <div class="group">
    <label>Photo</label>
    <img id="user-photo" src="{{ formUser.photo.url({width: 150}) }}" alt="">
  </div>

  <div class="group">
    <label for="delete-photo">
      <input id="delete-photo" type="checkbox" name="deletePhoto">
      Delete photo
    </label>
    <p class="instruction">If a new photo is selected, this checkbox has no effect.</p>
  </div>
  {% endif %}

  <div class="group">
    <label for="photo">Select photo</label>
    <input id="photo" type="file" name="photo" accept="image/png,image/jpeg">
  </div>

  {% if not craft.app.config.general.useEmailAsUsername %}
    {% set error = formUser.getFirstError('username')  %}
    {% set class = error ? 'has-error' : '' %}
    <div class="group {{  class }}">
      <label for="username">Username <span class="error-symbol">&#9888;</span></label>
      <p class="instruction">If left blank, this will become the email address.</p>

      <p class="error-message">{{ error }}</p>
      <input type="text" id="username" name="username" value="{{ formUser.username }}">
    </div>
  {% endif %}

  {% set error = formUser.getFirstError('email')  %}
  {% set class = error ? 'has-error' : '' %}
  <div class="group {{  class }}">
    <label for="email">Email <span class="error-symbol">&#9888;</span></label>

    {% if craft.app.projectConfig.get('users.requireEmailVerification') %}
      <p class="instruction">New email addresses need to be verified.</p>
    {% endif %}

    <p class="error-message">{{ error }}</p>
    <input type="text" id="email" name="email" value="{{ formUser.email }}">
  </div>

  {% set error = formUser.getFirstError('newPassword')  %}
  {% set class = error ? 'has-error' : '' %}
  <div class="group {{ class }}">
    <label for="new-password">New Password  <span class="error-symbol">&#9888;</span></label>
    <p class="error-message">{{ error }}</p>
    <input type="password" id="new-password" name="newPassword" value="{{ formUser.newPassword }}">
  </div>

  {% set error = formUser.getFirstError('currentPassword')  %}
  {% set class = error ? 'has-error' : '' %}
  <div class="group {{ class }}">
    <label for="current-password">Current Password <span class="error-symbol">&#9888;</span></label>
    <p class="instruction">Required to change Password and Email</p>
    <p class="error-message">{{ error }}</p>
    <input type="password" id="current-password" name="password" value="">
  </div>

  {# Custom field example. Delete if you don't have a `bio` field. #}
  {% set error = formUser.getFirstError('bio')  %}
  {% set class = error ? 'has-error' : '' %}
  <div class="group {{ class }}">
    <label for="bio">Bio <span class="error-symbol">&#9888;</span></label>
    <p class="error-message">{{ error }}</p>
    <textarea id="bio" name="fields[bio]">{{ formUser.bio }}</textarea>
  </div>

  <div class="group">
    <input type="submit" value="Save Profile">
    <a href="{{ craft.request.url }}">Reset</a>
  </div>
</form>
```

### Breaking it down

We’ll walk through the advanced form example step by step.

#### Require Login
```twig
{% requireLogin %}
```

Make sure the user is logged in or else the template will throw errors doing anything with `currentUser`. Be sure to read the documentation for [{% requireLogin %} Tags](https://docs.craftcms.com/v3/dev/tags/requirelogin.html) to avoid unexpected `404 Not Found` errors.

#### Form Action

```twig
<form id="profile-form" class="profile-form" method="post" accept-charset="UTF-8">
      <input type="hidden" name="action" value="users/save-user">
```

The `<form>` tag does not have an `action=""` parameter on purpose. The hidden `name="action"` input tells Craft which controller and controller method to use.

:::tip
The Control Panel profile form uses Craft’s [UserController::actionSaveUser()](api:craft\controllers\UsersController#method-actionsaveuser) controller and you’re free to use it on the front end too if it suits your needs. Otherwise, you can use it as inspiration to build your own controller in a custom module or plugin.
:::

#### Notice

```twig
{% set notice = craft.app.session.getFlash('notice') %}
{% if notice %}
  <p>{{ notice }}</p>
{% endif %}
```

Upon success, this notice will display the message, “User saved.” That is, of course, unless you’ve set a redirect input. (Jump to [Optional Redirect](#optional-redirect) to see how.)

#### User Variable

```twig
{% set formUser = user is defined ? user : currentUser  %}
```

When the form first loads, we use the `currentUser` variable. If there were validation errors, there will be a `user` variable with the previously-submitted values.

#### CSRF

```twig
{{ csrfInput() }}
```

The `csrfInput()` generator function is required in all forms for Craft’s [cross-site request forgery](https://en.wikipedia.org/wiki/Cross-site_request_forgery) protection unless you disabled it in the <config:enableCsrfProtection> setting.

#### Optional Redirect

```twig
{# {{ redirectInput('users/'~currentUser.username) }} #}
```

That line is commented out, but demonstrates that upon a successful save, you can redirect to another page; perhaps a user’s home page based on their username.

```twig
<input type="hidden" name="userId" value="{{ formUser.id }}">
```

The user id is required to update the correct user. You’ll want to make sure group permissions are set not to allow users to edit other users’ profiles.

#### Name Fields

```twig
<div class="group">
  <label for="first-name">First Name</label>
  <input type="text" id="first-name" name="firstName" value="{{ formUser.firstName }}">
</div>

<div class="group">
  <label for="last-name">Last Name</label>
  <input type="text" id="last-name" name="lastName" value="{{ formUser.lastName }}">
</div>
```

These fields don’t need any validation, so they’re pretty straightforward.

#### User Photo

```twig
{% if formUser.photo %}
  <div class="group">
    <label>Photo</label>
    <img id="user-photo" src="{{ formUser.photo.url({width: 150}) }}" alt="">
  </div>

  <div class="group">
    <label for="delete-photo">
      <input id="delete-photo" type="checkbox" name="deletePhoto">
      Delete photo
    </label>
    <p class="instruction">If a new photo is selected, this checkbox has no effect.</p>
  </div>
{% endif %}

<div class="group">
  <label for="photo">Select photo</label>
  <input id="photo" type="file" name="photo" accept="image/png,image/jpeg">
</div>
```

If a user photo exists, we’ll show it and include a checkbox for the option to delete it. No matter what, we’ll show a file field so they can choose a new photo. If this section feels unrefined, then some JavaScript enhancements might help. That’s up to you.

#### Username

```twig
{% if not craft.app.config.general.useEmailAsUsername %}
  {% set error = formUser.getFirstError('username')  %}
  {% set class = error ? 'has-error' : '' %}
  <div class="group {{  class }}">
    <label for="username">Username <span class="error-symbol">&#9888;</span></label>
    <p class="instruction">If left blank, this will become the email address.</p>

    <p class="error-message">{{ error }}</p>
    <input type="text" id="username" name="username" value="{{ formUser.username }}">
  </div>
{% endif %}
```

If you’ve set the <config:useEmailAsUsername> config setting to `true`, then we won’t show the Username field.

Here is where validation comes into play. Setting an `error` variable to `getFirstError('username')` tells us whether or not there is an error for this field. (It will be `null` if not.) If there is an error, then we’ll set the appropriate class names on HTML elements to reveal them and show the error message.

You’ll find styles in the [Extras](#extras) section to show and hide HTML elements based on class names. Of course, you can handle that however you like.

#### Email

```twig
{% set error = formUser.getFirstError('email')  %}
{% set class = error ? 'has-error' : '' %}
<div class="group {{  class }}">
  <label for="email">Email <span class="error-symbol">&#9888;</span></label>

  {% if craft.app.projectConfig.get('users.requireEmailVerification') %}
    <p class="instruction">New email addresses need to be verified.</p>
  {% endif %}

  <p class="error-message">{{ error }}</p>
  <input type="text" id="email" name="email" value="{{ formUser.email }}">
</div>
```

That is like the Username field except for showing a message that the user should expect to verify a new email address if you’ve ticked the checkbox for “Verify email addresses?” in the Control Panel under Settings → Users → Settings. The [Current Password](#current-password) field will be required to change an email address.

#### Password

```twig
{% set error = formUser.getFirstError('newPassword')  %}
{% set class = error ? 'has-error' : '' %}
<div class="group {{ class }}">
  <label for="new-password">New Password  <span class="error-symbol">&#9888;</span></label>
  <p class="error-message">{{ error }}</p>
  <input type="password" id="new-password" name="newPassword" value="{{ formUser.newPassword }}">
</div>
```

The user can change their password, but they’ll need to enter their [Current Password](#current-password) too. There will be an error if the given password is too short.

#### Current Password

```twig
{% set error = formUser.getFirstError('currentPassword')  %}
{% set class = error ? 'has-error' : '' %}
<div class="group {{ class }}">
  <label for="current-password">Current Password <span class="error-symbol">&#9888;</span></label>
  <p class="instruction">Required to change Password and Email</p>
  <p class="error-message">{{ error }}</p>
  <input type="password" id="current-password" name="password" value="">
</div>
```

This field is required when the email address or password has changed. Otherwise, the user can leave it blank. You could use some fancy JavaScript to hide or show this based on the state of the other fields.

#### Custom Field: Bio

```twig
{% set error = formUser.getFirstError('bio')  %}
{% set class = error ? 'has-error' : '' %}
<div class="group {{ class }}">
  <label for="bio">Bio <span class="error-symbol">&#9888;</span></label>
  <p class="error-message">{{ error }}</p>
  <textarea id="bio" name="fields[bio]">{{ formUser.bio }}</textarea>
</div>
```

Let’s say you added a custom field named “Bio” with a handle of `bio` to the user profile field layout under Settings → Users → Fields. Let’s also say it’s a required field. The difference here is that custom fields belong in a `fields` array with names like `field[<fieldname>]`.

:::tip
Handling complex custom fields, like Matrix or third-party plugin fields, can seem complicated. You might want to view the source code of a user profile form in the Control Panel to see how to handle those types of fields.
:::

#### Form Submission

```twig
<div class="group">
  <input type="submit" value="Save Profile">
  <a href="{{ craft.request.url }}">Reset</a>
</div>
```

A link to reload the current page is a good way to reset the form because it will use `currentUser` variable, and validation errors on will be forgotten.

## Extras

Here are some styles to make the forms on this page more readable in your browser.

```html
<style>
  #profile {
    width: 30rem;
  }

  #profile .group + .group {
    margin-top: 2em;
  }

  #profile label {
    display: block;
    font-weight: bold;
  }

  #profile input[type="text"],
  #profile input[type="password"] {
    margin: .5em 0;
    padding: .5em;
    width: 100%;
    font-size: 1em;
  }

  #profile .instruction {
    font-size: .75em;
    margin: .25em;
  }

  #profile .group .error-message,
  #profile .group .error-symbol{
    display: none;
  }

  #profile .group.has-error label,
  #profile .group.has-error .error-message {
    display: block;
    color: darkred;
    font-size: .75em;
    margin: .25em;
  }

  #profile .error-list {
    color: darkred;
    padding: 0 1em;
    border: 1px solid darkred;
    margin-bottom: 2em;
  }

  #profile .group.has-error .error-symbol {
    display: inline;
    font-size: 1.25em;
  }

  #profile .group.has-error input {
    border: 1px solid darkred;
  }
</style>
```

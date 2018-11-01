# User Queries

You can fetch users in your templates or PHP code using **user queries**.

::: code
```twig
{# Create a new user query #}
{% set myUserQuery = craft.users() %}
```
```php
// Create a new user query
$myUserQuery = \craft\elements\User::find();
```
:::

Once you’ve created a user query, you can set [parameters](#parameters) on it to narrow down the results, and then [execute it](README.md#executing-element-queries) by calling `.all()`. An array of [User](api:craft\elements\User) objects will be returned.

::: tip
See [Introduction to Element Queries](README.md) to learn about how element queries work.
:::

## Example

We can display a list of the users in an “Authors” user group by doing the following:

1. Create a user query with `craft.users()`.
2. Set the [group](#group) parameter on it.
3. Fetch the users with `.all()`.
4. Loop through the users using a [for](https://twig.symfony.com/doc/2.x/tags/for.html) tag to create the list HTML.

```twig
{# Create a user query with the 'group' parameter #}
{% set myUserQuery = craft.users()
    .group('authors') %}

{# Fetch the users #}
{% set users = myUserQuery.all() %}

{# Display the list #}
<ul>
    {% for user in users %}
        <li><a href="{{ url('authors/'~user.username) }}">{{ user.name }}</a></li>
    {% endfor %}
</ul>
```

## Parameters

User queries support the following parameters:

<!-- BEGIN PARAMS -->

### `admin`

Narrows the query results to only users that have admin accounts.



::: code
```twig
{# Fetch admins #}
{% set elements = {twig-function}
    .admin()
    .all() %}
```

```php
// Fetch admins
$elements = ElementClass::find()
    ->admin()
    ->all();
```
:::


### `anyStatus`

Clears out the [status](#status) and [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) parameters.





::: code
```twig
{# Fetch all elements, regardless of status #}
{% set elements = craft.queryFunction()
    .anyStatus()
    .all() %}
```

```php
// Fetch all elements, regardless of status
$elements = ElementClass::find()
    ->anyStatus()
    ->all();
```
:::


### `asArray`

Causes the query to return matching elements as arrays of data, rather than ElementClass objects.





::: code
```twig
{# Fetch elements as arrays #}
{% set elements = craft.queryFunction()
    .asArray()
    .all() %}
```

```php
// Fetch elements as arrays
$elements = ElementClass::find()
    ->asArray()
    ->all();
```
:::


### `can`

Narrows the query results to only users that have a certain user permission, either directly on the user account or through one of their user groups.

See [Users](https://docs.craftcms.com/v3/users.html) for a full list of available user permissions defined by Craft.



::: code
```twig
{# Fetch users that can access the Control Panel #}
{% set elements = {twig-function}
    .can('accessCp')
    .all() %}
```

```php
// Fetch users that can access the Control Panel
$elements = ElementClass::find()
    ->can('accessCp')
    ->all();
```
:::


### `dateCreated`

Narrows the query results based on the elements’ creation dates.



Possible values include:

| Value | Fetches elements…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.



::: code
```twig
{# Fetch elements created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set elements = craft.queryFunction()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch elements created last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$elements = ElementClass::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::


### `dateUpdated`

Narrows the query results based on the elements’ last-updated dates.



Possible values include:

| Value | Fetches elements…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.



::: code
```twig
{# Fetch elements updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set elements = craft.queryFunction()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php
// Fetch elements updated in the last week
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$elements = ElementClass::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::


### `email`

Narrows the query results based on the users’ email addresses.

Possible values include:

| Value | Fetches elements…
| - | -
| `'foo@bar.baz'` | with an email of `foo@bar.baz`.
| `'not foo@bar.baz'` | not with an email of `foo@bar.baz`.
| `'*@bar.baz'` | with an email that ends with `@bar.baz`.



::: code
```twig
{# Fetch users with a .co.uk domain on their email address #}
{% set elements = craft.queryFunction()
    .email('*.co.uk')
    .all() %}
```

```php
// Fetch users with a .co.uk domain on their email address
$elements = ElementClass::find()
    ->email('*.co.uk')
    ->all();
```
:::


### `firstName`

Narrows the query results based on the users’ first names.

Possible values include:

| Value | Fetches elements…
| - | -
| `'Jane'` | with a first name of `Jane`.
| `'not Jane'` | not with a first name of `Jane`.



::: code
```twig
{# Fetch all the Jane's #}
{% set elements = craft.queryFunction()
    .firstName('Jane')
    .all() %}
```

```php
// Fetch all the Jane's
$elements = ElementClass::find()
    ->firstName('Jane')
    ->one();
```
:::


### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).





::: code
```twig
{# Fetch elements in a specific order #}
{% set elements = craft.queryFunction()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php
// Fetch elements in a specific order
$elements = ElementClass::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::


### `group`

Narrows the query results based on the user group the users belong to.

Possible values include:

| Value | Fetches elements…
| - | -
| `'foo'` | in a group with a handle of `foo`.
| `'not foo'` | not in a group with a handle of `foo`.
| `['foo', 'bar']` | in a group with a handle of `foo` or `bar`.
| `['not', 'foo', 'bar']` | not in a group with a handle of `foo` or `bar`.
| a [UserGroup](api:craft\models\UserGroup) object | in a group represented by the object.



::: code
```twig
{# Fetch elements in the Foo user group #}
{% set elements = craft.queryFunction()
    .group('foo')
    .all() %}
```

```php
// Fetch elements in the Foo user group
$elements = ElementClass::find()
    ->group('foo')
    ->all();
```
:::


### `groupId`

Narrows the query results based on the user group the users belong to, per the groups’ IDs.

Possible values include:

| Value | Fetches elements…
| - | -
| `1` | in a group with an ID of 1.
| `'not 1'` | not in a group with an ID of 1.
| `[1, 2]` | in a group with an ID of 1 or 2.
| `['not', 1, 2]` | not in a group with an ID of 1 or 2.



::: code
```twig
{# Fetch elements in a group with an ID of 1 #}
{% set elements = craft.queryFunction()
    .groupId(1)
    .all() %}
```

```php
// Fetch elements in a group with an ID of 1
$elements = ElementClass::find()
    ->groupId(1)
    ->all();
```
:::


### `id`

Narrows the query results based on the elements’ IDs.



Possible values include:

| Value | Fetches elements…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.



::: code
```twig
{# Fetch the element by its ID #}
{% set element = craft.queryFunction()
    .id(1)
    .one() %}
```

```php
// Fetch the element by its ID
$element = ElementClass::find()
    ->id(1)
    ->one();
```
:::



::: tip
This can be combined with [fixedOrder](#fixedorder) if you want the results to be returned in a specific order.
:::


### `inReverse`

Causes the query results to be returned in reverse order.





::: code
```twig
{# Fetch elements in reverse #}
{% set elements = craft.queryFunction()
    .inReverse()
    .all() %}
```

```php
// Fetch elements in reverse
$elements = ElementClass::find()
    ->inReverse()
    ->all();
```
:::


### `lastLoginDate`

Narrows the query results based on the users’ last login dates.

Possible values include:

| Value | Fetches elements…
| - | -
| `'>= 2018-04-01'` | that last logged-in on or after 2018-04-01.
| `'< 2018-05-01'` | that last logged-in before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that last logged-in between 2018-04-01 and 2018-05-01.



::: code
```twig
{# Fetch elements that logged in recently #}
{% set aWeekAgo = date('7 days ago')|atom %}

{% set elements = craft.queryFunction()
    .lastLoginDate(">= #{aWeekAgo}")
    .all() %}
```

```php
// Fetch elements that logged in recently
$aWeekAgo = new \DateTime('7 days ago')->format(\DateTime::ATOM);

$elements = ElementClass::find()
    ->lastLoginDate(">= {$aWeekAgo}")
    ->all();
```
:::


### `lastName`

Narrows the query results based on the users’ last names.

Possible values include:

| Value | Fetches elements…
| - | -
| `'Doe'` | with a last name of `Doe`.
| `'not Doe'` | not with a last name of `Doe`.



::: code
```twig
{# Fetch all the Doe's #}
{% set elements = craft.queryFunction()
    .lastName('Doe')
    .all() %}
```

```php
// Fetch all the Doe's
$elements = ElementClass::find()
    ->lastName('Doe')
    ->one();
```
:::


### `limit`

Determines the number of elements that should be returned.



::: code
```twig
{# Fetch up to 10 elements  #}
{% set elements = craft.queryFunction()
    .limit(10)
    .all() %}
```

```php
// Fetch up to 10 elements
$elements = ElementClass::find()
    ->limit(10)
    ->all();
```
:::


### `offset`

Determines how many elements should be skipped in the results.



::: code
```twig
{# Fetch all elements except for the first 3 #}
{% set elements = craft.queryFunction()
    .offset(3)
    .all() %}
```

```php
// Fetch all elements except for the first 3
$elements = ElementClass::find()
    ->offset(3)
    ->all();
```
:::


### `orderBy`

Determines the order that the elements should be returned in.



::: code
```twig
{# Fetch all elements in order of date created #}
{% set elements = craft.queryFunction()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php
// Fetch all elements in order of date created
$elements = ElementClass::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::


### `relatedTo`

Narrows the query results to only elements that are related to certain other elements.



See [Relations](https://docs.craftcms.com/v3/relations.html) for a full explanation of how to work with this parameter.



::: code
```twig
{# Fetch all elements that are related to myCategory #}
{% set elements = craft.queryFunction()
    .relatedTo(myCategory)
    .all() %}
```

```php
// Fetch all elements that are related to $myCategory
$elements = ElementClass::find()
    ->relatedTo($myCategory)
    ->all();
```
:::


### `search`

Narrows the query results to only elements that match a search query.



See [Searching](https://docs.craftcms.com/v3/searching.html) for a full explanation of how to work with this parameter.



::: code
```twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.request.getQueryParam('q') %}

{# Fetch all elements that match the search query #}
{% set elements = craft.queryFunction()
    .search(searchQuery)
    .all() %}
```

```php
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->request->getQueryParam('q');

// Fetch all elements that match the search query
$elements = ElementClass::find()
    ->search($searchQuery)
    ->all();
```
:::


### `status`

Narrows the query results based on the elements’ statuses.

Possible values include:

| Value | Fetches elements…
| - | -
| `'active'` _(default)_ | with active accounts.
| `'locked'` | with locked accounts.
| `'suspended'` | with suspended accounts.
| `'pending'` | with accounts that are still pending activation.
| `['active', 'locked']` | with active or locked accounts.



::: code
```twig
{# Fetch active and locked elements #}
{% set elements = {twig-function}
    .status(['active', 'locked'])
    .all() %}
```

```php
// Fetch active and locked elements
$elements = ElementClass::find()
    ->status(['active', 'locked'])
    ->all();
```
:::


### `uid`

Narrows the query results based on the elements’ UIDs.





::: code
```twig
{# Fetch the element by its UID #}
{% set element = craft.queryFunction()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php
// Fetch the element by its UID
$element = ElementClass::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::


### `username`

Narrows the query results based on the users’ usernames.

Possible values include:

| Value | Fetches elements…
| - | -
| `'foo'` | with a username of `foo`.
| `'not foo'` | not with a username of `foo`.



::: code
```twig
{# Get the requested username #}
{% set requestedUsername = craft.app.request.getSegment(2) %}

{# Fetch that user #}
{% set element = craft.queryFunction()
    .username(requestedUsername|literal)
    .one() %}
```

```php
// Get the requested username
$requestedUsername = \Craft::$app->request->getSegment(2);

// Fetch that user
$element = ElementClass::find()
    ->username(\craft\helpers\Db::escapeParam($requestedUsername))
    ->one();
```
:::


### `with`

Causes the query to return matching elements eager-loaded with related elements.



See [Eager-Loading Elements](https://docs.craftcms.com/v3/dev/eager-loading-elements.html) for a full explanation of how to work with this parameter.



::: code
```twig
{# Fetch elements eager-loaded with the "Related" field’s relations #}
{% set elements = craft.queryFunction()
    .with(['related'])
    .all() %}
```

```php
// Fetch elements eager-loaded with the "Related" field’s relations
$elements = ElementClass::find()
    ->with(['related'])
    ->all();
```
:::



<!-- END PARAMS -->

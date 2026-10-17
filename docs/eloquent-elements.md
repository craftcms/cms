# Eloquent elements

Eloquent elements let a Laravel app manage its own Eloquent models in the control panel, as elements. A `Customer` model gets an element index with search, sorting, columns and cards, an editor, a nav item, Craft permissions, and everything else elements bring, such as custom fields and relations from entries.

Neither the model nor its table changes. Craft keeps track of which row each element represents in its own `elements_models` table, and keeps the element in step with writes the app makes to the model.

## Getting started

Write an element class that names its model:

```php
namespace App\Elements;

use App\Models\Customer as CustomerModel;
use CraftCms\Cms\Element\Eloquent\EloquentElement;

/**
 * @extends EloquentElement<CustomerModel>
 */
class Customer extends EloquentElement
{
    public static function modelClass(): string
    {
        return CustomerModel::class;
    }
}
```

Then register it from a service provider's `boot()` method:

```php
use App\Elements\Customer;
use CraftCms\Cms\Element\Eloquent\EloquentElements;

public function boot(EloquentElements $eloquentElements): void
{
    $eloquentElements->register(Customer::class);
}
```

A plugin can list the class in its `$elementTypes` property instead, and Craft registers it the same way.

That's all that's required. Registering the type:

- registers it as an element type
- starts syncing it with its model (see [Writes from the app](#writes-from-the-app))
- adds its control panel routes and a nav item under **Content**
- registers its permissions under **Settings → Users**
- lets templates query it as `craft.customers()`

Without a second element class to name, each model can only be registered once. Registering another element type for the same model throws an `InvalidArgumentException`.

## What Craft derives

Everything else has a default, derived from the model. Override a method on the element class to change it.

| Method | Default | `Customer` | `OrderLine` |
| --- | --- | --- | --- |
| `displayName()` | The model's class name, in words | Customer | Order Line |
| `pluralDisplayName()` | The plural of `displayName()` | Customers | Order Lines |
| `refHandle()` | The model's class name, kebab-cased | `customer` | `order-line` |
| `labelAttribute()` | The model's `name` column, or else its `title` column | `name` | — |
| `modelAttributes()` | Every column but the primary key, the timestamps, the soft-delete column, and any named like one of the element's own properties | `name`, `email`, `company` | |
| `cpIndexUri()` | The plural of `refHandle()` | `customers` | `order-lines` |
| `cpNavIcon()` | `database` | | |

The template query is named after `refHandle()`, pluralized and camel-cased: `craft.customers()`, `craft.orderLines()`.

Override `refHandle()` when the derived handle clashes with another element type's. A `User` or `Entry` model derives the same handle as Craft's own users or entries, so registering it throws until you do.

The element's label is used for its title in the index, its editor heading, its cards and its chips. Without a label attribute, an element is labeled "Customer 123".

## How the bridge works

### Model attributes

The element exposes each of the model's attributes as its own, so `$customer->email` reads and writes `$customer->getModel()->email`. Each model attribute is also:

- a native field in the field layout
- a column, a sort option and a search keyword on the element index

Request input is assigned to model attributes only when the model allows them to be mass-assigned, through its `$fillable` or `$guarded` properties. An attribute the model guards can't be set from the control panel.

A column named like one of the element's own properties, such as `title`, `enabled`, `slug` or `uri`, isn't exposed, because the element's property would always win. Read it from the model: `$customer->getModel()->title`. A `title` column can still be the label, and is still searchable.

Attributes in the model's `$hidden` property are left out when the element is serialized or looped over, as they are from the model itself.

### Validation

Return Laravel validation rules for the model attributes from `modelRules()`:

```php
public function modelRules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', Rule::unique('customers', 'email')->ignore($this->getModel()->getKey())],
    ];
}
```

The rules apply when an element is saved live: from the editor, or when a new element's draft is applied. They don't apply to draft saves, so an empty new customer can still be created.

### Saving

Saving a canonical element saves its model, then records the link between the two. Drafts and revisions never touch the model's table.

Creating an element in the control panel works the way Craft creates any element. It starts as an unpublished draft, and only applying that draft (**Create customer**) writes a row to the model's table.

Duplicating an element duplicates its model as a new row, with Eloquent's `replicate()`.

If the model's own observers cancel a save, delete or restore by returning `false`, the element's save, delete or restore fails too, with an exception, and its changes are rolled back.

### Querying

`Customer::find()` returns an element query that joins the model's table, so you can filter and sort by model columns. Each element carries its model, so `getModel()` doesn't run another query.

Custom fields can be queried by handle, as with any element: `Customer::find()->notes('Prefers email')`.

The join applies the model's global scopes, so a tenant scope, say, limits element queries — the control panel's index, Twig queries and all — as it limits the model's own queries. Elements whose models the scopes hide aren't found. The `SoftDeletes` scope is the exception: the element's own trash already mirrors it. To ignore them, construct the query yourself: `new EloquentElementQuery(Customer::class, modelScopes: false)`. That isn't a query method, so a request's query criteria can't turn the scopes off.

Two more helpers work from a model instance:

- `Customer::findForModel($model)` returns the model's element, including a trashed one, or `null`. It ignores the model's global scopes.
- `Customer::fromModel($model)` returns the model's element, or a new unsaved one.

## Writes from the app

Craft listens to the model's own Eloquent events, so writes the app makes outside the control panel keep the element up to date:

| The app… | The element… |
| --- | --- |
| saves a model, including creating it or touching it from a related model (`$touches`) | is created or saved, which updates its search keywords and date updated |
| changes a model's primary key | keeps its link to the model |
| soft-deletes a model | moves to the trash |
| restores a model | is restored |
| deletes a model for good | is hard-deleted |

These saves skip validation, so app writes are never rejected by `modelRules()`. Each save syncs the element once, even if nothing changed.

Craft follows the model's `saved` event, which `increment()` and `decrement()` on a model instance don't fire. After those, save the model or run the sync command below to bring its element up to date.

When the element writes the model, or the model's event updates the element, the other side doesn't write back. The app's own model observers fire once per write. Other models those observers write along the way are synced as usual.

Every model write also saves its element. For bulk imports of many rows, consider how much work that adds.

The model is written first, and its element is saved after. If saving the element fails, the model's write stays unless the app made it inside a database transaction, and the model row is left without an element. So is any row written while its events were muted (`withoutEvents()`, `saveQuietly()`, query builder updates), or before its element type was registered. Create the missing elements with:

```sh
php artisan craft:eloquent-elements:sync
php artisan craft:eloquent-elements:sync customer
```

## Deleting and the trash

What happens to the model when the element is moved to the trash depends on `trashBehavior()`:

| Behavior | Element trashed | Element restored | Element hard-deleted |
| --- | --- | --- | --- |
| `ModelTrashBehavior::SoftDelete` | Model soft-deleted | Model restored | Model force-deleted |
| `ModelTrashBehavior::Keep` | Model left alone | — | Model deleted |

The default is `SoftDelete` when the model uses `SoftDeletes`, and `Keep` otherwise. With `Keep`, a trashed customer stays visible to the app until the element is hard-deleted.

Garbage collection hard-deletes trashed elements once their soft-delete duration is up, and deletes their models with them.

`SoftDelete` requires the model to use `SoftDeletes`. Registering an element that sets it for a model without `SoftDeletes` throws.

## The control panel

Registering a type adds three routes beneath its `cpIndexUri()`. Their names start with `craft.cp.elements.{refHandle}`.

| Route | Page |
| --- | --- |
| `customers` | The element index |
| `customers/new` | Creates a new element's draft, then opens its editor |
| `customers/{id}` | The element's editor |

The editor sits beneath the index, so its nav item stays selected and its breadcrumbs lead back to the index. The edit route only opens elements of its own type.

Return `null` from `cpIndexUri()` for a type without an index page or nav item. Its elements are then edited at the generic `edit/{id}` URL.

Registering a type whose index URI another control panel route already answers throws, such as `users` for a model named `User`. Override `cpIndexUri()` to give it another.

### The nav item

The nav item is listed under **Content**. Override `cpNavIcon()` to change its icon, or `cpNavItem()` to change the item itself, or to return `null` and leave it out of the nav.

### Sources

An element type has a single source, which lists all of its elements. Add more by defining them on the element class:

```php
protected static function defineSources(string $context): array
{
    return [
        ['key' => '*', 'label' => 'All customers', 'criteria' => []],
        ['key' => 'companies', 'label' => 'Companies', 'criteria' => [/* … */]],
    ];
}
```

Once there's more than one source, the nav item lists them beneath it, and admins get the **Customize sources** gear beside it, as they do for entries. With a single source, the nav item has no subnav and no gear, so sources can only be added in code.

### Activity

The editor has an **Activity** panel, which shows the element's activity timeline and comments. As for assets, there's no full activity page.

The timeline records these events:

- **Created**, when a model is saved for the first time, from the app or from the control panel.
- **Updated**, when a save changes the model's attributes. The event lists each changed attribute with its old and new value. Craft labels the attribute with its headline name, such as **Email** for `email`, and stores the raw column value. Hidden attributes and timestamps are not listed. If a save changes none of the listed attributes, Craft records no event.
- **Trashed**, **Restored**, **Deleted** and **Duplicated**, as for other element types.

Writes that don't sync, such as `increment()` (see [Writes from the app](#writes-from-the-app)), aren't recorded.

## Field layouts

The editor shows a single tab with a field for each model attribute. Define your own layout in code with `defineFieldLayout()`:

```php
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\TextField;

public static function defineFieldLayout(FieldLayout $layout): FieldLayout
{
    return $layout
        ->tab('Contact', fn (FieldLayoutTab $tab) => $tab->add(
            new TextField(['attribute' => 'name', 'label' => 'Name']),
            new TextField(['attribute' => 'email', 'label' => 'Email']),
        ))
        ->tab('Company', fn (FieldLayoutTab $tab) => $tab
            ->add(new TextField(['attribute' => 'company', 'label' => 'Company']))
            ->tip('Shown on invoices.')
            ->field('notes'));
}
```

A `TextField` with a model attribute's name adds that attribute as a native field. `field()` adds a custom field by its handle; its values are offered as index columns and sort options, and can be queried by handle. Headings, tips and other layout elements work as they do anywhere else.

A code-defined layout isn't saved. Its tabs and elements get UIDs derived from the element type instead:

- fields by attribute or handle
- tabs by name
- other layout elements by position within their tab

Craft stores custom field values under these UIDs.

Generated fields aren't supported in code-defined layouts yet: their values aren't kept between requests, and they can't be queried by handle. Values stay put when a field is reordered or moved to another tab, but are left behind if a field is swapped for one with a different handle.

A layout saved for the element type takes precedence over the code-defined one. Craft doesn't have a settings page for saving one yet.

### Custom fields live in Craft

Custom field values are stored with the element, not on the model. App code reads them through the element:

```php
Customer::findForModel($customer)->notes;
```

## Cards

Cards show the element's label. Add model attributes and custom fields to them by handle:

```php
public static function defineFieldLayout(FieldLayout $layout): FieldLayout
{
    return $layout
        ->tab(/* … */)
        ->cardView(['email', 'company', 'notes']);
}
```

For a thumbnail, override `thumbUrl()` to return an image URL, for example from a model column, and `hasThumbs()` to return `true`.

## Templates

Query a type from Twig like Craft's own element types, with or without criteria:

```twig
{% for customer in craft.customers().orderBy('name').all() %}
    {{ customer.name }} – {{ customer.email }}
{% endfor %}

{% set grace = craft.customers({search: 'grace'}).one() %}
```

Model attributes read like any other element property, and custom fields by their handle. The model itself is available as `customer.getModel()`.

Templates written in the control panel are rendered sandboxed, such as object templates and generated fields. In those, an element's model attributes can be read except the ones listed in the model's `$hidden` property, so a column the app keeps out of serialization, like an API token, also stays out of those templates. Sandboxed templates can't call `getModel()`.

## Relating elements to records

To let editors relate elements, such as entries, to an element type's elements, give the type a relation field type. Extend `EloquentElementsField` and return the element type:

```php
use CraftCms\Cms\Element\Eloquent\EloquentElementsField;

class CustomersField extends EloquentElementsField
{
    public static function elementType(): string
    {
        return Customer::class;
    }
}
```

Then return the field type from the element type, so registering the element type registers the field type too:

```php
public static function relationFieldType(): ?string
{
    return CustomersField::class;
}
```

The field type is listed as **Customers**, with the element type's nav icon. Its settings work as they do for Craft's own relation fields. Override `defaultSelectionLabel()` to replace the default **Choose** label, for example with **Add a customer**.

Related elements load like any other relation field's value:

```twig
{% for customer in entry.customers.all() %}
    {{ customer.name }}
{% endfor %}

{% set entries = craft.entries().relatedTo(customer).all() %}
```

Eager-load the field with `.with(['customers'])` like any other relation field.

Craft stores a field's type by its class name. Renaming the field type class breaks the fields that use it.

These fields aren't available in GraphQL, because Eloquent element types have no GraphQL types.

## Permissions

### Craft permissions

Registering a type registers a permission group for it, named after the type. Its keys are namespaced by the element type's handle:

| Permission | Key |
| --- | --- |
| View customers | `elements:customer:view` |
| Create customers | `elements:customer:create` |
| Edit customers | `elements:customer:edit` |
| Delete customers | `elements:customer:delete` |

**Create**, **Edit** and **Delete** sit under **View**, as they do for users. Grant them to user groups or users under **Settings → Users**.

Without **View**, a user sees neither the index nor its nav item. The type offers them no sources either, even sources an element class defines itself, the way Entries only offers the sections a user can edit, so the endpoints behind an element index (fetching, counting, exporting and acting on elements, and element selection modals) find no customers in its sources. As for Craft's own element types, an index request for the implicit source (`__IMP__`) isn't checked against the user's sources. A custom source an admin adds through **Customize sources** is offered to the user groups it's configured for, as it is for other element types.

### Model policies

If the app has registered a Laravel policy for the model, the policy decides instead of the permissions:

| Control panel action | Without a policy | Policy method |
| --- | --- | --- |
| Open the index, see the nav item | View | `viewAny` |
| Open the editor | View | `view` |
| Create an element | Create | `create` |
| Save an element | Edit | `update` |
| Duplicate an element | Create | `create` |
| Delete an element | Delete | `delete` |

A policy receives the user Craft's auth guard returns, which is the User model in a request, the same as it does elsewhere in the app. It can combine both approaches by checking a permission itself:

```php
public function update(User $user, Customer $customer): bool
{
    return $user->can('elements:customer:edit') && ! $customer->isLocked();
}
```

Admins pass every check that falls back to the permissions, and so does everyone on Craft Solo. Drafts, copying, and deleting for a site are never allowed.

A listener for the `ElementAuthorizing` event runs before any of these checks and can allow or deny one.

## Limitations

- Elements aren't localized. Each element exists in the primary site only.
- Elements can't have drafts or revisions beyond the unpublished draft used to create them. That draft's model attributes are kept in memory until it's applied, so leaving a new element's editor before **Create customer** loses them.
- A code-defined layout can't use an Assets field as the card thumbnail, because Craft looks the thumbnail field up by a layout element key the definition can't know.
- There's no settings page for designing an element type's field layout or card in the control panel.
- Code-defined layouts don't support generated fields.
- `increment()` and `decrement()` on a model instance don't sync its element.
- Eloquent elements aren't exposed to GraphQL or the MCP server.

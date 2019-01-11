# Assets Fields

Assets fields allow you to relate [assets](assets.md) to other elements.

## Settings

Assets fields have the following settings:

- **Restrict uploads to a single folder?** – Whether file uploads/relations should be constrained to a single folder.

  If enabled, the following setting will be visible:

  - **Upload Location** – The location that files dragged directly onto the field should be saved in.

  If disabled, the following settings will be visible:

  - **Sources** – Which asset volumes (or other asset index sources) the field should be able to relate assets from.
  - **Default Upload Location** – The default location that files dragged directly onto the field should be saved in.

- **Restrict allowed file types?** Whether the field should only be able to upload/relate files of a certain type(s).
- **Limit** – The maximum number of assets that can be related with the field at once. (Default is no limit.)
- **View Mode** – How the field should appear for authors.
- **Selection Label** – The label that should be used on the field’s selection button.

### Multi-Site Settings

On multi-site installs, the following settings will also be available (under “Advanced”):

- **Relate assets from a specific site?** – Whether to only allow relations to assets from a specific site.

  If enabled, a new setting will appear where you can choose which site.

  If disabled, related assets will always be pulled from the current site.

- **Manage relations on a per-site basis** – Whether each site should get its own set of related assets.

### Dynamic Subfolder Paths

Subfolder paths defined by the “Upload Location” and “Default Upload Location” settings can optionally contain Twig tags (e.g. `news/{{ slug }}`).

Any properties supported by the source element (the element that has the Assets field) can be used here.

::: tip
If you are creating the Assets field within a [Matrix field](matrix-fields.md), the source element is going to be the Matrix block, _not_ the element that the Matrix field is being created on.

So if your Matrix field is attached to an entry, and you want to output the entry ID in your dynamic subfolder path, use `owner.id` rather than `id`.
:::

## The Field

Assets fields list all of the currently-related assets, with a button to select new ones.

Clicking the “Add an asset” button will bring up a modal window where you can find and select additional assets, as well as upload new ones.

### Inline Asset Editing

When you double-click on a related asset, a HUD will appear where you can edit the asset’s title and custom fields, and launch the Image Editor (if it’s an image).

::: tip
You can choose which custom fields should be available for your assets from Settings → Assets → [Volume Name] → Field Layout.
:::

## Templating

### Querying Elements with Assets Fields

When [querying for elements](dev/element-queries/README.md) that have an Assets field, you can filter the results based on the Assets field data using a query param named after your field’s handle.

Possible values include:

| Value | Fetches elements…
| - | -
| `':empty:'` | that don’t have any related assets.
| `':notempty:'` | that have at least one related asset.

```twig
{# Fetch entries with a related asset #}
{% set entries = craft.entries()
    .<FieldHandle>(':notempty:')
    .all() %}
```

### Working with Assets Field Data

If you have an element with an Assets field in your template, you can access its related assets using your Assets field’s handle:

```twig
{% set relatedAssets = entry.<FieldHandle> %}
```

That will give you an [asset query](dev/element-queries/asset-queries.md), prepped to output all of the related assets for the given field.

To loop through all of the related assets, call [all()](api:craft\db\Query::all()) and then loop over the results:

```twig
{% set relatedAssets = entry.<FieldHandle>.all() %}
{% if relatedAssets|length %}
    <ul>
        {% for rel in relatedAssets %}
            <li><a href="{{ rel.url }}">{{ rel.filename }}</a></li>
        {% endfor %}
    </ul>
{% endif %}
```

If you only want the first related asset, call [one()](api:craft\db\Query::one()) instead, and then make sure it returned something:

```twig
{% set rel = entry.<FieldHandle>.one() %}
{% if rel %}
    <p><a href="{{ rel.url }}">{{ rel.filename }}</a></p>
{% endif %}
```

If you just need to check if there are any related assets (but don’t need to fetch them), you can call [exists()](api:craft\db\Query::exists()):

```twig
{% if entry.<FieldHandle>.exists() %}
    <p>There are related assets!</p>
{% endif %}
```

You can set [parameters](dev/element-queries/asset-queries.md#parameters) on the asset query as well. For example, to ensure that only images are returned, you can set the [kind](dev/element-queries/asset-queries.md#kind) param:

```twig
{% set relatedAssets = entry.<FieldHandle>
    .kind('image')
    .all() %}
```

### Uploading Files from Front-End Entry Forms

If you want to allow users to upload files to an Assets field from a front-end [entry form](dev/examples/entry-form.md), you just need to do two things.

First, make sure your `<form>` tag has an `enctype="multipart/form-data"` attribute, so that it is capable of uploading files.

```markup
<form method="post" accept-charset="UTF-8" enctype="multipart/form-data">
```

Then add a file input to the form:

```markup
<input type="file" name="fields[<FieldHandle>]">
```

::: tip
Replace `<FieldHandle>` with you actual field handle. For example if you field handle is “heroImage”, the input name should be `fields[heroImage]`.
:::

If you want to allow multiple file uploads, add the `multiple` attribute and add `[]` to the end of the input name:

```markup
<input type="file" name="fields[<FieldHanlde>][]" multiple>
```

## See Also

* [Asset Queries](dev/element-queries/asset-queries.md)
* <api:craft\elements\Asset>
* [Relations](relations.md)

# Reference Tags

Reference tags can be used to create references to various elements in your site. They can be used in any plain text field, including plain text cells within a Table field.

The syntax for reference tags looks like this:

    {type:reference:property}

As you can see, they are made up three segments:

1.  **Type** – The type of element you’re creating a reference to.

    Possible values include:

    - `entry`
    - `asset`
    - `tag`
    - `user`
    - `globalset`

2.  **Reference** – An identifying reference to the element. Regardless of the element type, you can set this to the element’s ID.

    Entries support two additional reference formats:

    - `entry-slug`
    - `sectionHandle/entry-slug`

3.  **Property** – The property that the reference tag should return.

    See the available properties within each element type’s model reference:

    - [EntryModel](templating/entrymodel.md)
    - [AssetFileModel](templating/assetfilemodel.md)
    - [TagModel](templating/tagmodel.md)
    - [UserModel](templating/usermodel.md)
    - [GlobalSetModel](templating/globalsetmodel.md)

    Note that this third segment is actually optional. If omitted, the tag will default to “url”.

    You can also reference a textual field’s value in this segment by using its field handle:

        {entry:entry-slug:fieldHandle}

    Note that this will not work for any relational fields (entries, assets, users, etc.).

## Parsing Reference Tags

You can parse any string for reference tags in your templates using the [parseRefs](templating/filters.md#parserefs) filter:

```twig
{{ entry.body|parseRefs|raw }}
```

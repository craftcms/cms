# Working with Elements

Assets, categories, entries, global sets, Matrix blocks, tags, and users are all considered “elements” in Craft, and working with them is generally pretty consistent, thanks to shared element-focused APIs.

## Fetching Elements

To fetch elements in Craft, you first need to create a new <api:Craft\ElementCriteriaModel> instance (yes, the same type of object used to [fetch elements from your templates](../templating/elementcriteriamodel.md)).

<api:Craft\ElementsService> provides a `getCriteria()` function that makes this easy:

```php
$criteria = craft()->elements->getCriteria(ElementType::Entry);
```

You pass in the class handle of whatever element type you want to fetch. Craft provides constants for each of the built-in element types’ class handles:

* `ElementType::Asset`
* `ElementType::Category`
* `ElementType::Entry`
* `ElementType::GlobalSet`
* `ElementType::MatrixBlock`
* `ElementType::Tag`
* `ElementType::User`

What is returned is an ElementCriteriaModel that is ready to be populated with parameters that will tell Craft how it should filter the available elements.

The actual list of available parameters depends on the element type. They are identical to the parameters available to your templates. See [craft.entries](../templating/craft.entries.md) for a list of the parameters available when fetching entries, for example.

ElementCriteriaModel’s are really just regular old [models](models.md), and these “parameters” are model attributes. You can set them exactly the same way you would set other models’ attributes.

```php
$criteria->section = 'news';
$criteria->order   = 'postDate desc';
$criteria->limit   = 5;
```

Once you’ve set your parameters, the last step is to actually fetch the elements. You can do this by calling `find()`, `first()`, `last()`, `ids()`, or `total()` – again, [just like it works from your templates](../templating/elementcriteriamodel.md#outputting-elements).

```php
$entries = $criteria->find();
```

Note that `find()` will be called automatically within your ElementCriteriaModel if you treat it like an array, so it’s not actually necessary to call it yourself.

```php
foreach ($criteria as $entry)
{
    echo $entry->title . '<br>';
}
```


## Accessing Element Properties

The elements that are returned via `find()`, `first()`, and `last()` will be models that extend BaseElementModel. Accessing element properties like `id` and `title` is just like accessing regular object properties:

```php
$title = $entry->title;
```

You can access custom field values in the same way:

```php
$body = $entry->myBodyField;
```

It all works exactly the same as you’re probably used to when working with elements in your templates, because it _is_ exactly the same.

## Saving Elements

If you need to make changes to an element, or create a new one, generally the best place to do that will be through the element types’ own APIs. For example entries should be saved with [`craft()->entries->saveEntry()`](https://docs.craftcms.com/api/v2/services/EntriesService.html#saveEntry-detail).

## Creating new Element Types

If you’re writing a plugin that has a need to provide its own Element Type to the system, at a minimum you will need two classes:

* A class that extends `BaseElementType` in an `elementtypes/` subfolder
* A class that extends `BaseElementModel` in a `models/` subfolder

We are still documenting all of the different features these classes have at their disposal, along with several other aspects of creating custom element types.

Here are some existing resources, if you want to start diving in:

* [Introduction to Element Types in Craft CMS - Part 1 of 3](https://straightupcraft.com/events/introduction-to-element-types-in-craft-cms-part-1-of-3) _(video)_
* [Introduction to Element Types in Craft CMS - Part 2 of 3](https://straightupcraft.com/events/element-types-and-the-element-service-part-2-of-3) _(video)_
* [Introduction to Element Types in Craft CMS - Part 3 of 3](https://straightupcraft.com/events/exploring-the-element-type-model-part-3-of-3) _(video)_
* <api:Craft\BaseElementType> class reference
* <api:Craft\BaseElementModel> class reference
* <api:Craft\ElementsService> class reference
* [Events](https://github.com/pixelandtonic/Events) sample Element Type plugin

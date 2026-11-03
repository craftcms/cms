---
paths:
  - 'resources/templates/_includes/forms/**'
  - 'yii2-adapter/resources/templates/_includes/forms/**'
  - 'src/Cp/FormFields.php'
  - 'src/Field/BaseRelationField.php'
---

# Forms

## Preserve legacy form configuration
Delegate migrated Twig inputs through `FormFields::*FromConfig()`. Keep legacy config keys working and deprecate unsupported keys instead of dropping them.

## Keep legacy form includes in the adapter
Core templates render form controls with `ui()`, never through `_includes/forms` or the `forms` macros. The `craft.cp.*` input methods only map legacy config for the adapter's includes; core templates don't call them. Core ships no `_includes/forms` templates at all.

## Relation field inputs are components
Core relation fields render `ElementSelect`, which boots `<craft-*-select-input>`. Customize the input by overriding `elementSelectHtml()`. `$inputTemplate` and `$settingsTemplate` are adapter-only; core throws if a field sets `$inputTemplate` without the adapter.

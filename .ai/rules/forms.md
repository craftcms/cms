---
paths:
  - 'resources/templates/_includes/forms/**'
  - 'yii2-adapter/resources/templates/_includes/forms/**'
---

# Forms

## Preserve legacy form configuration
Delegate migrated Twig inputs through `FormFields::*FromConfig()`. Keep legacy config keys working and deprecate unsupported keys instead of dropping them.

## Keep legacy form includes in the adapter
Core templates render form controls with `ui()`, never through `_includes/forms` or the `forms` macros. The `craft.cp.*` input methods only map legacy config for the adapter's includes; core templates don't call them.

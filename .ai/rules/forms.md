---
paths:
  - 'resources/templates/_includes/forms/**'
  - 'yii2-adapter/resources/templates/_includes/forms/**'
---

# Forms

## Preserve legacy form configuration
Delegate migrated Twig inputs through `FormFields::*FromConfig()`. Keep legacy config keys working and deprecate unsupported keys instead of dropping them.

## Keep legacy form includes in the adapter
Core renders inputs through `FormFields` and `craft.cp.*`, never through `_includes/forms`. The adapter serves those templates for plugins; don't add new ones to core.

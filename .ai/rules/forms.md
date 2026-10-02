---
paths:
  - 'resources/templates/_includes/forms/**'
---

# Forms

## Preserve legacy form configuration
Delegate migrated Twig inputs through `FormFields::*FromConfig()`. Keep legacy config keys working and deprecate unsupported keys instead of dropping them.

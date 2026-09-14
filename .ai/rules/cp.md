---
paths:
  - 'src/Cp/**'
  - src/Cp/FormFields.php
---

# Cp

## Respect control panel migration states
Legacy pages return `view()`. Partially migrated pages return `CpScreenResponse`; a full migration converts the inner UI to Vue and returns `Inertia::render()`.

## Keep FormFields as the legacy configuration shim
Translate legacy Twig configuration through `*FromConfig()` methods and deprecate unsupported keys. Wrap inputs through `fieldHtml()` rather than bypassing the component layer.

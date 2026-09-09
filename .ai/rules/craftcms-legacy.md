---
paths:
  - 'packages/craftcms-legacy/**'
---

# Craftcms Legacy

## Follow the shared legacy migration module
When moving behavior to TypeScript, follow `resources/js/modules/README.md`: logic class, controller custom element, WeakMap support registry, and legacy global shim. Rebuild changed package output before type-checking consumers.

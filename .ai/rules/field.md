---
paths:
  - 'src/Field/**'
---

# Field

## Normalize nested element deltas through ElementHelper
Nested element fields (Matrix, Addresses) post an `{entries, sortOrder}` delta envelope keyed by element identity. The two CP stacks disagree on where the `uid:` prefix goes: `_components/fieldtypes/Matrix/block.twig` prefixes `entries` keys but writes bare `sortOrder` values, while the Form controls prefix both. Plugins may post either.

Never parse that envelope by hand — call `ElementHelper::nestedElementDelta($value)`, which returns `['delta', 'uids', 'entries', 'sortOrder']` with the prefix stripped from both halves. Hand-rolled parsing is what let a prefixed `sortOrder` silently drop every new Matrix block.

Identities are bare UUIDs everywhere inside PHP, including `NestedFormPayload.scope`. When you need the raw POST key back (e.g. `setFieldParamNamespace()`), re-add `ElementHelper::NESTED_ELEMENT_UID_PREFIX` rather than keeping an un-normalized copy around.

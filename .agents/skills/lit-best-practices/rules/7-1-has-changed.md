---
title: Custom hasChanged for Complex Types
impact: HIGH
impactDescription: Prevents unnecessary renders when object/array references change but data hasn't
tags: performance, updates, optimization, hasChanged
---

## Custom hasChanged for Complex Types

Implement `hasChanged` for properties where default comparison is insufficient.

**Problem:** Lit uses strict equality (`===`) by default. New object/array references trigger updates even when data is equivalent.

**Incorrect:**

```typescript
@customElement('my-list')
export class MyList extends LitElement {
  @property({ type: Object }) config = {};
  @property({ type: Array }) items = [];
  
  // Re-renders every time these are set, even with equivalent data
}
```

**Correct:**

```typescript
interface ListConfig {
  id: string;
  version: number;
  settings: Record<string, unknown>;
}

@customElement('my-list')
export class MyList extends LitElement {
  @property({
    type: Object,
    hasChanged: (newVal: ListConfig | null, oldVal: ListConfig | null) => {
      // Only update if meaningful data changed
      return newVal?.id !== oldVal?.id || newVal?.version !== oldVal?.version;
    }
  })
  config: ListConfig | null = null;
  
  @property({
    type: Array,
    hasChanged: (newVal: Item[], oldVal: Item[]) => {
      // Shallow comparison by id
      if (newVal?.length !== oldVal?.length) return true;
      return newVal.some((item, i) => item.id !== oldVal[i]?.id);
    }
  })
  items: Item[] = [];
}
```

**Common patterns:**

```typescript
// Compare by specific key
hasChanged: (n, o) => n?.id !== o?.id

// Compare by version/timestamp
hasChanged: (n, o) => n?.updatedAt !== o?.updatedAt

// Deep equality (use sparingly - can be expensive)
hasChanged: (n, o) => JSON.stringify(n) !== JSON.stringify(o)

// Array length + first/last item
hasChanged: (n, o) => {
  if (n?.length !== o?.length) return true;
  if (n?.[0]?.id !== o?.[0]?.id) return true;
  if (n?.[n.length - 1]?.id !== o?.[o.length - 1]?.id) return true;
  return false;
}
```

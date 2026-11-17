---
title: Use willUpdate for Derived State
impact: HIGH
impactDescription: Avoids extra render cycles, computed state ready before render
tags: lifecycle, state, performance, derived-state
---

## Use willUpdate for Derived State

Calculate derived state in `willUpdate`, not `updated`.

**Incorrect (triggers extra render):**

```typescript
@state() sortedItems: Item[] = [];
@state() itemCount = 0;

updated(changedProperties: PropertyValues) {
  if (changedProperties.has('items')) {
    // This triggers another render cycle!
    this.sortedItems = [...this.items].sort(compareItems);
    this.itemCount = this.items.length;
  }
}
```

**Correct (no extra render):**

```typescript
@state() private _sortedItems: Item[] = [];
@state() private _itemCount = 0;

willUpdate(changedProperties: PropertyValues) {
  if (changedProperties.has('items')) {
    // Runs before render, no extra cycle
    this._sortedItems = [...this.items].sort(compareItems);
    this._itemCount = this.items.length;
  }
}

render() {
  return html`
    <p>Total: ${this._itemCount}</p>
    <ul>
      ${this._sortedItems.map(item => html`<li>${item.name}</li>`)}
    </ul>
  `;
}
```

**Lifecycle timing:**

| Method | When | Use for |
|--------|------|---------|
| `willUpdate` | Before render | Compute derived state |
| `render` | During update | Return template |
| `updated` | After render | Side effects, DOM operations, events |

Setting `@state()` or `@property()` values in `updated` triggers a new update cycle. Do this only when intentional (rare).

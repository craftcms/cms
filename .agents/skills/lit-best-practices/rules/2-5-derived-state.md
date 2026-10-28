---
title: Compute Derived State in willUpdate()
impact: HIGH
impactDescription: Avoids redundant computation, prevents extra render cycles
tags: performance, state, lifecycle, derived-state
---

## Compute Derived State in willUpdate()

Move expensive computations out of `render()` into `willUpdate()`.

**Incorrect:**

```typescript
render() {
  // Expensive computation runs every render
  const sorted = [...this.items].sort((a, b) => 
    a.name.localeCompare(b.name)
  );
  const filtered = sorted.filter(item => 
    item.category === this.selectedCategory
  );
  const grouped = this._groupByDate(filtered);
  
  return html`
    ${Object.entries(grouped).map(([date, items]) => html`
      <section>
        <h3>${date}</h3>
        ${items.map(item => html`<item-card .item=${item}></item-card>`)}
      </section>
    `)}
  `;
}
```

**Correct:**

```typescript
@state() private _processedItems: Map<string, Item[]> = new Map();

willUpdate(changedProperties: PropertyValues) {
  if (changedProperties.has('items') || changedProperties.has('selectedCategory')) {
    const sorted = [...this.items].sort((a, b) => 
      a.name.localeCompare(b.name)
    );
    const filtered = sorted.filter(item => 
      item.category === this.selectedCategory
    );
    this._processedItems = this._groupByDate(filtered);
  }
}

render() {
  return html`
    ${[...this._processedItems.entries()].map(([date, items]) => html`
      <section>
        <h3>${date}</h3>
        ${items.map(item => html`<item-card .item=${item}></item-card>`)}
      </section>
    `)}
  `;
}
```

Benefits:
- Computation only runs when dependencies change
- `render()` stays simple and fast
- State is available for multiple renders without recalculation

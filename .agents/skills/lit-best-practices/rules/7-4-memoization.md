---
title: Memoize Expensive Computations
impact: MEDIUM
impactDescription: Avoids redundant expensive calculations, speeds up renders
tags: performance, memoization, caching, optimization
---

## Memoize Expensive Computations

Cache expensive computation results based on their inputs.

**Incorrect:**

```typescript
@customElement('filtered-list')
export class FilteredList extends LitElement {
  @property({ type: Array }) items: Item[] = [];
  @property({ type: String }) filter = '';
  @property({ type: String }) sortBy = 'name';
  
  // Recomputes on every access, even if inputs haven't changed
  get processedItems(): Item[] {
    console.log('Computing...'); // Runs every render
    let result = this.items.filter(item => 
      item.name.toLowerCase().includes(this.filter.toLowerCase())
    );
    return [...result].sort((a, b) => {
      const aVal = a[this.sortBy as keyof Item];
      const bVal = b[this.sortBy as keyof Item];
      return String(aVal).localeCompare(String(bVal));
    });
  }
}
```

**Correct:**

```typescript
@customElement('filtered-list')
export class FilteredList extends LitElement {
  @property({ type: Array }) items: Item[] = [];
  @property({ type: String }) filter = '';
  @property({ type: String }) sortBy = 'name';
  
  // Memoization cache
  private _cache: { key: string; result: Item[] } | null = null;
  
  private get _processedItems(): Item[] {
    const cacheKey = JSON.stringify({
      filter: this.filter,
      sortBy: this.sortBy,
      itemIds: this.items.map(i => i.id)
    });
    
    // Return cached result if inputs haven't changed
    if (this._cache?.key === cacheKey) {
      return this._cache.result;
    }
    
    // Expensive computation
    let result = this.items.filter(item => 
      item.name.toLowerCase().includes(this.filter.toLowerCase())
    );
    result = [...result].sort((a, b) => {
      const aVal = a[this.sortBy as keyof Item];
      const bVal = b[this.sortBy as keyof Item];
      return String(aVal).localeCompare(String(bVal));
    });
    
    // Cache the result
    this._cache = { key: cacheKey, result };
    return result;
  }
  
  render() {
    return html`
      <ul>
        ${this._processedItems.map(item => html`<li>${item.name}</li>`)}
      </ul>
    `;
  }
}
```

**Alternative: Use willUpdate for simpler cases**

```typescript
@state() private _processedItems: Item[] = [];

willUpdate(changedProperties: PropertyValues) {
  if (
    changedProperties.has('items') ||
    changedProperties.has('filter') ||
    changedProperties.has('sortBy')
  ) {
    // Only recompute when dependencies change
    this._processedItems = this._computeProcessedItems();
  }
}
```

**When to use which approach:**

| Approach | Use when |
|----------|----------|
| `willUpdate` | Dependencies are all reactive properties |
| Memoized getter | Multiple access points, complex cache keys |
| External memoize util | Shared across components, complex logic |

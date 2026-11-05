---
title: Batch Property Updates
impact: MEDIUM
impactDescription: Reduces render cycles when updating multiple properties
tags: performance, updates, batching, optimization
---

## Batch Property Updates

Minimize update cycles by batching related property changes.

**Incorrect:**

```typescript
async loadData() {
  this.loading = true;      // Update 1
  
  const response = await fetch('/api/data');
  const data = await response.json();
  
  this.items = data.items;  // Update 2
  this.total = data.total;  // Update 3
  this.page = data.page;    // Update 4
  this.error = null;        // Update 5
  this.loading = false;     // Update 6
}
```

**Correct:**

```typescript
async loadData() {
  this.loading = true; // Single update to show loading
  
  try {
    const response = await fetch('/api/data');
    const data = await response.json();
    
    // Batch all updates - Lit coalesces into single render
    Object.assign(this, {
      items: data.items,
      total: data.total,
      page: data.page,
      error: null,
      loading: false
    });
  } catch (e) {
    Object.assign(this, {
      error: e instanceof Error ? e.message : 'Unknown error',
      loading: false
    });
  }
}
```

**Why this works:** Lit batches property changes within the same microtask into a single update cycle. `Object.assign` sets all properties synchronously, so they're batched automatically.

**Alternative with explicit batching:**

```typescript
async loadData() {
  this.loading = true;
  
  const data = await fetch('/api/data').then(r => r.json());
  
  // Properties set synchronously batch automatically
  this.items = data.items;
  this.total = data.total;
  this.page = data.page;
  this.error = null;
  this.loading = false;
  // All above coalesce into one render
}
```

**Note:** Updates only trigger multiple renders when separated by `await` or put in different event loop tasks (e.g., `setTimeout`).

---
title: Async Operations with updateComplete
impact: MEDIUM
impactDescription: Ensures DOM reflects state changes before DOM manipulation
tags: async, lifecycle, dom, updateComplete
---

## Async Operations with updateComplete

Use `updateComplete` for operations that need the DOM to reflect state changes.

**Incorrect:**

```typescript
addItem(item: Item) {
  this.items = [...this.items, item];
  // DOM hasn't updated yet!
  const container = this.shadowRoot?.querySelector('.list');
  container?.scrollTo({ top: container.scrollHeight }); // Scrolls to old position
}

openDialog() {
  this.open = true;
  // Input doesn't exist yet!
  this.shadowRoot?.querySelector<HTMLInputElement>('input')?.focus();
}
```

**Correct:**

```typescript
async addItem(item: Item) {
  this.items = [...this.items, item];
  
  // Wait for render to complete
  await this.updateComplete;
  
  // Now DOM reflects new item
  const container = this.shadowRoot?.querySelector('.list');
  container?.scrollTo({
    top: container.scrollHeight,
    behavior: 'smooth'
  });
}

async openDialog() {
  this.open = true;
  await this.updateComplete;
  this.shadowRoot?.querySelector<HTMLInputElement>('input')?.focus();
}

async selectAndHighlight(id: string) {
  this.selectedId = id;
  await this.updateComplete;
  
  const element = this.shadowRoot?.querySelector(`[data-id="${id}"]`);
  element?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
```

**Note:** `updateComplete` returns a Promise that resolves after:
1. The `render()` method completes
2. The DOM is updated
3. Any child components with pending updates also complete (if awaited)

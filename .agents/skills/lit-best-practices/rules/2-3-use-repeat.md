---
title: Use repeat() for Keyed Lists
impact: HIGH
impactDescription: Efficient DOM updates, preserves element state during reorders
tags: performance, lists, rendering, repeat, directives
---

## Use repeat() for Keyed Lists

Always use `repeat()` with unique keys for dynamic lists.

**Incorrect:**

```typescript
render() {
  return html`
    <ul>
      ${this.items.map(item => html`<li>${item.name}</li>`)}
    </ul>
  `;
}
```

**Correct:**

```typescript
import { repeat } from 'lit/directives/repeat.js';

render() {
  return html`
    <ul>
      ${repeat(
        this.items,
        item => item.id, // Unique key function
        item => html`
          <li @click=${() => this._selectItem(item)}>${item.name}</li>
        `
      )}
    </ul>
  `;
}
```

The `repeat()` directive:
- Maintains DOM node identity during reorders
- Minimizes DOM operations for insertions/deletions
- Preserves component state in list items
- Essential when list items have internal state or event listeners

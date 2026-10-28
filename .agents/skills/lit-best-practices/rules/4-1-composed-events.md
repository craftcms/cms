---
title: Dispatch Composed Events
impact: CRITICAL
impactDescription: Events must cross shadow DOM boundaries to be useful
tags: events, shadow-dom, bubbling, composed
---

## Dispatch Composed Events

All custom events must be configured for shadow DOM traversal.

**Incorrect:**

```typescript
private _handleSelection(item: Item) {
  this.dispatchEvent(new CustomEvent('selection-changed', {
    detail: { item }
  }));
}
```

**Correct:**

```typescript
private _handleSelection(item: Item) {
  this.dispatchEvent(new CustomEvent('selection-changed', {
    bubbles: true,
    composed: true,
    detail: { item }
  }));
}
```

**Event configuration rules:**

| Option | Purpose |
|--------|---------|
| `bubbles: true` | Event propagates up the DOM tree |
| `composed: true` | Event crosses shadow DOM boundaries |

Use both for most custom events. Without `composed: true`, events won't reach listeners outside the shadow root.

**Exception:** Events that should only be handled internally (within the same shadow root) can omit these flags, but this is rare.

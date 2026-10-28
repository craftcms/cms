---
title: Use cache() for Conditional Subtrees
impact: MEDIUM
impactDescription: Preserves DOM state, avoids expensive re-creation of subtrees
tags: performance, caching, conditional-rendering, directives
---

## Use cache() for Conditional Subtrees

Use `cache()` when switching between complex template alternatives.

**When to use cache():**
- Switching between views/tabs with complex content
- Conditional content that should preserve state
- Expensive-to-recreate subtrees

**Incorrect:**

```typescript
render() {
  return html`
    <div class="container">
      ${this.currentTab === 'details'
        ? html`<details-view .data=${this.data}></details-view>`
        : html`<summary-view .data=${this.data}></summary-view>`
      }
    </div>
  `;
}
```

**Correct:**

```typescript
import { cache } from 'lit/directives/cache.js';

render() {
  return html`
    <div class="container">
      ${cache(this.currentTab === 'details'
        ? html`<details-view .data=${this.data}></details-view>`
        : html`<summary-view .data=${this.data}></summary-view>`
      )}
    </div>
  `;
}
```

The `cache()` directive stores rendered DOM for each template branch, so switching back restores the previous state rather than recreating from scratch.

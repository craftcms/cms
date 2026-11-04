---
title: Use nothing for Empty Content
impact: MEDIUM
impactDescription: Cleaner output, clearer intent, proper template handling
tags: rendering, templates, conditional, nothing
---

## Use nothing for Empty Content

Import and use `nothing` from Lit for conditional empty content.

**Incorrect:**

```typescript
render() {
  return html`
    <div>
      ${this.icon ? html`<span class="icon">${this.icon}</span>` : ''}
      ${this.title ? html`<h2>${this.title}</h2>` : null}
      ${this.subtitle ? html`<h3>${this.subtitle}</h3>` : undefined}
    </div>
  `;
}
```

**Correct:**

```typescript
import { html, nothing } from 'lit';

render() {
  return html`
    <div>
      ${this.icon ? html`<span class="icon">${this.icon}</span>` : nothing}
      ${this.title ? html`<h2>${this.title}</h2>` : nothing}
      ${this.subtitle ? html`<h3>${this.subtitle}</h3>` : nothing}
    </div>
  `;
}
```

Using `nothing`:
- Produces cleaner DOM output (no empty text nodes)
- Communicates intent clearly
- Is the idiomatic Lit pattern

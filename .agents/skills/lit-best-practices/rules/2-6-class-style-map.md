---
title: Use classMap and styleMap for Dynamic Attributes
impact: MEDIUM
impactDescription: Avoids full attribute rewrites and string-concat bugs on every render
tags: directives, templates, styling, performance
---

## Use `classMap` and `styleMap` for Dynamic Attributes

Building `class`/`style` strings by concatenation rewrites the whole attribute on each update and
clobbers classes set by other code.

**Incorrect:**

```typescript
render() {
  const classes = 'card ' + (this.selected ? 'selected ' : '') + (this.disabled ? 'disabled' : '');
  return html`<div class="${classes}" style="width:${this.width}px;color:${this.color}">
    <slot></slot>
  </div>`;
}
```

**Correct:**

```typescript
import { classMap } from 'lit/directives/class-map.js';
import { styleMap } from 'lit/directives/style-map.js';

render() {
  return html`<div
    class=${classMap({ card: true, selected: this.selected, disabled: this.disabled })}
    style=${styleMap({ width: `${this.width}px`, color: this.color })}
  >
    <slot></slot>
  </div>`;
}
```

Both directives diff key by key and only touch what changed. Prefer static rules in
`static styles` with `:host([selected])` when the state is a reflected attribute — that needs no
directive at all.

Reference: [lit.dev — Built-in directives](https://lit.dev/docs/templates/directives/)

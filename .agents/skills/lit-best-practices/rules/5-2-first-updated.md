---
title: Use firstUpdated for DOM Operations
impact: HIGH
impactDescription: DOM isn't available in connectedCallback, causes null references
tags: lifecycle, dom, initialization, firstUpdated
---

## Use firstUpdated for DOM Operations

Use `firstUpdated` for operations requiring rendered DOM.

**Incorrect:**

```typescript
connectedCallback() {
  super.connectedCallback();
  // DOM not rendered yet!
  this.shadowRoot?.querySelector('input')?.focus();
  this._measureHeight();
  const button = this.shadowRoot?.querySelector('button');
  button?.addEventListener('click', this._handleClick); // button is null
}
```

**Correct:**

```typescript
@query('input') private _input?: HTMLInputElement;
@query('.container') private _container?: HTMLElement;

firstUpdated(changedProperties: PropertyValues) {
  super.firstUpdated(changedProperties);
  
  // Now DOM is available
  this._measureHeight();
  
  if (this.autofocus) {
    this._input?.focus();
  }
  
  // Set up intersection observer on rendered element
  this._observer = new IntersectionObserver(this._handleIntersection);
  if (this._container) {
    this._observer.observe(this._container);
  }
}

private _measureHeight() {
  const height = this._container?.offsetHeight ?? 0;
  // Use measured height
}
```

**Timeline:**
1. `connectedCallback` - Element added to DOM, but shadow DOM not rendered
2. `willUpdate` - About to render, can compute derived state
3. `render()` - Returns template
4. `firstUpdated` - First render complete, DOM available ✓
5. `updated` - After each render

Use the `@query` decorator for type-safe element references instead of manual `querySelector`.

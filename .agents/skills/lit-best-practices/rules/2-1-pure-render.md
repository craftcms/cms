---
title: Keep render() Pure
impact: CRITICAL
impactDescription: Prevents infinite loops, ensures predictable rendering
tags: rendering, side-effects, correctness, purity
---

## Keep render() Pure

The `render()` method must be pure—no side effects, mutations, or async operations.

**Incorrect:**

```typescript
render() {
  // Side effects - BAD
  this._renderCount++;
  console.log('Rendering component');
  this.dispatchEvent(new CustomEvent('render'));
  localStorage.setItem('lastRender', Date.now().toString());
  
  // Mutation - BAD
  if (!this._initialized) {
    this._initialized = true;
    this._setupDefaults();
  }
  
  return html`<div>${this.content}</div>`;
}
```

**Correct:**

```typescript
render() {
  // Pure - only returns template based on current state
  return html`
    <div class=${classMap({ initialized: this._initialized })}>
      ${this.content}
    </div>
  `;
}

// Side effects go in lifecycle methods
updated(changedProperties: PropertyValues) {
  if (changedProperties.has('content')) {
    this.dispatchEvent(new CustomEvent('content-changed', {
      bubbles: true,
      composed: true,
      detail: { content: this.content }
    }));
  }
}
```

The `render()` method may be called multiple times and should always return the same output for the same state.

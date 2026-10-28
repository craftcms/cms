---
title: Style the Host Element Properly
impact: HIGH
impactDescription: Proper encapsulation, handles all states, enables external composition
tags: styles, host, encapsulation, css
---

## Style the Host Element Properly

Use `:host` and `:host()` selectors for component-level styling across all states.

**Incorrect:**

```typescript
static styles = css`
  .wrapper {
    display: block;
  }
  
  .wrapper[hidden] {
    display: none;
  }
`;

render() {
  return html`<div class="wrapper" ?hidden=${this.hidden}><slot></slot></div>`;
}
```

**Correct:**

```typescript
static styles = css`
  :host {
    display: block;
    position: relative;
    contain: content;
  }
  
  :host([hidden]) {
    display: none;
  }
  
  :host([disabled]) {
    opacity: 0.5;
    pointer-events: none;
    cursor: not-allowed;
  }
  
  :host(:focus-visible) {
    outline: 2px solid var(--focus-ring-color, #0066cc);
    outline-offset: 2px;
  }
  
  :host(:focus:not(:focus-visible)) {
    outline: none;
  }
  
  /* Contextual styling */
  :host([size="small"]) {
    --internal-padding: 8px;
    --internal-font-size: 12px;
  }
  
  :host([size="large"]) {
    --internal-padding: 16px;
    --internal-font-size: 16px;
  }
`;

render() {
  return html`<slot></slot>`;
}
```

This approach:
- Keeps styling on the actual custom element
- Works with external CSS and attribute selectors
- Handles hidden, disabled, focus states consistently

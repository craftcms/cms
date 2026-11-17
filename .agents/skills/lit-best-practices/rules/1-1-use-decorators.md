---
title: Use TypeScript Decorators
impact: HIGH
impactDescription: Clearer intent, better TypeScript integration, easier maintenance
tags: typescript, decorators, properties, maintainability
---

## Use TypeScript Decorators

Always use decorators for component and property declarations when using TypeScript.

**Incorrect (old static properties pattern):**

```typescript
class MyComponent extends LitElement {
  static properties = {
    label: { type: String },
    count: { type: Number },
    disabled: { type: Boolean, reflect: true }
  };
  
  constructor() {
    super();
    this.label = '';
    this.count = 0;
    this.disabled = false;
  }
  
  render() {
    return html`<span>${this.label}: ${this.count}</span>`;
  }
}
customElements.define('my-component', MyComponent);
```

**Correct (decorator pattern):**

```typescript
import { LitElement, html, css } from 'lit';
import { customElement, property, state } from 'lit/decorators.js';

@customElement('my-component')
export class MyComponent extends LitElement {
  @property({ type: String }) label = '';
  @property({ type: Number }) count = 0;
  @property({ type: Boolean, reflect: true }) disabled = false;
  
  render() {
    return html`<span>${this.label}: ${this.count}</span>`;
  }
}
```

Decorators provide clearer intent, better TypeScript integration, and easier maintenance.

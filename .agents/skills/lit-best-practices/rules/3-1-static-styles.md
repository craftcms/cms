---
title: Always Use Static Styles
impact: CRITICAL
impactDescription: Shared across instances, parsed once, uses adoptedStyleSheets
tags: performance, styles, best-practice, static-styles
---

## Always Use Static Styles

Never use inline `<style>` tags in templates. Always use `static styles`.

**Incorrect:**

```typescript
@customElement('my-card')
export class MyCard extends LitElement {
  render() {
    return html`
      <style>
        :host { display: block; }
        .card { padding: 16px; border-radius: 8px; }
        .title { font-size: 18px; font-weight: bold; }
      </style>
      <div class="card">
        <h2 class="title"><slot name="title"></slot></h2>
        <slot></slot>
      </div>
    `;
  }
}
```

**Correct:**

```typescript
@customElement('my-card')
export class MyCard extends LitElement {
  static styles = css`
    :host {
      display: block;
    }
    
    .card {
      padding: 16px;
      border-radius: 8px;
    }
    
    .title {
      font-size: 18px;
      font-weight: bold;
    }
  `;
  
  render() {
    return html`
      <div class="card">
        <h2 class="title"><slot name="title"></slot></h2>
        <slot></slot>
      </div>
    `;
  }
}
```

Static styles are:
- Parsed once and shared across all instances
- Adopted via `adoptedStyleSheets` for better performance
- Properly typed in TypeScript

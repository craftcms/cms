---
title: CSS Parts for Deep Styling
impact: MEDIUM
impactDescription: Allows consumers to style internal elements beyond CSS variables
tags: css-parts, shadow-dom, customization, styling
---

## CSS Parts for Deep Styling

Use `part` attribute for elements that consumers may need to style beyond CSS variables.

**Correct:**

```typescript
@customElement('fancy-card')
export class FancyCard extends LitElement {
  static styles = css`
    .header { padding: var(--card-header-padding, 16px); }
    .body { padding: var(--card-body-padding, 16px); }
    .footer { padding: var(--card-footer-padding, 8px 16px); }
  `;
  
  render() {
    return html`
      <header class="header" part="header">
        <slot name="header"></slot>
      </header>
      <main class="body" part="body">
        <slot></slot>
      </main>
      <footer class="footer" part="footer">
        <slot name="footer"></slot>
      </footer>
    `;
  }
}
```

**Consumer styling:**

```css
fancy-card::part(header) {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

fancy-card::part(footer) {
  border-top: 1px solid #eee;
  text-align: right;
}
```

CSS parts provide an explicit styling API without exposing all internal structure. Only add parts for elements that genuinely need external customization.

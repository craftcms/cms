---
title: CSS Custom Properties for Theming
impact: MEDIUM
impactDescription: Enables external customization without breaking encapsulation
tags: theming, css-variables, customization, design-tokens
---

## CSS Custom Properties for Theming

Expose CSS custom properties for external customization with sensible defaults.

**Incorrect:**

```typescript
static styles = css`
  button {
    background: #007bff;
    color: white;
    border-radius: 4px;
    font-size: 14px;
  }
`;
```

**Correct:**

```typescript
static styles = css`
  :host {
    /* Define internal properties with external overrides */
    --_button-bg: var(--button-bg, #007bff);
    --_button-color: var(--button-color, white);
    --_button-padding: var(--button-padding, 8px 16px);
    --_button-radius: var(--button-radius, 4px);
    --_button-font-size: var(--button-font-size, 14px);
  }
  
  button {
    background: var(--_button-bg);
    color: var(--_button-color);
    padding: var(--_button-padding);
    border-radius: var(--_button-radius);
    font-size: var(--_button-font-size);
    border: none;
    cursor: pointer;
    transition: opacity 0.2s;
  }
  
  button:hover {
    opacity: 0.9;
  }
`;
```

**Consumer usage:**

```css
my-button {
  --button-bg: #28a745;
  --button-radius: 20px;
}
```

The `--_` prefix convention indicates internal properties that reference external custom properties.

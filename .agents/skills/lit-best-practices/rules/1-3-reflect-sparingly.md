---
title: Reflect Properties Sparingly
impact: MEDIUM
impactDescription: Reduces DOM attribute churn, prevents serialization issues
tags: attributes, css, serialization, reflection
---

## Reflect Properties Sparingly

Only reflect properties when necessary for CSS selectors or HTML serialization.

**When to reflect:**
- Boolean states used in CSS (`:host([disabled])`, `:host([open])`)
- Enumerated variants (`variant="primary"`)
- Properties that should persist in HTML snapshots

**When NOT to reflect:**
- Complex types (Objects, Arrays) - these cannot be serialized
- Frequently changing values - causes DOM attribute churn
- Internal state - no external styling need

**Incorrect:**

```typescript
@customElement('data-card')
export class DataCard extends LitElement {
  @property({ type: Object, reflect: true }) user = {}; // Objects can't serialize
  @property({ type: Array, reflect: true }) tags = [];   // Arrays can't serialize
  @property({ type: String, reflect: true }) title = ''; // No CSS need
  @property({ type: Number, reflect: true }) scrollPosition = 0; // Changes too frequently
}
```

**Correct:**

```typescript
@customElement('data-card')
export class DataCard extends LitElement {
  // Reflected - used for CSS or meaningful in HTML
  @property({ type: Boolean, reflect: true }) loading = false;
  @property({ type: Boolean, reflect: true }) disabled = false;
  @property({ type: String, reflect: true }) variant: 'default' | 'outlined' = 'default';
  
  // Not reflected - complex types or no CSS need
  @property({ type: Object }) user: User | null = null;
  @property({ type: Array }) tags: string[] = [];
  @property({ type: String }) title = '';
  
  // Internal state
  @state() private _scrollPosition = 0;
  
  static styles = css`
    :host([loading]) { opacity: 0.6; }
    :host([disabled]) { pointer-events: none; }
    :host([variant="outlined"]) { border: 1px solid currentColor; }
  `;
}
```

---
title: Event Naming Conventions
impact: MEDIUM
impactDescription: Consistency, predictability, avoids conflicts with native events
tags: events, naming, api-design, conventions
---

## Event Naming Conventions

Follow consistent naming patterns for custom events.

**Conventions:**
- All lowercase with hyphens: `item-selected`, not `itemSelected`
- Past tense for state changes: `value-changed`, `item-removed`
- Present tense for actions: `click`, `input`, `submit`
- Prefix with component name for specificity if needed: `dropdown-opened`

**Incorrect:**

```typescript
this.dispatchEvent(new CustomEvent('onChange')); // camelCase
this.dispatchEvent(new CustomEvent('ITEM_SELECT')); // uppercase/underscore
this.dispatchEvent(new CustomEvent('select')); // conflicts with native
this.dispatchEvent(new CustomEvent('valueUpdate')); // mixed conventions
```

**Correct:**

```typescript
this.dispatchEvent(new CustomEvent('value-changed', {
  bubbles: true,
  composed: true,
  detail: { value: this.value }
}));

this.dispatchEvent(new CustomEvent('item-selected', {
  bubbles: true,
  composed: true,
  detail: { item }
}));

this.dispatchEvent(new CustomEvent('menu-opened', {
  bubbles: true,
  composed: true
}));
```

**Document events with JSDoc:**

```typescript
/**
 * @fires {CustomEvent<{value: string}>} value-changed - Fired when value changes
 * @fires {CustomEvent<{item: Item}>} item-selected - Fired when an item is selected
 */
@customElement('my-component')
export class MyComponent extends LitElement {}
```

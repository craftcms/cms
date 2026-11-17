---
title: Separate Public Properties from Internal State
impact: HIGH
impactDescription: Clear API boundaries, reduced attribute overhead, better encapsulation
tags: state-management, encapsulation, api-design
---

## Separate Public Properties from Internal State

Use `@property()` for public API and `@state()` for internal state.

**Incorrect (mixing concerns):**

```typescript
@customElement('my-dropdown')
export class MyDropdown extends LitElement {
  @property({ type: Boolean }) isOpen = false;
  @property({ type: Number }) selectedIndex = -1;
  @property({ type: Array }) _filteredItems = [];
  @property({ type: String }) _searchQuery = '';
}
```

**Correct (clear separation):**

```typescript
@customElement('my-dropdown')
export class MyDropdown extends LitElement {
  // Public API
  @property({ type: Array }) items: DropdownItem[] = [];
  @property({ type: String }) value = '';
  @property({ type: Boolean, reflect: true }) open = false;
  @property({ type: Boolean, reflect: true }) disabled = false;
  
  // Internal state
  @state() private _filteredItems: DropdownItem[] = [];
  @state() private _searchQuery = '';
  @state() private _highlightedIndex = -1;
}
```

This pattern:
- Makes the component's public API clear
- Prevents external code from depending on internal state
- Reduces attribute handling overhead for internal state

---
title: Set Semantics with ElementInternals, Not Host Attributes
impact: MEDIUM
impactDescription: Default roles and ARIA state that consumers can override, without attribute churn
tags: accessibility, aria, element-internals, shadow-dom
---

## Set Semantics with ElementInternals, Not Host Attributes

Writing `role`/`aria-*` onto your own host element in `connectedCallback` fights the consumer's
markup and pollutes the DOM. `ElementInternals` sets the *default* semantics: a consumer-supplied
attribute always wins.

**Incorrect:**

```typescript
connectedCallback() {
  super.connectedCallback();
  this.setAttribute('role', 'button');           // overwrites consumer's role
  this.setAttribute('aria-expanded', String(this.open));
}
```

**Correct:**

```typescript
@customElement('my-disclosure')
export class MyDisclosure extends LitElement {
  @property({ type: Boolean }) open = false;

  #internals = this.attachInternals();

  constructor() {
    super();
    this.#internals.role = 'button';
    this.#internals.ariaExpanded = 'false';
  }

  willUpdate(changed: PropertyValues<this>) {
    if (changed.has('open')) {
      this.#internals.ariaExpanded = String(this.open);
    }
  }
}
```

Notes:

- `attachInternals()` may only be called once per element; store the result.
- ARIA properties on internals are strings (`'true'`/`'false'`), not booleans.
- Internals also supply form association (`static formAssociated = true`) and
  `setFormValue`/`setValidity` — see rule 6-3.
- Relationship attributes (`aria-labelledby`) still cannot cross a shadow root boundary; expose a
  `label` property or use `aria-label` instead.

Reference: [lit.dev — Accessibility](https://lit.dev/docs/components/shadow-dom/)

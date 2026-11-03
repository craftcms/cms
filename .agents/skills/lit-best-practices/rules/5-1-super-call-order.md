---
title: Correct super() Call Order
impact: CRITICAL
impactDescription: Wrong order causes initialization failures and missed cleanup
tags: lifecycle, initialization, correctness, super
---

## Correct super() Call Order

Call `super` at the right time in lifecycle methods.

**Pattern:**
- `connectedCallback`: `super` first, then your code
- `disconnectedCallback`: your code first, then `super`
- Other lifecycle methods: `super` first (if overriding)

**Incorrect:**

```typescript
connectedCallback() {
  this._setupObserver(); // Runs before Lit is ready!
  super.connectedCallback();
}

disconnectedCallback() {
  super.disconnectedCallback(); // Lit cleanup happens first!
  this._cleanupObserver(); // May reference cleaned-up state
}
```

**Correct:**

```typescript
connectedCallback() {
  super.connectedCallback(); // First - Lit sets up
  this._controller = new AbortController();
  this._setupSubscriptions();
}

disconnectedCallback() {
  this._controller?.abort(); // First - your cleanup
  this._cleanupSubscriptions();
  super.disconnectedCallback(); // Last - Lit cleans up
}

firstUpdated(changedProperties: PropertyValues) {
  super.firstUpdated(changedProperties);
  this._measureDimensions();
}

updated(changedProperties: PropertyValues) {
  super.updated(changedProperties);
  if (changedProperties.has('value')) {
    this._notifyChange();
  }
}

willUpdate(changedProperties: PropertyValues) {
  super.willUpdate(changedProperties);
  // Compute derived state
}
```

**Why this matters:**
- `connectedCallback`: Lit needs to set up rendering before your code runs
- `disconnectedCallback`: Your cleanup may depend on Lit state that gets cleared in `super`

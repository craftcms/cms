---
title: delegatesFocus for Interactive Components
impact: HIGH
impactDescription: Enables proper focus behavior, clicking host focuses internal element
tags: accessibility, focus, forms, delegatesFocus
---

## delegatesFocus for Interactive Components

Enable focus delegation for components wrapping interactive elements.

**Incorrect:**

```typescript
@customElement('fancy-input')
export class FancyInput extends LitElement {
  @property({ type: String }) value = '';
  
  // Clicking the host doesn't focus the input
  // Calling element.focus() doesn't work
  
  render() {
    return html`
      <div class="wrapper">
        <input type="text" .value=${this.value} />
      </div>
    `;
  }
}
```

**Correct:**

```typescript
@customElement('fancy-input')
export class FancyInput extends LitElement {
  static shadowRootOptions: ShadowRootInit = {
    ...LitElement.shadowRootOptions,
    delegatesFocus: true
  };
  
  @property({ type: String }) value = '';
  @property({ type: String }) placeholder = '';
  
  static styles = css`
    :host {
      display: inline-block;
    }
    
    :host(:focus-within) {
      outline: 2px solid var(--focus-color, #0066cc);
      outline-offset: 2px;
    }
    
    input {
      border: 1px solid #ccc;
      padding: 8px;
      outline: none; /* Host handles focus ring */
    }
  `;
  
  render() {
    return html`
      <input
        type="text"
        .value=${this.value}
        placeholder=${this.placeholder}
        @input=${this._handleInput}
      />
    `;
  }
}
```

**With `delegatesFocus: true`:**
- Clicking anywhere on the host focuses the first focusable element inside
- Calling `element.focus()` focuses the internal element
- `:focus-within` on `:host` works correctly
- Tab navigation works as expected

---
title: Form-Associated Custom Elements
impact: HIGH
impactDescription: Enables native form participation, validation, and submission
tags: forms, accessibility, standards, ElementInternals
---

## Form-Associated Custom Elements

Implement `ElementInternals` for proper form integration.

**Correct:**

```typescript
@customElement('custom-input')
export class CustomInput extends LitElement {
  static formAssociated = true;
  
  private _internals: ElementInternals;
  
  @property({ type: String }) name = '';
  @property({ type: String }) value = '';
  @property({ type: Boolean }) required = false;
  @property({ type: Boolean }) disabled = false;
  
  constructor() {
    super();
    this._internals = this.attachInternals();
  }
  
  // Form lifecycle callbacks
  formResetCallback() {
    this.value = this.getAttribute('value') ?? '';
  }
  
  formDisabledCallback(disabled: boolean) {
    this.disabled = disabled;
  }
  
  formStateRestoreCallback(state: string) {
    this.value = state;
  }
  
  updated(changedProperties: PropertyValues) {
    if (changedProperties.has('value')) {
      this._internals.setFormValue(this.value);
      this._validate();
    }
  }
  
  private _validate() {
    if (this.required && !this.value) {
      this._internals.setValidity(
        { valueMissing: true },
        'This field is required',
        this.shadowRoot?.querySelector('input') ?? undefined
      );
    } else {
      this._internals.setValidity({});
    }
  }
  
  // Expose validity API
  get validity() { return this._internals.validity; }
  get validationMessage() { return this._internals.validationMessage; }
  get willValidate() { return this._internals.willValidate; }
  
  checkValidity() { return this._internals.checkValidity(); }
  reportValidity() { return this._internals.reportValidity(); }
  
  // Optional: expose form property
  get form() { return this._internals.form; }
  
  static styles = css`
    :host(:invalid) input {
      border-color: red;
    }
  `;
  
  render() {
    return html`
      <input
        type="text"
        .value=${this.value}
        ?disabled=${this.disabled}
        @input=${(e: Event) => {
          this.value = (e.target as HTMLInputElement).value;
        }}
      />
    `;
  }
}
```

**Usage in forms:**

```html
<form @submit=${this._handleSubmit}>
  <custom-input name="username" required></custom-input>
  <custom-input name="email" type="email"></custom-input>
  <button type="submit">Submit</button>
</form>
```

**Form association provides:**
- Automatic form submission with `name` and `value`
- Native constraint validation (`:valid`, `:invalid` CSS)
- Form reset handling
- Disabled state propagation from `<fieldset disabled>`
- Autofill support

---
title: ARIA for Custom Interactive Components
impact: CRITICAL
impactDescription: Makes components accessible to screen readers and assistive technology
tags: accessibility, aria, semantics, keyboard
---

## ARIA for Custom Interactive Components

Implement proper ARIA roles, states, and keyboard interaction for custom controls.

**Incorrect:**

```typescript
@customElement('my-switch')
export class MySwitch extends LitElement {
  @property({ type: Boolean }) checked = false;
  
  render() {
    return html`
      <div class="switch" @click=${this._toggle}>
        <span class="thumb"></span>
      </div>
    `;
  }
}
```

**Correct:**

```typescript
@customElement('my-switch')
export class MySwitch extends LitElement {
  @property({ type: Boolean, reflect: true }) checked = false;
  @property({ type: Boolean, reflect: true }) disabled = false;
  @property({ type: String }) label = '';
  
  static styles = css`
    :host {
      display: inline-flex;
      align-items: center;
      cursor: pointer;
    }
    
    :host([disabled]) {
      cursor: not-allowed;
      opacity: 0.5;
    }
    
    .track {
      width: 40px;
      height: 24px;
      background: var(--track-bg, #ccc);
      border-radius: 12px;
      position: relative;
      transition: background 0.2s;
    }
    
    :host([checked]) .track {
      background: var(--track-bg-checked, #007bff);
    }
    
    .thumb {
      width: 20px;
      height: 20px;
      background: white;
      border-radius: 50%;
      position: absolute;
      top: 2px;
      left: 2px;
      transition: transform 0.2s;
    }
    
    :host([checked]) .thumb {
      transform: translateX(16px);
    }
    
    :host(:focus-visible) .track {
      outline: 2px solid var(--focus-color, #0066cc);
      outline-offset: 2px;
    }
  `;
  
  render() {
    return html`
      <div
        class="track"
        role="switch"
        tabindex=${this.disabled ? -1 : 0}
        aria-checked=${this.checked}
        aria-disabled=${this.disabled}
        aria-label=${this.label || nothing}
        @click=${this._handleClick}
        @keydown=${this._handleKeydown}
      >
        <div class="thumb"></div>
      </div>
      ${this.label ? html`<span class="label">${this.label}</span>` : nothing}
    `;
  }
  
  private _handleClick() {
    if (!this.disabled) {
      this._toggle();
    }
  }
  
  private _handleKeydown(e: KeyboardEvent) {
    if (this.disabled) return;
    
    if (e.key === ' ' || e.key === 'Enter') {
      e.preventDefault();
      this._toggle();
    }
  }
  
  private _toggle() {
    this.checked = !this.checked;
    this.dispatchEvent(new CustomEvent('change', {
      bubbles: true,
      composed: true,
      detail: { checked: this.checked }
    }));
  }
}
```

**ARIA checklist for interactive components:**
- [ ] Appropriate `role` attribute
- [ ] `tabindex` for keyboard focus (0 for focusable, -1 when disabled)
- [ ] `aria-*` state attributes reflecting current state
- [ ] `aria-label` or `aria-labelledby` for accessible name
- [ ] Keyboard handlers for Space, Enter, Arrow keys as appropriate
- [ ] Visual focus indicator

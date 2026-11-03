---
title: Clean Up Event Listeners
impact: HIGH
impactDescription: Prevents memory leaks, ensures proper garbage collection
tags: memory, cleanup, lifecycle, events
---

## Clean Up Event Listeners

Always remove event listeners added in `connectedCallback`.

**Incorrect:**

```typescript
connectedCallback() {
  super.connectedCallback();
  window.addEventListener('resize', this._handleResize);
  document.addEventListener('keydown', this._handleKeydown);
}
// No cleanup - memory leak!
```

**Correct:**

```typescript
@customElement('resize-aware')
export class ResizeAware extends LitElement {
  private _resizeObserver?: ResizeObserver;
  private _boundHandleKeydown = this._handleKeydown.bind(this);
  
  connectedCallback() {
    super.connectedCallback();
    
    // Window/document listeners
    window.addEventListener('resize', this._handleResize);
    document.addEventListener('keydown', this._boundHandleKeydown);
    
    // Observers
    this._resizeObserver = new ResizeObserver(this._handleElementResize);
    this._resizeObserver.observe(this);
  }
  
  disconnectedCallback() {
    // Remove listeners
    window.removeEventListener('resize', this._handleResize);
    document.removeEventListener('keydown', this._boundHandleKeydown);
    
    // Clean up observers
    this._resizeObserver?.disconnect();
    this._resizeObserver = undefined;
    
    super.disconnectedCallback();
  }
  
  // Arrow function preserves 'this' automatically
  private _handleResize = () => {
    this.requestUpdate();
  };
  
  private _handleElementResize = (entries: ResizeObserverEntry[]) => {
    // Handle resize
  };
  
  // Regular method needs .bind() for removal
  private _handleKeydown(e: KeyboardEvent) {
    // Handle keydown
  }
}
```

**Key patterns:**
- Arrow functions for simple handlers (auto-bind `this`)
- Store bound references for methods that need removal
- Clean up observers, subscriptions, and timers too

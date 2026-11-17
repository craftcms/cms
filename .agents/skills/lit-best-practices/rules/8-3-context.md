---
title: Use @lit/context Instead of Prop Drilling
impact: MEDIUM
impactDescription: Removes pass-through properties across component layers
tags: context, composition, dependency-injection, state
---

## Use `@lit/context` Instead of Prop Drilling

When a value (theme, locale, API client, auth session) must reach a deep descendant, threading it
through every intermediate component couples those components to data they never use.

**Incorrect:**

```typescript
render() {
  // each layer re-declares and forwards `theme` it doesn't consume
  return html`<app-sidebar .theme=${this.theme}></app-sidebar>`;
}
```

**Correct:**

```typescript
// theme-context.ts
import { createContext } from '@lit/context';
export interface Theme { mode: 'light' | 'dark'; }
export const themeContext = createContext<Theme>(Symbol('theme'));

// provider
import { provide } from '@lit/context';

@customElement('app-root')
export class AppRoot extends LitElement {
  @provide({ context: themeContext })
  @property({ attribute: false })
  theme: Theme = { mode: 'light' };
}

// consumer, any depth below
import { consume } from '@lit/context';

@customElement('theme-badge')
export class ThemeBadge extends LitElement {
  @consume({ context: themeContext, subscribe: true })
  @state()
  private _theme?: Theme;

  render() {
    return html`<span>${this._theme?.mode ?? 'light'}</span>`;
  }
}
```

Rules:

- Use a `Symbol` (or a unique string) as the context key to avoid collisions across bundles.
- Set `subscribe: true` when the value can change after the first read; without it the consumer
  gets one value and never updates.
- Context is for ambient dependencies, not for a component's own API — keep an element's inputs as
  `@property`.
- For pure CSS theming, CSS custom properties are simpler; context is for JS values.

Reference: [lit.dev — Context](https://lit.dev/docs/data/context/)

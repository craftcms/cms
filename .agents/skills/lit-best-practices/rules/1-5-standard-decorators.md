---
title: Standard Decorators Require the accessor Keyword
impact: HIGH
impactDescription: Silently broken reactivity when compiling with standard (2023-05) decorators
tags: typescript, decorators, lit-3, tooling
---

## Standard Decorators Require the `accessor` Keyword

Lit 3 supports both TypeScript *experimental decorators* and the stage 3 *standard decorators*
(TS 5.2+, Babel `"version": "2023-05"`). They are not interchangeable: with standard decorators,
`@property` and `@state` must decorate an `accessor` field, or the property is never reactive.

**Incorrect (standard decorators without `accessor`):**

```typescript
// tsconfig: "experimentalDecorators": false, "useDefineForClassFields": true
@customElement('my-component')
export class MyComponent extends LitElement {
  @property({ type: String }) label = ''; // compile error / no reactivity
}
```

**Correct (standard decorators):**

```typescript
// tsconfig.json
// { "compilerOptions": { "target": "es2022", "useDefineForClassFields": true } }
@customElement('my-component')
export class MyComponent extends LitElement {
  @property({ type: String }) accessor label = '';
  @state() private accessor _open = false;
}
```

**Correct (experimental decorators — Lit's current production recommendation):**

```typescript
// tsconfig.json
// { "compilerOptions": {
//     "experimentalDecorators": true,
//     "useDefineForClassFields": false } }
@customElement('my-component')
export class MyComponent extends LitElement {
  @property({ type: String }) label = '';
}
```

Rules:

- Pick one mode per project; never mix.
- With experimental decorators, `useDefineForClassFields` **must** be `false` (or `target` below
  `ES2022`), otherwise class fields shadow Lit's accessors and updates stop firing.
- `@query`, `@queryAll`, and `@eventOptions` work in both modes without `accessor`.
- Standard decorators currently produce larger, slower output than experimental ones; prefer
  experimental decorators unless the toolchain forces otherwise.

Reference: [lit.dev — Decorators](https://lit.dev/docs/components/decorators/)

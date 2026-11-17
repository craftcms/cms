---
title: Never Pass Untrusted Data to unsafeHTML or unsafeSVG
impact: CRITICAL
impactDescription: Direct XSS: injected markup executes with your origin's privileges
tags: security, xss, directives, sanitization
---

## Never Pass Untrusted Data to `unsafeHTML` or `unsafeSVG`

Lit escapes every ordinary template expression, so `html\`<p>${userInput}</p>\`` is safe. The
`unsafe*` directives opt out of that escaping entirely — anything reaching them is parsed as
markup.

**Incorrect:**

```typescript
import { unsafeHTML } from 'lit/directives/unsafe-html.js';

render() {
  // comment.body === '<img src=x onerror="fetch(`/api/keys`).then(...)">'
  return html`<div>${unsafeHTML(this.comment.body)}</div>`;
}
```

**Correct (escape by default — no directive needed):**

```typescript
render() {
  return html`<div>${this.comment.body}</div>`;
}
```

**Correct (rich text that must render as markup — sanitize first):**

```typescript
import DOMPurify from 'dompurify';
import { unsafeHTML } from 'lit/directives/unsafe-html.js';

render() {
  const clean = DOMPurify.sanitize(this.comment.body, { USE_PROFILES: { html: true } });
  return html`<div>${unsafeHTML(clean)}</div>`;
}
```

Related sinks in Lit templates:

- Never build a template string dynamically (`html(new Array(...))`, `eval`-style construction) —
  Lit's XSS guarantees depend on static template strings.
- Treat `href`/`src` bindings as sinks too: reject `javascript:` and `data:` URLs before binding.
- `unsafeSVG`, `unsafeStatic`, and `staticHtml` carry the same risk as `unsafeHTML`.
- Sanitize on the way *in* when possible, so the dangerous value never reaches component state.

Reference: [lit.dev — Security](https://lit.dev/docs/templates/expressions/#static-expressions)

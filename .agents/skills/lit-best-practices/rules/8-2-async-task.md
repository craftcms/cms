---
title: Use Task for Async Data
impact: HIGH
impactDescription: Removes hand-rolled loading flags and out-of-order response bugs
tags: async, task, data-fetching, race-conditions
---

## Use `Task` for Async Data

Hand-written fetch logic in `updated()`/`willUpdate()` reliably produces the same defects: a stale
response overwriting a newer one, no abort on disconnect, and three separate state flags.
`@lit/task` handles all of it.

**Incorrect:**

```typescript
@state() private _user?: User;
@state() private _loading = false;
@state() private _error?: unknown;

updated(changed: PropertyValues) {
  if (changed.has('userId')) {
    this._loading = true;
    fetch(`/api/users/${this.userId}`)  // no abort; late response wins
      .then((r) => r.json())
      .then((u) => { this._user = u; })
      .catch((e) => { this._error = e; })
      .finally(() => { this._loading = false; });
  }
}
```

**Correct:**

```typescript
import { Task } from '@lit/task';

@customElement('user-card')
export class UserCard extends LitElement {
  @property({ type: String }) userId = '';

  #userTask = new Task(this, {
    task: async ([userId], { signal }) => {
      if (!userId) return undefined;
      const res = await fetch(`/api/users/${userId}`, { signal });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return (await res.json()) as User;
    },
    args: () => [this.userId],
  });

  render() {
    return this.#userTask.render({
      pending: () => html`<p aria-busy="true">Loading…</p>`,
      complete: (user) => (user ? html`<h2>${user.name}</h2>` : nothing),
      error: (e) => html`<p role="alert">${(e as Error).message}</p>`,
    });
  }
}
```

`Task` reruns only when `args` change, aborts the in-flight request via `signal`, and keeps the
render branches exhaustive. For an async value rendered inline with no state machine, `until()`
is still the lighter choice.

Reference: [lit.dev — Async tasks](https://lit.dev/docs/data/task/)

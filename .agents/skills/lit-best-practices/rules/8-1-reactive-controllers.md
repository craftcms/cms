---
title: Extract Shared Logic into Reactive Controllers
impact: HIGH
impactDescription: Reusable, self-cleaning behavior without mixins or base-class inheritance
tags: controllers, composition, lifecycle, reuse
---

## Extract Shared Logic into Reactive Controllers

Cross-cutting behavior (media queries, resize observation, polling, keyboard shortcuts) does not
belong duplicated in every component or buried in a base class. Use a **reactive controller**: a
plain object attached with `addController` that hooks the host lifecycle and requests updates.

**Incorrect (logic duplicated per component, easy to leak):**

```typescript
@customElement('my-panel')
export class MyPanel extends LitElement {
  @state() private _narrow = false;
  #mq = window.matchMedia('(max-width: 600px)');
  #onChange = () => { this._narrow = this.#mq.matches; };

  connectedCallback() {
    super.connectedCallback();
    this.#mq.addEventListener('change', this.#onChange);
    this.#onChange();
  }
  // forget disconnectedCallback and the listener leaks
}
```

**Correct (one controller, reused everywhere):**

```typescript
import { ReactiveController, ReactiveControllerHost } from 'lit';

export class MediaQueryController implements ReactiveController {
  matches = false;
  #host: ReactiveControllerHost;
  #mq: MediaQueryList;

  constructor(host: ReactiveControllerHost, query: string) {
    this.#host = host;
    this.#mq = window.matchMedia(query);
    host.addController(this);
  }

  hostConnected() {
    this.#mq.addEventListener('change', this.#onChange);
    this.#onChange();
  }

  hostDisconnected() {
    this.#mq.removeEventListener('change', this.#onChange);
  }

  #onChange = () => {
    this.matches = this.#mq.matches;
    this.#host.requestUpdate();
  };
}

@customElement('my-panel')
export class MyPanel extends LitElement {
  #narrow = new MediaQueryController(this, '(max-width: 600px)');

  render() {
    return html`<div class=${this.#narrow.matches ? 'narrow' : 'wide'}><slot></slot></div>`;
  }
}
```

Controllers over mixins: composition instead of an inheritance chain, multiple instances per host,
and cleanup lives next to setup. Available hooks: `hostConnected`, `hostDisconnected`,
`hostUpdate`, `hostUpdated`.

Reference: [lit.dev — Reactive controllers](https://lit.dev/docs/composition/controllers/)

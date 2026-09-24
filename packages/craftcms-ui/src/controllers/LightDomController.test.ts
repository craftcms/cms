import {beforeEach, describe, expect, test} from 'vite-plus/test';
import {html, LitElement} from 'lit';
import {customElement} from 'lit/decorators.js';

import {hasSlotted, LightDomController} from './LightDomController';

@customElement('light-dom-host')
class LightDomHost extends LitElement {
  renders = 0;

  // Assigned for its side effect: the controller registers itself on the host.
  private _lightDom = new LightDomController(this, {
    onChange: () => this.changes++,
  });

  changes = 0;

  override render() {
    this.renders++;
    return html`<slot name="footer"></slot>`;
  }
}

function mount(markup: string): LightDomHost {
  document.body.innerHTML = markup;
  return document.body.firstElementChild as LightDomHost;
}

describe('hasSlotted', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  test('finds a direct child in the named slot', () => {
    const host = mount(
      '<light-dom-host><p slot="footer"></p></light-dom-host>'
    );
    expect(hasSlotted(host, 'footer')).toBe(true);
  });

  test('matches any of the names given', () => {
    const host = mount(
      '<light-dom-host><p slot="actions"></p></light-dom-host>'
    );
    expect(hasSlotted(host, 'footer', 'actions')).toBe(true);
  });

  test('ignores a matching slot nested deeper in the subtree', () => {
    const host = mount(
      '<light-dom-host><div><p slot="footer"></p></div></light-dom-host>'
    );
    expect(hasSlotted(host, 'footer')).toBe(false);
  });

  test('matches the default slot as the empty string', () => {
    const host = mount('<light-dom-host><p></p></light-dom-host>');
    expect(hasSlotted(host, '')).toBe(true);
  });
});

describe('LightDomController', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  test('syncs once on connect', async () => {
    const host = mount('<light-dom-host></light-dom-host>');
    await host.updateComplete;
    expect(host.changes).toBe(1);
  });

  test('re-renders when a child is added', async () => {
    const host = mount('<light-dom-host></light-dom-host>');
    await host.updateComplete;
    const before = host.renders;

    const child = document.createElement('p');
    child.slot = 'footer';
    host.append(child);
    await new Promise((resolve) => setTimeout(resolve, 0));
    await host.updateComplete;

    expect(host.renders).toBeGreaterThan(before);
    expect(hasSlotted(host, 'footer')).toBe(true);
  });

  test('re-renders when content moves between slots', async () => {
    const host = mount(
      '<light-dom-host><p slot="footer"></p></light-dom-host>'
    );
    await host.updateComplete;
    const before = host.renders;

    host.querySelector('p')!.slot = 'header';
    await new Promise((resolve) => setTimeout(resolve, 0));
    await host.updateComplete;

    expect(host.renders).toBeGreaterThan(before);
    expect(hasSlotted(host, 'footer')).toBe(false);
  });

  test('stops observing once disconnected', async () => {
    const host = mount('<light-dom-host></light-dom-host>');
    await host.updateComplete;
    host.remove();
    const after = host.changes;

    host.append(document.createElement('p'));
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(host.changes).toBe(after);
  });
});

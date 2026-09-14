import {LitElement, html} from 'lit';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import {HasSlotController} from './slot';

class SlotHost extends LitElement {
  readonly hasSlot = new HasSlotController(
    this,
    HasSlotController.DEFAULT_SLOT,
    'footer'
  );

  renderCount = 0;

  override render() {
    this.renderCount++;
    return html`<slot></slot><slot name="footer"></slot>`;
  }
}

customElements.define('test-slot-host', SlotHost);

async function createHost(innerHTML = ''): Promise<SlotHost> {
  const host = document.createElement('test-slot-host') as SlotHost;
  host.innerHTML = innerHTML;
  document.body.append(host);
  await host.updateComplete;
  return host;
}

/** Lets the `MutationObserver` deliver, then the host re-render. */
async function settle(host: SlotHost): Promise<void> {
  await new Promise((resolve) => setTimeout(resolve));
  await host.updateComplete;
}

afterEach(() => {
  document.body.innerHTML = '';
});

describe('HasSlotController', () => {
  it('detects a named slot', async () => {
    const host = await createHost('<div slot="footer">Footer</div>');
    expect(host.hasSlot.test('footer')).toBe(true);
  });

  it('reports an empty named slot', async () => {
    const host = await createHost('<div slot="other">Other</div>');
    expect(host.hasSlot.test('footer')).toBe(false);
  });

  it('only counts direct children', async () => {
    const host = await createHost(
      '<div><span slot="footer">Nested</span></div>'
    );
    expect(host.hasSlot.test('footer')).toBe(false);
  });

  it('detects default slot text and elements', async () => {
    expect((await createHost('Text')).hasSlot.test('[default]')).toBe(true);
    expect((await createHost('<p>Text</p>')).hasSlot.test('[default]')).toBe(
      true
    );
  });

  it('ignores whitespace and named slot content for the default slot', async () => {
    const host = await createHost('  <div slot="footer">Footer</div>  ');
    expect(host.hasSlot.test(HasSlotController.DEFAULT_SLOT)).toBe(false);
  });

  it('re-renders when a tracked slot gains or loses content', async () => {
    const host = await createHost();
    const rendersBefore = host.renderCount;

    const footer = document.createElement('div');
    footer.slot = 'footer';
    host.append(footer);
    await settle(host);
    expect(host.hasSlot.test('footer')).toBe(true);
    expect(host.renderCount).toBe(rendersBefore + 1);

    footer.remove();
    await settle(host);
    expect(host.hasSlot.test('footer')).toBe(false);
    expect(host.renderCount).toBe(rendersBefore + 2);
  });

  it('re-renders when a child is re-slotted in place', async () => {
    const host = await createHost('<div slot="other">Footer</div>');
    const rendersBefore = host.renderCount;

    host.querySelector('div')!.slot = 'footer';
    await settle(host);

    expect(host.renderCount).toBe(rendersBefore + 1);
  });

  it('does not re-render when presence is unchanged', async () => {
    const host = await createHost('<div slot="footer">Footer</div>');
    const rendersBefore = host.renderCount;

    host.querySelector('div')!.textContent = 'Updated';
    host.append(Object.assign(document.createElement('div'), {slot: 'other'}));
    await settle(host);

    expect(host.renderCount).toBe(rendersBefore);
  });

  it('stops observing once disconnected', async () => {
    const host = await createHost();
    host.remove();
    const rendersBefore = host.renderCount;

    host.append(Object.assign(document.createElement('div'), {slot: 'footer'}));
    await settle(host);

    expect(host.renderCount).toBe(rendersBefore);
  });
});

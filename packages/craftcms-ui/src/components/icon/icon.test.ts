import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { html } from 'lit';
import type CraftIcon from './icon.js';
import './icon.js';
import { defaultIconResolver, setIconResolver } from '../../utilities/icons.js';

const SVG_BODY = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M0 0h448v512H0z"/></svg>';

function stubFetch(body = SVG_BODY, ok = true) {
  const mock = vi.fn().mockResolvedValue({
    ok,
    text: () => Promise.resolve(body),
  } as Response);
  vi.stubGlobal('fetch', mock);
  return mock;
}

async function createIcon(attrs: Record<string, string> = {}): Promise<CraftIcon> {
  const element = document.createElement('craft-icon') as CraftIcon;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  document.body.append(element);
  await element.updateComplete;
  // Wait out the async fetch + re-render.
  await new Promise((resolve) => setTimeout(resolve));
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

afterEach(() => {
  vi.unstubAllGlobals();
  setIconResolver(defaultIconResolver);
});

describe('craft-icon', () => {
  it('fetches the icon from the solid folder by default', async () => {
    const fetchMock = stubFetch();
    await createIcon({ name: 'pencil' });
    expect(fetchMock).toHaveBeenCalledWith('/vendor/craft/icons/solid/pencil.svg', {
      mode: 'cors',
    });
  });

  it('resolves variant-prefixed names', async () => {
    const fetchMock = stubFetch();
    await createIcon({ name: 'custom-icons/graphql' });
    expect(fetchMock).toHaveBeenCalledWith('/vendor/craft/icons/custom-icons/graphql.svg', {
      mode: 'cors',
    });
  });

  it('renders the fetched svg with fill=currentColor', async () => {
    stubFetch();
    const element = await createIcon({ name: 'house' });
    const svg = element.shadowRoot!.querySelector('svg');
    expect(svg).not.toBeNull();
    expect(svg!.getAttribute('fill')).toBe('currentColor');
  });

  it('resolves icons through the configured Lion icon resolver', async () => {
    const fetchMock = stubFetch();
    setIconResolver(
      (name, family, variant) => html` <svg data-name=${name} data-family=${family} data-variant=${variant}></svg> `,
    );

    const element = await createIcon({
      name: 'github',
      family: 'brands',
      variant: 'regular',
    });
    const svg = element.shadowRoot!.querySelector('svg');

    expect(fetchMock).not.toHaveBeenCalled();
    expect(svg!.dataset.name).toBe('github');
    expect(svg!.dataset.family).toBe('brands');
    expect(svg!.dataset.variant).toBe('regular');
  });

  it('only fetches once per URL', async () => {
    const fetchMock = stubFetch();
    await createIcon({ name: 'clock' });
    await createIcon({ name: 'clock' });
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it('is aria-hidden without a label', async () => {
    stubFetch();
    const element = await createIcon({ name: 'house' });
    expect(element.getAttribute('aria-hidden')).toBe('true');
    expect(element.hasAttribute('role')).toBe(false);
  });

  it('exposes role=img and aria-label with a label', async () => {
    stubFetch();
    const element = await createIcon({ name: 'house', label: 'Home' });
    expect(element.getAttribute('role')).toBe('img');
    expect(element.getAttribute('aria-label')).toBe('Home');
    expect(element.hasAttribute('aria-hidden')).toBe(false);
  });

  it('suppresses the fetched icon when svg content is slotted', async () => {
    stubFetch();
    const element = document.createElement('craft-icon') as CraftIcon;
    element.setAttribute('name', 'house');
    element.innerHTML = '<svg viewBox="0 0 24 24"></svg>';
    document.body.append(element);
    await element.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));
    await element.updateComplete;
    // The shadow root should contain the slot but not a fetched svg sibling.
    expect(element.shadowRoot!.querySelector('svg')).toBeNull();
  });

  it('defaults badges to the warning color', async () => {
    stubFetch();
    const element = await createIcon({ name: 'house', appearance: 'badge' });
    expect(element.getAttribute('data-color')).toBe('warning');
  });
});

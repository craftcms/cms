import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import type {BreadcrumbItem} from '@/common/types';
import Breadcrumbs from './Breadcrumbs.vue';

let teardown: (() => void) | undefined;

function mount(items: Array<BreadcrumbItem>): HTMLElement {
  const container = document.createElement('div');
  document.body.append(container);

  const app = createApp({render: () => h(Breadcrumbs, {items})});
  app.mount(container);

  teardown = () => {
    app.unmount();
    container.remove();
  };

  return container;
}

function links(container: HTMLElement): Array<string | null> {
  return [...container.querySelectorAll('craft-breadcrumb-item')].map(
    (item) => item.querySelector('a')?.getAttribute('href') ?? null
  );
}

function linkClasses(container: HTMLElement): Array<string | null> {
  return [...container.querySelectorAll('craft-breadcrumb-item')].map(
    (item) => item.querySelector('a')?.getAttribute('class') ?? null
  );
}

afterEach(() => {
  teardown?.();
  teardown = undefined;
});

describe('Breadcrumbs', () => {
  it('links a crumb however its producer spelled the link', async () => {
    const container = mount([
      // `href` is what `Cp\Data\ActionItem` sends — the same spelling HTML
      // uses — while `url` is what the navigation and the legacy templates
      // used to call it, still accepted for a producer written that way.
      {label: 'Settings', href: '/admin/settings'},
      {label: 'Utilities', url: '/admin/utilities'},
      {label: 'Deprecation Warnings'},
    ]);
    await nextTick();

    expect(links(container)).toEqual([
      '/admin/settings',
      '/admin/utilities',
      null,
    ]);
  });

  it('prefers href when a crumb carries both', async () => {
    const container = mount([
      {label: 'Settings', href: '/admin/settings', url: '/stale'},
    ]);
    await nextTick();

    expect(links(container)).toEqual(['/admin/settings']);
  });

  it('underlines linked crumbs so they are not identified by color alone', async () => {
    const container = mount([
      {label: 'Settings', href: '/admin/settings'},
      {label: 'Deprecation Warnings'},
    ]);
    await nextTick();

    const classes = linkClasses(container);
    expect(classes[0]).toContain('cp-link--underline');
    expect(classes[1]).toBeNull();
  });
});

describe('Breadcrumbs switcher', () => {
  it('invokes the switcher with an xsmall button', async () => {
    const container = mount([
      {
        label: 'All entries',
        href: '/admin/content/entries',
        items: [{type: 'link', label: 'Singles', href: '/admin/singles'}],
      },
    ]);
    await nextTick();

    const invoker = container.querySelector('craft-button');

    // Smaller than the crumb text beside it, with its target area floored by
    // the size rather than the visible box.
    expect(invoker?.getAttribute('size')).toBe('xsmall');
  });
});

it('does not pull the switcher flush against the crumb label', async () => {
  const container = mount([
    {
      label: 'All entries',
      href: '/admin/content/entries',
      items: [{type: 'link', label: 'Singles', href: '/admin/singles'}],
    },
  ]);
  await nextTick();

  const invoker = container.querySelector('craft-button')!;
  await (invoker as unknown as {updateComplete?: Promise<unknown>})
    .updateComplete;

  // `flush` puts a negative inline margin on both sides of the invoker, which
  // eats the gap the breadcrumb item leaves before its suffix.
  expect(invoker.hasAttribute('flush')).toBe(false);
});

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
});

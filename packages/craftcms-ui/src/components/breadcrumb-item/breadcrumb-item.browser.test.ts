import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftBreadcrumbItem from './breadcrumb-item.js';
import './breadcrumb-item.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-breadcrumb-item accessibility', () => {
  it('underlines a linked crumb persistently, not only on hover', async () => {
    const element = document.createElement(
      'craft-breadcrumb-item'
    ) as CraftBreadcrumbItem;
    element.setAttribute('href', '/entries');
    element.textContent = 'Entries';
    document.body.append(element);
    await element.updateComplete;

    const link = element.shadowRoot!.querySelector('a.label')!;
    expect(getComputedStyle(link).textDecorationLine).toBe('underline');
  });

  it('does not underline a crumb with no href', async () => {
    const element = document.createElement(
      'craft-breadcrumb-item'
    ) as CraftBreadcrumbItem;
    element.textContent = 'Entries';
    document.body.append(element);
    await element.updateComplete;

    expect(element.shadowRoot!.querySelector('a.label')).toBeNull();
    const label = element.shadowRoot!.querySelector('span.label')!;
    expect(getComputedStyle(label).textDecorationLine).toBe('none');
  });
});

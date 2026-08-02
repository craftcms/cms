import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import './object-select.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-object-select', () => {
  it('adds, removes, and reorders selected objects by their stable identity', async () => {
    const article = {uid: 'article', name: 'Article'};
    const page = {uid: 'page', name: 'Page'};
    const news = {uid: 'news', name: 'News'};
    const element = document.createElement('craft-object-select');
    const listener = vi.fn();

    element.identityKey = 'uid';
    element.options = [
      {key: 'article', label: 'Article', value: article},
      {key: 'page', label: 'Page', value: page},
      {key: 'news', label: 'News', value: news},
    ];
    element.value = [article, page];
    element.addEventListener('input', listener);
    document.body.append(element);
    await element.updateComplete;

    element
      .shadowRoot!.querySelector(
        '[data-object-select-row="article"] craft-reorder-button'
      )!
      .dispatchEvent(
        new CustomEvent('reorder', {
          bubbles: true,
          detail: {direction: 'down'},
        })
      );
    await element.updateComplete;

    expect(element.value).toEqual([page, article]);

    const select = element.shadowRoot!.querySelector<HTMLSelectElement>(
      '[data-object-select-available]'
    )!;

    select.value = 'news';
    select.dispatchEvent(new Event('change', {bubbles: true}));
    element
      .shadowRoot!.querySelector('[data-object-select-add]')!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await element.updateComplete;

    expect(element.value).toEqual([page, article, news]);
    expect(
      element.shadowRoot!.querySelector('[data-object-select-add]')
    ).toBeNull();

    element
      .shadowRoot!.querySelector(
        '[data-object-select-row="page"] [data-object-select-remove]'
      )!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await element.updateComplete;

    expect(element.value).toEqual([article, news]);
    expect(listener).toHaveBeenCalledTimes(3);
  });

  it('exposes accessible read-only selection without accepting updates', async () => {
    const article = {uid: 'article', name: 'Article'};
    const page = {uid: 'page', name: 'Page'};
    const element = document.createElement('craft-object-select');
    const listener = vi.fn();

    element.identityKey = 'uid';
    element.options = [
      {key: 'article', label: 'Article', value: article},
      {key: 'page', label: 'Page', value: page},
    ];
    element.value = [article];
    element.readOnly = true;
    element.setAttribute('aria-labelledby', 'entry-types-label');
    element.setAttribute('aria-describedby', 'entry-types-help');
    element.addEventListener('input', listener);
    document.body.append(element);
    await element.updateComplete;

    expect(element.getAttribute('role')).toBe('group');
    expect(element.getAttribute('aria-readonly')).toBe('true');
    expect(element.getAttribute('aria-labelledby')).toBe('entry-types-label');
    expect(element.getAttribute('aria-describedby')).toBe('entry-types-help');
    expect(
      element
        .shadowRoot!.querySelector('[data-object-select-remove]')!
        .getAttribute('aria-label')
    ).toBe('Remove Article');

    element
      .shadowRoot!.querySelector('[data-object-select-add]')!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    element
      .shadowRoot!.querySelector('[data-object-select-remove]')!
      .dispatchEvent(new CustomEvent('activate', {bubbles: true}));
    await element.updateComplete;

    expect(element.value).toEqual([article]);
    expect(listener).not.toHaveBeenCalled();
  });

  it('submits selected object values under the host-provided input name', async () => {
    const form = document.createElement('form');
    const element = document.createElement('craft-object-select');

    element.name = 'settings[entryTypes]';
    element.identityKey = 'uid';
    element.value = [
      {uid: 'article', siteSettings: {english: {uriFormat: 'articles/{slug}'}}},
    ];
    form.append(element);
    document.body.append(form);
    await element.updateComplete;

    const data = new FormData(form);

    expect(data.get('settings[entryTypes][0][uid]')).toBe('article');
    expect(
      data.get('settings[entryTypes][0][siteSettings][english][uriFormat]')
    ).toBe('articles/{slug}');
  });
});

import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import './element-condition.js';

beforeEach(() => {
  document.body.innerHTML = '';
  (window as any).Craft = {initUiElements: vi.fn()};
  (window as any).htmx = {process: vi.fn()};
});

afterEach(() => {
  vi.unstubAllGlobals();
  document.head
    .querySelectorAll('[data-condition-asset]')
    .forEach((asset) => asset.remove());
});

describe('craft-element-condition', () => {
  it('initializes and synchronizes a declarative server-rendered builder', async () => {
    const form = document.createElement('form');
    const element = document.createElement('craft-element-condition');
    const listener = vi.fn();

    element.setAttribute('name', 'condition');
    element.setAttribute('condition-class', 'EntryCondition');
    element.setAttribute('builder-config', '{"elementType":"entry"}');
    element.setAttribute('sortable', 'false');
    element.innerHTML = `
      <div class="condition-main">
        <input type="hidden" name="condition[class]" value="EntryCondition">
        <input type="hidden" name="condition[conditionRules][0][type]" value="title">
      </div>
    `;
    element.addEventListener('model-value-changed', listener);
    form.append(element);
    document.body.append(form);
    await element.updateComplete;

    expect(element.getAttribute('role')).toBe('group');
    expect(element.name).toBe('condition');
    expect(element.conditionClass).toBe('EntryCondition');
    expect(element.builderConfig).toEqual({elementType: 'entry'});
    expect(element.sortable).toBe(false);
    expect((window as any).Craft.initUiElements).toHaveBeenCalledWith(element);
    expect((window as any).htmx.process).toHaveBeenCalledWith(element);

    const rule = document.createElement('input');
    rule.type = 'hidden';
    rule.name = 'condition[conditionRules][1][type]';
    rule.value = 'slug';
    element.querySelector('.condition-main')!.append(rule);

    await vi.waitFor(() => {
      expect(element.modelValue?.conditionRules).toEqual([
        {type: 'title'},
        {type: 'slug'},
      ]);
    });

    expect(listener).toHaveBeenCalledTimes(1);
  });

  it('blocks read-only interaction and disables controls added later', async () => {
    const element = document.createElement('craft-element-condition');
    const input = document.createElement('input');
    const activate = vi.fn();

    element.readOnly = true;
    input.addEventListener('click', activate);
    element.append(input);
    document.body.append(element);
    await element.updateComplete;

    input.click();

    expect(element.getAttribute('aria-disabled')).toBe('true');
    expect(input.disabled).toBe(true);
    expect(activate).not.toHaveBeenCalled();

    const button = document.createElement('craft-button');
    element.append(button);

    await vi.waitFor(() => expect(button.disabled).toBe(true));

    element.readOnly = false;
    await element.updateComplete;

    expect(element.hasAttribute('aria-disabled')).toBe(false);
    expect(input.disabled).toBe(false);
    expect(button.disabled).toBe(false);
  });

  it('renders a remote builder and owns its serialized value', async () => {
    const conditionClass = 'craft\\elements\\conditions\\ElementCondition';

    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(
        new Response(`
          <template class="hx-head-html"><meta data-condition-asset="head"></template>
          <template class="hx-body-html"><div data-condition-asset="body"></div></template>
          <div data-condition-builder>
            <input type="hidden" name="settings[condition][class]" value="${conditionClass}">
            <input type="hidden" name="settings[condition][conditionRules][0][type]" value="title">
          </div>
        `)
      )
    );
    (window as any).Craft._actionHeaders = () => ({
      'X-CSRF-Token': 'token',
    });
    const form = document.createElement('form');
    const element = document.createElement('craft-element-condition');
    const listener = vi.fn();

    element.id = 'entry-condition';
    element.name = 'settings[condition]';
    element.conditionClass = conditionClass;
    element.builderConfig = {elementType: 'craft\\elements\\Entry'};
    element.renderUrl = '/actions/conditions/render';
    element.addRuleLabel = 'Add a condition';
    element.modelValue = {
      class: conditionClass,
      conditionRules: [{type: 'title'}],
    };
    element.addEventListener('model-value-changed', listener);
    form.append(element);
    document.body.append(form);

    await vi.waitFor(() => {
      expect(element.querySelector('[data-condition-builder]')).not.toBeNull();
    });

    const [, options = {}] = vi.mocked(fetch).mock.calls[0]!;
    const body = options.body as FormData;
    const config = JSON.parse(String(body.get('config')));

    expect(options.headers).toMatchObject({
      Accept: 'text/html',
      'HX-Request': 'true',
      'X-CSRF-Token': 'token',
    });
    expect(config).toMatchObject({
      class: conditionClass,
      id: 'entry-condition',
      name: 'settings[condition]',
      sortable: true,
      forProjectConfig: true,
      addRuleLabel: 'Add a condition',
      elementType: 'craft\\elements\\Entry',
    });
    expect(body.get('settings[condition][conditionRules][0][type]')).toBe(
      'title'
    );
    expect(
      document.head.querySelector('[data-condition-asset]')
    ).not.toBeNull();
    expect(
      document.body.querySelector(':scope > [data-condition-asset]')
    ).not.toBeNull();
    expect((window as any).Craft.initUiElements).toHaveBeenCalledWith(element);
    expect((window as any).htmx.process).toHaveBeenCalledWith(element);

    const secondRule = document.createElement('input');
    secondRule.type = 'hidden';
    secondRule.name = 'settings[condition][conditionRules][1][type]';
    secondRule.value = 'slug';
    element.querySelector('[data-condition-builder]')!.append(secondRule);

    await vi.waitFor(() => {
      expect(element.modelValue?.conditionRules).toEqual([
        {type: 'title'},
        {type: 'slug'},
      ]);
    });

    expect(listener).toHaveBeenCalledTimes(1);
  });
});

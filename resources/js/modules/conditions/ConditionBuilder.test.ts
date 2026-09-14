import {createApp, nextTick, type App} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui';
import type CraftActionMenu from '@craftcms/ui/components/action-menu/action-menu';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {registerFormComponents} from '@/modules/forms/register';
import {expandFormData} from '@/common/utils/forms';
import type {BuilderPayload, RulePayload, GroupConfig} from './types';
import ConditionBuilder from './ConditionBuilder.vue';
import {defineConditionBuilderHost} from './condition-builder-host';

function rule(
  value = 'Alpha',
  uid: string = crypto.randomUUID(),
  type = 'Title'
): RulePayload {
  const scope = ['_conditionRules', uid];

  return {
    config: {class: type, uid, operator: '=', value},
    label: type,
    hint: null,
    showHint: false,
    form: {
      scope,
      refreshable: true,
      nodes: [
        {
          type: 'Field',
          component: 'craft:field',
          props: {label: 'Operator'},
          control: {
            type: 'Choice',
            component: 'craft:choice',
            props: {
              options: [
                {label: 'equals', value: '='},
                {label: 'contains', value: '**'},
              ],
              multiple: false,
              presentation: 'select',
            },
            path: [...scope, 'operator'],
            mode: 'editable',
            deltaGroup: [...scope, 'operator'],
            reactive: true,
          },
        },
        {
          type: 'Field',
          component: 'craft:field',
          props: {label: type},
          control: {
            type: 'Text',
            component: 'craft:text',
            props: {},
            path: [...scope, 'value'],
            mode: 'editable',
            deltaGroup: [...scope, 'value'],
          },
        },
      ],
      values: {_conditionRules: {[uid]: {operator: '=', value}}},
      errors: [],
      globalErrors: [],
    },
  };
}

function response(payload = rule()) {
  return {data: {rule: payload, headHtml: '', bodyHtml: ''}};
}

function builder(rules: RulePayload[] = []): BuilderPayload {
  return {
    config: {class: 'Entry'},
    value: {
      class: 'Entry',
      conditionRules: {
        operator: 'and',
        rules: rules.map((rule) => rule.config),
      },
    },
    rules: Object.fromEntries(rules.map((rule) => [rule.config.uid, rule])),
    ruleTypes: ['Title', 'Slug'].map((value) => ({
      value,
      label: value,
      hint: null,
      showHint: false,
      group: null,
    })),
    addRuleLabel: 'Add a rule',
  };
}

const attachInternals = Object.getOwnPropertyDescriptor(
  HTMLElement.prototype,
  'attachInternals'
);

let app: App | undefined;
let editor: {validate: () => Promise<boolean>};
let form: HTMLFormElement;
let container: HTMLElement;

const components = createCpComponentRegistry();
registerFormComponents(components);
defineConditionBuilderHost(components);

beforeEach(() => {
  Object.defineProperty(HTMLElement.prototype, 'attachInternals', {
    configurable: true,
    value: () => ({setFormValue: vi.fn()}),
  });
  vi.stubGlobal('confirm', vi.fn().mockReturnValue(true));
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));

  form = document.createElement('form');
  container = document.createElement('div');
  form.append(container);
  document.body.append(form);
});

afterEach(async () => {
  if (app) {
    components.uninstall(app);
    app.unmount();
    app = undefined;
  }

  form.remove();
  await nextTick();
  vi.restoreAllMocks();
  vi.unstubAllGlobals();

  if (attachInternals) {
    Object.defineProperty(
      HTMLElement.prototype,
      'attachInternals',
      attachInternals
    );
  } else {
    Reflect.deleteProperty(HTMLElement.prototype, 'attachInternals');
  }
});

async function mount(payload = builder(), editable = true): Promise<void> {
  app = createApp(ConditionBuilder, {payload, name: 'condition', editable});
  components.install(app);
  editor = app.mount(container) as unknown as typeof editor;
  await nextTick();
}

function button(label: string, parent: Element = container): HTMLElement {
  const result = [...parent.querySelectorAll<HTMLElement>('craft-button')].find(
    (element) =>
      element.textContent?.trim() === label ||
      element.getAttribute('aria-label') === label
  );

  if (!result) throw new Error(`Missing button: ${label}`);

  return result;
}

async function selectType(label: string, menuIndex = 0): Promise<void> {
  const menu =
    container.querySelectorAll<CraftActionMenu>('craft-action-menu')[
      menuIndex
    ]!;
  await vi.waitFor(() =>
    expect(menu.querySelector('craft-action-item')).not.toBeNull()
  );

  const item = [
    ...menu.querySelectorAll<HTMLElement>('craft-action-item'),
  ].find((item) => item.textContent === label)!;
  item.click();
  await nextTick();
}

async function changeOperator(): Promise<void> {
  const operator = container.querySelector<HTMLSelectElement>(
    '.condition-rule select'
  )!;
  operator.value = '**';
  operator.dispatchEvent(new Event('change', {bubbles: true}));
  await nextTick();
}

function submitted(): GroupConfig {
  return (
    expandFormData(new FormData(form)).condition as {
      conditionRules: GroupConfig;
    }
  ).conditionRules;
}

it('edits nested operators locally and prunes empty groups only in submitted values', async () => {
  const post = vi.spyOn(actionClient, 'request');
  await mount();
  button('Any').click();
  button('Add a group').click();
  await nextTick();

  expect(container.querySelectorAll('.condition-group')).toHaveLength(2);
  expect(submitted().operator).toBe('or');
  expect(submitted().rules).toBeUndefined();
  expect(post).not.toHaveBeenCalled();

  const confirm = vi.spyOn(window, 'confirm');
  button('Remove group').click();
  await nextTick();
  expect(container.querySelectorAll('.condition-group')).toHaveLength(1);
  expect(confirm).not.toHaveBeenCalled();
});

it('adds repeated rules inside a group and confirms removal of a populated group', async () => {
  vi.spyOn(actionClient, 'request').mockImplementation(async () => response());
  await mount();
  button('Add a group').click();
  await nextTick();
  await selectType('Title');
  await vi.waitFor(() =>
    expect(container.querySelectorAll('.condition-rule')).toHaveLength(1)
  );
  await selectType('Title', 1);
  await vi.waitFor(() =>
    expect(container.querySelectorAll('.condition-rule')).toHaveLength(2)
  );

  const nested = submitted().rules[0] as GroupConfig;
  expect(nested.rules).toHaveLength(2);
  expect(nested.rules[0]!.uid).not.toBe(nested.rules[1]!.uid);

  const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);
  button('Remove group').click();
  await nextTick();
  expect(container.querySelectorAll('.condition-rule')).toHaveLength(2);

  confirm.mockReturnValue(true);
  button('Remove group').click();
  await nextTick();
  expect(container.querySelectorAll('.condition-rule')).toHaveLength(0);
});

it('retains values while switching type and replaces the menu label', async () => {
  const initial = rule();
  const post = vi
    .spyOn(actionClient, 'request')
    .mockResolvedValue(response(rule('Alpha', initial.config.uid, 'Slug')));
  await mount(builder([initial]));
  await selectType('Slug');
  await vi.waitFor(() => expect(submitted().rules[0]?.class).toBe('Slug'));
  expect(post.mock.calls[0]?.[0]?.data).toMatchObject({
    rule: {class: 'Title', type: 'Slug', value: 'Alpha'},
  });
  expect(button('Slug')).toBeDefined();
  expect(submitted().rules[0]?.value).toBe('Alpha');
});

it.each([
  [new Error('Offline'), 'Couldn’t update the condition rule.'],
  [
    {
      isAxiosError: true,
      response: {data: {message: 'The selected condition rule is invalid.'}},
    },
    'The selected condition rule is invalid.',
  ],
])(
  'keeps edits and blocks submission after %s until a subsequent update succeeds',
  async (failure, message) => {
    const initial = rule();
    const post = vi
      .spyOn(actionClient, 'request')
      .mockRejectedValueOnce(failure)
      .mockResolvedValueOnce(
        response(rule('Alpha', initial.config.uid, 'Slug'))
      );
    await mount(builder([initial]));
    await selectType('Slug');
    await vi.waitFor(() =>
      expect(container.querySelector('[role="alert"]')?.textContent).toContain(
        message
      )
    );
    expect(submitted().rules[0]?.class).toBe('Title');
    expect(
      form.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}))
    ).toBe(false);

    await selectType('Slug');
    await vi.waitFor(() => expect(submitted().rules[0]?.class).toBe('Slug'));
    expect(post).toHaveBeenCalledTimes(2);
    expect(container.querySelector('[role="alert"]')).toBeNull();
    expect(form.dispatchEvent(new Event('submit', {cancelable: true}))).toBe(
      true
    );
  }
);

it('preserves added rules and typed values when the native host disconnects and reconnects', async () => {
  vi.spyOn(actionClient, 'request').mockResolvedValue(response());
  const host = document.createElement('craft-condition-builder');
  host.dataset.payload = JSON.stringify(builder());
  host.dataset.name = 'condition';
  container.append(host);
  await nextTick();
  await selectType('Title');
  await vi.waitFor(() =>
    expect(host.querySelector('craft-input input')).not.toBeNull()
  );

  const input = host.querySelector<HTMLInputElement>('craft-input input')!;
  input.value = 'Retained';
  input.dispatchEvent(new Event('input', {bubbles: true}));
  await vi.waitFor(() => expect(submitted().rules[0]?.value).toBe('Retained'));

  host.remove();
  await nextTick();
  container.append(host);
  await nextTick();
  await vi.waitFor(() =>
    expect(
      host.querySelector<HTMLInputElement>('craft-input input')?.value
    ).toBe('Retained')
  );
  expect(submitted().rules[0]?.value).toBe('Retained');
});

it('keeps later input while a reactive operator request is pending and becomes submittable again', async () => {
  const initial = rule();
  let resolve!: (response: unknown) => void;
  vi.spyOn(actionClient, 'request').mockImplementation(
    () =>
      new Promise((done) => {
        resolve = done;
      })
  );
  await mount(builder([initial]));
  await changeOperator();
  expect(form.dispatchEvent(new Event('submit', {cancelable: true}))).toBe(
    false
  );

  const input = container.querySelector<HTMLInputElement>('craft-input input')!;
  input.value = 'Later input';
  input.dispatchEvent(new Event('input', {bubbles: true}));
  await vi.waitFor(() =>
    expect(submitted().rules[0]?.value).toBe('Later input')
  );

  resolve(response(initial));
  await vi.waitFor(() =>
    expect(container.querySelector('[aria-busy="true"]')).toBeNull()
  );
  expect(submitted().rules[0]?.operator).toBe('**');
  expect(input.value).toBe('Later input');
  expect(form.dispatchEvent(new Event('submit', {cancelable: true}))).toBe(
    true
  );
});

it('does not allow edits or submit values in disabled mode', async () => {
  await mount(builder([rule()]), false);
  await vi.waitFor(() =>
    expect(
      container.querySelector<HTMLInputElement>('craft-input input')?.disabled
    ).toBe(true)
  );
  expect([...new FormData(form).entries()]).toHaveLength(0);
  expect(
    container.querySelector('craft-button[aria-label="Remove"]')
  ).toBeNull();
});

it('blocks native submission when a rule Form provider cannot render', async () => {
  const initial = rule();
  initial.form.nodes[0] = {
    ...initial.form.nodes[0]!,
    control: {...initial.form.nodes[0]!.control!, component: 'missing:control'},
  };
  await mount(builder([initial]));
  await vi.waitFor(() =>
    expect(container.querySelector('[role="alert"]')).not.toBeNull()
  );
  expect(form.dispatchEvent(new Event('submit', {cancelable: true}))).toBe(
    false
  );
});

it('aborts an operator refresh when switching type and ignores its late response', async () => {
  const initial = rule();
  let finish!: () => void;
  const post = vi
    .spyOn(actionClient, 'request')
    .mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          finish = () => resolve(response(initial));
        })
    )
    .mockResolvedValueOnce(response(rule('Alpha', initial.config.uid, 'Slug')));
  await mount(builder([initial]));
  await changeOperator();

  await selectType('Slug');
  await vi.waitFor(() => expect(submitted().rules[0]?.class).toBe('Slug'));
  expect(post.mock.calls[0]?.[0]?.cancelToken?.reason).toBeDefined();

  finish();
  await post.mock.results[0]!.value;
  await nextTick();
  expect(submitted().rules[0]?.class).toBe('Slug');
  expect(container.querySelector('[role="alert"]')).toBeNull();
  expect(form.dispatchEvent(new Event('submit', {cancelable: true}))).toBe(
    true
  );
});

it('aborts a pending refresh when its rule is removed', async () => {
  let reject!: (reason: unknown) => void;
  const pending = new Promise<never>((_resolve, fail) => {
    reject = fail;
  });
  const post = vi.spyOn(actionClient, 'request').mockReturnValueOnce(pending);
  await mount(builder([rule()]));
  await changeOperator();
  const cancelToken = post.mock.calls[0]?.[0]?.cancelToken;
  button('Remove').click();
  await nextTick();
  expect(cancelToken?.reason).toBeDefined();

  reject(new DOMException('Aborted', 'AbortError'));
  await vi.waitFor(() =>
    expect(container.querySelector('.condition-rule')).toBeNull()
  );
  expect(container.querySelector('[role="alert"]')).toBeNull();
  expect(form.dispatchEvent(new Event('submit', {cancelable: true}))).toBe(
    true
  );
});

it('shows rule validation errors on apply and allows a successful retry', async () => {
  const initial = rule();
  vi.spyOn(actionClient, 'request')
    .mockRejectedValueOnce({
      isAxiosError: true,
      response: {
        data: {
          errors: {
            [`_conditionRules.${initial.config.uid}.value`]: ['Invalid value.'],
          },
        },
      },
    })
    .mockResolvedValueOnce({data: {valid: true}});
  await mount(builder([initial]));

  expect(await editor.validate()).toBe(false);
  await nextTick();
  expect(container.textContent).toContain('Invalid value.');
  expect(submitted().rules[0]?.value).toBe('Alpha');

  expect(await editor.validate()).toBe(true);
  await nextTick();
  expect(container.textContent).not.toContain('Invalid value.');
});

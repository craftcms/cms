import {afterEach, expect, it, vi} from 'vite-plus/test';
import {nextTick} from 'vue';
import {serializeFormInputs} from '@craftcms/ui/utilities/dom';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FieldNode from './FieldNode.vue';
import TextControl from './TextControl.vue';
import DateTimeControl from './DateTimeControl.vue';
import {
  defineInlineAttributeFormHost,
  type InlineAttributeFormHost,
} from './inline-attribute-form-host';
import type {FormPayload} from './types';

const components = createCpComponentRegistry();
components.register('craft:field', FieldNode);
components.register('craft:text', TextControl);
components.register('craft:date-time', DateTimeControl);
defineInlineAttributeFormHost(components);

afterEach(() => {
  document.body.replaceChildren();
  vi.restoreAllMocks();
});

function mountInput(payload: FormPayload): InlineAttributeFormHost {
  const host = document.createElement(
    'craft-inline-attribute-form'
  ) as InlineAttributeFormHost;
  host.dataset.payload = JSON.stringify(payload);
  document.body.append(host);

  return host;
}

function textPayload(mode: 'editable' | 'disabled' = 'editable'): FormPayload {
  return {
    scope: ['index', 'element-42', 'fields'],
    refreshable: false,
    nodes: [
      {
        type: 'Field',
        component: 'craft:field',
        props: {},
        control: {
          type: 'Text',
          component: 'craft:text',
          path: ['index', 'element-42', 'fields', 'summary'],
          deltaGroup: ['index', 'element-42', 'fields', 'summary'],
          mode,
          props: {},
        },
      },
    ],
    values: {index: {'element-42': {fields: {summary: 'Original & value=1'}}}},
    errors: [],
    globalErrors: [],
  };
}

it('waits for posting inputs before capturing the baseline and serializes edits', async () => {
  const host = mountInput(textPayload());
  await host.ready;
  const baseline = serializeFormInputs(host);

  expect(
    new URLSearchParams(baseline).get('index[element-42][fields][summary]')
  ).toBe('Original & value=1');

  const input = host.querySelector('craft-input');
  if (!input) throw new Error('Expected the Form text control.');
  input.modelValue = 'Edited & value=2';
  input.dispatchEvent(new CustomEvent('model-value-changed', {bubbles: true}));
  await nextTick();
  await input.updateComplete;

  expect(
    new URLSearchParams(serializeFormInputs(host)).get(
      'index[element-42][fields][summary]'
    )
  ).toBe('Edited & value=2');
  expect(serializeFormInputs(host)).not.toBe(baseline);
});

it('omits disabled controls from row submissions', async () => {
  const host = mountInput(textPayload('disabled'));
  await host.ready;

  expect(serializeFormInputs(host)).toBe('');
});

it('prevents submission when a Form control cannot be rendered', async () => {
  const payload = textPayload();
  const host = mountInput({
    ...payload,
    nodes: [
      {
        ...payload.nodes[0]!,
        control: {...payload.nodes[0]!.control!, component: 'missing:control'},
      },
    ],
  });
  await host.ready;

  expect(host.canSubmit()).toBe(false);
  expect(host.textContent).toContain('missing:control');
});

it('shows field validation errors without discarding edits', async () => {
  const host = mountInput(textPayload());
  await host.ready;
  host.errors = {'field:summary': ['Summary is invalid.']};
  await nextTick();

  expect(host.textContent).toContain('Summary is invalid.');
  expect(
    new URLSearchParams(serializeFormInputs(host)).get(
      'index[element-42][fields][summary]'
    )
  ).toBe('Original & value=1');
});

it('posts empty dates and retains their timezone', async () => {
  const payload: FormPayload = {
    scope: ['index', 'element-42'],
    refreshable: false,
    nodes: [
      {
        type: 'Field',
        component: 'craft:field',
        props: {},
        control: {
          type: 'DateTime',
          component: 'craft:date-time',
          path: ['index', 'element-42', 'expiryDate'],
          deltaGroup: ['index', 'element-42', 'expiryDate'],
          mode: 'editable',
          props: {
            showDate: true,
            showTime: true,
            showTimeZone: false,
            locale: 'en-US',
            minuteIncrement: 1,
          },
        },
      },
    ],
    values: {
      index: {
        'element-42': {
          expiryDate: {date: '', time: '', timezone: 'Europe/Brussels'},
        },
      },
    },
    errors: [],
    globalErrors: [],
  };
  const host = mountInput(payload);
  await host.ready;

  expect(
    Object.fromEntries(new URLSearchParams(serializeFormInputs(host)))
  ).toMatchObject({
    'index[element-42][expiryDate][date]': '',
    'index[element-42][expiryDate][time]': '',
    'index[element-42][expiryDate][timezone]': 'Europe/Brussels',
  });
});

it('unmounts the Vue app when a row is removed', async () => {
  const uninstall = vi.spyOn(components, 'uninstall');
  const host = mountInput(textPayload());
  await host.ready;
  host.remove();

  expect(uninstall).toHaveBeenCalledOnce();
  expect(host.childElementCount).toBe(0);
});

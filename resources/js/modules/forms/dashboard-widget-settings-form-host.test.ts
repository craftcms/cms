import {nextTick} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {defineDashboardWidgetSettingsFormHost} from './dashboard-widget-settings-form-host';
import FieldNode from './FieldNode.vue';
import ComboboxControl from './ComboboxControl.vue';
import type {FormPayload} from './types';

afterEach(() => document.body.replaceChildren());

it('mounts widget settings into the light DOM for native submission', async () => {
  const components = createCpComponentRegistry();
  components.register('craft:field', FieldNode);
  components.register('craft:combobox', ComboboxControl);
  defineDashboardWidgetSettingsFormHost(components);

  // SAFETY: The definition above registers the tested host API for this tag.
  const host = document.createElement(
    'craft-dashboard-widget-settings-form'
  ) as HTMLElement & {payload: FormPayload | null};
  const payload: FormPayload = {
    scope: ['settings'],
    refreshable: false,
    nodes: [
      {
        type: 'Field',
        component: 'craft:field',
        props: {label: 'Placeholder', required: false},
        control: {
          type: 'Combobox',
          component: 'craft:combobox',
          props: {
            options: [{label: '@webroot', value: '@webroot'}],
          },
          path: ['settings', 'placeholder'],
          mode: 'editable',
          deltaGroup: ['settings', 'placeholder'],
        },
      },
    ],
    values: {settings: {placeholder: 'Submitted placeholder'}},
    errors: [],
    globalErrors: [],
  };
  host.payload = payload;
  document.body.append(host);
  await nextTick();

  expect(host.querySelector('craft-combobox')?.getAttribute('name')).toBe(
    'settings[placeholder]'
  );
  expect(host.querySelector('craft-combobox')?.modelValue).toBe(
    'Submitted placeholder'
  );
});

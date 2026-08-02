import {nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
import '@craftcms/ui/components/input/input';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {
  FormDefinitionHost,
  type FormDefinitionHostContext,
} from './FormDefinitionHost';

afterEach(() => {
  document.body.innerHTML = '';
});

describe('Form Definition host', () => {
  it('mounts the generic renderer with explicit host context', async () => {
    const registry = createCpComponentRegistry();
    const values = {
      'widget7-settings': {
        title: 'Craft News',
      },
    };
    const context: FormDefinitionHostContext = {
      definition: {
        elements: [
          {
            type: 'craft:field',
            props: {label: 'Title', readOnly: true},
            children: [{type: 'craft:text-input', name: 'title'}],
          },
        ],
      },
      bindingScope: 'widget7-settings',
      values,
      errors: {'widget7-settings.title': ['Title is required.']},
      readOnly: false,
    };
    const form = document.createElement('form');
    const host = new FormDefinitionHost();

    registry.register('form-element:craft:text-input', CraftInput);
    (window as any).Cp = {$components: registry};
    host.context = context;
    form.appendChild(host);
    document.body.appendChild(form);
    await nextTick();

    const input =
      host.querySelector<HTMLElementTagNameMap['craft-input']>('craft-input');

    expect(input?.getAttribute('name')).toBe('widget7-settings[title]');
    expect(input?.value).toBe('Craft News');
    expect(input?.readOnly).toBe(true);
    expect(host.textContent).toContain('Title is required.');

    host.errors = {'widget7-settings.title': ['Title is invalid.']};
    await nextTick();

    expect(host.textContent).not.toContain('Title is required.');
    expect(host.textContent).toContain('Title is invalid.');
  });
});

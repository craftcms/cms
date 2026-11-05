import '@craftcms/ui/components/combobox/combobox';
import {createApp, h} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import {page} from 'vite-plus/test/browser/context';
import CraftCombobox from './CraftCombobox.vue';

let teardown: () => void;

afterEach(() => {
  teardown?.();
  document.body.innerHTML = '';
});

function mount(
  props: Record<string, unknown>,
  slots: Record<string, () => unknown> = {}
) {
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp({
    render: () =>
      h(CraftCombobox, {label: 'Timezone', options: [], ...props}, slots),
  });
  app.mount(host);
  teardown = () => app.unmount();
}

it('describes the input with its environment variable callout', async () => {
  mount({callouts: ['envVars']});

  await expect
    .element(page.getByRole('textbox', {name: 'Timezone'}))
    .toHaveAccessibleDescription(
      /^This can begin with an environment variable\.\s+Learn more$/
    );
});

it('has no description without callouts', async () => {
  mount({});

  await expect
    .element(page.getByRole('textbox', {name: 'Timezone'}))
    .toHaveAccessibleDescription('');
});

it('only describes the input with slotted content marked as a description', async () => {
  mount(
    {callouts: ['envVars']},
    {after: () => h('button', {slot: 'after', type: 'button'}, 'Refresh')}
  );

  await expect
    .element(page.getByRole('textbox', {name: 'Timezone'}))
    .toHaveAccessibleDescription('');
  await expect
    .element(page.getByRole('button', {name: 'Refresh'}))
    .toBeVisible();
});

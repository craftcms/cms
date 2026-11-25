import '../../../css/cp.css';
import '@craftcms/ui/components/tabs/tabs';
import '@craftcms/ui/components/tab/tab';
import '@craftcms/ui/components/button/button';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import {createApp, h, nextTick, type App} from 'vue';
import ErrorSummary from './ErrorSummary.vue';

let app: App | undefined;

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
});

it('switches a tab strip’s external panel to show a linked field, and focuses its input', async () => {
  document.body.innerHTML = `
    <craft-tabs label="Fields">
      <craft-tab slot="tab" controls="panel-content">Content</craft-tab>
      <craft-tab slot="tab" controls="panel-visit">Visit</craft-tab>
    </craft-tabs>
    <section id="panel-content"><p>Title</p></section>
    <section id="panel-visit" class="hidden">
      <div class="row">
        <craft-field id="ui-fields-venue"><div slot="input"><input></div></craft-field>
      </div>
    </section>`;
  await customElements.whenDefined('craft-tabs');
  const panel = document.getElementById('panel-visit')!;
  await vi.waitFor(() => expect(panel.getAttribute('role')).toBe('tabpanel'));

  const container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () =>
      h(ErrorSummary, {errors: {'fields.venue': 'Venue is required.'}}),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  await nextTick();
  await nextTick();

  container
    .querySelector<HTMLAnchorElement>('a[href="#ui-fields-venue"]')!
    .click();

  await vi.waitFor(() => {
    expect(panel.classList.contains('hidden')).toBe(false);
    expect(document.activeElement).toBe(
      document.querySelector('#ui-fields-venue input')
    );
  });
});

it('gives each linked error a target at least 24px tall that doesn’t overlap the next', async () => {
  document.body.innerHTML = `
    <craft-field id="ui-date"><div slot="input"><input></div></craft-field>
    <craft-field id="ui-time"><div slot="input"><input></div></craft-field>`;
  const container = document.createElement('div');
  document.body.prepend(container);
  app = createApp({
    render: () =>
      h(ErrorSummary, {
        errors: {
          date: 'The *Date* field is required.',
          time: '*Time* must be no later than 6:00 PM.',
        },
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  await nextTick();
  await nextTick();

  const [first, second] = [...container.querySelectorAll('a')].map((link) =>
    link.getBoundingClientRect()
  );

  expect(first!.height).toBeGreaterThanOrEqual(24);
  expect(second!.height).toBeGreaterThanOrEqual(24);
  expect(second!.top).toBeGreaterThanOrEqual(first!.bottom);
});

it('keeps the Enter that follows a link from also pressing the button it focuses', async () => {
  document.body.innerHTML = `
    <craft-field id="ui-fields-related">
      <div slot="input"><craft-button type="button">Add an entry</craft-button></div>
    </craft-field>`;
  await customElements.whenDefined('craft-button');
  const button = document.querySelector('craft-button')!;
  const pressed = vi.fn();
  button.addEventListener('click', pressed);

  const container = document.createElement('div');
  document.body.prepend(container);
  app = createApp({
    render: () =>
      h(ErrorSummary, {errors: {'fields.related': 'Related is required.'}}),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  await nextTick();
  await nextTick();

  container.querySelector<HTMLAnchorElement>('a')!.focus();
  await userEvent.keyboard('{Enter}');

  await vi.waitFor(() => expect(document.activeElement).toBe(button));
  expect(pressed).not.toHaveBeenCalled();

  // The button still works: the next Enter is its own.
  await userEvent.keyboard('{Enter}');
  expect(pressed).toHaveBeenCalledOnce();
});

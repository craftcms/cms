import '../../../css/cp.css';
import '@craftcms/ui/components/tabs/tabs';
import '@craftcms/ui/components/tab/tab';
import {afterEach, expect, it, vi} from 'vite-plus/test';
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

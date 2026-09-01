import {createApp, defineComponent, nextTick, reactive} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import {useSiteStatuses} from './useSiteStatuses';

type SubmittedStatus = string | number | boolean | null | undefined;

interface StatusForm {
  enabled?: SubmittedStatus;
  enabledForSite?: Record<string, SubmittedStatus>;
}

describe('useSiteStatuses', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  function mount(initial: StatusForm): StatusForm {
    const form = reactive(initial);

    container = document.createElement('div');
    document.body.append(container);
    app = createApp(
      defineComponent({
        setup() {
          useSiteStatuses(form);

          return () => null;
        },
      })
    );
    app.mount(container);

    return form;
  }

  it('applies the global switch to every site', async () => {
    const form = mount({
      enabled: true,
      enabledForSite: {'1': true, '2': true},
    });

    form.enabled = false;
    await nextTick();

    expect(form.enabledForSite).toEqual({'1': false, '2': false});
  });

  it('turns the global switch off when every site is off', async () => {
    const form = mount({
      enabled: true,
      enabledForSite: {'1': true, '2': true},
    });

    const statuses = form.enabledForSite;
    if (!statuses) throw new Error('Expected per-site statuses.');
    Object.assign(statuses, {'1': false, '2': false});
    await nextTick();

    expect(form.enabled).toBe(false);
  });

  it('marks the global switch indeterminate when sites disagree', async () => {
    const form = mount({
      enabled: true,
      enabledForSite: {'1': true, '2': true},
    });

    const statuses = form.enabledForSite;
    if (!statuses) throw new Error('Expected per-site statuses.');
    Object.assign(statuses, {'2': false});
    await nextTick();

    expect(form.enabled).toBe('-');
  });

  it('turns the global switch on when every site is on', async () => {
    const form = mount({
      enabled: '-',
      enabledForSite: {'1': true, '2': false},
    });

    const statuses = form.enabledForSite;
    if (!statuses) throw new Error('Expected per-site statuses.');
    Object.assign(statuses, {'2': true});
    await nextTick();

    expect(form.enabled).toBe(true);
  });

  it('leaves the sites alone while the global switch is indeterminate', async () => {
    const form = mount({
      enabled: true,
      enabledForSite: {'1': true, '2': false},
    });

    form.enabled = '-';
    await nextTick();

    expect(form.enabledForSite).toEqual({'1': true, '2': false});
  });

  it('does nothing without per-site switches', async () => {
    const form = mount({enabled: true});

    form.enabled = false;
    await nextTick();

    expect(form.enabled).toBe(false);
  });
});

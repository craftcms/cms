import '../../../css/cp.css';
import '@craftcms/ui/components/pane/pane';
import {createApp, defineComponent, h, ref} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import ModalForm from './ModalForm.vue';

let teardown: () => void;

afterEach(() => {
  teardown?.();
  document.body.innerHTML = '';
});

function mount() {
  const active = ref(false);
  const mounted = ref(true);
  const nested = ref(false);
  const confirmation = ref(false);
  const dismissible = ref(true);
  const submitted = vi.fn();
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp(
    defineComponent({
      setup: () => () =>
        h('div', [
          h('button', {onClick: () => (active.value = true)}, 'Open form'),
          h('a', {href: '#background'}, 'Background'),
          mounted.value
            ? h(
                ModalForm,
                {
                  isActive: active.value,
                  title: 'Edit settings',
                  width: 'md',
                  height: '20rem',
                  resizable: true,
                  dismissible: dismissible.value,
                  onClose: () => (active.value = false),
                  onSubmit: submitted,
                },
                () => [
                  h('label', {for: 'setting'}, 'Setting'),
                  h('input', {id: 'setting'}),
                  h(
                    'button',
                    {type: 'button', onClick: () => (nested.value = true)},
                    'Open child'
                  ),
                  h(
                    'button',
                    {
                      type: 'button',
                      onClick: () => (confirmation.value = true),
                    },
                    'Open confirmation'
                  ),
                  h(
                    'craft-dialog',
                    {
                      label: 'Confirm settings',
                      '.opened': confirmation.value,
                      'onCraft-hide': () => (confirmation.value = false),
                    },
                    'Confirm these settings.'
                  ),
                  h(
                    ModalForm,
                    {
                      isActive: nested.value,
                      title: 'Child settings',
                      onClose: () => (nested.value = false),
                    },
                    () => h('input', {'aria-label': 'Child setting'})
                  ),
                ]
              )
            : null,
        ]),
    })
  );
  app.mount(host);
  teardown = () => app.unmount();
  return {host, active, mounted, dismissible, submitted};
}

it('names the form dialog, contains focus, submits, and restores focus after closing or unmounting', async () => {
  const {host, active, mounted, submitted} = mount();
  const trigger = page.getByRole('button', {name: 'Open form', exact: true});
  await trigger.click();
  const dialog = page.getByRole('dialog', {name: 'Edit settings', exact: true});
  await expect.element(dialog).toBeVisible();
  const background = host.querySelector<HTMLAnchorElement>('a')!;
  background.focus();
  expect(document.activeElement).not.toBe(background);
  page.getByRole('button', {name: 'Close', exact: true}).element().focus();
  await userEvent.tab();
  await expect
    .element(page.getByRole('textbox', {name: 'Setting', exact: true}))
    .toHaveFocus();
  await page
    .getByRole('textbox', {name: 'Setting', exact: true})
    .fill('Updated');
  await userEvent.keyboard('{Enter}');
  expect(submitted).toHaveBeenCalledOnce();
  await userEvent.keyboard('{Escape}');
  await vi.waitFor(() => expect(active.value).toBe(false));
  await expect.element(trigger).toHaveFocus();
  await trigger.click();
  await expect.element(dialog).toBeVisible();
  mounted.value = false;
  await expect.element(dialog).not.toBeInTheDocument();
  await expect.element(trigger).toHaveFocus();
  expect(getComputedStyle(document.body).overflow).not.toBe('hidden');
  expect(document.querySelector(':modal')).toBeNull();
});

it('dismisses only the nested dialog and preserves non-dismissible forms and keyboard resizing', async () => {
  const {host, dismissible} = mount();
  await page.getByRole('button', {name: 'Open form', exact: true}).click();
  const parent = page.getByRole('dialog', {name: 'Edit settings', exact: true});
  await expect.element(parent).toBeVisible();
  await page.getByRole('button', {name: 'Open child'}).click();
  await expect
    .element(page.getByRole('dialog', {name: 'Child settings'}))
    .toBeVisible();
  await userEvent.keyboard('{Escape}');
  await expect
    .element(page.getByRole('dialog', {name: 'Child settings'}))
    .not.toBeInTheDocument();
  await expect.element(parent).toBeVisible();
  await expect
    .element(page.getByRole('button', {name: 'Open child'}))
    .toHaveFocus();
  await page.getByRole('button', {name: 'Open confirmation'}).click();
  const confirmationDialog = page.getByRole('dialog', {
    name: 'Confirm settings',
  });
  await expect.element(confirmationDialog).toBeVisible();
  await confirmationDialog
    .getByRole('button', {name: 'Close', exact: true})
    .click();
  await expect.element(parent).toBeVisible();
  await expect.element(confirmationDialog).not.toBeInTheDocument();
  expect(getComputedStyle(document.body).overflow).toBe('hidden');
  dismissible.value = false;
  await userEvent.keyboard('{Escape}');
  await expect.element(parent).toBeVisible();
  const resize = page.getByRole('button', {name: 'Resize', exact: true});
  const before = parent.element().getBoundingClientRect();
  resize.element().focus();
  await userEvent.keyboard('{ArrowLeft}{ArrowUp}');
  await vi.waitFor(() => {
    const after = parent.element().getBoundingClientRect();
    expect(after.width).toBeLessThan(before.width);
    expect(after.height).toBeLessThan(before.height);
  });
  await page.getByRole('button', {name: 'Cancel', exact: true}).click();
  await expect.element(parent).not.toBeInTheDocument();
  expect(host.querySelector('input')).toBeNull();
  expect(getComputedStyle(document.body).overflow).not.toBe('hidden');
});

it('animates entering and leaving while keeping the page blocked, unless reduced motion is requested', async () => {
  const {host, active} = mount();
  const trigger = page.getByRole('button', {name: 'Open form', exact: true});
  await trigger.click();
  const dialog = page.getByRole('dialog', {name: 'Edit settings', exact: true});
  await expect.element(dialog).toBeVisible();
  const element = dialog.element();
  const reducedMotion = window.matchMedia(
    '(prefers-reduced-motion: reduce)'
  ).matches;
  await new Promise<void>((resolve) =>
    requestAnimationFrame(() => requestAnimationFrame(() => resolve()))
  );

  if (reducedMotion) {
    expect(element.getAnimations({subtree: true})).toHaveLength(0);
  } else {
    await vi.waitFor(() =>
      expect(element.getAnimations({subtree: true}).length).toBeGreaterThan(0)
    );
    const entering = element.getAnimations({subtree: true});
    await Promise.all(entering.map((animation) => animation.finished));
  }

  active.value = false;
  if (!reducedMotion) {
    await vi.waitFor(() =>
      expect(element.getAnimations({subtree: true}).length).toBeGreaterThan(0)
    );
    expect(element.isConnected).toBe(true);
    const background = host.querySelector<HTMLAnchorElement>('a')!;
    background.focus();
    expect(document.activeElement).not.toBe(background);
  }
  await expect.element(dialog).not.toBeInTheDocument();
  await expect.element(trigger).toHaveFocus();
  expect(getComputedStyle(document.body).overflow).not.toBe('hidden');
});

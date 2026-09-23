import {afterEach, expect, it} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import CpButtonLink from './CpButtonLink.vue';

let teardown: (() => void) | undefined;

afterEach(() => {
  teardown?.();
  teardown = undefined;
});

async function mount(props: InstanceType<typeof CpButtonLink>['$props']) {
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp({
    render: () => h(CpButtonLink, props, {default: () => 'New site'}),
  });

  app.mount(container);
  teardown = () => {
    app.unmount();
    container.remove();
  };
  await nextTick();

  return container.querySelector(':scope > craft-button')!;
}

it('renders a craft-button that links to its href', async () => {
  const button = await mount({
    href: '/settings/sites/new',
    variant: 'primary',
    icon: 'plus',
    size: 'small',
  });

  expect(button.getAttribute('href')).toBe('/settings/sites/new');
  expect(button.getAttribute('variant')).toBe('primary');
  expect(button.getAttribute('icon')).toBe('plus');
  expect(button.getAttribute('size')).toBe('small');
  expect(button.textContent).toContain('New site');
});

// CpLink used to default `variant` to its own `neutral`, which it forwarded to
// the button even though craft-button has no such variant.
it('leaves the variant to craft-button when none is given', async () => {
  const button = await mount({href: '/settings/sites/new'});

  expect(button.hasAttribute('variant')).toBe(false);
});

it('renders a plain craft-button link when Inertia is off', async () => {
  const button = await mount({
    href: 'https://example.com/buy',
    inertia: false,
    target: '_blank',
  });

  expect(button.getAttribute('href')).toBe('https://example.com/buy');
  expect(button.getAttribute('target')).toBe('_blank');
});

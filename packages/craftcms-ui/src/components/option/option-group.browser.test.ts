import {
  afterEach,
  beforeAll,
  beforeEach,
  describe,
  expect,
  it,
} from 'vite-plus/test';
import {cdp, page} from 'vite-plus/test/browser';
import {contrast, loadTokens} from '../../../test/contrast';
import type CraftOptionGroup from './option-group.js';
import './option-group.js';
import './option.js';
import '../../styles/cp.css';

beforeAll(loadTokens);

beforeEach(() => {
  document.body.innerHTML = '';
});

afterEach(async () => {
  await cdp().send('Emulation.setEmulatedMedia', {features: []});
});

async function fixture(theme = 'light') {
  document.body.innerHTML = `
    <div data-theme="${theme}" role="listbox" aria-label="Fruit"
      style="background-color: var(--c-surface-overlay); width: 20rem">
      <craft-option-group label="Pome">
        <craft-option>Apple</craft-option>
        <craft-option>Pear</craft-option>
      </craft-option-group>
    </div>`;
  const group = document.querySelector<CraftOptionGroup>('craft-option-group')!;
  await group.updateComplete;

  return {
    group,
    label: group.shadowRoot!.querySelector<HTMLElement>('.label')!,
    background: getComputedStyle(group.parentElement!).backgroundColor,
  };
}

it('is a group named by its label, holding its options', async () => {
  await fixture();

  for (const name of ['Apple', 'Pear']) {
    await expect
      .element(
        page.getByRole('group', {name: 'Pome'}).getByRole('option', {name})
      )
      .toBeInTheDocument();
  }
});

it('renames the group when its label changes', async () => {
  const {group, label} = await fixture();

  group.label = 'Stone';
  await group.updateComplete;

  expect(label).toHaveTextContent('Stone');
  await expect
    .element(page.getByRole('group', {name: 'Stone'}))
    .toBeInTheDocument();
});

describe.each(['light', 'dark'])('in the %s theme', (theme) => {
  it('draws its label at 4.5:1 against the listbox', async () => {
    const {label, background} = await fixture(theme);

    expect(
      contrast(getComputedStyle(label).color, background)
    ).toBeGreaterThanOrEqual(4.5);
  });
});

it('keeps its label visible in forced colors mode', async () => {
  await cdp().send('Emulation.setEmulatedMedia', {
    features: [{name: 'forced-colors', value: 'active'}],
  });
  const {label, background} = await fixture();

  expect(label.getBoundingClientRect().height).toBeGreaterThan(0);
  expect(
    contrast(getComputedStyle(label).color, background)
  ).toBeGreaterThanOrEqual(4.5);
});

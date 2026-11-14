import {beforeEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';
import '../checkbox/checkbox.js';
import './radio.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

function drawn(input: HTMLInputElement): Record<string, string> {
  const style = getComputedStyle(input);

  return {
    appearance: style.appearance,
    width: style.width,
    borderRadius: style.borderRadius,
    borderColor: style.borderColor,
    backgroundColor: style.backgroundColor,
  };
}

it.each([
  ['radio', 'craft-radio', 'cp-radio'],
  ['checkbox', 'craft-checkbox', 'cp-checkbox'],
])(
  'draws a native %s with the opt-in class as its component draws one',
  async (type, component, className) => {
    document.body.innerHTML = `
      <${component}><input slot="input" type="${type}" id="in-component" /></${component}>
      <input type="${type}" class="${className}" id="native" />
    `;
    await customElements.whenDefined(component);
    const inComponent =
      document.querySelector<HTMLInputElement>('#in-component')!;
    const native = document.querySelector<HTMLInputElement>('#native')!;

    expect(drawn(native).appearance).toBe('none');
    expect(drawn(native)).toEqual(drawn(inComponent));

    inComponent.checked = true;
    native.checked = true;

    expect(drawn(native)).toEqual(drawn(inComponent));
  }
);

it('shows its icon between the input and the label, sized to the text', async () => {
  document.body.innerHTML = `
    <craft-radio>
      <input slot="input" type="radio" id="cards" />
      <span slot="icon" aria-hidden="true"
        ><svg viewBox="0 0 80 60"><rect width="80" height="60" /></svg
      ></span>
      <label slot="label" for="cards">Cards</label>
    </craft-radio>`;
  const radio = document.querySelector('craft-radio')!;
  await radio.updateComplete;

  const [input, icon, label] = ['input', '[slot="icon"]', 'label'].map(
    (selector) => radio.querySelector(selector)!.getBoundingClientRect()
  );

  expect(icon!.left).toBeGreaterThanOrEqual(input!.right);
  expect(label!.left).toBeGreaterThanOrEqual(icon!.right);
  expect(icon!.height).toBeLessThan(label!.height * 1.5);
});

it('takes no space without an icon', async () => {
  document.body.innerHTML = `
    <craft-radio>
      <input slot="input" type="radio" id="list" />
      <label slot="label" for="list">List</label>
    </craft-radio>`;
  const radio = document.querySelector('craft-radio')!;
  await radio.updateComplete;

  const input = radio.querySelector('input')!.getBoundingClientRect();
  const label = radio.querySelector('label')!.getBoundingClientRect();
  const gap = parseFloat(getComputedStyle(radio).columnGap);

  expect(label.left - input.right).toBeCloseTo(gap, 0);
});

it('lines its help text up under the label', async () => {
  document.body.innerHTML = `
    <craft-radio>
      <input slot="input" type="radio" id="match" />
      <label slot="label" for="match">Match</label>
      <span slot="help-text">Also find the existing elements to update by it.</span>
    </craft-radio>`;
  const radio = document.querySelector('craft-radio')!;
  await radio.updateComplete;

  const label = radio.querySelector('label')!.getBoundingClientRect();
  const help = radio
    .querySelector('[slot="help-text"]')!
    .getBoundingClientRect();

  expect(help.left).toBeCloseTo(label.left, 0);
  expect(help.top).toBeGreaterThanOrEqual(label.bottom);
});

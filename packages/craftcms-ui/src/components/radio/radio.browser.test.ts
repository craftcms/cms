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

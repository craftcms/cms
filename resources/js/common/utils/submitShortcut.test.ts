import {afterEach, expect, it, vi} from 'vite-plus/test';
import {submitFormOnCtrlEnter} from './submitShortcut';

let form: HTMLFormElement | undefined;

afterEach(() => {
  form?.remove();
});

function press(
  target: HTMLElement,
  init: KeyboardEventInit = {ctrlKey: true}
): {event: KeyboardEvent; submit: ReturnType<typeof vi.fn>} {
  const submit = vi.fn((event: Event) => event.preventDefault());
  form!.addEventListener('submit', submit);
  const event = new KeyboardEvent('keydown', {
    key: 'Enter',
    bubbles: true,
    cancelable: true,
    composed: true,
    ...init,
  });
  target.addEventListener('keydown', submitFormOnCtrlEnter);
  target.dispatchEvent(event);
  return {event, submit};
}

function mount(html: string): HTMLFormElement {
  form = document.createElement('form');
  form.innerHTML = html;
  document.body.append(form);
  return form;
}

it.each([
  ['a text input', '<input type="text">', {ctrlKey: true}],
  ['a textarea', '<textarea></textarea>', {metaKey: true}],
])(
  'submits the form when Ctrl/Command + Enter is pressed in %s',
  (_, html, init) => {
    const {event, submit} = press(
      mount(html).firstElementChild as HTMLElement,
      init
    );

    expect(submit).toHaveBeenCalledOnce();
    expect(event.defaultPrevented).toBe(true);
  }
);

it('submits the form around a text input in a shadow root', () => {
  const host = mount('<div></div>').firstElementChild!;
  const input = document.createElement('input');
  host.attachShadow({mode: 'open'}).append(input);

  expect(press(input).submit).toHaveBeenCalledOnce();
});

it.each([
  ['without Ctrl/Command', '<input type="text">', {}],
  ['for checkboxes', '<input type="checkbox">', {ctrlKey: true}],
  ['for read-only inputs', '<input type="text" readonly>', {ctrlKey: true}],
])('leaves Enter alone %s', (_, html, init) => {
  const {event, submit} = press(
    mount(html).firstElementChild as HTMLElement,
    init
  );

  expect(submit).not.toHaveBeenCalled();
  expect(event.defaultPrevented).toBe(false);
});

it('leaves the shortcut to handlers that claimed it', () => {
  const input = mount('<input type="text">').firstElementChild as HTMLElement;
  input.addEventListener('keydown', (event) => event.preventDefault());

  expect(press(input).submit).not.toHaveBeenCalled();
});

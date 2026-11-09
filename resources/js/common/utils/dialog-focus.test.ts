import {
  afterAll,
  afterEach,
  beforeAll,
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vite-plus/test';
import {computeAccessibleName} from 'dom-accessibility-api';
import {firstEditableField, focusDialogStart} from './dialog-focus';

/** The focused element's accessible name, as a screen reader would announce it. */
function focusedName(): string {
  return document.activeElement
    ? computeAccessibleName(document.activeElement)
    : '';
}

/** A dialog with a link and a button ahead of its first field. */
function dialog(fields = '<label>Name <input></label>'): {
  container: HTMLElement;
  heading: HTMLElement;
} {
  document.body.innerHTML = `
    <div role="dialog" aria-labelledby="dialog-title">
      <h1 id="dialog-title">Set up</h1>
      <a href="#help">Help</a>
      <button type="button">Copy</button>
      ${fields}
    </div>`;

  return {
    container: document.querySelector<HTMLElement>('[role="dialog"]')!,
    heading: document.querySelector<HTMLElement>('h1')!,
  };
}

/** Waits past the frame in which the field takes focus. */
function nextFrame(): Promise<void> {
  return new Promise((resolve) => requestAnimationFrame(() => resolve()));
}

// happy-dom doesn't implement `checkVisibility()`. Hidden, or inside
// something hidden, stands in for not visible.
const hadCheckVisibility = 'checkVisibility' in HTMLElement.prototype;
beforeAll(() => {
  if (!hadCheckVisibility) {
    HTMLElement.prototype.checkVisibility = function (this: HTMLElement) {
      return !this.closest('[hidden]');
    };
  }
});
afterAll(() => {
  if (!hadCheckVisibility) {
    delete (HTMLElement.prototype as Partial<HTMLElement>).checkVisibility;
  }
});

beforeEach(() => {
  vi.stubGlobal('Craft', {});
});

afterEach(() => {
  vi.unstubAllGlobals();
  document.body.innerHTML = '';
});

describe('with "Disable autofocus" on', () => {
  it('focuses the heading', async () => {
    const {container, heading} = dialog();

    focusDialogStart(container, heading);
    await nextFrame();

    expect(document.activeElement).toBe(heading);
    expect(focusedName()).toBe('Set up');
    // Focusable from script, but not a Tab stop.
    expect(heading.tabIndex).toBe(-1);
  });
});

describe('with autofocus preferred', () => {
  beforeEach(() => {
    vi.stubGlobal('Craft', {autofocusPreferred: true});
  });

  it('focuses the first field, not a link or button ahead of it', async () => {
    const {container, heading} = dialog();

    focusDialogStart(container, heading);
    await nextFrame();

    expect(focusedName()).toBe('Name');
  });

  it('focuses the heading when there is no field to fill in', async () => {
    const {container, heading} = dialog('');

    focusDialogStart(container, heading);
    await nextFrame();

    expect(document.activeElement).toBe(heading);
  });

  it('leaves focus on whatever took it before the field could', async () => {
    const {container, heading} = dialog();

    focusDialogStart(container, heading);
    // A control that focuses itself as it renders, as `TextControl` does.
    container.querySelector('button')!.focus();
    await nextFrame();

    expect(focusedName()).toBe('Copy');
  });

  it('focuses the first field when there is no heading', () => {
    const {container} = dialog();

    focusDialogStart(container, null);

    expect(focusedName()).toBe('Name');
  });
});

it('leaves focus alone when it is already inside', async () => {
  vi.stubGlobal('Craft', {autofocusPreferred: true});
  const {container, heading} = dialog();
  container.querySelector('a')!.focus();

  focusDialogStart(container, heading);
  await nextFrame();

  expect(focusedName()).toBe('Help');
});

it('focuses the first focusable element when there is no heading or field', () => {
  const {container} = dialog('');

  focusDialogStart(container, null);

  expect(focusedName()).toBe('Help');
});

describe('firstEditableField', () => {
  it('skips fields that cannot be filled in', () => {
    document.body.innerHTML = `
      <div>
        <input type="hidden" name="token">
        <input aria-label="Disabled" disabled>
        <input aria-label="Setup key" readonly>
        <input aria-label="Hidden" hidden>
        <input type="checkbox" aria-label="Remember">
        <div class="prevent-autofocus"><input aria-label="Skipped"></div>
        <input aria-label="Code">
      </div>`;

    expect(
      firstEditableField(document.body.firstElementChild!)?.getAttribute(
        'aria-label'
      )
    ).toBe('Code');
  });

  it('prefers a field marked autofocus', () => {
    document.body.innerHTML = `
      <div>
        <input aria-label="First">
        <select aria-label="Country" autofocus><option>Canada</option></select>
      </div>`;

    expect(
      firstEditableField(document.body.firstElementChild!)?.getAttribute(
        'aria-label'
      )
    ).toBe('Country');
  });

  it('finds a field inside a component’s shadow root', () => {
    document.body.innerHTML = '<div><span id="host"></span></div>';
    const shadow = document
      .getElementById('host')!
      .attachShadow({mode: 'open'});
    shadow.innerHTML = '<input aria-label="Verification code">';

    expect(
      firstEditableField(document.body.firstElementChild!)?.getAttribute(
        'aria-label'
      )
    ).toBe('Verification code');
  });
});

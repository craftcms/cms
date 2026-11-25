import {afterEach, beforeAll, describe, expect, it, vi} from 'vitest';
import {FieldToggle} from './field-toggle';
import {fieldToggleData} from './support';
import {observeFieldToggles} from './observe';

const markup = `
  <select class="fieldtoggle" data-target-prefix="pane-">
    <option value="a" selected>A</option>
    <option value="b">B</option>
  </select>
  <div id="pane-a">A</div>
  <div id="pane-b" class="hidden">B</div>
`;

function choose(value: string): void {
  const select = document.querySelector('select')!;
  select.value = value;
  select.dispatchEvent(new Event('change'));
}

/** Lets the observer see what was just added. */
const flush = () => new Promise((resolve) => setTimeout(resolve));

beforeAll(() => {
  document.body.innerHTML = markup;
  observeFieldToggles();
});

afterEach(() => {
  vi.restoreAllMocks();
  document.body.replaceChildren();
});

describe('observeFieldToggles()', () => {
  it('binds the toggles already on the page', () => {
    choose('b');

    expect(document.getElementById('pane-a')!.classList).toContain('hidden');
    expect(document.getElementById('pane-b')!.classList).not.toContain(
      'hidden'
    );
  });

  it('binds toggles in markup added later', async () => {
    const fragment = document.createElement('div');
    fragment.innerHTML = markup;
    document.body.append(fragment);
    await flush();

    choose('b');

    expect(document.getElementById('pane-b')!.classList).not.toContain(
      'hidden'
    );
  });

  it('leaves toggles alone that are already bound', async () => {
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
    const fragment = document.createElement('div');
    fragment.innerHTML = markup;
    const select = fragment.querySelector('select')!;
    const own = new FieldToggle(select);

    document.body.append(fragment);
    await flush();
    // Moving it is reported as another addition
    document.body.prepend(select);
    await flush();

    expect(fieldToggleData.get(select)).toBe(own);
    expect(warn).not.toHaveBeenCalled();
  });
});

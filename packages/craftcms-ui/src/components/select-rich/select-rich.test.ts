import {LionSelectRich} from '@lion/ui/select-rich.js';
import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import './select-rich.js';
import type CraftSelectRich from './select-rich.js';

async function createSelectRich(
  attrs: Record<string, string> = {}
): Promise<CraftSelectRich> {
  const element = document.createElement(
    'craft-select-rich'
  ) as CraftSelectRich;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = '<label slot="label">Favorite Fruit</label>';
  document.body.append(element);
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

/**
 * Slotted `craft-option`s are not exercised here: registering one runs Lion's
 * listbox wiring, which needs layout happy-dom does not provide and throws
 * before the option lands. The options, the keyboard navigation, and the
 * selection are covered by the Storybook stories, which run in a real browser.
 */
describe('craft-select-rich', () => {
  it('upgrades and renders its field chrome', async () => {
    const element = await createSelectRich();

    expect(element.shadowRoot).toBeTruthy();
    expect(element.querySelector('label[slot="label"]')).toBeTruthy();
  });

  it('leaves keyboard shortcuts out of type-ahead', async () => {
    const element = await createSelectRich();
    const typeAhead = vi
      // @ts-expect-error Lion marks the method protected.
      .spyOn(LionSelectRich.prototype, '_handleTypeAhead')
      .mockImplementation(() => {});

    // ⌘S while the select has focus must not jump to an "S" option.
    element._handleTypeAhead(
      new KeyboardEvent('keydown', {key: 's', metaKey: true}),
      {
        setAsChecked: true,
      }
    );
    element._handleTypeAhead(
      new KeyboardEvent('keydown', {key: 's', ctrlKey: true}),
      {
        setAsChecked: true,
      }
    );
    expect(typeAhead).not.toHaveBeenCalled();

    element._handleTypeAhead(new KeyboardEvent('keydown', {key: 's'}), {
      setAsChecked: true,
    });
    expect(typeAhead).toHaveBeenCalledOnce();

    typeAhead.mockRestore();
  });

  it('takes a name for posting', async () => {
    const element = await createSelectRich({name: 'fruit'});

    expect(element.name).toBe('fruit');
  });

  it('reflects small so the stylesheet can act on it', async () => {
    const element = await createSelectRich({small: ''});

    expect(element.hasAttribute('small')).toBe(true);
  });

  /**
   * The component registers its own elements into the shadow root rather than
   * relying on globally defined tags, which is how a rich option renders.
   */
  it('scopes the elements it renders', async () => {
    const scoped = (
      CraftSelectRichConstructor as unknown as {
        scopedElements: Record<string, unknown>;
      }
    ).scopedElements;

    expect(Object.keys(scoped).length).toBeGreaterThan(0);
  });
});

const CraftSelectRichConstructor = customElements.get('craft-select-rich')!;

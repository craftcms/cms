import {describe, expect, it, vi} from 'vite-plus/test';
import {DEFAULT_FORM_ACTIONS, formActionItems} from './formActionItems';

describe('formActionItems', () => {
  it('puts the built-in actions before the page’s own', () => {
    const items = formActionItems(
      DEFAULT_FORM_ACTIONS,
      [{label: 'Publish'}],
      vi.fn()
    );

    expect(items.map((item) => 'label' in item && item.label)).toEqual([
      'Save and continue editing',
      'Publish',
    ]);
  });

  it('saves without redirecting for “Save and continue editing”', () => {
    const save = vi.fn();
    const [item] = formActionItems(DEFAULT_FORM_ACTIONS, undefined, save);

    (item as unknown as {onClick: () => void}).onClick();

    expect(save).toHaveBeenCalledWith({redirect: false});
  });

  it('drops the built-ins when the page opts out', () => {
    expect(formActionItems([], undefined, vi.fn())).toEqual([]);
  });
});

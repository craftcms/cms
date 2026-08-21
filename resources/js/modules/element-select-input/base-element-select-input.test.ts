import {expect, it} from 'vite-plus/test';
import {BaseElementSelectInput} from './base-element-select-input';
import {EntrySelectInput} from './entry-select-input';
import {TagSelectInput} from './tag-select-input';

it('preserves legacy element select input extension points', () => {
    class UninitializedElementSelectInput extends BaseElementSelectInput {}

    const input = new UninitializedElementSelectInput();

    expect('_ignoreSearchBlur' in input).toBe(true);
    expect('_initialized' in input).toBe(true);
    expect('_$replaceElement' in input).toBe(true);
    expect(
        typeof Object.getOwnPropertyDescriptor(
            BaseElementSelectInput.prototype,
            'thumbLoader'
        )?.get
    ).toBe('function');
    expect(
        typeof Object.getOwnPropertyDescriptor(
            BaseElementSelectInput.prototype,
            '_animateStructureElementAway'
        )?.value
    ).toBe('function');

    expect(
        typeof Object.getOwnPropertyDescriptor(
            EntrySelectInput.prototype,
            'section'
        )?.get
    ).toBe('function');
    expect(
        typeof Object.getOwnPropertyDescriptor(
            EntrySelectInput.prototype,
            'showElementEditor'
        )?.value
    ).toBe('function');

    for (const method of [
        'focusOption',
        'searchForTags',
        'focusSelectedOption',
        'focusFirstOption',
        'killSearchMenu',
    ]) {
        expect(Object.hasOwn(TagSelectInput.prototype, method)).toBe(true);
    }
    expect(
        typeof Object.getOwnPropertyDescriptor(
            TagSelectInput.prototype,
            'fieldName'
        )?.get
    ).toBe('function');

    expect(BaseElementSelectInput.defaults).toMatchObject({
        allowAdd: true,
        allowRemove: true,
        modalSettings: {},
    });
    expect(TagSelectInput.defaults).toEqual({tagGroupId: null});
});

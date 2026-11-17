import {defineComponent, h} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';

const received = vi.hoisted(() => ({sets: undefined as unknown}));

vi.mock('@/common/form/IconPicker.vue', () => ({
  default: defineComponent({
    props: ['sets'],
    setup(props) {
      received.sets = props.sets;
      return () => h('div');
    },
  }),
}));

const {default: CraftIconPicker} = await import('./icon-picker.ce');

if (!customElements.get('craft-icon-picker-test')) {
  customElements.define('craft-icon-picker-test', CraftIconPicker);
}

describe('craft-icon-picker', () => {
  let el: HTMLElement | undefined;

  afterEach(() => {
    el?.remove();
    received.sets = undefined;
  });

  function mount(sets?: string) {
    el = document.createElement('craft-icon-picker-test');
    if (sets !== undefined) {
      el.setAttribute('sets', sets);
    }
    document.body.append(el);
  }

  it.each([
    ['["custom","fontawesome"]', ['custom', 'fontawesome']],
    ['*', '*'],
    ['"*"', '*'],
    ['not json', null],
    [undefined, null],
  ])('passes sets=%s to the picker', (attr, expected) => {
    mount(attr);
    expect(received.sets).toEqual(expected);
  });
});

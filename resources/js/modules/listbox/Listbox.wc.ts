import {Listbox} from '@/modules/listbox/Listbox';
import {ControllerElement} from '@/common/web-components';

/**
 * `<craft-listbox>` — boots a {@link Listbox} around the server-rendered
 * `<craft-button-group>` it wraps, so PHP/Twig can emit the element instead of a
 * manual `new Craft.Listbox(...)` boot.
 *
 * This element wraps the `<craft-button-group>` plus an optional hidden input.
 * On selection change it syncs that hidden input's value from the option's
 * `data-value`. Unlike the legacy `Craft.Listbox` global, this WC uses the bare
 * {@link Listbox} class and works with RAW option elements — it does NOT go
 * through the jQuery-wrapping global.
 *
 * The `disabled` attribute (emitted by the Twig when the group is disabled or
 * static) maps to the listbox's `readOnly` setting.
 */
export default class CraftListbox extends ControllerElement<Listbox> {
  protected readonly rootSelector = 'craft-button-group';

  protected create(root: HTMLElement): Listbox {
    const hidden = this.querySelector<HTMLInputElement>('input[type="hidden"]');
    // Only set `onChange` when there's a hidden input to sync — passing
    // `onChange: undefined` would clobber the class default (setSettings merges
    // via `Object.assign`, which copies `undefined`) and break click handling.
    return new Listbox(root, {
      readOnly: this.hasAttribute('disabled'),
      ...(hidden && {
        onChange: (option: HTMLElement) => {
          hidden.value = option.getAttribute('data-value') ?? '';
        },
      }),
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-listbox': CraftListbox;
  }
}

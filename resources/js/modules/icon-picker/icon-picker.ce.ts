import {createApp, type App} from 'vue';
import IconPicker from '@/common/form/IconPicker.vue';

/**
 * `<craft-icon-picker>` — mounts the Vue `IconPicker.vue` component on legacy
 * (non-Inertia) CP surfaces, replacing the legacy jQuery `Craft.IconPicker`
 * widget. The modern entry-types Vue page uses `IconPicker.vue` directly; this is
 * the bridge for Twig/jQuery surfaces (settings fields, editable-table icon
 * cells, CustomizeSourcesModal).
 *
 * Settings come from attributes; the component renders its own form-postable
 * control (`craft-input` with `hidden-input` + `name`), so the value posts with
 * no separate hidden input here. Model changes re-emit as a bubbling `change`
 * CustomEvent for legacy listeners. The per-instance Vue app is unmounted on
 * disconnect (e.g. an editable-table row removal) to avoid leaks.
 */
export default class CraftIconPicker extends HTMLElement {
  #app: App | null = null;

  connectedCallback(): void {
    if (this.#app) {
      return;
    }

    this.#app = createApp(IconPicker, {
      name: this.getAttribute('name') ?? undefined,
      label: this.getAttribute('label') ?? undefined,
      error: this.getAttribute('error') ?? undefined,
      labelledBy: this.getAttribute('labelled-by') ?? undefined,
      describedBy: this.getAttribute('described-by') ?? undefined,
      freeOnly: this.hasAttribute('free-only'),
      disabled: this.hasAttribute('disabled'),
      modelValue: this.getAttribute('value') ?? '',
      'onUpdate:modelValue': (value: string) => {
        this.dispatchEvent(
          new CustomEvent('change', {bubbles: true, detail: {value}})
        );
      },
    });

    this.#app.mount(this);
  }

  disconnectedCallback(): void {
    this.#app?.unmount();
    this.#app = null;
  }
}

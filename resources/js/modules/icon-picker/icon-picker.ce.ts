import {createApp, h, reactive, type App} from 'vue';
import IconPicker from '@/common/form/IconPicker.vue';

type IconPickerProps = {
  id?: string;
  name?: string;
  label?: string;
  error?: string;
  labelledBy?: string;
  describedBy?: string;
  freeOnly: boolean;
  disabled: boolean;
  modelValue: string;
};

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
  static get observedAttributes(): string[] {
    return [
      'id',
      'name',
      'label',
      'error',
      'labelled-by',
      'described-by',
      'free-only',
      'disabled',
      'value',
    ];
  }

  #app: App | null = null;
  #props = reactive<IconPickerProps>(this.#readProps());

  attributeChangedCallback(): void {
    Object.assign(this.#props, this.#readProps());
  }

  connectedCallback(): void {
    if (this.#app) {
      return;
    }

    this.#app = createApp({
      render: () =>
        h(IconPicker, {
          ...this.#props,
          'onUpdate:modelValue': (value: string | undefined) => {
            const nextValue = value ?? '';

            this.#props.modelValue = nextValue;
            this.dispatchEvent(
              new CustomEvent('change', {
                bubbles: true,
                detail: {value: nextValue},
              })
            );
          },
        }),
    });

    this.#app.mount(this);
  }

  disconnectedCallback(): void {
    this.#app?.unmount();
    this.#app = null;
  }

  #readProps(): IconPickerProps {
    return {
      id: this.getAttribute('id') ?? undefined,
      name: this.getAttribute('name') ?? undefined,
      label: this.getAttribute('label') ?? undefined,
      error: this.getAttribute('error') ?? undefined,
      labelledBy: this.getAttribute('labelled-by') ?? undefined,
      describedBy: this.getAttribute('described-by') ?? undefined,
      freeOnly: this.hasAttribute('free-only'),
      disabled: this.hasAttribute('disabled'),
      modelValue: this.getAttribute('value') ?? '',
    };
  }
}

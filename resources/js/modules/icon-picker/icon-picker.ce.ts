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
 * no separate hidden input here. Each pick or removal bubbles out as the
 * input's own `input` and `change`, once the input has the new value. The
 * per-instance Vue app is unmounted on
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
      sets: this.#sets(),
      disabled: this.hasAttribute('disabled'),
      // Only the initial value: with no `onUpdate:modelValue` listener, the
      // picker keeps its own model, which is what its input posts.
      modelValue: this.getAttribute('value') ?? '',
    });

    this.#app.mount(this);
  }

  /** `sets` is `*`, or a JSON array of icon set handles. */
  #sets(): string[] | '*' | null {
    const sets = this.getAttribute('sets');

    if (!sets) {
      return null;
    }

    if (sets === '*') {
      return '*';
    }

    try {
      const parsed: unknown = JSON.parse(sets);

      if (parsed === '*') {
        return '*';
      }

      return Array.isArray(parsed) ? parsed.map(String) : null;
    } catch {
      return null;
    }
  }

  disconnectedCallback(): void {
    this.#app?.unmount();
    this.#app = null;
  }
}

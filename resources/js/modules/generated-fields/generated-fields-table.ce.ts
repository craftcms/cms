import {h, nextTick, shallowRef} from 'vue';
import {cpComponentRegistry} from '@/bootstrap/components';
import FormRenderer from '@/modules/forms/FormRenderer.vue';
import {mountFormHost} from '@/modules/forms/mountFormHost';
import {isRecord, valueAt} from '@/modules/forms/runtime';
import type {FormPayload, FormValues} from '@/modules/forms/types';
import type {FormRendererInstance} from '@/modules/forms/useInertiaFormRenderer';
import {cvdData} from '@/modules/field-layout-designer/support';
import type {CardViewDesigner} from '@/modules/field-layout-designer/card-view-designer';

/** Mounts the shared Form table and synchronizes generated field identities. */
export default class CraftGeneratedFieldsTable extends HTMLElement {
  ready: Promise<void> = Promise.resolve();
  readonly #renderer =
    shallowRef<Pick<FormRendererInstance, 'currentValues'>>();
  #mount: ReturnType<typeof mountFormHost> | null = null;
  #path: string[] = [];
  #labels = new Map<string, string>();

  connectedCallback(): void {
    if (this.#mount) return;

    // SAFETY: PHP serializes a resolved Form payload into this attribute.
    const payload = JSON.parse(this.dataset.payload!) as FormPayload;
    this.#path = payload.nodes[0]!.control!.path;
    this.#mount = mountFormHost(this, cpComponentRegistry, () =>
      h(FormRenderer, {
        ref: this.#renderer,
        payload,
        onChange: () => this.#changed(),
      })
    );
    this.ready = this.#mount.ready.then(() => {
      this.#labels = this.#fieldLabels();
    });
  }

  disconnectedCallback(): void {
    this.#mount?.unmount();
    this.#mount = null;
    this.#renderer.value = undefined;
    this.#labels.clear();
  }

  get #cvd(): CardViewDesigner | undefined {
    const container = this.closest('.fld-cvd');
    const element = container?.querySelector('.card-view-designer');
    return element ? cvdData.get(element) : undefined;
  }

  serialize(): FormValues[] {
    const values = this.#renderer.value?.currentValues();
    const rows = values ? valueAt(values, this.#path) : [];
    return Array.isArray(rows) ? rows.filter(isRecord) : [];
  }

  #changed(): void {
    this.#syncCardFields();
    void nextTick().then(() => {
      if (this.isConnected) {
        this.dispatchEvent(new Event('change', {bubbles: true}));
      }
    });
  }

  #fieldLabels(): Map<string, string> {
    return new Map(
      this.serialize().flatMap((row) => {
        const name = typeof row.name === 'string' ? row.name.trim() : '';
        return name && typeof row.uid === 'string' && row.uid
          ? [[`generatedField:${row.uid}`, name]]
          : [];
      })
    );
  }

  #syncCardFields(): void {
    const cvd = this.#cvd;
    const labels = this.#fieldLabels();

    if (cvd) {
      for (const value of this.#labels.keys()) {
        if (!labels.has(value)) cvd.removeCheckbox(value);
      }
      for (const [value, name] of labels) {
        if (this.#labels.get(value) === name) continue;
        if (cvd.findCheckboxByValue(value)) {
          cvd.updateCheckboxLabel(value, name);
        } else {
          const label = document.createElement('span');
          label.textContent = name;
          cvd.addCheckbox({
            value,
            labelHtml: label.innerHTML,
            data: {'field-label': name},
          });
        }
      }
    }

    this.#labels = labels;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-generated-fields-table': CraftGeneratedFieldsTable;
  }
}

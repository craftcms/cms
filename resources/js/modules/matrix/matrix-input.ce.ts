import {MatrixInput, type MatrixEntryType} from './matrix-input';
import {MatrixEntry} from './matrix-entry';
import {ControllerElement} from '@/common/web-components';
import {t} from '@craftcms/ui';

/**
 * `<craft-matrix-input>` — boots a {@link MatrixInput} around the
 * server-rendered `.matrix-field` it wraps, so PHP/Twig can emit the element
 * instead of a manual `new Craft.MatrixInput(...)` boot script.
 *
 * Configuration comes from attributes:
 *
 * - `entry-types` — JSON array of `{id, handle, name}` descriptors
 * - `input-name-prefix` — the field's namespaced input name
 * - `settings` — JSON {@link MatrixInputSettings}
 *
 * The wrapped `.matrix-field` keeps its own `id`, which is what the
 * {@link MatrixInput} constructor resolves.
 */
export default class CraftMatrixInput extends ControllerElement<MatrixInput> {
  protected readonly rootSelector = '.matrix-field';

  private listener?: AbortController;

  override connectedCallback(): void {
    super.connectedCallback();
    this.listener?.abort();
    this.listener = new AbortController();
    const {signal} = this.listener;
    this.addEventListener('click', this.onClick, {signal});
    this.addEventListener('craft-reorder', this.onReorder, {signal});
    this.addEventListener('entrySortDragStop', this.changed, {signal});
  }

  override disconnectedCallback(): void {
    this.listener?.abort();
    super.disconnectedCallback();
  }

  protected create(root: HTMLElement): MatrixInput {
    // SAFETY: PHP renders `entry-types` from the Matrix entry type descriptor schema.
    const entryTypes = JSON.parse(
      this.getAttribute('entry-types') ?? '[]'
    ) as MatrixEntryType[];
    const settings = JSON.parse(this.getAttribute('settings') ?? '{}');

    const input = new MatrixInput(
      root.id,
      entryTypes,
      this.getAttribute('input-name-prefix') ?? '',
      settings
    );

    if (this.hasAttribute('form-control')) {
      input.entryFactory = (type) => this.createEntry(type, entryTypes);
    }

    return input;
  }

  private onClick = (event: Event): void => {
    if (!(event.target instanceof Element)) {
      return;
    }
    const target = event.target;
    const add = target.closest<HTMLElement>('[data-form-matrix-add]');
    const remove = target.closest('[data-form-matrix-remove]');

    if (add) {
      void this.instance
        ?.addEntry(add.dataset.formMatrixAdd ?? '')
        .then(this.changed);

      return;
    }

    if (remove && this.canRemove()) {
      const entry = remove.closest<HTMLElement>('.matrixblock');
      if (entry) {
        const controller = MatrixEntry.forContainer(entry);

        if (controller) {
          controller.selfDestruct();
        } else {
          entry.remove();
        }
        this.changed();
      }
    }
  };

  private onReorder = (event: Event): void => {
    if (!(event instanceof CustomEvent) || !(event.target instanceof Element)) {
      return;
    }
    const entry = event.target.closest<HTMLElement>('.matrixblock');
    const controller = entry ? MatrixEntry.forContainer(entry) : undefined;
    const direction = event.detail.direction;

    if (direction === 'up') {
      controller?.moveUp();
    } else {
      controller?.moveDown();
    }
    this.changed();
  };

  private changed = (): void => {
    this.dispatchEvent(
      new CustomEvent('form-change', {bubbles: true, composed: true})
    );
    this.dispatchEvent(new Event('input', {bubbles: true, composed: true}));
  };

  private canRemove(): boolean {
    const minimum = Number(this.getAttribute('min-entries') ?? 0);
    const entries =
      this.instance?.entryElements().length ??
      this.querySelectorAll('.matrixblock').length;

    return entries > minimum;
  }

  private createEntry(
    type: string,
    entryTypes: MatrixEntryType[]
  ): HTMLElement {
    const uid = `uid:${crypto.randomUUID()}`;
    const name = this.getAttribute('input-name-prefix')!;
    const label =
      entryTypes.find((entryType) => entryType.handle === type)?.name ?? type;
    const entry = document.createElement('div');
    entry.className = 'matrixblock js-deletable';
    entry.dataset.id = uid;
    entry.dataset.type = type;
    entry.setAttribute('role', 'listitem');
    entry.append(
      this.hiddenInput(`${name}[sortOrder][]`, uid),
      this.hiddenInput(`${name}[entries][${uid}][type]`, type),
      this.titlebar(label),
      this.actions(label),
      this.fields()
    );

    return entry;
  }

  private titlebar(label: string): HTMLElement {
    const titlebar = document.createElement('div');
    titlebar.className = 'titlebar';
    const type = document.createElement('div');
    type.className = 'blocktype';
    type.textContent = label;
    const preview = document.createElement('div');
    preview.className = 'preview';
    titlebar.append(type, preview);

    return titlebar;
  }

  private actions(label: string): HTMLElement {
    const actions = document.createElement('div');
    actions.className = 'actions';
    const reorder = document.createElement('craft-reorder-button');
    const remove = document.createElement('craft-button');
    remove.dataset.formMatrixRemove = '';
    remove.setAttribute('icon', 'trash');
    remove.setAttribute('aria-label', t('Remove {type}', {type: label}));
    actions.append(reorder, remove);

    return actions;
  }

  private fields(): HTMLElement {
    const fields = document.createElement('div');
    fields.className = 'fields';
    const spinner = document.createElement('craft-spinner');
    spinner.setAttribute('label', t('Loading'));
    fields.append(spinner);

    return fields;
  }

  private hiddenInput(name: string, value: string): HTMLInputElement {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;

    return input;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-matrix-input': CraftMatrixInput;
  }
}

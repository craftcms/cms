import {MatrixInput, type MatrixEntryType} from './matrix-input';
import {MatrixEntry} from './matrix-entry';
import {ControllerElement} from '@/common/web-components';
import {t} from '@craftcms/ui';
import {NESTED_ELEMENT_UID_PREFIX} from '@/modules/forms/types';

/**
 * `<craft-matrix-input>` — boots a {@link MatrixInput} around the
 * server-rendered `[data-matrix-field]` it wraps, so PHP/Twig can emit the element
 * instead of a manual `new Craft.MatrixInput(...)` boot script.
 *
 * Configuration comes from attributes:
 *
 * - `entry-types` — JSON array of `{id, handle, name}` descriptors
 * - `input-name-prefix` — the field's namespaced input name
 * - `settings` — JSON {@link MatrixInputSettings}
 *
 * The wrapped `[data-matrix-field]` keeps its own `id`, which is what the
 * {@link MatrixInput} constructor resolves.
 */
export default class CraftMatrixInput extends ControllerElement<MatrixInput> {
  protected readonly rootSelector = '[data-matrix-field]';

  private listener?: AbortController;

  override connectedCallback(): void {
    super.connectedCallback();
    // `craft-field` only stretches a slotted control carrying this class
    // (`::slotted(.form-control)`); without it the field sizes to its own
    // content, so a nested Matrix ends up narrower than the field it sits in.
    // Applied here rather than in each renderer so both stacks get it.
    this.classList.add('form-control');
    this.listener?.abort();
    this.listener = new AbortController();
    const {signal} = this.listener;
    this.addEventListener('click', this.onClick, {signal});
    this.addEventListener('reorder', this.onReorder, {signal});
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
      const entry = remove.closest<HTMLElement>('[data-matrix-block]');
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
    const entry = event.target.closest<HTMLElement>('[data-matrix-block]');
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
      this.querySelectorAll('[data-matrix-block]').length;

    return entries > minimum;
  }

  private createEntry(
    type: string,
    entryTypes: MatrixEntryType[]
  ): HTMLElement {
    const uid = `${NESTED_ELEMENT_UID_PREFIX}${crypto.randomUUID()}`;
    const name = this.getAttribute('input-name-prefix')!;
    const label =
      entryTypes.find((entryType) => entryType.handle === type)?.name ?? type;
    // The same `craft-card` frame the server renders its blocks in.
    const entry = document.createElement('div');
    entry.dataset.matrixBlock = '';
    entry.dataset.id = uid;
    entry.dataset.type = type;
    entry.setAttribute('role', 'listitem');
    const card = document.createElement('craft-card');
    card.append(
      this.hiddenInput(`${name}[sortOrder][]`, uid),
      this.hiddenInput(`${name}[entries][${uid}][type]`, type),
      this.hiddenInput(`${name}[entries][${uid}][enabled]`, '1'),
      this.hiddenInput(`${name}[entries][${uid}][collapsed]`, ''),
      // A block the browser just minted has nothing behind it yet, so the save
      // has to propagate it to every site rather than treat it as an edit.
      this.hiddenInput(`${name}[entries][${uid}][fresh]`, '1'),
      this.titlebar(label),
      this.actions(label),
      this.fields()
    );
    entry.append(card);

    return entry;
  }

  private titlebar(label: string): HTMLElement {
    const titlebar = document.createElement('div');
    titlebar.slot = 'label';
    titlebar.className = 'flex flex-nowrap gap-1 items-center';
    titlebar.dataset.matrixBlockTitlebar = '';
    const type = document.createElement('div');
    type.textContent = label;
    const preview = document.createElement('div');
    preview.dataset.matrixBlockPreview = '';
    titlebar.append(type, preview);

    return titlebar;
  }

  private actions(label: string): HTMLElement {
    const actions = document.createElement('div');
    actions.slot = 'actions';
    actions.className = 'flex gap-1 items-center';
    actions.dataset.matrixBlockActions = '';
    const reorder = document.createElement('craft-reorder-button');
    const remove = document.createElement('craft-button');
    remove.dataset.formMatrixRemove = '';
    remove.setAttribute('icon', 'trash');
    remove.setAttribute('accessible-name', t('Remove {type}', {type: label}));
    actions.append(reorder, remove);

    return actions;
  }

  private fields(): HTMLElement {
    const fields = document.createElement('div');
    fields.dataset.matrixBlockFields = '';
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

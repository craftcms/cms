import {t, type ElementInfo} from '@craftcms/ui';
import {createElementSelectorModal} from '@/modules/element-selector-modal/create-element-selector-modal';
import {isInteractiveClick} from '@/common/utils/dom';
import type {ActionItem} from '@/common/types';
import {
  createElementActionDispatcher,
  createElementActionMenu,
  type ElementActionBehavior,
  type ElementActionMenuItem,
} from '@/modules/elements/composables/useElementActionMenu';
import type {PresentedElement} from '@/modules/elements/element-presentations';
import {ElementPresentationsController} from '@/modules/elements/element-presentations-controller';
import {openElementEditor} from '@/modules/elements/open-element-editor';
import {openImageEditorDialog} from '@/modules/image-editor/open-image-editor-dialog';
import type {ChipElement} from '@/modules/elements/types/element-data';
import {chipMenu} from '@/modules/ui/element-select/chipMenu';
import type {ElementPresentation} from '@/modules/ui/element-select/types';
import '@craftcms/ui/components/action-menu/action-menu';
import '@craftcms/ui/components/chip/chip';
import '@craftcms/ui/components/disclosure/disclosure';
import '@craftcms/ui/components/field/field';
import '@craftcms/ui/components/field-group/field-group';
import '@craftcms/ui/components/icon/icon';
import '@craftcms/ui/components/indicator/indicator';
import '@craftcms/ui/components/pane/pane';
import '@craftcms/ui/components/popover/popover';
import '@craftcms/ui/components/truncate/truncate';
import {
  html,
  LitElement,
  nothing,
  render,
  type PropertyValues,
  type TemplateResult,
} from 'lit';
import {customElement, property, state} from 'lit/decorators.js';
import {unsafeHTML} from 'lit/directives/unsafe-html.js';
import './craft-link-field.css';

/** A linked element, as its chip and its action menu draw it. */
export type LinkedElement = ElementPresentation &
  Pick<ChipElement, 'color'> & {status?: ChipElement['status']};

export type LinkTypeConfig = {
  id: string;
  label: string;
  kind: 'custom' | 'element' | 'text';
  prefixes?: string[];
  pattern?: string;
  inputAttributes?: Record<string, string>;
  elementType?: string;
  refHandle?: string;
  elementSelectConfig?: import('@/modules/ui/types').UiValues;
};

/** The link attributes a field can let authors set, under “Advanced”. */
export type LinkAdvancedField =
  | 'urlSuffix'
  | 'target'
  | 'title'
  | 'class'
  | 'id'
  | 'rel'
  | 'ariaLabel'
  | 'download';

type LinkAttributes = {
  urlSuffix: string;
  target: string;
  title: string;
  class: string;
  id: string;
  rel: string;
  ariaLabel: string;
  download: boolean;
  filename: string;
};

export type LinkFieldValue = LinkAttributes & {
  defaultLabel: string;
  /** The linked element's chip, for display only. */
  element?: LinkedElement | null;
  href: string;
  label: string;
  type: string;
  value: string;
};

/** Whether a change came from typing, or from a discrete choice. */
export type LinkChangeKind = 'typing' | 'discrete';

export type LinkChangeDetail = {
  kind: LinkChangeKind;
  value: LinkFieldValue;
};

type ElementSelectStartDetail = {
  restoreFocusTo?: HTMLElement;
  waitUntil: (promise: Promise<unknown>) => void;
};

const emptyAttributes: LinkAttributes = {
  urlSuffix: '',
  target: '',
  title: '',
  class: '',
  id: '',
  rel: '',
  ariaLabel: '',
  download: false,
  filename: '',
};

const formValueKeys = [
  'type',
  'value',
  'label',
  ...(Object.keys(emptyAttributes) as Array<keyof LinkAttributes>),
] as const;

let advancedPanelIndex = 0;

/** The narrowest a live field shows the link type and its destination side by side; narrower, it's a chip. */
const rowMinWidth = 320;

/** A chip's data, to tell chips apart: links are copied on their way back to a field. */
function chipData(element: LinkedElement): string {
  return JSON.stringify(element);
}

/** Element actions that open a dialog: the file preview and the image editor. */
const dialogBehaviors = new Set<ElementActionBehavior['type']>([
  'previewFile',
  'editImage',
]);

/** An element as the shared cache knows it: by its ID and site. */
function presentedElement(element: LinkedElement): PresentedElement {
  return {id: element.id, siteId: Number(element.siteId) || null};
}

/** Whether a chip is the one drawn from the selector's label, before the rest of it is fetched. */
function isPlaceholder(element: LinkedElement): boolean {
  return Object.keys(element).every((key) =>
    ['id', 'label', 'siteId'].includes(key)
  );
}

/**
 * Edits a link: its type, its destination, and optionally its label and
 * advanced attributes.
 *
 * By default, edits wait for `apply()`, so whatever contains the field draws
 * its own Apply and Cancel buttons; to cancel, give the link back as a new
 * `modelValue`. With `live`, it's a field like any other: each edit fires
 * `link-change` straight away, and it sits in a pane when it has a label or
 * advanced attributes. A live field too narrow for the link type and its
 * destination side by side shows the link as a chip instead, edited in a
 * popover; its menu's other actions, such as Replace, apply straight away.
 * With `compact`, as in table cells, a wide one keeps its label and advanced
 * attributes in a popover too, so it stays one line tall.
 *
 * @fires apply - The link was applied. `detail` is the `LinkFieldValue`, whose `value` is empty when the given link's element was removed.
 * @fires link-change - A `live` or `compact` field was edited. `detail` is a `LinkChangeDetail`.
 */
@customElement('craft-link-field')
class CraftLinkField extends LitElement {
  @property({attribute: 'advanced-fields', type: Array})
  advancedFields: string[] = [];

  @property({attribute: 'show-label-field', type: Boolean})
  showLabelField = false;

  @property({type: Array})
  types: LinkTypeConfig[] = [];

  @property({attribute: 'model-value', type: Object})
  modelValue: Partial<LinkFieldValue> | null = null;

  @property()
  name = '';

  @property({reflect: true, type: Boolean})
  disabled = false;

  /** Applies each edit as it's made, rather than on Apply. */
  @property({reflect: true, type: Boolean})
  live = false;

  /** Keeps a live field one line tall, for tight spots such as table cells. */
  @property({reflect: true, type: Boolean})
  compact = false;

  /** Names the field's buttons after it, where several read the same, as in a table's cells. */
  @property({attribute: 'context-label'})
  contextLabel = '';

  /**
   * Whether the field previews a value, such as in “Compare changes”. Previews
   * show in modals, which slideouts can't open over, so the linked element
   * can't be edited.
   */
  @property({type: Boolean})
  previewing = false;

  @state()
  private defaultLabel = '';

  @state()
  private label = '';

  /** The linked element, as the field was given or chose it. */
  @state()
  private selectedElement: LinkedElement | null = null;

  /**
   * Whether the given link's element was removed, so applying it unlinks it.
   * Part of the editing state, so it resets with the rest of it: when a new
   * link is given, an element is chosen, or the link type changes.
   */
  private elementRemoved = false;

  /**
   * The placeholder chip whose rest was last fetched, so it's fetched once.
   * Choosing the element again gives a new one, which is fetched again.
   */
  private requestedChip: LinkedElement | null = null;

  /**
   * The chips the field was given. A popover gives its link back each time it
   * opens, with the same chip, which may be older than what's cached. A new
   * server render brings new objects, even for the same data.
   */
  private readonly givenChips = new WeakSet<LinkedElement>();

  /**
   * The chips the field sent out since the server last gave it one. They
   * come back as copies, so they're told apart by their data.
   */
  private readonly sentChips = new Set<string>();

  /**
   * The linked element's chip, cached with relation fields' chips, so it's
   * refetched when the element is saved, in this tab or another one.
   */
  private readonly presentations = new ElementPresentationsController(
    this,
    () => ({
      elementType: this.selectedType?.elementType ?? '',
      viewMode: 'list',
      // As relation fields resolve it for chips, so they share cached chips.
      thumbSize: 'sm',
    }),
    () => {
      const element = this.linkedElement();
      return element ? [presentedElement(element)] : [];
    }
  );

  /** One dispatcher for the actions the server describes for the chip's element. */
  private readonly elementActionItems = createElementActionMenu();

  /** Runs the chip's actions that open a dialog, so the field can tell when one can't. */
  private readonly dispatchAction = createElementActionDispatcher();

  /**
   * The chip's element's own actions. Those that open a dialog first set aside
   * a popover the field is in, as Edit does, and bring it back once the dialog
   * has closed: the dialog taking focus would otherwise dismiss it.
   */
  private readonly toActionItems = (
    items: ElementActionMenuItem[]
  ): ActionItem[] =>
    this.elementActionItems(items).map((action, index) => {
      const item = items[index];

      if (
        !item ||
        item.type === 'hr' ||
        !dialogBehaviors.has(item.behavior.type)
      ) {
        return action;
      }

      const {behavior, destructive} = item;

      return {
        ...action,
        onClick: (event: Event) =>
          void this.runDialogAction(() =>
            behavior.type === 'editImage'
              ? this.editImage(behavior)
              : this.dispatchAction(behavior, destructive, event)
          ),
      };
    });

  @state()
  private linkAttributes: LinkAttributes = {...emptyAttributes};

  @state()
  private typeId = '';

  @state()
  private value = '';

  @state()
  private valueError = '';

  private readonly advancedPanelId = `craft-link-field-advanced-${++advancedPanelIndex}`;

  private readonly valueErrorId = `${this.advancedPanelId}-value-errors`;

  /** The last value a `live` edit sent out, so its echo doesn't reset what's being typed. */
  private emittedValue: Partial<LinkFieldValue> | null = null;

  /**
   * Where a live field's popover is drawn: outside a table cell, whose input
   * styles and click-to-focus would otherwise reach the editor, but in the
   * same top-level container, so a modal layer hides it along with the cell.
   */
  private popoverContainer: HTMLElement | null = null;

  /** The link a chip's popover edits, until it's applied. */
  @state()
  private draft: Partial<LinkFieldValue> | null = null;

  /** Whether a live field is too narrow for its link type and destination side by side. */
  @state()
  private narrow = false;

  /** What the popover shows: the whole link, for a chip, or a wide compact field's advanced attributes. */
  @state()
  private popoverContent: 'editor' | 'advanced' = 'editor';

  /** Whether the advanced attributes' popover is open, for the cog's `aria-expanded`. */
  @state()
  private advancedOpen = false;

  private width = 0;

  // Switches views a frame later: switching changes the field's height, which
  // would otherwise need another notification in the same frame.
  private readonly resizeObserver = new ResizeObserver(([entry]) => {
    this.width = entry?.contentRect.width ?? this.width;
    requestAnimationFrame(() => this.syncWidth());
  });

  /** Whether the popover is set aside while a dialog it opened is open. */
  private suspended = false;

  private resuming = false;

  private restoreFocusTo: HTMLElement | null = null;

  override connectedCallback(): void {
    super.connectedCallback();

    if (!this.typeId) {
      this.typeId = this.types[0]?.id ?? '';
    }

    // A field moved in the DOM, as a table row is, draws its popover again.
    if (this.live && this.hasUpdated) {
      this.requestUpdate();
    }

    this.resizeObserver.observe(this);
    this.addEventListener('focusout', this.handleFocusOut);
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.resizeObserver.disconnect();
    this.removeEventListener('focusout', this.handleFocusOut);
    this.popoverContainer?.remove();
    this.popoverContainer = null;
  }

  /**
   * Switches between the chip and the side-by-side view to suit the field's
   * width, except while it's being edited, which a switch would interrupt.
   */
  private syncWidth(): void {
    const narrow = this.width > 0 && this.width < rowMinWidth;

    if (narrow === this.narrow || this.isBeingEdited()) {
      return;
    }

    this.narrow = narrow;
  }

  private isBeingEdited(): boolean {
    const active = document.activeElement;

    return Boolean(
      this.editorPopover()?.open ||
      (active && active !== this && this.contains(active))
    );
  }

  private readonly handleFocusOut = (): void => {
    // Focus has left once the event is over.
    setTimeout(() => this.syncWidth());
  };

  /** Whether the field shows its link as a chip. */
  private get showsChip(): boolean {
    return this.live && (this.narrow || this.unavailable);
  }

  /**
   * Whether the link's type isn't one the field offers. Its editor would swap
   * in the first type that is, so it's kept as a chip that can only be removed.
   */
  private get unavailable(): boolean {
    return Boolean(this.modelValue?.value) && !this.modelType();
  }

  protected override updated(changedProperties: PropertyValues<this>): void {
    super.updated(changedProperties);

    if (!this.live) {
      return;
    }

    if (!this.popoverContainer) {
      this.popoverContainer = document.createElement('div');
      (this.closest('body > *') ?? document.body).append(this.popoverContainer);
    }

    render(this.renderPopover(), this.popoverContainer, {host: this});
  }

  override willUpdate(changedProperties: PropertyValues<this>): void {
    const modelChanged = changedProperties.has('modelValue');
    const echo = modelChanged && this.isEcho(this.modelValue);
    const given = modelChanged ? this.modelValue?.element : null;
    const fromServer = Boolean(
      given &&
      !this.givenChips.has(given) &&
      !this.sentChips.has(chipData(given))
    );

    if (modelChanged && !echo) {
      const value = this.modelValue ?? {};
      this.elementRemoved = false;
      // A link without a chip is one whose element no longer exists.
      this.selectedElement = value.element ?? null;
      this.defaultLabel = value.defaultLabel ?? '';
      this.label = value.label ?? '';
      this.linkAttributes = {
        urlSuffix: value.urlSuffix ?? '',
        target: value.target ?? '',
        title: value.title ?? '',
        class: value.class ?? '',
        id: value.id ?? '',
        rel: value.rel ?? '',
        ariaLabel: value.ariaLabel ?? '',
        download: Boolean(value.download),
        filename: value.filename ?? '',
      };
      this.typeId = value.type ?? '';
      this.value = value.value ?? '';
    }

    if (!this.typeId || !this.types.some((type) => type.id === this.typeId)) {
      this.typeId = this.types[0]?.id ?? '';
    }

    if (given) {
      // As relation fields' renders: the one the field first draws only fills
      // a gap, since it may be older than what's cached. Later ones replace it.
      if (fromServer && !isPlaceholder(given)) {
        this.presentations.seed([given], null, {
          replace: this.hasUpdated,
        });
      }
      if (fromServer) {
        this.sentChips.clear();
      }
      this.givenChips.add(given);
    }

    this.loadPlaceholderElement();
  }

  /** The element the link points to, if it's an element link. */
  private linkedElement(): LinkedElement | null {
    const type = this.selectedType;

    return this.value && type?.kind === 'element' && type.elementType
      ? this.selectedElement
      : null;
  }

  /** The linked element's chip: the cached one, as relation fields draw it, or the one the field has. */
  private get chip(): LinkedElement | null {
    const element = this.linkedElement();

    return element
      ? (this.presentations.get(element.id, element.siteId) ?? element)
      : null;
  }

  /** Fetches the rest of a chip the selector or a copied link only gave a label for. */
  private loadPlaceholderElement(): void {
    const element = this.chip;

    if (!element || !isPlaceholder(element)) {
      this.requestedChip = null;
      return;
    }

    if (element === this.requestedChip) {
      return;
    }

    this.requestedChip = element;
    this.presentations.load([presentedElement(element)]).catch(() => {});
  }

  private isEcho(value: Partial<LinkFieldValue> | null): boolean {
    const emitted = this.emittedValue;

    return Boolean(
      emitted &&
      value &&
      (Object.keys(emitted) as Array<keyof LinkFieldValue>).every(
        (key) =>
          key === 'href' || key === 'element' || value[key] === emitted[key]
      )
    );
  }

  private get selectedType(): LinkTypeConfig | undefined {
    return this.types.find((type) => type.id === this.typeId);
  }

  private hasAdvancedField(field: LinkAdvancedField): boolean {
    return this.advancedFields.includes(field);
  }

  private get showAdvancedFields(): boolean {
    return this.showLabelField || this.advancedFields.length > 0;
  }

  private handleTypeChange(event: Event): void {
    if (!(event.target instanceof HTMLSelectElement)) {
      return;
    }
    this.typeId = event.target.value;
    this.value = '';
    this.elementRemoved = false;
    this.defaultLabel = '';
    this.selectedElement = null;
    this.valueError = '';
    this.change('discrete');
  }

  private handleValueChange(event: Event): void {
    // SAFETY: This handler is registered only on the craft-input value control.
    const input = event.currentTarget as HTMLElementTagNameMap['craft-input'];
    this.value = input.value;
    this.valueError = '';
    this.defaultLabel = this.defaultLabelFor(
      this.normalizeTextValue(this.value)
    );
    this.change('typing');
  }

  private handleLabelChange(event: Event): void {
    // SAFETY: this handler is registered only on the craft-input label control.
    this.label = (
      event.currentTarget as HTMLElementTagNameMap['craft-input']
    ).value;
    this.change('typing');
  }

  private handleAttributeInput(
    attribute: Exclude<keyof LinkAttributes, 'download'>
  ): (event: Event) => void {
    return (event) => {
      // SAFETY: this handler is registered only on craft-input controls.
      this.linkAttributes = {
        ...this.linkAttributes,
        [attribute]: (
          event.currentTarget as HTMLElementTagNameMap['craft-input']
        ).value,
      };
      this.change('typing');
    };
  }

  private handleSwitch(
    update: (checked: boolean) => Partial<LinkAttributes>
  ): (event: Event) => void {
    return (event) => {
      // SAFETY: this handler is registered only on craft-switch controls.
      const {checked} =
        event.currentTarget as HTMLElementTagNameMap['craft-switch'];
      this.linkAttributes = {
        ...this.linkAttributes,
        ...update(checked),
      };
      this.change('discrete');
    };
  }

  /** Opens the element selector, from `trigger`, which focus returns to. */
  private async chooseElement(trigger: HTMLElement | null): Promise<void> {
    const type = this.selectedType;

    if (!type?.elementType || !type.refHandle) {
      return;
    }

    const config = {...type.elementSelectConfig};
    delete config.elementType;
    delete config.limit;
    delete config.single;

    await this.dispatchElementSelectStartEvent(trigger);

    /** Set when the selector hands off to a new entry's slideout. */
    let handoff: Promise<void> | null = null;

    const modal = await createElementSelectorModal(type.elementType, {
      ...config,
      hideOnSelect: true,
      modalTitle: t('Choose {type}', {type: type.label}),
      multiSelect: false,
      // Where a slideout opened from the selector, such as a new entry's,
      // nests and returns focus to
      triggerElement: () => this.actionMenuInvoker() ?? trigger,
      onHandoff: (settled) => {
        handoff = settled;
      },
      onSelect: (elements: ElementInfo[]) => {
        const [element] = elements;

        if (!element?.id) {
          return;
        }

        this.value = `{${type.refHandle}:${element.id}@${element.siteId}:url}`;
        this.elementRemoved = false;
        this.defaultLabel = String(element.label || '');
        this.selectedElement = {
          id: element.id,
          label: this.defaultLabel,
          siteId: element.siteId,
        };
        this.valueError = '';
        this.change('discrete');
      },
    });

    // The modal lives on <body>, outside this element's shadow root, so its DOM
    // events never reach here — the controller is the subscription point.
    modal.on('close', () => {
      const end = () => this.dispatchElementSelectEvent('element-select-end');

      // A popover this field is in would otherwise come back while the
      // slideout is open, and focus moving into the slideout dismisses it.
      if (handoff) {
        void handoff.then(end);
      } else {
        end();
      }

      modal.destroy();
    });
  }

  /** Unlinks the element, and moves focus to the Choose button that takes the chip's place. */
  private async removeElement(): Promise<void> {
    if (this.showsChip) {
      return this.removeLink();
    }

    this.value = '';
    this.defaultLabel = '';
    this.selectedElement = null;
    this.elementRemoved = Boolean(this.modelValue?.value);
    this.change('discrete');
    await this.updateComplete;
    this.querySelector<HTMLElement>(
      '.craft-link-field__element > craft-button'
    )?.focus();
  }

  /**
   * Opens the linked element's editor in a slideout. The chip is refetched
   * when the element is saved.
   */
  private async editElement(
    type: LinkTypeConfig,
    element: LinkedElement
  ): Promise<void> {
    if (this.previewing) {
      return;
    }

    const opener = this.actionMenuInvoker();

    // A popover this field is in would otherwise be dismissed by focus moving
    // into the slideout.
    await this.dispatchElementSelectStartEvent(opener);

    const panel = await openElementEditor(element, type.elementType!, {
      opener,
      onSaved: ({data}) =>
        this.replaceAsset(
          type,
          element,
          data?.replacedAssetId,
          data?.newAssetId
        ),
      onClosed: () => this.dispatchElementSelectEvent('element-select-end'),
    });

    if (!panel) {
      this.dispatchElementSelectEvent('element-select-end');
    }
  }

  /**
   * Points the link at the asset an image was saved as, in place of the one
   * it was edited from, unless the field is disabled or has moved on.
   */
  private replaceAsset(
    type: LinkTypeConfig,
    element: LinkedElement,
    replacedAssetId: unknown,
    newAssetId: unknown
  ): void {
    if (
      this.disabled ||
      this.typeId !== type.id ||
      this.selectedElement?.id !== element.id
    ) {
      return;
    }

    const replacedId = Number(replacedAssetId);
    const newId = Number(newAssetId);
    // Swaps only the ID, keeping the reference's site and whatever follows.
    const newValue = this.value.replace(/^\{(\w+):\d+/, `{$1:${newId}`);
    const replaced =
      replacedId === element.id &&
      Number.isInteger(newId) &&
      newId > 0 &&
      newValue !== this.value;

    if (replaced) {
      this.value = newValue;
      this.selectedElement = {
        id: newId,
        label: this.defaultLabel,
        siteId: element.siteId,
      };
      this.change('discrete');
    }
  }

  /**
   * Opens the image editor for the chip's asset. A save in place is
   * broadcast, which refetches the chip; one saved as a new asset takes the
   * linked one's place.
   */
  private editImage(
    behavior: Extract<ElementActionBehavior, {type: 'editImage'}>
  ): Promise<void> {
    const type = this.selectedType;
    const element = this.chip;

    return openImageEditorDialog(behavior.settings, (result) => {
      if (type && element && result.newAssetId) {
        this.replaceAsset(type, element, behavior.assetId, result.newAssetId);
      }
    }).then(({closed}) => closed);
  }

  /** Runs an action that opens a dialog, with the field's popover set aside until the dialog closes. */
  private async runDialogAction(
    open: () => void | Promise<void>
  ): Promise<void> {
    await this.dispatchElementSelectStartEvent(this.actionMenuInvoker());

    try {
      await open();
    } catch (error) {
      console.error(error);
    }

    if (this.isConnected) {
      this.dispatchElementSelectEvent('element-select-end');
    }
  }

  /** Opens the editor on a double-click, unless it landed on one of the chip's own controls. */
  private handleChipDoubleClick(type: LinkTypeConfig, event: MouseEvent): void {
    const element = this.chip;

    if (
      element?.canEdit !== true ||
      isInteractiveClick(event, event.currentTarget)
    ) {
      return;
    }

    void this.editElement(type, element);
  }

  /** Clears the chip's link, and moves focus to the Add link button that takes its place. */
  private async removeLink(): Promise<void> {
    this.value = '';
    this.label = '';
    this.defaultLabel = '';
    this.selectedElement = null;
    this.linkAttributes = {...emptyAttributes};
    this.change('discrete');
    await this.updateComplete;
    this.addLinkButton()?.focus();
  }

  private addLinkButton(): HTMLElement | null {
    return this.querySelector<HTMLElement>('.craft-link-field__add');
  }

  /** The button that opens the chip's action menu. */
  private actionMenuInvoker(): HTMLElement | null {
    return this.querySelector<HTMLElement>(
      '.craft-link-field__chip craft-action-menu > [slot="invoker"]'
    );
  }

  /**
   * The chip's menu, built from the current chip each time it opens. The menu
   * keeps this one function, so a chip that's refreshed while the menu is open
   * doesn't rebuild its items and lose their focus.
   */
  private readonly chipMenuProvider = () => {
    const type = this.selectedType;
    const element = this.chip;

    return type && element ? this.chipActions(type, element) : [];
  };

  /** The chip's menu: the element's own actions, then Edit and Replace, then Remove. */
  private chipActions(type: LinkTypeConfig, element: LinkedElement) {
    return chipMenu(
      element,
      {
        elementType: type.elementType ?? '',
        elementDisplayName: type.label,
        editable: !this.disabled,
        toActionItems: this.toActionItems,
      },
      {
        edit: this.previewing
          ? undefined
          : {
              label: t('Edit {type}', {type: type.label}),
              open: () => void this.editElement(type, element),
            },
        replace: () => void this.chooseElement(this.actionMenuInvoker()),
        remove: () => void this.removeElement(),
        fieldActions: this.showsChip ? [this.editLinkAction()] : [],
        removeLabel: this.showsChip ? t('Remove link') : undefined,
      }
    );
  }

  private editLinkAction(): ActionItem {
    return {icon: 'link', label: t('Edit link'), onClick: this.openEditor};
  }

  /** The chip's menu, for a link that isn't to an element. */
  private readonly linkMenuProvider = (): ActionItem[] =>
    this.disabled
      ? []
      : [
          ...(this.modelType() ? [this.editLinkAction()] : []),
          {
            icon: 'remove',
            label: t('Remove link'),
            variant: 'danger',
            onClick: () => void this.removeLink(),
          },
        ];

  private async dispatchElementSelectStartEvent(
    restoreFocusTo: HTMLElement | null
  ): Promise<void> {
    const promises: Array<Promise<unknown>> = [];

    this.dispatchEvent(
      new CustomEvent<ElementSelectStartDetail>('element-select-start', {
        bubbles: true,
        detail: {
          restoreFocusTo: restoreFocusTo ?? undefined,
          waitUntil: (promise) => promises.push(promise),
        },
      })
    );

    await Promise.all(promises);
  }

  private dispatchElementSelectEvent(name: string): void {
    this.dispatchEvent(
      new CustomEvent(name, {
        bubbles: true,
      })
    );
  }

  private normalizeTextValue(value: string): string {
    const type = this.selectedType;
    value = value.trim();

    if (!value || type?.kind !== 'text') {
      return value;
    }

    if (this.validateTextValue(value)) {
      return value;
    }

    const prefix = type.prefixes?.[0] ?? '';
    const prefixed = `${prefix}${value}`;

    return this.validateTextValue(prefixed) ? prefixed : value;
  }

  private normalizeUrlSuffix(value: string): string {
    value = value.trim();

    if (!value || value.startsWith('#') || value.startsWith('?')) {
      return value;
    }

    return `?${value}`;
  }

  private renderTextDestination(typeId: string, value: string): string {
    if (typeId === 'url' || typeId === 'email') {
      return value.replaceAll(' ', '+');
    }

    if (typeId === 'sms') {
      return this.renderSmsDestination(value);
    }

    if (typeId === 'tel') {
      return value.replaceAll(' ', '-');
    }

    return value;
  }

  private renderSmsDestination(value: string): string {
    const [, root = '', queryString = ''] =
      value.match(/^([^?&]*)(?:[?&]+(.*))?$/) ?? [];
    const destination = queryString
      ? `${root}&${queryString.replaceAll(' ', '%20')}`
      : root;

    return destination.replaceAll(' ', '-');
  }

  private validateTextValue(value: string): boolean {
    const pattern = this.selectedType?.pattern;

    if (!pattern) {
      return true;
    }

    try {
      return new RegExp(pattern, 'i').test(value);
    } catch {
      return true;
    }
  }

  private defaultLabelFor(value: string): string {
    const type = this.selectedType;

    if (!value || type?.kind !== 'text') {
      return this.chip?.label || this.defaultLabel;
    }

    let label = value;
    for (const prefix of type.prefixes ?? []) {
      if (label.toLowerCase().startsWith(prefix.toLowerCase())) {
        label = label.slice(prefix.length);
      }
    }

    return /^[^/]+\/$/.test(label) ? label.slice(0, -1) : label;
  }

  /** The link as it stands, or `null` when no link type is selected. */
  private currentValue(): LinkFieldValue | null {
    const type = this.selectedType;

    if (!type) {
      return null;
    }

    const value =
      type.kind === 'text' ? this.normalizeTextValue(this.value) : this.value;
    const urlSuffix = this.normalizeUrlSuffix(this.linkAttributes.urlSuffix);
    const destination =
      type.kind === 'text' ? this.renderTextDestination(type.id, value) : value;

    const element = type.kind === 'element' ? this.chip : null;

    if (element) {
      this.sentChips.add(chipData(element));
    }

    return {
      ...this.linkAttributes,
      defaultLabel: this.defaultLabelFor(value),
      element,
      href: value ? `${destination}${urlSuffix}` : '',
      label: this.label.trim(),
      title: this.linkAttributes.title.trim(),
      type: type.id,
      urlSuffix,
      value,
    };
  }

  /** Commits an edit straight away when `live`. */
  private change(kind: LinkChangeKind): void {
    if (!this.live) {
      return;
    }

    const value = this.currentValue();

    if (!value) {
      return;
    }

    this.emittedValue = value;
    this.modelValue = value;
    this.dispatchEvent(
      new CustomEvent<LinkChangeDetail>('link-change', {
        bubbles: true,
        detail: {kind, value},
      })
    );
  }

  /** Fires `apply` with the edited link, or shows why it can't be applied. */
  apply(): void {
    const type = this.selectedType;
    const linkValue = this.currentValue();

    if (!type || !linkValue) {
      return;
    }

    if (!linkValue.value && !this.elementRemoved) {
      this.valueError = t('{attribute} cannot be blank.', {
        attribute: type.label,
      });

      return;
    }

    if (type.kind === 'text' && !this.validateTextValue(linkValue.value)) {
      this.valueError = t('{attribute} is invalid.', {
        attribute: type.label,
      });

      return;
    }

    this.valueError = '';
    this.modelValue = linkValue;
    this.dispatchEvent(
      new CustomEvent<LinkFieldValue>('apply', {
        bubbles: true,
        detail: linkValue,
      })
    );
  }

  /** A label for one of the controls in the type + destination row, marked up like a grouped UI field's. */
  private renderLabel(label: string): TemplateResult {
    // A table's column heading already labels a compact field.
    return html`<span slot="label" class=${this.compact ? 'sr-only' : nothing}
      >${label}</span
    >`;
  }

  private renderTypeSelect(): TemplateResult | typeof nothing {
    if (this.types.length < 2) {
      return nothing;
    }

    return html`
      <craft-field>
        ${this.renderLabel(t('Link Type'))}
        <craft-select
          slot="input"
          .value=${this.typeId}
          .disabled=${this.disabled}
        >
          <select
            slot="input"
            .value=${this.typeId}
            @change=${this.handleTypeChange}
          >
            ${this.types.map(
              (type) => html`<option value=${type.id}>${type.label}</option>`
            )}
          </select>
        </craft-select>
      </craft-field>
    `;
  }

  /**
   * The linked element's chip and its action menu, as relation fields draw them.
   * A link whose element no longer exists shows the Choose button instead, with
   * its reference kept until another element is chosen, as in Craft 5: the
   * server treats it as empty (`BaseElementLinkType::isValueEmpty()`).
   */
  private renderSelectedElement(
    type: LinkTypeConfig,
    element: LinkedElement,
    size: 'small' | 'xsmall' = 'small'
  ): TemplateResult {
    const label = element.label || this.defaultLabel;
    const status = element.status;
    const hasActions = this.chipActions(type, element).length > 0;

    return html`
      <craft-chip
        size=${size}
        class="craft-link-field__chip"
        data-color=${element.color ?? nothing}
        ?show-status=${Boolean(status)}
        ?show-thumb=${Boolean(element.thumbHtml)}
        @dblclick=${(event: MouseEvent) =>
          this.handleChipDoubleClick(type, event)}
      >
        ${element.thumbHtml
          ? html`<div slot="thumbnail" class="flex">
              ${unsafeHTML(element.thumbHtml)}
            </div>`
          : nothing}
        ${status
          ? html`<div slot="status">
              ${status.draft
                ? html`<craft-icon
                    name="scribble"
                    label=${`${t('Status:')} ${status.label}`}
                  ></craft-icon>`
                : html`<craft-indicator
                    fill=${status.fill}
                    shape=${status.shape ?? nothing}
                    label=${`${t('Status:')} ${status.label}`}
                  ></craft-indicator>`}
            </div>`
          : nothing}
        <craft-truncate>${label}</craft-truncate>
        ${hasActions
          ? html`<div slot="suffix" class="flex items-center">
              <craft-action-menu
                label=${this.buttonName(t('Actions'))}
                .actions=${this.chipMenuProvider}
              ></craft-action-menu>
            </div>`
          : nothing}
      </craft-chip>
    `;
  }

  private renderTypeInput(): TemplateResult | typeof nothing {
    const type = this.selectedType;

    if (!type) {
      return nothing;
    }

    if (type.kind === 'element') {
      return html`
        <craft-field>
          ${this.renderLabel(type.label)}
          <div slot="input" class="craft-link-field__element">
            ${this.chip
              ? this.renderSelectedElement(type, this.chip)
              : html`<craft-button
                  type="button"
                  variant="dashed"
                  icon="plus"
                  ?disabled=${this.disabled}
                  aria-describedby=${this.valueError
                    ? this.valueErrorId
                    : nothing}
                  aria-invalid=${this.valueError ? 'true' : nothing}
                  @click=${(event: Event) =>
                    void this.chooseElement(event.currentTarget as HTMLElement)}
                >
                  ${t('Choose')}
                </craft-button>`}
          </div>
          ${this.renderValueError('feedback')}
        </craft-field>
      `;
    }

    const inputType = type.inputAttributes?.type ?? 'text';
    const inputMode = type.inputAttributes?.inputmode ?? undefined;

    return html`
      <craft-field>
        ${this.renderLabel(type.label)}
        <craft-input
          slot="input"
          data-link-value
          type=${inputType}
          inputmode=${inputMode ?? nothing}
          ?invalid=${Boolean(this.valueError)}
          .value=${this.value}
          .disabled=${this.disabled}
          @input=${this.handleValueChange}
        ></craft-input>
        ${this.renderValueError('feedback')}
      </craft-field>
    `;
  }

  private renderValueError(slot?: string): TemplateResult | typeof nothing {
    if (!this.valueError) {
      return nothing;
    }

    return html`
      <ul id=${this.valueErrorId} class="errors" slot=${slot ?? nothing}>
        <li>
          <span class="sr-only">${t('Error:')}</span>
          ${this.valueError}
        </li>
      </ul>
    `;
  }

  private renderTextAttribute(
    attribute: Exclude<keyof LinkAttributes, 'download' | 'target'>,
    label: string,
    options: {helpText?: string; code?: boolean; placeholder?: string} = {}
  ): TemplateResult {
    return html`
      <craft-input
        label=${label}
        help-text=${options.helpText ?? nothing}
        placeholder=${options.placeholder ?? nothing}
        type="text"
        ?monospace=${options.code}
        .value=${this.linkAttributes[attribute] ?? ''}
        .disabled=${this.disabled}
        @input=${this.handleAttributeInput(attribute)}
      ></craft-input>
    `;
  }

  private renderAdvancedField(field: LinkAdvancedField): TemplateResult {
    const separateWithSpaces = t('Separate multiple values with spaces.');

    switch (field) {
      case 'urlSuffix':
        return this.renderTextAttribute('urlSuffix', t('URL Suffix'), {
          helpText: t(
            'Query params (e.g. {ex1}) or a URI fragment (e.g. {ex2}) that should be appended to the URL.',
            {ex1: '?p1=foo&p2=bar', ex2: '#anchor'}
          ),
        });
      case 'target':
        return html`
          <craft-switch
            label=${t('Open in a new tab')}
            .checked=${this.linkAttributes.target === '_blank'}
            .disabled=${this.disabled}
            @change=${this.handleSwitch((checked) => ({
              target: checked ? '_blank' : '',
            }))}
          ></craft-switch>
        `;
      case 'title':
        return this.renderTextAttribute('title', t('Title Text'));
      case 'class':
        return this.renderTextAttribute('class', t('Class Name'), {
          helpText: separateWithSpaces,
          code: true,
        });
      case 'id':
        return this.renderTextAttribute('id', t('ID'), {code: true});
      case 'rel':
        return this.renderTextAttribute(
          'rel',
          t('Relation ({ex})', {ex: 'rel'}),
          {
            helpText: separateWithSpaces,
            code: true,
          }
        );
      case 'ariaLabel':
        return this.renderTextAttribute('ariaLabel', t('ARIA Label'));
      case 'download':
        return html`
          <craft-switch
            label=${t('Download')}
            .checked=${this.linkAttributes.download}
            .disabled=${this.disabled}
            @change=${this.handleSwitch((download) => ({
              download,
            }))}
          ></craft-switch>
          ${this.linkAttributes.download
            ? this.renderTextAttribute('filename', t('Filename'))
            : nothing}
        `;
    }
  }

  private renderAdvancedFields(): TemplateResult | typeof nothing {
    if (!this.showAdvancedFields) {
      return nothing;
    }

    return html`
      <craft-disclosure id=${this.advancedPanelId} size="small">
        <craft-button
          slot="invoker"
          type="button"
          variant="plain"
          size="small"
          flush
          icon="chevron-right"
          icon-position="suffix"
        >
          ${t('Advanced')}
        </craft-button>
        ${this.renderAdvancedFieldGroup('content')}
      </craft-disclosure>
    `;
  }

  /** The label and advanced attribute inputs. */
  private renderAdvancedFieldGroup(slot?: string): TemplateResult {
    const fields = (
      [
        'urlSuffix',
        'target',
        'title',
        'class',
        'id',
        'rel',
        'ariaLabel',
        'download',
      ] as const
    ).filter((field) => this.hasAdvancedField(field));

    return html`
      <craft-field-group slot=${slot ?? nothing}>
        ${this.showLabelField
          ? html`
              <craft-input
                label=${t('Label')}
                type="text"
                placeholder=${this.chip?.label || this.defaultLabel || nothing}
                .value=${this.label}
                .disabled=${this.disabled}
                @input=${this.handleLabelChange}
              ></craft-input>
            `
          : nothing}
        ${fields.map((field) => this.renderAdvancedField(field))}
      </craft-field-group>
    `;
  }

  private renderHiddenInputs(): TemplateResult[] | typeof nothing {
    if (!this.name) {
      return nothing;
    }

    return formValueKeys.map((key) => {
      const value = this.modelValue?.[key];

      return html`
        <input
          type="hidden"
          name=${`${this.name}[${key}]`}
          .value=${typeof value === 'boolean'
            ? value
              ? '1'
              : ''
            : (value ?? '')}
          ?disabled=${this.disabled}
        />
      `;
    });
  }

  /** The type of the link the field was given, unless it's no longer available. */
  private modelType(): LinkTypeConfig | undefined {
    return this.types.find((type) => type.id === this.modelValue?.type);
  }

  /** Names a button after the field. */
  private buttonName(action: string): string {
    return this.contextLabel
      ? t('{action}, {cell}', {action, cell: this.contextLabel})
      : action;
  }

  private readonly openEditor = (): void => {
    void this.openPopover('editor');
  };

  /** The cog opens the advanced attributes, and closes them again. */
  private readonly toggleAdvanced = (): void => {
    const popover = this.editorPopover();

    if (popover?.open && this.popoverContent === 'advanced') {
      void popover.hide();
      return;
    }

    void this.openPopover('advanced');
  };

  /** Shows the popover once it's drawn with the content and anchor asked for. */
  private async openPopover(content: 'editor' | 'advanced'): Promise<void> {
    this.popoverContent = content;
    await this.updateComplete;
    await this.editorPopover()?.show();
  }

  /** What the popover sits beside: the chip, or a wide field's Advanced button. */
  private popoverAnchor(): HTMLElement | undefined {
    return (
      this.querySelector<HTMLElement>(
        this.popoverContent === 'advanced'
          ? '.craft-link-field__advanced-button'
          : '.craft-link-field--compact'
      ) ?? undefined
    );
  }

  private editorPopover(): HTMLElementTagNameMap['craft-popover'] | null {
    return (
      this.popoverContainer?.querySelector<
        HTMLElementTagNameMap['craft-popover']
      >(':scope > craft-popover') ?? null
    );
  }

  private popoverEditor(): CraftLinkField | null {
    return (
      this.popoverContainer?.querySelector<CraftLinkField>(
        'craft-link-field'
      ) ?? null
    );
  }

  private renderCompact(): TemplateResult {
    const value = this.modelValue;
    const type = this.modelType();

    if (!value?.value) {
      return html`
        <div class="craft-link-field--compact">
          <craft-button
            class="craft-link-field__add"
            type="button"
            size="small"
            variant="dashed"
            icon="plus"
            ?disabled=${this.disabled}
            aria-label=${this.contextLabel
              ? this.buttonName(t('Add link'))
              : nothing}
            @click=${this.openEditor}
          >
            ${t('Add link')}
          </craft-button>
        </div>
      `;
    }

    const element = type?.kind === 'element' ? this.chip : null;
    const label = value.label || value.defaultLabel || value.value;

    return html`
      <div class="craft-link-field--compact">
        ${type && element
          ? this.renderSelectedElement(type, element, 'xsmall')
          : html`
              <craft-chip size="xsmall" class="craft-link-field__chip">
                <craft-truncate>${label}</craft-truncate>
                ${this.disabled
                  ? nothing
                  : html`<div slot="suffix" class="flex items-center">
                      <craft-action-menu
                        label=${this.buttonName(t('Actions'))}
                        .actions=${this.linkMenuProvider}
                      ></craft-action-menu>
                    </div>`}
              </craft-chip>
            `}
        ${type
          ? nothing
          : html`<p class="craft-link-field__note">
              ${t('This link type is no longer available.')}
            </p>`}
      </div>
    `;
  }

  private renderPopover(): TemplateResult {
    if (this.popoverContent === 'advanced' && !this.showsChip) {
      return html`
        <craft-popover
          class="craft-link-popover"
          .anchor=${this.popoverAnchor()}
          manual
          without-invoker-aria
          placement="bottom-end"
          @craft-after-show=${this.handlePopoverAfterShow}
          @craft-after-hide=${this.handlePopoverAfterHide}
        >
          <div slot="content-body">${this.renderAdvancedFieldGroup()}</div>
        </craft-popover>
      `;
    }

    return html`
      <craft-popover
        class="craft-link-popover"
        .anchor=${this.popoverAnchor()}
        manual
        without-invoker-aria
        placement="bottom-start"
        @craft-show=${this.handlePopoverShow}
        @craft-after-show=${this.handlePopoverAfterShow}
        @craft-after-hide=${this.handlePopoverAfterHide}
      >
        <div slot="content-body">
          <craft-link-field
            .types=${this.types}
            .modelValue=${this.draft}
            .showLabelField=${this.showLabelField}
            .advancedFields=${this.advancedFields}
            .disabled=${this.disabled}
            .previewing=${this.previewing}
            @apply=${this.handleEditorApply}
            @element-select-start=${this.suspendPopover}
            @element-select-end=${this.resumePopover}
          ></craft-link-field>
        </div>
        <div slot="content-footer">
          <craft-button
            type="button"
            variant="plain"
            size="small"
            @click=${() => void this.editorPopover()?.hide()}
          >
            ${t('Cancel')}
          </craft-button>
          <craft-button
            type="button"
            variant="primary"
            size="small"
            @click=${() => this.popoverEditor()?.apply()}
          >
            ${t('Apply')}
          </craft-button>
        </div>
      </craft-popover>
    `;
  }

  private readonly handlePopoverShow = (event: Event): void => {
    if (event.target === this.editorPopover() && !this.resuming) {
      this.draft = {...this.modelValue};
    }
  };

  private readonly handlePopoverAfterShow = async (
    event: Event
  ): Promise<void> => {
    if (event.target !== this.editorPopover()) {
      return;
    }

    this.advancedOpen = this.popoverContent === 'advanced';
    this.resuming = false;
    await this.updateComplete;

    if (this.restoreFocusTo?.isConnected) {
      this.restoreFocusTo.focus();
      this.restoreFocusTo = null;

      return;
    }

    (this.popoverContent === 'advanced'
      ? this.popoverContainer
      : this.popoverEditor()
    )
      ?.querySelector<HTMLElement>('select, input, craft-input, craft-button')
      ?.focus();
  };

  /**
   * The popover isn't opened by a button of its own, so focus goes back to the
   * chip's menu, or the Add link button, unless it has already moved on.
   */
  private readonly handlePopoverAfterHide = async (
    event: Event
  ): Promise<void> => {
    if (event.target !== this.editorPopover()) {
      return;
    }

    this.advancedOpen = false;

    if (this.suspended) {
      return;
    }

    await this.updateComplete;
    const active = document.activeElement;

    if (
      !active ||
      active === document.body ||
      this.popoverContainer?.contains(active)
    ) {
      (this.popoverContent === 'advanced'
        ? this.querySelector<HTMLElement>('.craft-link-field__advanced-button')
        : (this.actionMenuInvoker() ?? this.addLinkButton())
      )?.focus();
    }

    this.syncWidth();
  };

  private readonly handleEditorApply = (event: Event): void => {
    const {detail: value} = event as CustomEvent<LinkFieldValue>;
    event.stopPropagation();
    // Not an echo of the field's own edit, so it loads into the editing state
    this.emittedValue = null;
    this.modelValue = value;
    this.dispatchEvent(
      new CustomEvent<LinkChangeDetail>('link-change', {
        bubbles: true,
        detail: {kind: 'discrete', value},
      })
    );
    void this.editorPopover()?.hide();
  };

  private readonly suspendPopover = (event: Event): void => {
    const {detail} = event as CustomEvent<ElementSelectStartDetail>;
    this.restoreFocusTo = detail.restoreFocusTo ?? null;
    this.suspended = true;
    detail.waitUntil(this.editorPopover()?.hide() ?? Promise.resolve());
  };

  private readonly resumePopover = (): void => {
    this.suspended = false;
    this.resuming = true;
    void this.editorPopover()?.show();
  };

  /** A wide compact field's button for its label and advanced attributes, which open in a popover. */
  private renderAdvancedButton(): TemplateResult | typeof nothing {
    if (!this.showAdvancedFields) {
      return nothing;
    }

    return html`
      <div class="craft-link-field__advanced">
        <craft-button
          class="craft-link-field__advanced-button"
          type="button"
          variant="plain"
          size="small"
          icon="gear"
          aria-label=${this.buttonName(t('Advanced'))}
          aria-expanded=${this.advancedOpen ? 'true' : 'false'}
          @click=${this.toggleAdvanced}
        ></craft-button>
      </div>
    `;
  }

  override render(): TemplateResult {
    if (this.showsChip) {
      return html`${this.renderCompact()}${this.renderHiddenInputs()}`;
    }

    const content = html`
      <div class="craft-link-field">
        <div class="craft-link-field__destination">
          ${this.renderTypeSelect()} ${this.renderTypeInput()}
          ${this.compact ? this.renderAdvancedButton() : nothing}
        </div>
        ${this.compact ? nothing : this.renderAdvancedFields()}
      </div>
    `;

    return html`
      ${this.live && !this.compact && this.showAdvancedFields
        ? html`<craft-pane
            class="craft-link-field__pane"
            appearance="outline"
            padding="lg"
            >${content}</craft-pane
          >`
        : content}
      ${this.renderHiddenInputs()}
    `;
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }
}

export default CraftLinkField;

declare global {
  interface HTMLElementTagNameMap {
    'craft-link-field': CraftLinkField;
  }
}

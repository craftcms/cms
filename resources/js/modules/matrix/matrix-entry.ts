/**
 * MatrixEntry — modern TypeScript port of the legacy `Craft.MatrixInput.Entry`.
 *
 * The per-`.matrixblock` controller: collapse/expand (with the preview-text
 * summary and localStorage persistence), the block action menu, enable/disable,
 * move/duplicate/copy/paste/delete.
 */

import {Base, getInputPostVal, hasAttr} from '@craftcms/garnish';
import {t} from '@craftcms/ui';
import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';
import type {EntryFieldLayoutFormHost} from '@/modules/forms/entry-field-layout-form-host';
import {animationDuration, MatrixInput} from './matrix-input';
import {containerMatrixEntries} from './support';
import {
  type LegacyDisclosureMenu,
  craft,
  jqData,
  legacyGarnish,
  setJqData,
} from './interop';

type JsonValue =
  | string
  | number
  | boolean
  | null
  | JsonValue[]
  | {[key: string]: JsonValue};

export class MatrixEntry extends Base {
  /** The entry controller for a `.matrixblock` container, if one was booted. */
  static forContainer(container: Element): MatrixEntry | undefined {
    return containerMatrixEntries.get(container);
  }

  matrix: MatrixInput;
  container: HTMLElement;
  titlebar: HTMLElement | null;
  fieldsContainer: HTMLElement | null;
  previewContainer: HTMLElement | null;
  actionMenu: HTMLElement | null = null;
  collapsedInput: HTMLInputElement | null = null;

  actionDisclosure: LegacyDisclosureMenu | null = null;
  uiLabel: string | null = null;

  isNew: boolean;
  id: string | null;

  collapsed = false;

  constructor(matrix: MatrixInput, container: HTMLElement) {
    super();

    this.matrix = matrix;
    this.container = container;
    this.titlebar = container.querySelector(':scope > .titlebar');
    this.previewContainer =
      this.titlebar?.querySelector(':scope > .preview') ?? null;
    this.fieldsContainer = container.querySelector(':scope > .fields');
    const formHost =
      this.fieldsContainer?.querySelector<EntryFieldLayoutFormHost>(
        'craft-entry-field-layout-form'
      );
    if (formHost) {
      formHost.requestMetadata = () => ({
        elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
        elementId: null,
        canonicalId: null,
        draftId: null,
        revisionId: null,
        provisional: null,
        elementUid:
          this.matrix.elementEditor?.getDraftElementUid(
            this.container.dataset.uid
          ) ?? this.container.dataset.uid,
        fieldId: this.matrix.settings!.fieldId,
        ownerId: this.matrix.settings!.ownerId,
        siteId: this.matrix.settings!.siteId,
        typeId: this.container.dataset.typeId,
        sortOrder:
          Array.from(this.container.parentElement?.children ?? []).indexOf(
            this.container
          ) + 1,
      });
    }

    containerMatrixEntries.set(container, this);
    // PHP-emitted snippets (expand/collapse-all) read `$(block).data('entry')`.
    setJqData(container, 'entry', this);

    this.id = container.dataset.id ?? null;
    this.isNew = !this.id || this.id.startsWith('new');

    const actionMenuBtn = this.container.querySelector<HTMLElement>(
      ':scope > .actions > .action-btn'
    );
    if (actionMenuBtn) {
      this.actionDisclosure =
        jqData(actionMenuBtn, 'disclosureMenu') ||
        new (legacyGarnish().DisclosureMenu)(actionMenuBtn);
      this.actionMenu = this.actionDisclosure.$container[0] ?? null;
    }

    this.uiLabel = container.dataset.uiLabel ?? null;

    this.actionDisclosure?.on('show', () => this.prepareActionMenu());
    this.actionDisclosure?.on('hide', () => {
      this.container.classList.remove('active');
    });

    for (const option of this.actionMenuOptions()) {
      this.addListener(option, 'activate', (event) => {
        if (
          !(event instanceof Event) ||
          !(event.target instanceof HTMLElement)
        ) {
          return;
        }
        event.preventDefault();
        this.onActionSelect(event.target);
      });
    }

    // Was this entry already collapsed?
    if (hasAttr(container, 'data-collapsed')) {
      this.collapse();
    }

    if (this.titlebar) {
      // (Legacy used the Garnish `doubletap` event; `dblclick` covers both
      // double-click and double-tap in modern browsers.)
      this.addListener(this.titlebar, 'dblclick', (event) => {
        if (
          !(event instanceof Event) ||
          !(event.target instanceof HTMLElement)
        ) {
          return;
        }
        // don't expand/collapse the matrix "block" if double tapping the tabs
        if (!event.target.closest('.tab-label')) {
          event.preventDefault();
          this.toggle();
        }
      });
    }
  }

  /** Reads a JSON-ish data attribute the way jQuery `.data()` did. */
  private dataJson(name: string): JsonValue {
    const raw = this.container.getAttribute(`data-${name}`);
    if (raw === null) {
      return null;
    }
    try {
      // SAFETY: JSON.parse can only produce values represented by JsonValue.
      return JSON.parse(raw) as JsonValue;
    } catch {
      return raw;
    }
  }

  private actionMenuOptions(): HTMLElement[] {
    return Array.from(
      this.actionMenu?.querySelectorAll<HTMLElement>('button[data-action]') ??
        []
    );
  }

  /** Show/hide/relabel the action menu items for the current state. */
  private prepareActionMenu(): void {
    this.container.classList.add('active');
    const hideActions: string[] = [];

    hideActions.push(this.collapsed ? 'collapse' : 'expand');
    hideActions.push(
      this.container.classList.contains('disabled-entry') ? 'disable' : 'enable'
    );

    if (!this.previousBlock()) {
      hideActions.push('moveUp');
    }
    if (!this.nextBlock()) {
      hideActions.push('moveDown');
    }
    if (!this.matrix.canAddMoreEntries()) {
      hideActions.push('add', 'duplicate');
    }

    const buttons = this.actionMenuOptions();
    for (const button of buttons) {
      const action = button.getAttribute('data-action') ?? '';
      if (hideActions.includes(action)) {
        this.actionDisclosure?.hideItem(button);
      } else {
        this.actionDisclosure?.showItem(button);
      }
    }

    const bulk = this.bulkActionMode();
    const labels = {
      collapse: bulk ? t('Collapse selected blocks') : t('Collapse'),
      expand: bulk ? t('Expand selected blocks') : t('Expand'),
      disable: bulk
        ? t('Disable selected {type}', {type: t('blocks')})
        : t('Disable'),
      enable: bulk
        ? t('Enable selected {type}', {type: t('blocks')})
        : t('Enable'),
      duplicate: bulk
        ? t('Duplicate selected {type}', {type: t('blocks')})
        : t('Duplicate'),
      copy: bulk ? t('Copy selected {type}', {type: t('blocks')}) : t('Copy'),
      delete: bulk
        ? t('Delete selected {type}', {type: t('blocks')})
        : t('Delete'),
    } satisfies Record<string, string>;
    for (const button of buttons) {
      const action = button.getAttribute('data-action') ?? '';
      const actionLabel = Object.entries(labels).find(
        ([name]) => name === action
      )?.[1];
      if (actionLabel) {
        const label = button.querySelector(':scope > .menu-item-label');
        if (label) {
          label.textContent = actionLabel;
        }
      }
    }

    const pasteBtn = buttons.find(
      (button) => button.getAttribute('data-action') === 'paste'
    );
    if (pasteBtn) {
      const copiedElements = craft().cp.getCopiedElements();
      const showPasteButton =
        copiedElements.length && this.matrix.canPaste(copiedElements);
      if (showPasteButton) {
        this.actionDisclosure?.showItem(pasteBtn);
        const label = pasteBtn.querySelector(':scope > .menu-item-label');
        if (label) {
          label.textContent =
            copiedElements.length === 1
              ? t('Paste {type} above', {type: t('block')})
              : t('Paste {type} above', {type: t('blocks')});
        }
      } else {
        this.actionDisclosure?.hideItem(pasteBtn);
      }
    }
  }

  private previousBlock(): HTMLElement | null {
    const prev = this.container.previousElementSibling;
    return prev instanceof HTMLElement && prev.classList.contains('matrixblock')
      ? prev
      : null;
  }

  private nextBlock(): HTMLElement | null {
    const next = this.container.nextElementSibling;
    return next instanceof HTMLElement && next.classList.contains('matrixblock')
      ? next
      : null;
  }

  toggle(): void {
    if (this.collapsed) {
      this.expand();
    } else {
      this.collapse(true);
    }
  }

  collapse(animate?: boolean): void {
    if (this.collapsed) {
      return;
    }

    this.container.classList.add('collapsed');

    if (this.previewContainer) {
      this.previewContainer.innerHTML = this.previewHtml();
    }

    const fields = this.fieldsContainer;
    const finishCollapse = () => {
      if (this.previewContainer) {
        this.previewContainer.style.display = '';
      }
      if (fields) {
        fields.style.display = 'none';
        fields.style.opacity = '';
      }
      this.container.style.height = '30px';
    };

    if (animate && animationDuration()) {
      fields?.animate([{opacity: 1}, {opacity: 0}], {
        duration: animationDuration(),
      });
      const heightAnimation = this.container.animate(
        [
          {height: `${this.container.getBoundingClientRect().height}px`},
          {height: '30px'},
        ],
        {duration: animationDuration()}
      );
      heightAnimation.finished.then(finishCollapse).catch(() => {});
    } else {
      finishCollapse();
    }

    // Remember that?
    if (!this.matrix.settings!.formControl && !this.isNew) {
      MatrixInput.rememberCollapsedEntryId(this.id!);
    } else if (!this.matrix.settings!.formControl) {
      if (!this.collapsedInput) {
        this.collapsedInput = document.createElement('input');
        this.collapsedInput.type = 'hidden';
        this.collapsedInput.name = `${this.matrix.inputNamePrefix}[entries][${this.id}][collapsed]`;
        this.collapsedInput.value = '1';
        this.container.append(this.collapsedInput);
      } else {
        this.collapsedInput.value = '1';
      }
    }

    this.collapsed = true;
  }

  previewHtml(): string {
    if (this.uiLabel) {
      return escapeHtml(this.uiLabel);
    }

    let previewHtml = '';
    const fields = Array.from(
      this.fieldsContainer?.querySelectorAll<HTMLElement>(':scope > * > *') ??
        []
    );

    for (const field of fields) {
      const inputs = Array.from(
        field.querySelectorAll<HTMLElement>(
          ':scope > .input select, :scope > .input input:not([type="hidden"]), :scope > .input textarea, :scope > .input .label'
        )
      );
      let inputPreviewText = '';

      for (const input of inputs) {
        let value: unknown;

        if (input.classList.contains('label')) {
          const lightswitch = input.closest('.lightswitch');
          if (
            lightswitch &&
            ((lightswitch.classList.contains('on') &&
              input.classList.contains('off')) ||
              (!lightswitch.classList.contains('on') &&
                input.classList.contains('on')))
          ) {
            continue;
          }

          if (input.closest('button[aria-pressed=false]')) {
            continue;
          }

          value = input.textContent;
        } else {
          const previewText = this.inputPreviewText(input);
          value = Array.isArray(previewText)
            ? previewText.map((text) => craft().getText(text))
            : previewText
              ? craft().getText(previewText)
              : null;
        }

        if (Array.isArray(value)) {
          value = value.join(', ');
        }

        if (value) {
          const escaped = escapeHtml(String(value)).trim();
          if (escaped) {
            if (inputPreviewText) {
              inputPreviewText += ', ';
            }
            inputPreviewText += escaped;
          }
        }
      }

      if (inputPreviewText) {
        previewHtml +=
          (previewHtml ? ' <span>|</span> ' : '') + inputPreviewText;
      }
    }

    return previewHtml;
  }

  private inputPreviewText(input: HTMLElement): string | string[] | null {
    if (input instanceof HTMLSelectElement) {
      return Array.from(input.selectedOptions).map((option) => option.text);
    }

    if (
      input instanceof HTMLInputElement &&
      (input.type === 'checkbox' || input.type === 'radio') &&
      input.checked
    ) {
      const label = input.id
        ? document.querySelector(`label[for="${input.id}"]`)
        : null;
      if (label) {
        return label.textContent;
      }
    }

    if (!(input instanceof HTMLInputElement)) {
      return null;
    }
    const value = getInputPostVal(input);
    return Array.isArray(value)
      ? value.map(String)
      : value == null
        ? null
        : String(value);
  }

  expand(): void {
    if (!this.collapsed) {
      return;
    }

    this.container.classList.remove('collapsed');

    const fields = this.fieldsContainer;

    const collapsedContainerHeight =
      this.container.getBoundingClientRect().height;
    this.container.style.height = 'auto';
    if (fields) {
      fields.style.display = '';
    }
    const expandedContainerHeight =
      this.container.getBoundingClientRect().height;
    this.container.style.height = `${collapsedContainerHeight}px`;

    fields?.animate([{opacity: 0}, {opacity: 1}], {
      duration: animationDuration(),
    });

    const finishExpand = () => {
      if (this.previewContainer) {
        this.previewContainer.innerHTML = '';
      }
      this.container.style.height = 'auto';
      this.container.dispatchEvent(new Event('scroll'));
    };

    const heightAnimation = this.container.animate(
      [
        {height: `${collapsedContainerHeight}px`},
        {height: `${expandedContainerHeight}px`},
      ],
      {duration: animationDuration()}
    );
    heightAnimation.finished.then(finishExpand).catch(() => {});

    // Remember that?
    if (!this.matrix.settings!.formControl && !this.isNew) {
      MatrixInput.forgetCollapsedEntryId(this.id!);
    } else if (!this.matrix.settings!.formControl && this.collapsedInput) {
      this.collapsedInput.value = '';
    }

    this.collapsed = false;
  }

  override disable(): void {
    const enabledInput = this.container.querySelector<HTMLInputElement>(
      ':scope > input[name$="[enabled]"]'
    );
    if (enabledInput) {
      enabledInput.value = '';
    }
    this.container.classList.add('disabled-entry');
    this.collapse(true);
  }

  override enable(): void {
    const enabledInput = this.container.querySelector<HTMLInputElement>(
      ':scope > input[name$="[enabled]"]'
    );
    if (enabledInput) {
      enabledInput.value = '1';
    }
    this.container.classList.remove('disabled-entry');
  }

  moveUp(): void {
    this.matrix.trigger('beforeMoveEntryUp', {entry: this});
    const prev = this.previousBlock();
    if (prev) {
      prev.before(this.container);
      this.matrix.entrySelect?.resetItemOrder();
    }
    this.matrix.trigger('moveEntryUp', {entry: this});
  }

  moveDown(): void {
    this.matrix.trigger('beforeMoveEntryDown', {entry: this});
    const next = this.nextBlock();
    if (next) {
      next.after(this.container);
      this.matrix.entrySelect?.resetItemOrder();
    }
    this.matrix.trigger('moveEntryDown', {entry: this});
  }

  duplicate(): void {
    const type = this.container.dataset.type ?? '';
    const elementEditor = this.matrix.elementEditor;
    this.matrix.addEntry(type, this.nextBlock(), true, {
      duplicate: elementEditor?.getDraftElementId(this.id) || this.id,
    });
  }

  bulkActionMode(): boolean {
    return (
      (this.matrix.entrySelect?.totalSelected ?? 0) > 1 &&
      (this.matrix.entrySelect?.isSelected(this.container) ?? false)
    );
  }

  onActionSelect(option: HTMLElement): void {
    switch (option.getAttribute('data-action')) {
      case 'collapse': {
        if (this.bulkActionMode()) {
          this.matrix.collapseSelectedEntries();
        } else {
          this.collapse(true);
        }
        break;
      }

      case 'expand': {
        if (this.bulkActionMode()) {
          this.matrix.expandSelectedEntries();
        } else {
          this.expand();
        }
        break;
      }

      case 'disable': {
        if (this.bulkActionMode()) {
          this.matrix.disableSelectedEntries();
        } else {
          this.disable();
        }
        break;
      }

      case 'enable': {
        if (this.bulkActionMode()) {
          this.matrix.enableSelectedEntries();
        } else {
          this.enable();
          this.expand();
        }
        break;
      }

      case 'moveUp': {
        this.moveUp();
        break;
      }

      case 'moveDown': {
        this.moveDown();
        break;
      }

      case 'editEntryType': {
        new Craft.CpScreenSlideout(
          Craft.getCpUrl(
            `settings/entry-types/${this.container.dataset.typeId}`
          )
        );
        break;
      }

      case 'add': {
        const type = option.getAttribute('data-type') ?? '';
        this.matrix.addEntry(type, this.container);
        break;
      }

      case 'duplicate': {
        if (this.bulkActionMode()) {
          this.matrix.duplicateSelectedEntries();
        } else {
          this.duplicate();
        }
        break;
      }

      case 'copy': {
        const entries = this.bulkActionMode()
          ? Array.from(this.matrix.entrySelect?.getSelectedItems() ?? [])
              .map((item) => MatrixEntry.forContainer(item))
              .filter((entry): entry is MatrixEntry => !!entry)
          : [this];

        craft().cp.copyElements(
          entries.map((entry) => ({
            type: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
            id:
              entry.matrix.elementEditor?.getDraftElementId(entry.id) ||
              entry.id,
            draftId: entry.dataJson('draft-id'),
            revisionId: entry.dataJson('revision-id'),
            fieldId: entry.matrix.settings!.fieldId,
            ownerId: entry.matrix.settings!.ownerId,
            siteId: entry.matrix.settings!.siteId,
          }))
        );
        break;
      }

      case 'paste': {
        this.matrix.pasteEntries(this.container);
        break;
      }

      case 'delete': {
        if (this.bulkActionMode()) {
          if (
            confirm(
              t('Are you sure you want to delete the selected {type}?', {
                type:
                  craft().elementTypeNames[
                    'CraftCms\\Cms\\Entry\\Elements\\Entry'
                  ]?.[3] ?? t('blocks'),
              })
            )
          ) {
            this.matrix.deleteSelectedEntries();
          }
        } else {
          this.selfDestruct();
        }
        break;
      }
    }

    this.actionDisclosure?.hide();
  }

  selfDestruct(): void {
    this.destroy();

    if (this.matrix.settings!.formControl) {
      this.container.remove();
      this.matrix.updateAddEntryBtn();
      this.matrix.trigger('entryDeleted', {$entry: this.container});

      return;
    }

    // Remove any inputs from the form data
    for (const el of this.container.querySelectorAll('[name]')) {
      el.removeAttribute('name');
    }

    const height = this.container.getBoundingClientRect().height;
    const animation = this.container.animate(
      [
        {opacity: 1, marginBottom: '0px'},
        {opacity: 0, marginBottom: `${-height}px`},
      ],
      {duration: animationDuration()}
    );
    animation.finished
      .catch(() => {})
      .finally(() => {
        this.container.remove();
        this.matrix.updateAddEntryBtn();
        this.matrix.trigger('entryDeleted', {$entry: this.container});
      });
  }

  override destroy(): void {
    this.actionDisclosure?.hide();

    this.actionDisclosure?.destroy();
    this.actionDisclosure = null;

    containerMatrixEntries.delete(this.container);
    setJqData(this.container, 'entry', null);

    // alert any nested inputs that we're getting deleted (bubbles like the
    // legacy jQuery trigger; listeners filter with `target === currentTarget`)
    this.container.dispatchEvent(new Event('delete', {bubbles: true}));

    super.destroy();
  }
}

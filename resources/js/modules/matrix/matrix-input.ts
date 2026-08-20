/**
 * MatrixInput — modern TypeScript port of the legacy `Craft.MatrixInput`
 * (packages/craftcms-legacy/matrix/src/MatrixInput.js), following the shared
 * module pattern (see the listbox module).
 *
 * The outer controller for a Matrix field in `blocks` view mode: owns the
 * add-entry buttons (max-entries gating, XHR block rendering), block drag-sort
 * and multi-select, copy/paste, and one {@link MatrixEntry} per `.matrixblock`.
 *
 * jQuery is gone from the class itself; the module still cooperates with
 * legacy-runtime widgets through `./interop` (see that file for the seams and
 * their retirement plan). Velocity animations are replaced with the Web
 * Animations API, honoring reduced-motion.
 */

import {
  Base,
  DragSort,
  type GarnishBaseSettings,
  deferUntil,
  firstFocusableElement,
  prefersReducedMotion,
  scrollContainerToElement,
} from '@craftcms/garnish';
import {createPasteButton, t, type CraftButton} from '@craftcms/ui';
import {MatrixEntry} from './matrix-entry';
import {containerMatrixInputs} from './support';
import {
  type CopiedElementInfo,
  type LegacyElementEditor,
  type LegacySelect,
  craft,
  jqData,
  legacyGarnish,
  setJqData,
} from './interop';

/** The entry-type descriptors the server passes to the constructor. */
export interface MatrixEntryType {
  id: number;
  handle: string;
  name: string;
}

export interface MatrixInputSettings extends GarnishBaseSettings {
  fieldId: number | null;
  maxEntries: number | null;
  namespace: string | null;
  baseInputName: string | null;
  ownerElementType: string | null;
  ownerId: number | string | null;
  siteId: number | null;
  staticEntries: boolean;
  /**
   * Auto-create default entries after init (min-entries fields; see
   * craftcms/cms#12973 — the server resets the field's delta initial value so
   * the auto-added entries still register as changes).
   */
  addDefaultEntries: {type: string; count: number} | null;
  formControl: boolean;
}

/** The jQuery `'fast'` duration the legacy velocity calls used. */
export const FAST = 200;

/** Animation duration honoring the user's reduced-motion preference. */
export function animationDuration(): number {
  return prefersReducedMotion() ? 0 : FAST;
}

export class MatrixInput extends Base<MatrixInputSettings> {
  static defaults: MatrixInputSettings = {
    fieldId: null,
    maxEntries: null,
    namespace: null,
    baseInputName: null,
    ownerElementType: null,
    ownerId: null,
    siteId: null,
    staticEntries: false,
    addDefaultEntries: null,
    formControl: false,
  };

  entryFactory: ((type: string) => HTMLElement) | null = null;

  static get collapsedEntryStorageKey(): string {
    return `Craft-${craft().systemUid}.MatrixInput.collapsedEntries`;
  }

  static getCollapsedEntryIds(): string[] {
    const value = localStorage[MatrixInput.collapsedEntryStorageKey];
    return typeof value === 'string'
      ? craft().filterArray(value.split(','))
      : [];
  }

  static setCollapsedEntryIds(ids: Array<string | number>): void {
    localStorage[MatrixInput.collapsedEntryStorageKey] = ids.join(',');
  }

  static rememberCollapsedEntryId(id: string | number): void {
    if (typeof Storage === 'undefined') {
      return;
    }
    const collapsedEntries = MatrixInput.getCollapsedEntryIds();
    if (!collapsedEntries.includes(`${id}`)) {
      collapsedEntries.push(`${id}`);
      MatrixInput.setCollapsedEntryIds(collapsedEntries);
    }
  }

  static forgetCollapsedEntryId(id: string | number): void {
    if (typeof Storage === 'undefined') {
      return;
    }
    const collapsedEntries = MatrixInput.getCollapsedEntryIds();
    const index = collapsedEntries.indexOf(`${id}`);
    if (index !== -1) {
      collapsedEntries.splice(index, 1);
      MatrixInput.setCollapsedEntryIds(collapsedEntries);
    }
  }

  id: string;
  entryTypes: MatrixEntryType[];
  entryTypesByHandle: Record<string, MatrixEntryType> = {};
  inputNamePrefix: string;

  container: HTMLElement | null = null;
  form: HTMLElement | null = null;
  entriesContainer: HTMLElement | null = null;
  addEntryBtnContainer: HTMLElement | null = null;
  addEntryBtn: HTMLElement | null = null;
  addEntryMenuBtns: HTMLElement[] = [];
  pasteBtn: CraftButton | null = null;
  statusMessage: HTMLElement | null = null;

  entrySort: DragSort | null = null;
  entrySelect: LegacySelect | null = null;

  elementEditor: LegacyElementEditor | null = null;
  /** Aborts the after-init `elementEditor` lookup on `destroy()`. */
  private elementEditorController: AbortController | null = null;

  addingEntry = false;

  constructor(
    id: string,
    entryTypes: MatrixEntryType[],
    inputNamePrefix: string,
    settings?: Partial<MatrixInputSettings> | number
  ) {
    super();

    this.id = id;
    this.entryTypes = entryTypes;
    this.inputNamePrefix = inputNamePrefix;

    // see if settings was actually set to the maxEntries value
    if (typeof settings === 'number') {
      settings = {maxEntries: settings};
    }
    this.setSettings(settings, MatrixInput.defaults);

    this.container = document.getElementById(this.id);
    if (!this.container) {
      return;
    }

    this.form = this.container.closest('form');
    // `.blocks` is the Twig markup's class; the Vue control styles its own
    // container, so it marks the hook explicitly.
    this.entriesContainer = this.container.querySelector(
      ':scope > [data-matrix-blocks], :scope > .blocks'
    );
    this.addEntryBtnContainer =
      this.container.querySelector(':scope > .buttons');
    this.addEntryBtn =
      this.addEntryBtnContainer?.querySelector('.btn:not(.menubtn)') ?? null;
    this.addEntryMenuBtns = Array.from(
      this.addEntryBtnContainer?.querySelectorAll<HTMLElement>('.menubtn') ?? []
    );
    this.statusMessage = this.container.querySelector('[data-status-message]');

    containerMatrixInputs.set(this.container, this);
    // Legacy code and PHP-emitted snippets read `$(container).data('matrix')`.
    setJqData(this.container, 'matrix', this);

    for (const entryType of this.entryTypes) {
      this.entryTypesByHandle[entryType.handle] = entryType;
    }

    const entries = this.entryElements();
    const collapsedEntries = this.settings!.formControl
      ? []
      : MatrixInput.getCollapsedEntryIds();

    // only initialise drag-sort if the device has mouse events
    if (this.settings!.formControl || craft().hasMousePointerEvents()) {
      this.entrySort = new DragSort(entries, {
        // Native querySelector needs `:scope` for a leading combinator
        // (the legacy jQuery selector was `> .actions > .move-btn`).
        handle: ':scope > .actions > .move-btn',
        ignoreHandleSelector: null,
        axis: 'y',
        filter: () => {
          // Only return all the selected items if the target item is selected
          if (
            this.entrySort?.$targetItem?.classList.contains('sel') &&
            this.entrySelect
          ) {
            return Array.from(this.entrySelect.getSelectedItems());
          }
          return this.entrySort?.$targetItem ?? null;
        },
        collapseDraggees: true,
        magnetStrength: 4,
        helperLagBase: 1.5,
        helperOpacity: 0.9,
        onDragStop: () => {
          this.trigger('entrySortDragStop');
        },
        onSortChange: () => {
          this.entrySelect?.resetItemOrder();
        },
      });
    } else {
      // hide the diamond icon (for drag-sort) if the device is touch-capable
      for (const btn of document.querySelectorAll<HTMLElement>(
        '.actions > .move-btn'
      )) {
        btn.style.display = 'none';
      }
    }

    // `Garnish.Select` has no modern port yet — see ./interop.
    if (!this.settings!.formControl) {
      this.entrySelect = new (legacyGarnish().Select)(
        this.entriesContainer,
        entries,
        {
          multi: true,
          vertical: true,
          handle: '> .actions > .checkbox, > .titlebar',
          filter: (target: unknown) =>
            !(target as HTMLElement).closest?.('.tab-label'),
          checkboxMode: true,
        }
      );
    }

    for (const container of entries) {
      const entry = new MatrixEntry(this, container);
      if (entry.id && collapsedEntries.includes(`${entry.id}`)) {
        entry.collapse();
      }
    }

    if (this.addEntryBtn && !this.settings!.formControl) {
      this.addListener(this.addEntryBtn, 'activate', async () => {
        const btn = this.addEntryBtn!;
        if (btn.classList.contains('loading')) {
          return;
        }
        btn.classList.add('loading');
        craft().cp.announce(t('Loading'));
        try {
          await this.addEntry(btn.getAttribute('data-type') ?? '');
        } finally {
          btn.classList.remove('loading');
        }
      });
    }

    for (const btn of this.settings!.formControl ? [] : this.addEntryMenuBtns) {
      // The disclosure menu is initialized by the legacy bundle and stored in
      // jQuery data; its container holds the per-type buttons.
      const menu = jqData(btn, 'disclosureMenu') as
        | {$container: {0?: HTMLElement}}
        | undefined;
      const menuContainer = menu?.$container?.[0] ?? null;

      for (const menuBtn of menuContainer?.querySelectorAll<HTMLElement>(
        'button'
      ) ?? []) {
        this.addListener(menuBtn, 'activate', async (event) => {
          const ev = event as unknown as Event;
          btn.classList.add('loading');
          craft().cp.announce(t('Loading'));
          try {
            await this.addEntry(
              (ev.currentTarget as HTMLElement).getAttribute('data-type') ?? ''
            );
          } finally {
            btn.classList.remove('loading');
          }
        });
      }
    }

    this.updateAddEntryBtn();

    // The owner's element editor boots after this input does; keep checking
    // until it's attached (legacy parity: the poll interval mirrors the old
    // fixed delay, but retries instead of gambling on a single check).
    const finishInit = (elementEditor: LegacyElementEditor | null): void => {
      this.elementEditor = elementEditor;
      this.elementEditor?.on('update', () => {
        this.settings!.ownerId = this.elementEditor!.getDraftElementId(
          this.settings!.ownerId
        ) as MatrixInputSettings['ownerId'];
      });

      this.trigger('afterInit');

      const defaultEntries = this.settings!.addDefaultEntries;
      if (defaultEntries && defaultEntries.count > 0) {
        void this.addDefaultEntries(defaultEntries.type, defaultEntries.count);
      }
    };

    this.elementEditorController = new AbortController();

    const form = this.form;
    if (form) {
      deferUntil(
        () => jqData(form, 'elementEditor') as LegacyElementEditor | undefined,
        100,
        this.elementEditorController.signal
      )
        .then((elementEditor) => finishInit(elementEditor ?? null))
        .catch(() => {
          // Destroyed before the editor showed up — nothing left to do.
        });
    } else {
      finishInit(null);
    }

    // If this field is nested within something that's deletable, be ready to
    // handle that
    const deletable = this.container.closest('.js-deletable');
    if (deletable) {
      this.addListener(deletable, 'delete', (event) => {
        const ev = event as unknown as Event;
        // Ignore delete events that came from nested elements
        if (ev.target === ev.currentTarget) {
          this.destroy();
        }
      });
    }

    if (!this.settings!.formControl) {
      craft().cp.onCopyElements((elementInfo, buttonLabel) => {
        this.updatePasteBtn(elementInfo);
        if (this.pasteBtn && buttonLabel) {
          const label = this.pasteBtn.querySelector('.label');
          if (label) {
            label.textContent = buttonLabel;
          }
        }
      });
    }
  }

  /** The field's current top-level `.matrixblock` elements. */
  entryElements(): HTMLElement[] {
    return Array.from(
      this.entriesContainer?.querySelectorAll<HTMLElement>(
        ':scope > .matrixblock'
      ) ?? []
    );
  }

  get maxEntries(): number | null {
    return this.settings!.maxEntries;
  }

  canAddMoreEntries(num = 1): boolean {
    if (num === 0) {
      return false;
    }
    return (
      !this.maxEntries ||
      (this.entriesContainer?.children.length ?? 0) + num <= this.maxEntries
    );
  }

  canPaste(elementInfo: CopiedElementInfo[]): boolean {
    if (this.settings!.formControl) {
      return false;
    }

    if (!this.canAddMoreEntries(elementInfo.length)) {
      return false;
    }

    for (const e of elementInfo) {
      if (e.type !== 'CraftCms\\Cms\\Entry\\Elements\\Entry') {
        return false;
      }
    }

    const entryTypeIds = this.entryTypes.map((entryType) => entryType.id);
    for (const info of elementInfo) {
      if (
        info.data?.entryTypeId === undefined ||
        !entryTypeIds.includes(info.data.entryTypeId)
      ) {
        return false;
      }
    }

    return true;
  }

  async pasteEntries(before: HTMLElement | null = null): Promise<void> {
    craft().cp.announce(t('Loading'));
    if (this.pasteBtn) {
      this.pasteBtn.loading = true;
    }

    try {
      if (this.elementEditor) {
        // First ensure we're working with drafts for all elements leading up
        // to this field’s element
        await this.elementEditor.setFormValue(
          this.settings!.baseInputName ?? '',
          '*'
        );
      }

      const newElementInfo = await craft().cp.pasteElements({
        primaryOwnerId: this.settings!.ownerId,
        ownerId: this.settings!.ownerId,
        fieldId: this.settings!.fieldId,
        siteId: this.settings!.siteId,
      });

      if (!newElementInfo.length) {
        return;
      }

      let data: {blockHtml: string; headHtml: string; bodyHtml: string};
      try {
        const response = await Craft.sendActionRequest(
          'POST',
          'matrix/render-blocks',
          {
            data: {
              entryIds: newElementInfo.map((info) => info.id),
              siteId: this.settings!.siteId,
              namespace: this.settings!.namespace,
            },
          }
        );
        data = response.data;
      } catch (e) {
        craft().cp.displayError(
          (e as {response?: {data?: {message?: string}}})?.response?.data
            ?.message
        );
        return;
      }

      // Pause the element editor
      await this.elementEditor?.pause();

      const newEntries = parseBlockHtml(data.blockHtml);

      for (const entry of newEntries) {
        if (before) {
          before.before(entry);
        } else {
          this.entriesContainer?.append(entry);
        }
      }

      await craft().appendHeadHtml(data.headHtml);
      await craft().appendBodyHtml(data.bodyHtml);
      Craft.initUiElements($(newEntries));

      for (const entry of newEntries) {
        new MatrixEntry(this, entry);
        this.trigger('entryAdded', {$entry: entry});
      }

      this.entrySort?.addItems(newEntries);
      this.entrySelect?.addItems(newEntries);
      this.updateAddEntryBtn();
      if (newEntries[0]) {
        firstFocusableElement(newEntries[0])?.focus();
      }
    } finally {
      if (this.pasteBtn) {
        this.pasteBtn.loading = false;
      }
    }

    // Resume the element editor
    void this.elementEditor?.resume();
  }

  updateAddEntryBtn(): void {
    if (this.canAddMoreEntries()) {
      this.addEntryBtn?.classList.remove('disabled');
      this.addEntryBtn?.removeAttribute('aria-disabled');
      for (const btn of this.addEntryMenuBtns) {
        btn.classList.remove('disabled');
      }
    } else {
      this.addEntryBtn?.classList.add('disabled');
      this.addEntryBtn?.setAttribute('aria-disabled', 'true');
      for (const btn of this.addEntryMenuBtns) {
        btn.classList.add('disabled');
      }
    }

    this.updatePasteBtn();
  }

  updatePasteBtn(elementInfo: CopiedElementInfo[] | null = null): void {
    if (this.settings!.formControl) {
      return;
    }

    elementInfo = elementInfo || craft().cp.getCopiedElements();
    if (this.canPaste(elementInfo)) {
      if (!this.pasteBtn) {
        this.pasteBtn = createPasteButton();
        this.addEntryBtnContainer?.append(this.pasteBtn);
        this.addListener(this.pasteBtn, 'activate', () => {
          void this.pasteEntries();
        });
      } else {
        this.pasteBtn.hidden = false;
      }
    } else if (this.pasteBtn) {
      this.pasteBtn.hidden = true;
    }
  }

  updateStatusMessage(): void {
    if (!this.statusMessage) {
      return;
    }
    this.statusMessage.textContent = '';
    let message: string | undefined;

    if (!this.canAddMoreEntries()) {
      message = t(
        'Entry could not be added. Maximum number of entries reached.'
      );
    }

    setTimeout(() => {
      this.statusMessage!.textContent = message ?? '';
    }, 250);
  }

  async addEntry(
    type: string,
    insertBefore?: HTMLElement | null,
    autofocus = true,
    params: Record<string, unknown> = {}
  ): Promise<void> {
    if (!this.canAddMoreEntries()) {
      this.updateStatusMessage();
      return;
    }

    if (this.entryFactory) {
      const entry = this.entryFactory(type);

      if (insertBefore?.isConnected) {
        insertBefore.before(entry);
      } else {
        this.entriesContainer?.append(entry);
      }

      new MatrixEntry(this, entry);
      this.entrySort?.addItems(entry);
      this.entrySelect?.addItems(entry);
      this.updateAddEntryBtn();
      this.trigger('entryAdded', {$entry: entry});

      return;
    }

    if (this.elementEditor) {
      // First ensure we're working with drafts for all elements leading up
      // to this field’s element
      await this.elementEditor.setFormValue(
        this.settings!.baseInputName ?? '',
        '*'
      );
    }

    const queue = this.elementEditor?.queue ?? craft().queue;
    await queue.push(async () => {
      if (this.addingEntry) {
        // only one new entry at a time
        return;
      }

      this.addingEntry = true;

      try {
        const {data} = await Craft.sendActionRequest(
          'POST',
          'matrix/create-entry',
          {
            data: {
              fieldId: this.settings!.fieldId,
              entryTypeId: this.entryTypesByHandle[type]?.id,
              ownerId: this.settings!.ownerId,
              ownerElementType: this.settings!.ownerElementType,
              siteId: this.settings!.siteId,
              namespace: this.settings!.namespace,
              staticEntries: this.settings!.staticEntries,
              ...params,
            },
          }
        );

        const [entry] = parseBlockHtml(data.blockHtml);
        if (!entry) {
          return;
        }

        // hide the diamond icon (for drag-sort) if the device doesn't have
        // mouse events
        if (!craft().hasMousePointerEvents()) {
          for (const btn of entry.querySelectorAll<HTMLElement>(
            '.actions > .move-btn'
          )) {
            btn.style.display = 'none';
          }
        }

        // Pause the element editor
        await this.elementEditor?.pause();

        if (insertBefore?.isConnected) {
          insertBefore.before(entry);
        } else {
          this.entriesContainer?.append(entry);
        }

        this.trigger('entryAdded', {$entry: entry});

        // Animate the entry into position
        const height = entry.getBoundingClientRect().height;
        const animation = entry.animate(
          [
            {opacity: 0, marginBottom: `${-height}px`},
            {opacity: 1, marginBottom: '8px'},
          ],
          {duration: animationDuration()}
        );
        await animation.finished;

        // Execute the response JS first so any Selectize inputs, etc., get
        // instantiated before field toggles
        await craft().appendHeadHtml(data.headHtml);
        await craft().appendBodyHtml(data.bodyHtml);
        Craft.initUiElements(entry);
        new MatrixEntry(this, entry);
        this.entrySort?.addItems(entry);
        this.entrySelect?.addItems(entry);
        this.updateAddEntryBtn();

        requestAnimationFrame(() => {
          if (autofocus) {
            // Scroll to the entry
            scrollContainerToElement(entry);
            // Focus on the first focusable element
            const fields = entry.querySelector<HTMLElement>('.flex-fields');
            const focusable = fields ? firstFocusableElement(fields) : null;
            if (
              focusable &&
              !focusable.classList.contains('prevent-autofocus')
            ) {
              focusable.focus();
            }
          }

          // Resume the element editor
          void this.elementEditor?.resume();
        });
      } finally {
        this.addingEntry = false;
      }
    });
  }

  /** Auto-adds the missing default entries for a min-entries field. */
  private async addDefaultEntries(type: string, count: number): Promise<void> {
    if (this.elementEditor) {
      await this.elementEditor.pause();
    }

    for (let i = 0; i < count; i++) {
      await this.addEntry(type, null, false);
    }

    setTimeout(() => {
      requestAnimationFrame(() => {
        void this.elementEditor?.resume();
      });
    }, 100);
  }

  getEntryTypeByHandle(handle: string): MatrixEntryType | undefined {
    return this.entryTypes.find((entryType) => entryType.handle === handle);
  }

  collapseSelectedEntries(): void {
    this.callOnSelectedEntries('collapse');
  }

  expandSelectedEntries(): void {
    this.callOnSelectedEntries('expand');
  }

  disableSelectedEntries(): void {
    this.callOnSelectedEntries('disable');
  }

  enableSelectedEntries(): void {
    this.callOnSelectedEntries('enable');
  }

  deleteSelectedEntries(): void {
    this.callOnSelectedEntries('selfDestruct');
  }

  duplicateSelectedEntries(): void {
    this.callOnSelectedEntries('duplicate');
  }

  callOnSelectedEntries(
    fn:
      | 'collapse'
      | 'expand'
      | 'disable'
      | 'enable'
      | 'selfDestruct'
      | 'duplicate'
  ): void {
    for (const item of Array.from(this.entrySelect?.getSelectedItems() ?? [])) {
      MatrixEntry.forContainer(item)?.[fn]();
    }
  }

  override destroy(): void {
    this.elementEditorController?.abort();
    this.elementEditorController = null;

    this.entrySort?.destroy();
    this.entrySelect?.destroy();
    this.entrySort = null;
    this.entrySelect = null;

    for (const container of this.entryElements()) {
      MatrixEntry.forContainer(container)?.destroy();
    }

    if (this.container) {
      containerMatrixInputs.delete(this.container);
      setJqData(this.container, 'matrix', null);
    }

    super.destroy();
  }
}

/** Parses the server's block HTML into its top-level block elements. */
export function parseBlockHtml(html: string): HTMLElement[] {
  const template = document.createElement('template');
  template.innerHTML = html.trim();
  return Array.from(template.content.children).filter(
    (node): node is HTMLElement => node instanceof HTMLElement
  );
}

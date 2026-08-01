/**
 * MatrixEntry — modern TypeScript port of the legacy `Craft.MatrixInput.Entry`.
 *
 * The per-`.matrixblock` controller: collapse/expand (with the preview-text
 * summary and localStorage persistence), the block action menu, enable/disable,
 * move/duplicate/copy/paste/delete, and conditional field-layout updates via
 * `elements/update-field-layout` driven by a `Craft.FormObserver`.
 */

import {Base, getInputPostVal, hasAttr} from '@craftcms/garnish';
import {t} from '@craftcms/ui';
import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';
import {animationDuration, MatrixInput} from './matrix-input';
import {containerMatrixEntries} from './support';
import {
  type LegacyDisclosureMenu,
  type LegacyFormObserver,
  type LegacyTabs,
  craft,
  jqData,
  jqParam,
  legacyGarnish,
  setJqData,
} from './interop';

declare const axios: {
  CancelToken: {source(): {token: unknown; cancel(): void}};
};

interface UpdateFieldLayoutResponse {
  missingElements: Array<{
    uid: string;
    id: string;
    elements: Array<{uid: string; html: string | false; static?: boolean}>;
  }>;
  tabs?: string;
  uiLabel?: string;
  headHtml: string;
  bodyHtml: string;
}

export class MatrixEntry extends Base {
  /** The entry controller for a `.matrixblock` container, if one was booted. */
  static forContainer(container: Element): MatrixEntry | undefined {
    return containerMatrixEntries.get(container);
  }

  /**
   * Initializes a tab manager for a `.matrixblock-tabs` container via the
   * legacy `Craft.Tabs` (no modern port yet — see ./interop).
   */
  static initTabs(container: HTMLElement): LegacyTabs | null {
    const tabs = container.querySelector(':scope > .pane-tabs');
    if (!tabs) {
      return null;
    }

    const tabManager = new (craft().Tabs)(tabs);

    // prevent items in the disclosure menu from changing the URL
    const disclosureMenu = tabManager.$menuBtn.data('trigger') as
      | {$container: {0?: HTMLElement}}
      | undefined;
    const menuContainer = disclosureMenu?.$container?.[0];
    for (const el of menuContainer?.querySelectorAll('li, a') ?? []) {
      el.addEventListener('click', (ev) => {
        ev.preventDefault();
      });
    }

    tabManager.on('selectTab', (ev) => {
      const href = ev.$tab.attr('href');

      // Show its content area
      if (href?.startsWith('#')) {
        document.querySelector(href)?.classList.remove('hidden');
      }

      // Trigger a resize event to update any UI components listening for it
      window.dispatchEvent(new Event('resize'));

      // Fixes Redactor fixed toolbars on previously hidden panes
      document.dispatchEvent(new Event('scroll'));
    });

    tabManager.on('deselectTab', (ev) => {
      const href = ev.$tab.attr('href');
      if (href?.startsWith('#')) {
        // Hide its content area
        document.querySelector(href)?.classList.add('hidden');
      }
    });

    return tabManager;
  }

  matrix: MatrixInput;
  container: HTMLElement;
  titlebar: HTMLElement | null;
  tabContainer: HTMLElement | null;
  fieldsContainer: HTMLElement | null;
  previewContainer: HTMLElement | null;
  actionMenu: HTMLElement | null = null;
  collapsedInput: HTMLInputElement | null = null;

  tabManager: LegacyTabs | null = null;
  actionDisclosure: LegacyDisclosureMenu | null = null;
  formObserver: LegacyFormObserver | null = null;
  visibleLayoutElements: unknown;
  staticLayoutElements: unknown;
  cancelToken: {token: unknown; cancel(): void} | null = null;
  ignoreFailedRequest = false;
  uiLabel: string | null = null;
  hasTabs = false;

  isNew: boolean;
  id: string | number | null;

  collapsed = false;

  constructor(matrix: MatrixInput, container: HTMLElement) {
    super();

    this.matrix = matrix;
    this.container = container;
    this.titlebar = container.querySelector(':scope > .titlebar');
    this.tabContainer =
      this.titlebar?.querySelector(':scope > .matrixblock-tabs') ?? null;
    this.previewContainer =
      this.titlebar?.querySelector(':scope > .preview') ?? null;
    this.fieldsContainer = container.querySelector(':scope > .fields');

    containerMatrixEntries.set(container, this);
    // PHP-emitted snippets (expand/collapse-all) read `$(block).data('entry')`.
    setJqData(container, 'entry', this);

    this.id = container.dataset.id ?? null;
    this.isNew =
      !this.id || (typeof this.id === 'string' && this.id.startsWith('new'));

    if (this.tabContainer) {
      this.tabManager = MatrixEntry.initTabs(this.tabContainer);
    }

    const actionMenuBtn = this.container.querySelector<HTMLElement>(
      ':scope > .actions > .action-btn'
    );
    if (actionMenuBtn) {
      this.actionDisclosure =
        (jqData(actionMenuBtn, 'disclosureMenu') as LegacyDisclosureMenu) ||
        new (legacyGarnish().DisclosureMenu)(actionMenuBtn);
      this.actionMenu =
        (this.actionDisclosure.$container as {0?: HTMLElement})?.[0] ?? null;
    }

    this.uiLabel = container.dataset.uiLabel ?? null;

    this.actionDisclosure?.on('show', () => this.prepareActionMenu());
    this.actionDisclosure?.on('hide', () => {
      this.container.classList.remove('active');
    });

    for (const option of this.actionMenuOptions()) {
      this.addListener(option, 'activate', (event) => {
        const ev = event as unknown as Event;
        ev.preventDefault();
        this.onActionSelect(ev.target as HTMLElement);
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
        const ev = event as unknown as Event;
        // don't expand/collapse the matrix "block" if double tapping the tabs
        if (!(ev.target as HTMLElement).closest('.tab-label')) {
          ev.preventDefault();
          this.toggle();
        }
      });
    }

    this.visibleLayoutElements = this.dataJson('visible-layout-elements');
    this.staticLayoutElements = this.dataJson('static-layout-elements');
    this.formObserver = new (craft().FormObserver)(container, (data) => {
      this.updateFieldLayout(data);
    });
  }

  /** Reads a JSON-ish data attribute the way jQuery `.data()` did. */
  private dataJson(name: string): unknown {
    const raw = this.container.getAttribute(`data-${name}`);
    if (raw === null) {
      return null;
    }
    try {
      return JSON.parse(raw);
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
    const labels: Record<string, string> = {
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
    };
    for (const button of buttons) {
      const action = button.getAttribute('data-action') ?? '';
      if (labels[action]) {
        const label = button.querySelector(':scope > .menu-item-label');
        if (label) {
          label.textContent = labels[action];
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
    return prev?.classList.contains('matrixblock')
      ? (prev as HTMLElement)
      : null;
  }

  private nextBlock(): HTMLElement | null {
    const next = this.container.nextElementSibling;
    return next?.classList.contains('matrixblock')
      ? (next as HTMLElement)
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

    if (this.tabContainer) {
      this.tabContainer.style.display = 'none';
    }

    // Remember that?
    if (!this.isNew) {
      MatrixInput.rememberCollapsedEntryId(this.id!);
    } else {
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
          value = craft().getText(this.inputPreviewText(input));
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

  private inputPreviewText(input: HTMLElement): unknown {
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

    return getInputPostVal(input as HTMLInputElement);
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
      if (this.tabContainer) {
        this.tabContainer.style.display = '';
      }
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
    if (!this.isNew) {
      MatrixInput.forgetCollapsedEntryId(this.id!);
    } else if (this.collapsedInput) {
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

  updateFieldLayout(data: string): Promise<void> {
    return new Promise((resolve, reject) => {
      const elementEditor = this.matrix.elementEditor;
      const baseInputName = this.container.dataset.baseInputName ?? '';

      // Ignore if we're already submitting the main form
      if (elementEditor?.submittingForm) {
        reject(new Error('Form already being submitted.'));
        return;
      }

      if (this.cancelToken) {
        this.ignoreFailedRequest = true;
        this.cancelToken.cancel();
      }

      const param = (n: string) => craft().namespaceInputName(n, baseInputName);
      const extraData: Record<string, unknown> = {
        [param('visibleLayoutElements')]: this.visibleLayoutElements,
        [param('staticLayoutElements')]: this.staticLayoutElements,
        [param('elementType')]: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
        [param('siteId')]: this.matrix.settings!.siteId,
        [param('ownerId')]: this.matrix.settings!.ownerId,
        [param('fieldId')]: this.matrix.settings!.fieldId,
        [param('sortOrder')]:
          Array.from(this.container.parentElement?.children ?? []).indexOf(
            this.container
          ) + 1,
        [param('typeId')]: this.container.dataset.typeId,
        [param('elementUid')]:
          elementEditor?.getDraftElementUid(this.container.dataset.uid) ??
          this.container.dataset.uid,
      };

      const selectedTab = this.fieldsContainer?.querySelector<HTMLElement>(
        ':scope > [data-layout-tab]:not(.hidden)'
      );
      const selectedTabId = selectedTab?.dataset.id;
      if (selectedTabId) {
        extraData[param('selectedTab')] = selectedTabId;
      }

      data += `&${jqParam(extraData)}`;

      this.cancelToken = axios.CancelToken.source();

      Craft.sendActionRequest('POST', 'elements/update-field-layout', {
        cancelToken: this.cancelToken.token,
        headers: {
          'content-type': 'application/x-www-form-urlencoded',
          'X-Craft-Namespace': baseInputName,
        },
        data,
      })
        .then((response: {data: UpdateFieldLayoutResponse}) => {
          this.afterUpdateFieldLayout(selectedTabId, baseInputName, response);
          resolve();
        })
        .catch((e: unknown) => {
          if (!this.ignoreFailedRequest) {
            reject(e);
          }
          this.ignoreFailedRequest = false;
        })
        .finally(() => {
          this.cancelToken = null;
        });
    });
  }

  private async afterUpdateFieldLayout(
    selectedTabId: string | undefined,
    baseInputName: string,
    response: {data: UpdateFieldLayoutResponse}
  ): Promise<void> {
    // capture the new selected tab ID, in case it just changed
    const newSelectedTabId = this.fieldsContainer?.querySelector<HTMLElement>(
      ':scope > [data-layout-tab]:not(.hidden)'
    )?.dataset.id;

    // Update the visible elements
    const allTabContainers: HTMLElement[] = [];
    const visibleLayoutElements: Record<string, string[]> = {};
    const staticLayoutElements: Record<string, string[]> = {};

    for (const tabInfo of response.data.missingElements) {
      let tabContainer = this.fieldsContainer?.querySelector<HTMLElement>(
        `:scope > [data-layout-tab="${tabInfo.uid}"]`
      );

      if (!tabContainer) {
        tabContainer = document.createElement('div');
        tabContainer.id = craft().namespaceId(tabInfo.id, baseInputName);
        tabContainer.className = 'flex-fields';
        tabContainer.dataset.id = tabInfo.id;
        tabContainer.dataset.layoutTab = tabInfo.uid;
        if (tabInfo.id !== selectedTabId) {
          tabContainer.classList.add('hidden');
        }
        this.fieldsContainer?.append(tabContainer);
      }

      allTabContainers.push(tabContainer);

      for (const elementInfo of tabInfo.elements) {
        if (elementInfo.html !== false) {
          (visibleLayoutElements[tabInfo.uid] ??= []).push(elementInfo.uid);

          if (elementInfo.static) {
            (staticLayoutElements[tabInfo.uid] ??= []).push(elementInfo.uid);
          }

          if (typeof elementInfo.html === 'string') {
            const oldElement = tabContainer.querySelector(
              `:scope > [data-layout-element="${elementInfo.uid}"]`
            );
            const template = document.createElement('template');
            template.innerHTML = elementInfo.html.trim();
            const newElement = template.content.firstElementChild;
            if (newElement) {
              if (oldElement) {
                oldElement.replaceWith(newElement);
              } else {
                tabContainer.append(newElement);
              }
              Craft.initUiElements(newElement);
            }
          }
        } else {
          const oldElement = tabContainer.querySelector(
            `:scope > [data-layout-element="${elementInfo.uid}"]`
          );
          if (
            !oldElement ||
            !hasAttr(oldElement, 'data-layout-element-placeholder')
          ) {
            const placeholder = document.createElement('div');
            placeholder.className = 'hidden';
            placeholder.dataset.layoutElement = elementInfo.uid;
            placeholder.setAttribute('data-layout-element-placeholder', '');

            if (oldElement) {
              oldElement.replaceWith(placeholder);
            } else {
              tabContainer.append(placeholder);
            }
          }
        }
      }
    }

    // Remove any unused tab content containers
    // (`[data-layout-tab=""]` == unconditional containers, so ignore those)
    for (const container of this.fieldsContainer?.querySelectorAll<HTMLElement>(
      ':scope > [data-layout-tab]'
    ) ?? []) {
      if (
        !allTabContainers.includes(container) &&
        container.getAttribute('data-layout-tab') !== ''
      ) {
        container.remove();
      }
    }

    // Make the first tab visible if no others are
    if (!allTabContainers.some((c) => !c.classList.contains('hidden'))) {
      allTabContainers[0]?.classList.remove('hidden');
    }

    this.visibleLayoutElements = visibleLayoutElements;
    this.staticLayoutElements = staticLayoutElements;

    // Update the tabs
    if (this.tabManager) {
      this.tabManager.destroy();
      this.tabManager = null;
      if (this.tabContainer) {
        this.tabContainer.innerHTML = '';
      }
    }

    this.hasTabs = !!response.data.tabs;

    if (this.hasTabs && this.tabContainer) {
      this.tabContainer.insertAdjacentHTML('beforeend', response.data.tabs!);
      this.tabManager = MatrixEntry.initTabs(this.tabContainer);

      // was a new tab selected after the request was kicked off?
      if (
        this.tabManager &&
        selectedTabId &&
        newSelectedTabId &&
        selectedTabId !== newSelectedTabId
      ) {
        const tabs = Array.from(
          this.tabContainer.querySelectorAll<HTMLElement>('[data-id]')
        );
        const newSelectedTab = tabs.find(
          (tab) => tab.dataset.id === newSelectedTabId
        );
        if (newSelectedTab) {
          // if the new tab is visible - switch to it
          this.tabManager.selectTab(newSelectedTab);
        } else if (tabs[0]) {
          // if the new tab is not visible (e.g. hidden by a condition)
          // switch to the first tab
          this.tabManager.selectTab(tabs[0]);
        }
      }
    }

    this.uiLabel = response.data.uiLabel ?? null;
    if (this.collapsed && this.previewContainer) {
      this.previewContainer.innerHTML = this.previewHtml();
    }

    await craft().appendHeadHtml(response.data.headHtml);
    await craft().appendBodyHtml(response.data.bodyHtml);

    // re-grab dismissible tips, re-attach listener, hide on re-load
    this.matrix.elementEditor?.handleDismissibleTips?.();
  }

  override destroy(): void {
    this.actionDisclosure?.hide();

    this.tabManager?.destroy();
    this.actionDisclosure?.destroy();
    this.formObserver?.destroy();
    this.tabManager = null;
    this.actionDisclosure = null;
    this.formObserver = null;

    containerMatrixEntries.delete(this.container);
    setJqData(this.container, 'entry', null);

    // alert any nested inputs that we're getting deleted (bubbles like the
    // legacy jQuery trigger; listeners filter with `target === currentTarget`)
    this.container.dispatchEvent(new Event('delete', {bubbles: true}));

    super.destroy();
  }
}

import {Base} from '@craftcms/garnish';
import {cvdData} from './support';
import type {FieldLayoutDesigner} from './field-layout-designer';
import type {Element as FldElement} from './element';

declare const Craft: any;
declare const axios: any;

/**
 * The card view designer (preview + thumbnail/attribute management). Native DOM
 * port of the legacy `Craft.FieldLayoutDesigner.CardViewDesigner`. jQuery
 * survives only at the `Craft.ui` seam; the sortable checkbox library is owned by
 * the `<craft-sortable-checkbox-select>` element, whose DOM events the CVD
 * consumes — it no longer references the `SortableCheckboxSelect` class.
 */
export class CardViewDesigner extends Base {
  designer: FieldLayoutDesigner;
  $container: HTMLElement;
  $previewContainer: any = null;
  $libraryContainer: HTMLElement;
  sortableCheckboxSelect: any = null;
  $thumbManagementContainer: any = null;
  alwaysShowThumbAlignmentBtns = false;
  cancelToken: any = null;
  attribute: any = null;

  constructor(designer: FieldLayoutDesigner, container: HTMLElement) {
    super();
    this.designer = designer;
    this.$container = container;
    cvdData.set(this.$container, this);

    this.$previewContainer = this.$container.querySelector('.cvd-preview');
    const libraryContainer = this.$container.querySelector(
      '.cvd-library .cp-checkbox-select'
    );
    if (!(libraryContainer instanceof HTMLElement)) {
      throw new Error('Card view designer requires a checkbox library.');
    }
    this.$libraryContainer = libraryContainer;

    // The <craft-sortable-checkbox-select> custom element boots the sortable list
    // itself; the CVD only consumes the DOM events it emits (see
    // listenToCheckboxEvents) and no longer references the SortableCheckboxSelect
    // class or its WeakMap.
    this.sortableCheckboxSelect = this.$container.querySelector(
      'craft-sortable-checkbox-select'
    );

    this.$thumbManagementContainer =
      this.$container.querySelector('.thumb-management');
    this.alwaysShowThumbAlignmentBtns =
      designer.settings!.alwaysShowThumbAlignmentBtns;

    const $thumbSelectDropdown = this.$thumbManagementContainer.querySelector(
      'select[id$="thumb-source"]'
    );
    this.addListener($thumbSelectDropdown, 'change', (ev: any) => {
      this.manageThumbnails(ev.target);
    });

    if (this.alwaysShowThumbAlignmentBtns) {
      // always show the thumbnail alignment buttons
      this.showThumbAlignment();
    }

    // The thumb-alignment button group is now a <craft-listbox> (wrapping a
    // <craft-button-group>) that re-emits its garnish selection change as a
    // native, bubbling `change` CustomEvent; `detail.$selectedOption` is the
    // selected <craft-button>. Listen for that instead of the old per-button
    // `activate` event on `.btn` elements.
    const $thumbAlignmentListbox = this.$thumbManagementContainer
      .querySelector('craft-button-group[id$="thumb-alignment"]')
      ?.closest('craft-listbox');
    if ($thumbAlignmentListbox) {
      this.addListener($thumbAlignmentListbox, 'change', (ev: any) => {
        this.manageThumbnailAlignment(ev.detail.$selectedOption);
      });
    }

    this.listenToCheckboxEvents();
    this.disablePreviewLinks();
  }

  listenToCheckboxEvents(): void {
    // trigger preview update when items are checked/unchecked
    this.addListener(this.$libraryContainer, 'change', (ev: any) => {
      if (
        ev.target instanceof HTMLElement &&
        ev.target.matches('input[type=checkbox]')
      ) {
        this.updateCardViewConfig();
        this.updatePreview();
      }
    });
    // `sortChange` is the native DOM event the <craft-sortable-checkbox-select>
    // element re-emits from its garnish sorter when items are reordered.
    this.sortableCheckboxSelect.addEventListener('sortChange', () => {
      this.updateCardViewConfig();
      this.updatePreview();
    });
  }

  updateCardViewConfig(): void {
    this.designer.updateConfig((config: any) => {
      // can't rely on :checked
      config.cardView = Array.from(
        this.$libraryContainer.querySelectorAll<HTMLInputElement>(
          'input[type=checkbox]'
        )
      )
        .filter((el) => el.checked)
        .map((el) => el.value);
      return config;
    });
  }

  async updatePreview(): Promise<void> {
    this.$previewContainer.classList.add('loading');
    Craft.cp.announce(Craft.t('app', 'Loading'));

    if (this.cancelToken) {
      this.cancelToken.cancel();
    }

    this.cancelToken = axios.CancelToken.source();

    let response;
    try {
      response = await Craft.sendActionRequest(
        'POST',
        'fields/render-card-preview',
        {
          cancelToken: this.cancelToken.token,
          data: {
            fieldLayoutConfig: {
              ...this.designer.config,
              generatedFields:
                document
                  .querySelector('craft-generated-fields-table')
                  ?.serialize() ?? [],
            },
          },
        }
      );
    } catch (e: any) {
      if (!axios.isCancel(e)) {
        Craft.cp.displayError(e?.response?.data?.message);
        throw e;
      }
      // otherwise the request was cancelled by a newer preview — ignore.
    } finally {
      this.$previewContainer.classList.remove('loading');
      Craft.cp.announce(Craft.t('app', 'Loading complete'));
      this.cancelToken = null;
    }

    if (response) {
      this.$previewContainer.innerHTML = response.data.previewHtml;
      this.disablePreviewLinks();
    }
  }

  disablePreviewLinks(): void {
    this.$previewContainer
      .querySelectorAll('a')
      .forEach((anchor: HTMLElement) => {
        // add aria-disabled to the preview links
        anchor.setAttribute('aria-disabled', 'true');
        // prevent the preview links from being clickable
        anchor.addEventListener('click', (ev) => {
          ev.preventDefault();
        });
      });
  }

  addCheckbox(config: any = {}): void {
    if (!this.$libraryContainer) {
      return;
    }

    const $draggable = document.createElement('div');
    $draggable.className = 'cp-checkbox-select__item';

    Craft.ui.createCheckbox({checked: false, ...config}).appendTo($draggable);

    // The <craft-sortable-checkbox-select> observes its items, so appending here
    // is enough — it gives this item its drag handle and sorter membership
    // automatically (see SortableCheckboxSelect.observeItems).
    this.$libraryContainer.appendChild($draggable);
  }

  updateCheckboxLabel(value: string, label: string): void {
    const $draggable = this.findCheckboxByValue(value);
    if ($draggable) {
      const $label = $draggable.querySelector('label');
      if ($label) {
        $label.textContent = label;
      }
    }
  }

  removeCheckbox(value: string): void {
    const $draggable = this.findCheckboxByValue(value);

    if ($draggable) {
      const updateConfig = $draggable.querySelector<HTMLInputElement>(
        'input[type="checkbox"]'
      )?.checked;
      $draggable.remove();

      // and now make a call to update the card preview
      if (updateConfig) {
        this.updateCardViewConfig();
        this.updatePreview();
      }
    }
  }

  findCheckboxByValue(value: string): HTMLElement | null {
    if (!this.$libraryContainer) {
      return null;
    }

    return (
      this.$libraryContainer
        .querySelector(`input[value="${value}"]`)
        ?.closest('.cp-checkbox-select__item') ?? null
    );
  }

  /** THUMBNAILS **/
  manageThumbnailAlignment(target: any): void {
    const alignment = target.dataset.value;

    if (alignment !== this.designer.config.thumbAlignment) {
      this.designer.updateConfig((config: any) => {
        config.cardThumbAlignment = alignment;
        return config;
      });
      this.updatePreview();
    }
  }

  manageThumbnails(target: any): void {
    const $select = target;
    let thumbFieldKey = null;

    if ($select.value === '__none__' || $select.value === '__default__') {
      if (
        $select.value === '__none__' &&
        !this.designer.settings!.alwaysShowThumbAlignmentBtns
      ) {
        // hide the alignment buttons
        this.hideThumbAlignment();
      }
    } else {
      thumbFieldKey = $select.value;
      // show the alignment buttons
      this.showThumbAlignment();
    }

    if (thumbFieldKey !== this.designer.config.thumbFieldKey) {
      this.designer.updateConfig((config: any) => {
        config.thumbFieldKey = thumbFieldKey;
        return config;
      });
      this.updatePreview();
    }
  }

  updateThumbnailsDropdown(element: FldElement, action: string): void {
    const $select = this.$thumbManagementContainer.querySelector(
      'select[id$="thumb-source"]'
    );
    if (!$select) {
      return;
    }

    const thumbOptions = element.$container.dataset.thumbOptions
      ? JSON.parse(element.$container.dataset.thumbOptions)
      : null;
    if (!thumbOptions?.length) {
      return;
    }

    thumbOptions.forEach((option: any) => {
      const value = option.value.replace(/\{uid}/g, element.uid);
      switch (action) {
        case 'add': {
          const $option = document.createElement('option');
          $option.value = value;
          $option.text = option.label;
          $select.appendChild($option);
          break;
        }
        case 'remove': {
          const $option = $select.querySelector(`option[value="${value}"]`);
          if ($option) {
            const selected = $option.selected;
            $option.remove();
            if (selected) {
              this.manageThumbnails($select);
            }
          }
          break;
        }
      }
    });
  }

  updateThumbnailsDropdownOptionLabel($container: any): void {
    const uid = $container.dataset.uid;
    const $select = this.$thumbManagementContainer.querySelector(
      'select[id$="thumb-source"]'
    );
    const $option = $select?.querySelector(`option[value="${uid}"]`);

    if (!$option) {
      return;
    }

    const label =
      $container.querySelector('.fld-element-label')?.textContent ??
      this.attribute;
    $option.textContent = label;
  }

  showThumbAlignment(): void {
    this.$thumbManagementContainer
      .querySelectorAll('[data-attribute="thumb-alignment"]')
      .forEach((el: HTMLElement) => el.classList.remove('hidden'));
  }

  hideThumbAlignment(): void {
    this.$thumbManagementContainer
      .querySelectorAll('[data-attribute="thumb-alignment"]')
      .forEach((el: HTMLElement) => el.classList.add('hidden'));
  }

  /**
   * Tear down the CVD so the FLD can be re-booted (host innerHTML swap): cancel any
   * in-flight preview, clear the `cvdData` back-reference, then run the base
   * teardown. The sortable checkbox custom element owns its controller teardown.
   */
  override destroy(): void {
    this.cancelToken?.cancel();
    this.sortableCheckboxSelect = null;

    if (this.$container) {
      cvdData.delete(this.$container);
    }

    super.destroy();
  }
}

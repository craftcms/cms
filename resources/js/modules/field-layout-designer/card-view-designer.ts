import {isCancel} from '@craftcms/ui/utilities/api/http';
import {Base} from '@craftcms/garnish';
import {cvdData} from './support';
import type {FieldLayoutDesigner} from './field-layout-designer';
import type {Element as FldElement} from './element';

declare const Craft: any;

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
  abortController: AbortController | null = null;
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

    // The thumb-alignment buttons are a plain <craft-button-group> with no
    // `name`, so it doesn't track a selection itself; mark the clicked button
    // pressed here and apply its value.
    const $thumbAlignmentGroup = this.$thumbManagementContainer.querySelector(
      'craft-button-group[id$="thumb-alignment"]'
    );
    if ($thumbAlignmentGroup) {
      this.addListener($thumbAlignmentGroup, 'click', (ev: any) => {
        const $button = (ev.target as Element).closest?.(
          'craft-button[data-value]'
        );
        if (!$button || $button.hasAttribute('disabled')) {
          return;
        }
        for (const $option of $thumbAlignmentGroup.querySelectorAll(
          'craft-button[data-value]'
        )) {
          const pressed = $option === $button;
          $option.toggleAttribute('active', pressed);
          $option.setAttribute('aria-pressed', pressed ? 'true' : 'false');
        }
        this.manageThumbnailAlignment($button);
      });
    }

    this.listenToCheckboxEvents();
    this.listenToAppearanceControls();
    this.disablePreviewLinks();
    this.syncPreviewAppearance();
  }

  /**
   * The Name, Color, and Icon controls of the component that owns the layout
   * (an entry type, say), when its edit form has them.
   */
  appearanceControl(key: 'name' | 'color' | 'icon'): HTMLElement | null {
    return (this.$container.closest('form') ?? document).querySelector(
      `[data-ui-control-path='["${key}"]']`
    );
  }

  /** Reflects changes to the Name, Color, and Icon controls in the preview card. */
  listenToAppearanceControls(): void {
    // The container may not be in the form yet, so listen on the document.
    document.addEventListener('model-value-changed', this.onAppearanceChange);
  }

  // The control's field re-dispatches the event, so it may come from either.
  onAppearanceChange = (ev: Event): void => {
    const target = ev.target;
    if (
      target instanceof Node &&
      (['name', 'color', 'icon'] as const).some((key) => {
        const control = this.appearanceControl(key);
        return control !== null && target.contains(control);
      })
    ) {
      this.syncPreviewAppearance();
    }
  };

  /**
   * Gives the preview card the owner's color, and its icon and name in the
   * header, as the cards of its elements get them, falling back to a white
   * card with an empty header.
   */
  syncPreviewAppearance(): void {
    const card = this.$previewContainer?.querySelector('craft-card');
    if (!card) {
      return;
    }

    const value = (key: 'name' | 'color' | 'icon'): string =>
      String((this.appearanceControl(key) as any)?.modelValue ?? '').trim();
    // Only for owners with a color or icon, like entry types, whose elements'
    // cards show the owner in their headers.
    if (!this.appearanceControl('color') && !this.appearanceControl('icon')) {
      return;
    }

    const name = value('name');
    const color = value('color');
    const icon = value('icon');
    card.setAttribute('data-color', color || 'white');

    const header = card.querySelector(':scope > [slot="header"]');
    if (!header) {
      return;
    }

    header.replaceChildren();
    if (!icon && !name) {
      return;
    }

    const label = document.createElement('div');
    label.className = 'flex flex-nowrap gap-1 grow-1 items-center';
    if (icon) {
      const iconElement = document.createElement('craft-icon');
      iconElement.setAttribute('name', icon);
      label.append(iconElement);
    }
    if (name) {
      const nameElement = document.createElement('div');
      nameElement.className = 'card-titlebar-label';
      nameElement.textContent = name;
      label.append(nameElement);
    }
    header.append(label);
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

    if (this.abortController) {
      this.abortController.abort();
    }

    this.abortController = new AbortController();

    let response;
    try {
      response = await Craft.sendActionRequest(
        'POST',
        'fields/render-card-preview',
        {
          signal: this.abortController.signal,
          data: {
            fieldLayoutConfig: {
              ...this.designer.config,
              // Not part of `this.designer.config` (that lives at
              // `designer.settings.elementType` instead), but `CardDesigner::previewHtml()`
              // needs a `type` on the layout config to instantiate a sample element.
              type: this.designer.settings!.elementType,
              generatedFields:
                document
                  .querySelector('craft-generated-fields-table')
                  ?.serialize() ?? [],
            },
          },
        }
      );
    } catch (e: any) {
      if (!isCancel(e)) {
        Craft.cp.displayError(e?.response?.data?.message);
        throw e;
      }
      // otherwise the request was canceled by a newer preview — ignore.
    } finally {
      this.$previewContainer.classList.remove('loading');
      Craft.cp.announce(Craft.t('app', 'Loading complete'));
      this.abortController = null;
    }

    if (response) {
      this.$previewContainer.innerHTML = response.data.previewHtml;
      this.syncPreviewAppearance();
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

    if (alignment !== this.designer.config.cardThumbAlignment) {
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
    this.abortController?.abort();
    this.sortableCheckboxSelect = null;
    document.removeEventListener(
      'model-value-changed',
      this.onAppearanceChange
    );

    if (this.$container) {
      cvdData.delete(this.$container);
    }

    super.destroy();
  }
}

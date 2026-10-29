import {isCancel} from '@craftcms/ui/utilities/api/http';
import type CraftCheckboxSelect from '@craftcms/ui/components/checkbox-select/checkbox-select';
import {Base} from '@craftcms/garnish';
import {cvdData} from './support';
import type {FieldLayoutDesigner} from './field-layout-designer';
import type {Element as FldElement} from './element';

declare const Craft: any;

/**
 * The card view designer (preview + thumbnail/attribute management). Native DOM
 * port of the legacy `Craft.FieldLayoutDesigner.CardViewDesigner`. jQuery
 * survives only at the `Craft.ui` seam. The attribute library is a
 * `<craft-checkbox-select>`, changed through its options API.
 */
export class CardViewDesigner extends Base {
  designer: FieldLayoutDesigner;
  $container: HTMLElement;
  $previewContainer: any = null;
  $libraryContainer: CraftCheckboxSelect;
  $thumbManagementContainer: any = null;
  $thumbSources: CraftCheckboxSelect | null = null;
  alwaysShowThumbAlignmentBtns = false;
  hasThumbAlignment = false;
  abortController: AbortController | null = null;
  attribute: any = null;
  previewCardObserver = new ResizeObserver(() => this.snapPreviewCard());
  /**
   * The preview's sample image depends on the theme, so it's rendered again
   * when the theme changes without a page load (System following the
   * operating system, say).
   */
  themeObserver = new MutationObserver(() => void this.updatePreview());

  constructor(designer: FieldLayoutDesigner, container: HTMLElement) {
    super();
    this.designer = designer;
    this.$container = container;
    cvdData.set(this.$container, this);

    this.$previewContainer = this.$container.querySelector('.cvd-preview');
    const libraryContainer = this.$container.querySelector<CraftCheckboxSelect>(
      '.cvd-library craft-checkbox-select'
    );
    if (!libraryContainer) {
      throw new Error('Card view designer requires a checkbox library.');
    }
    this.$libraryContainer = libraryContainer;

    this.$thumbManagementContainer =
      this.$container.querySelector('.thumb-management');
    this.alwaysShowThumbAlignmentBtns =
      designer.settings!.alwaysShowThumbAlignmentBtns;

    this.$thumbSources = this.$thumbManagementContainer.querySelector(
      'craft-checkbox-select[id$="thumb-sources"]'
    );
    if (this.$thumbSources) {
      this.addListener(this.$thumbSources, 'change', (ev: any) => {
        if (
          ev.target instanceof HTMLElement &&
          ev.target.matches('input[type=checkbox]')
        ) {
          this.manageThumbnails();
        }
      });
      this.addListener(this.$thumbSources, 'craft-order-change', () => {
        this.manageThumbnails();
      });
    }

    this.hasThumbAlignment =
      this.alwaysShowThumbAlignmentBtns || this.thumbSources().length > 0;
    this.toggleThumbFields();

    // The thumb-alignment options are radios, so whichever way the selection
    // changes (clicks or arrow keys), the checked one's value is applied.
    const $thumbAlignmentGroup = this.$thumbManagementContainer.querySelector(
      '[id$="thumb-alignment"].radio-group'
    );
    if ($thumbAlignmentGroup) {
      this.addListener($thumbAlignmentGroup, 'change', (ev: any) => {
        const $radio = ev.target;
        if ($radio instanceof HTMLInputElement && $radio.checked) {
          this.manageThumbnailAlignment($radio.value);
        }
      });
    }

    const $thumbStyleGroup = this.$thumbManagementContainer.querySelector(
      '[id$="thumb-style"].radio-group'
    );
    if ($thumbStyleGroup) {
      this.addListener($thumbStyleGroup, 'change', (ev: any) => {
        const $radio = ev.target;
        if ($radio instanceof HTMLInputElement && $radio.checked) {
          this.manageThumbnailStyle($radio.value);
        }
      });
    }

    this.listenToCheckboxEvents();
    this.listenToAppearanceControls();
    this.disablePreviewLinks();
    this.syncPreviewAppearance();
    this.observePreviewCard();
    this.themeObserver.observe(document.documentElement, {
      attributeFilter: ['data-theme'],
    });
  }

  /**
   * Watches the preview card, and what's in it, for size changes, so it can
   * be snapped to the workspace grid.
   */
  observePreviewCard(): void {
    this.previewCardObserver.disconnect();
    const card = this.$previewContainer?.querySelector('craft-card');

    if (!card) {
      return;
    }

    this.previewCardObserver.observe(card);
    for (const child of Array.from(card.children) as Element[]) {
      this.previewCardObserver.observe(child);
    }
  }

  /**
   * Rounds the preview card's height up to a whole number of the workspace
   * grid's cells, plus its bottom border, so both of its outer edges land on a
   * row of dots, as the chip's do.
   */
  snapPreviewCard(): void {
    const card = this.$previewContainer?.querySelector('craft-card');

    if (!card) {
      return;
    }

    const cell = parseFloat(
      getComputedStyle(this.$previewContainer, '::before').backgroundSize
    );

    card.style.removeProperty('--cvd-card-min-block-size');

    if (!cell) {
      return;
    }

    const height = card.getBoundingClientRect().height;
    const snapped = Math.ceil((height - 1) / cell) * cell + 1;
    card.style.setProperty('--cvd-card-min-block-size', `${snapped}px`);
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
   * header, as the cards of its elements get them, falling back to a card
   * with no color of its own and an empty header.
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
    // "No color" is the color select's `__blank__` option: no color of the
    // card's own, as the cards of elements without a color have.
    const color = value('color').replace(/^__blank__$/, '');
    const icon = value('icon');
    // The sample chip takes the color too, but not the icon: chips show a
    // thumbnail or status instead.
    const chip = this.$previewContainer?.querySelector('craft-chip.cvd-chip');
    for (const element of [card, chip]) {
      if (color) {
        element?.setAttribute('data-color', color);
      } else {
        element?.removeAttribute('data-color');
      }
    }

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
      nameElement.setAttribute('data-card-label', '');
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
    this.addListener(this.$libraryContainer, 'craft-order-change', () => {
      this.updateCardViewConfig();
      this.updatePreview();
    });
  }

  updateCardViewConfig(): void {
    this.designer.updateConfig((config: any) => {
      config.cardView = this.$libraryContainer.modelValue;
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
      // The new card keeps the old one's snapped height until it's snapped
      // itself, so it doesn't show at its natural height for a frame first,
      // with a cover card's content a few pixels higher.
      const snapped = this.$previewContainer
        .querySelector('craft-card')
        ?.style.getPropertyValue('--cvd-card-min-block-size');
      this.$previewContainer.innerHTML = response.data.previewHtml;
      if (snapped) {
        this.$previewContainer
          .querySelector('craft-card')
          ?.style.setProperty('--cvd-card-min-block-size', snapped);
      }
      this.syncPreviewAppearance();
      this.disablePreviewLinks();
      this.observePreviewCard();
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

  addCheckbox(config: {value: string; label: string; checked?: boolean}): void {
    this.$libraryContainer.addOption({checked: false, ...config});
  }

  hasCheckbox(value: string): boolean {
    return this.$libraryContainer.options.some(
      (option) => option.value === value
    );
  }

  updateCheckboxLabel(value: string, label: string): void {
    this.$libraryContainer.updateOption(value, {label});
  }

  removeCheckbox(value: string): void {
    const checked = this.$libraryContainer.options.find(
      (option) => option.value === value
    )?.checked;
    this.$libraryContainer.removeOption(value);

    // and now make a call to update the card preview
    if (checked) {
      this.updateCardViewConfig();
      this.updatePreview();
    }
  }

  /** THUMBNAILS **/
  manageThumbnailAlignment(alignment: string): void {
    if (alignment !== this.designer.config.cardThumbAlignment) {
      this.designer.updateConfig((config: any) => {
        config.cardThumbAlignment = alignment;
        return config;
      });
      this.updatePreview();
    }
  }

  manageThumbnailStyle(style: string): void {
    if (style !== this.designer.config.cardThumbStyle) {
      this.designer.updateConfig((config: any) => {
        config.cardThumbStyle = style;
        return config;
      });
      this.toggleThumbFields();
      this.updatePreview();
    }
  }

  thumbSources(): string[] {
    return this.$thumbSources?.modelValue ?? [];
  }

  manageThumbnails(): void {
    const thumbSources = this.thumbSources();
    this.hasThumbAlignment =
      this.alwaysShowThumbAlignmentBtns || thumbSources.length > 0;

    const current = (this.designer.config.thumbSources ?? []) as string[];
    if (thumbSources.join() !== current.join()) {
      this.designer.updateConfig((config: any) => {
        config.thumbSources = thumbSources;
        return config;
      });
      this.updatePreview();
    }

    this.toggleThumbFields();
  }

  /** The thumb sources a layout element provides, with its UID filled in. */
  elementThumbOptions(
    $container: HTMLElement
  ): {value: string; label: string}[] {
    const thumbOptions = $container.dataset.thumbOptions
      ? JSON.parse($container.dataset.thumbOptions)
      : [];
    return thumbOptions.map((option: any) => ({
      ...option,
      value: option.value.replace(/\{uid}/g, $container.dataset.uid),
    }));
  }

  updateThumbnailsDropdown(element: FldElement, action: string): void {
    if (!this.$thumbSources) {
      return;
    }

    let checkedRemoved = false;
    for (const option of this.elementThumbOptions(element.$container)) {
      switch (action) {
        case 'add':
          this.$thumbSources.addOption({checked: false, ...option});
          break;
        case 'remove':
          checkedRemoved ||= this.thumbSources().includes(option.value);
          this.$thumbSources.removeOption(option.value);
          break;
      }
    }

    if (checkedRemoved) {
      this.manageThumbnails();
    }
  }

  updateThumbnailsDropdownOptionLabel($container: any): void {
    for (const option of this.elementThumbOptions($container)) {
      this.$thumbSources?.updateOption(option.value, {label: option.label});
    }
  }

  /**
   * Shows Thumb Style only when the thumbnail can be an asset's image (always for assets; otherwise once a
   * thumb source is a field outside a Content Block), and hides Thumb Alignment when the thumbnail covers the card.
   */
  toggleThumbFields(): void {
    const showStyle =
      this.designer.settings!.alwaysShowThumbStyle ||
      this.thumbSources().some((key) => !key.startsWith('contentBlock:'));
    const cover = showStyle && this.designer.config.cardThumbStyle === 'cover';

    this.$thumbManagementContainer
      .querySelectorAll('[data-attribute="thumb-style"]')
      .forEach((el: HTMLElement) => el.classList.toggle('hidden', !showStyle));
    this.$thumbManagementContainer
      .querySelectorAll('[data-attribute="thumb-alignment"]')
      .forEach((el: HTMLElement) =>
        el.classList.toggle('hidden', !this.hasThumbAlignment || cover)
      );
  }

  /**
   * Tear down the CVD so the FLD can be re-booted (host innerHTML swap): cancel any
   * in-flight preview, clear the `cvdData` back-reference, then run the base
   * teardown.
   */
  override destroy(): void {
    this.abortController?.abort();
    this.previewCardObserver.disconnect();
    this.themeObserver.disconnect();
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

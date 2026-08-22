import {Base, Modal, Select} from '@craftcms/garnish';
import axios from 'axios';

declare const Craft: any;

/**
 * The legacy `Craft.ElementIndex` surface EntryMover reads. The element index
 * isn't ported yet, so it's still a jQuery-based object — `$source`/`$elements`
 * are jQuery collections. A documented legacy-interop seam (methods are called
 * on the passed object; no jQuery is imported here).
 */
interface LegacyElementIndex {
  siteId: number;
  $source: {data(key: string): string | number | null | undefined};
  $elements: {attr(name: string, value: string): {focus(): void}};
  updateElements(): void;
}

/**
 * EntryMover — a port of `Craft.EntryMover` onto `@craftcms/garnish` `Base`.
 * Opens a modal listing the sections the selected entries can be moved to, and
 * performs the move on confirm.
 *
 * First consumer of the modern `@craftcms/garnish` `Modal` + `Select` from
 * `resources/js` — both replace their legacy `Garnish.*` equivalents 1:1.
 * `Craft.ui.createSubmitButton` stays a legacy jQuery seam (not yet ported to
 * `@craftcms/ui/factory`). Booted imperatively from `MoveToSection.php`
 * (`new Craft.EntryMover(...)`), so the class is exposed on `window.Craft`.
 */
export class EntryMover extends Base {
  modal: Modal | null = null;

  entryIds: Array<number | string> = [];
  currentSectionUid: string | null = null;
  elementIndex: LegacyElementIndex | null = null;

  #cancelToken: {cancel(): void; token: unknown} | null = null;
  #sectionsList: HTMLElement | null = null;
  #selectBtn: HTMLElement | null = null;
  #sectionSelect: Select | null = null;

  constructor(
    entryIds?: Array<number | string>,
    elementIndex?: LegacyElementIndex
  ) {
    super();
    if (new.target === EntryMover) {
      this.init(entryIds ?? [], elementIndex ?? null);
    }
  }

  init(
    entryIds: Array<number | string>,
    elementIndex: LegacyElementIndex | null
  ): void {
    this.entryIds = entryIds;
    this.elementIndex = elementIndex;

    // uid of the section we're moving from
    const sourceKey = String(elementIndex?.$source.data('key') ?? '');
    this.currentSectionUid = sourceKey.startsWith('section:')
      ? sourceKey.substring(8)
      : null;

    this.createModal();
  }

  createModal(): void {
    const container = document.createElement('div');
    container.className = 'modal entry-mover-modal';

    const header = document.createElement('div');
    header.className = 'header';
    container.append(header);

    const headingId = `sectionSelectorModalHeading-${Math.floor(
      Math.random() * 1000000
    )}`;
    const heading = document.createElement('h1');
    heading.id = headingId;
    heading.textContent = Craft.t('app', 'Move to');
    header.append(heading);

    const body = document.createElement('div');
    body.className = 'body';
    container.append(body);

    const footer = document.createElement('div');
    footer.className = 'footer';
    container.append(footer);

    const listContainer = document.createElement('div');
    listContainer.className = 'entry-mover-modal--list';
    body.append(listContainer);

    this.#sectionsList = document.createElement('fieldset');
    this.#sectionsList.className = 'chips';
    this.#sectionsList.setAttribute('aria-labelledby', headingId);
    listContainer.append(this.#sectionsList);

    const secondaryButtons = document.createElement('div');
    secondaryButtons.className = 'buttons left secondary-buttons';
    footer.append(secondaryButtons);

    const primaryButtons = document.createElement('div');
    primaryButtons.className = 'buttons right';
    footer.append(primaryButtons);

    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'btn';
    cancelBtn.textContent = Craft.t('app', 'Cancel');
    primaryButtons.append(cancelBtn);

    // `Craft.ui.createSubmitButton` is still a legacy jQuery factory (not yet in
    // @craftcms/ui/factory); take its DOM element.
    // SAFETY: The legacy button factory returns a jQuery collection whose first item is the created button.
    this.#selectBtn = Craft.ui.createSubmitButton({
      class: 'disabled',
      label: Craft.t('app', 'Move'),
      spinner: true,
    })[0] as HTMLElement;
    this.#selectBtn.setAttribute('aria-disabled', 'true');
    primaryButtons.append(this.#selectBtn);

    this.addListener(cancelBtn, 'activate', 'cancel');
    this.addListener(this.#selectBtn, 'activate', 'selectSection');

    this.modal = new Modal(container);
    this.getCompatibleSections();
  }

  getCompatibleSections(): void {
    if (this.#cancelToken) {
      this.#cancelToken.cancel();
    }

    this.#selectBtn?.classList.add('loading');
    this.#cancelToken = axios.CancelToken.source();

    Craft.sendActionRequest('POST', 'entries/move-to-section-modal-data', {
      data: {
        entryIds: this.entryIds,
        siteId: this.elementIndex?.siteId,
        currentSectionUid: this.currentSectionUid,
      },
      cancelToken: this.#cancelToken?.token,
    })
      .then(({data}: {data?: {listHtml?: string}}) => {
        const listHtml = data?.listHtml;
        if (!listHtml || !this.#sectionsList) {
          return;
        }

        this.#sectionsList.innerHTML = listHtml;

        for (const cb of Array.from(
          this.#sectionsList.querySelectorAll('.checkbox')
        )) {
          cb.setAttribute('role', 'radio');
        }

        const chips = Array.from(
          this.#sectionsList.querySelectorAll<HTMLElement>('.chip')
        );

        this.#sectionSelect = new Select(this.#sectionsList, chips, {
          vertical: true,
          filter: (target: EventTarget | null) =>
            !(target instanceof Element
              ? target.closest('a[href],.toggle,.btn,[role=button]')
              : null),
          checkboxMode: true,
          onSelectionChange: () => {
            if (this.#sectionSelect?.$selectedItems.length) {
              this.#selectBtn?.classList.remove('disabled');
            } else {
              this.#selectBtn?.classList.add('disabled');
            }
          },
        });
      })
      .catch(({response}: {response?: {data?: {message?: string}}}) => {
        Craft.cp.displayError(response?.data?.message);
        this.modal?.hide();
      })
      .finally(() => {
        this.#selectBtn?.classList.remove('loading');
        this.#cancelToken = null;
      });
  }

  selectSection(): void {
    if (this.#selectBtn?.classList.contains('loading')) {
      return;
    }

    this.#selectBtn?.classList.add('loading');
    Craft.cp.announce(Craft.t('app', 'Loading'));

    const data = {
      sectionId: this.#sectionSelect?.$selectedItems[0]?.dataset.id,
      entryIds: this.entryIds,
    };

    Craft.sendActionRequest('POST', 'entries/move-to-section', {data})
      .then((response: {data: {message: string}}) => {
        Craft.cp.displaySuccess(response.data.message);
        Craft.cp.announce(response.data.message);

        this.elementIndex?.updateElements();
        this.elementIndex?.$elements.attr('tabindex', '-1').focus();
        this.modal?.hide();
      })
      .catch((e: {response?: {data?: {message?: string}}}) => {
        Craft.cp.displayError(e?.response?.data?.message);
        Craft.cp.announce(e?.response?.data?.message);
      })
      .finally(() => {
        this.#selectBtn?.classList.remove('loading');
      });
  }

  cancel(): void {
    this.modal?.hide();
  }
}

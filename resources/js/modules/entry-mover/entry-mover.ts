import {Base, Modal} from '@craftcms/garnish';
import {t} from '@craftcms/ui/utilities/translate';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {showError, showSuccess} from '@/modules/messages/messages';
import {announce} from '@/common/announcer';

/** A `craft-button`, whose `disabled` and `loading` states are properties. */
type SelectButton = HTMLElement & {disabled: boolean; loading: boolean};

/** A `craft-radio-group`; its `value` is the chosen section's ID. */
type SectionGroup = HTMLElement & {value: string};

/**
 * Settings for moving entries without an element index to read the site and
 * section from, such as from a section's deletion blockers.
 */
export interface EntryMoverSettings {
  /**
   * A section to move all entries from. Its entries' IDs are loaded when the
   * mover opens, in place of `entryIds`.
   */
  sectionId?: number | null;
  siteId?: number | null;
  currentSectionUid?: string | null;
  onMove?: () => void;
  onCancel?: () => void;
}

interface MoveTargetSection {
  id: number;
  label: string;
}

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
 * Booted imperatively from `MoveToSection.php`
 * (`new Craft.EntryMover(...)`), so the class is exposed on `window.Craft`.
 */
export class EntryMover extends Base {
  modal: Modal | null = null;

  entryIds: Array<number | string> = [];
  currentSectionUid: string | null = null;
  elementIndex: LegacyElementIndex | null = null;
  entryMoverSettings: EntryMoverSettings = {};
  moved = false;

  #abortController: AbortController | null = null;
  #sectionsList: SectionGroup | null = null;
  #selectBtn: SelectButton | null = null;

  constructor(
    entryIds?: Array<number | string>,
    elementIndex?: LegacyElementIndex | null,
    settings?: EntryMoverSettings
  ) {
    super();
    if (new.target === EntryMover) {
      this.init(entryIds ?? [], elementIndex ?? null, settings);
    }
  }

  init(
    entryIds: Array<number | string>,
    elementIndex: LegacyElementIndex | null,
    settings: EntryMoverSettings = {}
  ): void {
    this.entryIds = entryIds;
    this.elementIndex = elementIndex;
    this.entryMoverSettings = settings;

    if (elementIndex) {
      // uid of the section we're moving from
      const sourceKey = String(elementIndex.$source.data('key') ?? '');
      this.currentSectionUid = sourceKey.startsWith('section:')
        ? sourceKey.substring(8)
        : null;
    } else {
      this.currentSectionUid = settings.currentSectionUid ?? null;
    }

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
    heading.textContent = t('Move to');
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

    // A radio group, since exactly one section is chosen. Its label repeats
    // the modal's heading for screen readers; the group manages its own
    // `aria-labelledby`, so it can't point at the heading.
    this.#sectionsList = document.createElement(
      'craft-radio-group'
    ) as SectionGroup;
    this.#sectionsList.setAttribute('label', t('Move to'));
    this.#sectionsList.setAttribute('label-sr-only', '');
    this.#sectionsList.setAttribute('name', 'sectionId');
    listContainer.append(this.#sectionsList);

    const secondaryButtons = document.createElement('div');
    secondaryButtons.className = 'buttons left secondary-buttons';
    footer.append(secondaryButtons);

    const primaryButtons = document.createElement('div');
    primaryButtons.className = 'buttons right';
    footer.append(primaryButtons);

    const cancelBtn = document.createElement('craft-button');
    cancelBtn.setAttribute('type', 'button');
    cancelBtn.textContent = t('Cancel');
    primaryButtons.append(cancelBtn);

    this.#selectBtn = document.createElement('craft-button') as SelectButton;
    this.#selectBtn.setAttribute('type', 'button');
    this.#selectBtn.setAttribute('variant', 'primary');
    this.#selectBtn.textContent = t('Move');
    this.#selectBtn.disabled = true;
    primaryButtons.append(this.#selectBtn);

    this.addListener(cancelBtn, 'click', 'cancel');
    this.addListener(this.#selectBtn, 'click', 'selectSection');

    this.modal = new Modal(container, {
      onHide: () => {
        if (!this.moved) {
          this.entryMoverSettings.onCancel?.();
        }
      },
    });
    this.getCompatibleSections();
  }

  getCompatibleSections(): void {
    if (this.#abortController) {
      this.#abortController.abort();
    }

    if (this.#selectBtn) {
      this.#selectBtn.loading = true;
    }
    this.#abortController = new AbortController();
    const {signal} = this.#abortController;
    const {sectionId} = this.entryMoverSettings;

    const loadEntryIds = sectionId
      ? actionClient
          .post('entries/section-entry-ids', {sectionId}, {signal})
          .then(({data}: {data?: {entryIds?: number[]}}) => {
            this.entryIds = data?.entryIds ?? [];
          })
      : Promise.resolve();

    loadEntryIds
      .then(() =>
        actionClient.post(
          'entries/move-to-section-modal-data',
          {
            entryIds: this.entryIds,
            siteId: this.elementIndex
              ? this.elementIndex.siteId
              : this.entryMoverSettings.siteId,
            currentSectionUid: this.currentSectionUid,
          },
          {signal}
        )
      )
      .then(({data}: {data?: {sections?: MoveTargetSection[]}}) => {
        const list = this.#sectionsList;
        if (!list) {
          return;
        }

        const sections = data?.sections ?? [];

        if (!sections.length) {
          const message = document.createElement('p');
          message.className = 'zilch';
          message.textContent = t(
            'Couldn’t find any sections that all selected elements could be moved to.'
          );
          list.replaceWith(message);
          return;
        }

        for (const section of sections) {
          const radio = document.createElement('craft-radio') as HTMLElement & {
            label: string;
            value: string;
          };
          radio.label = section.label;
          radio.value = String(section.id);
          list.append(radio);
        }

        this.addListener(list, 'change', () => {
          if (this.#selectBtn) {
            this.#selectBtn.disabled = !list.value;
          }
        });
      })
      .catch(({response}: {response?: {data?: {message?: string}}}) => {
        showError(response?.data?.message);
        this.modal?.hide();
      })
      .finally(() => {
        if (this.#selectBtn) {
          this.#selectBtn.loading = false;
        }
        this.#abortController = null;
      });
  }

  selectSection(): void {
    if (!this.#selectBtn || this.#selectBtn.loading) {
      return;
    }

    this.#selectBtn.loading = true;
    announce(t('Loading'));

    const data = {
      sectionId: this.#sectionsList?.value || undefined,
      entryIds: this.entryIds,
    };

    actionClient
      .post('entries/move-to-section', data)
      .then((response: {data: {message: string}}) => {
        showSuccess(response.data.message);
        announce(response.data.message);

        this.moved = true;
        this.elementIndex?.updateElements();
        this.elementIndex?.$elements.attr('tabindex', '-1').focus();
        this.modal?.hide();
        this.entryMoverSettings.onMove?.();
      })
      .catch((e: {response?: {data?: {message?: string}}}) => {
        showError(e?.response?.data?.message);
        announce(e?.response?.data?.message);
      })
      .finally(() => {
        if (this.#selectBtn) {
          this.#selectBtn.loading = false;
        }
      });
  }

  cancel(): void {
    this.modal?.hide();
  }
}

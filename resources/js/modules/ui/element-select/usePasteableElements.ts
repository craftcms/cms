import {actionClient, t} from '@craftcms/ui';
import {computed, nextTick, shallowRef, watch, type Ref} from 'vue';
import {craft} from '@/modules/matrix/clipboard';
import {useCopiedElements} from '@/modules/matrix/copied-elements';
import type {ElementPresentation, ElementSelectProps} from './types';

/**
 * Offers the copied elements an element-select field can relate, and pastes
 * them in.
 *
 * Which copied elements qualify is the server's call: a paste bypasses the
 * selector's sources, criteria and condition, so it's asked again whenever
 * the clipboard or any of those change.
 */
export function usePasteableElements(options: {
  field: () => ElementSelectProps;
  editable: () => boolean;
  /** The elements already related. */
  ids: Readonly<Ref<number[]>>;
  atLimit: Readonly<Ref<boolean>>;
  /** Relates the elements after the current ones, returning the ids now related. */
  relate: (elements: ElementPresentation[]) => number[];
  /** Where the related elements render, for moving focus to a pasted one. */
  container: Readonly<Ref<HTMLElement | null>>;
  /** Takes focus when no pasted element can. */
  fallbackFocus: Readonly<Ref<HTMLElement | null>>;
}) {
  const copiedElements = useCopiedElements();

  /**
   * The copied elements this field might relate: ones of its element type,
   * leaving out drafts and revisions, which a relation never points at.
   */
  const copiedIds = computed(() =>
    copiedElements.value
      .filter(
        (element) =>
          element.type === options.field().elementType &&
          !element.draftId &&
          !element.revisionId
      )
      .map((element) => Number(element.id))
      .filter((elementId) => Number.isInteger(elementId) && elementId > 0)
  );

  const pasteable = shallowRef<ElementPresentation[]>([]);

  watch(
    () =>
      options.editable() && copiedIds.value.length
        ? JSON.stringify(request())
        : '',
    async (key, _previous, onCleanup) => {
      if (key === '') {
        pasteable.value = [];
        return;
      }

      const controller = new AbortController();
      onCleanup(() => controller.abort());

      try {
        const {data} = await actionClient.post<{
          elements?: ElementPresentation[];
        }>('element-selects/pasteable', request(), {
          signal: controller.signal,
        });

        if (!controller.signal.aborted) {
          pasteable.value = data.elements ?? [];
        }
      } catch {
        if (!controller.signal.aborted) {
          pasteable.value = [];
        }
      }
    },
    {immediate: true}
  );

  function request() {
    const field = options.field();

    return {
      elementType: field.elementType,
      context: field.context,
      sources: field.sources,
      criteria: field.criteria,
      condition: field.selectionCondition,
      siteId: Craft.siteId ?? null,
      viewMode: field.viewMode,
      elementIds: copiedIds.value,
    };
  }

  /** What's left to paste once the elements already related are set aside. */
  const candidates = computed(() =>
    pasteable.value.filter((element) => !options.ids.value.includes(element.id))
  );

  const label = computed(() => {
    const field = options.field();
    const names = craft().elementTypeNames?.[field.elementType];
    const type =
      (candidates.value.length === 1 ? names?.[2] : names?.[3]) ??
      field.elementDisplayName;

    return t('Paste {type}', {type});
  });

  /**
   * Relates the copied elements, after the ones already related, then clears
   * the clipboard, as pasting does elsewhere.
   *
   * The button goes once they're pasted, so focus moves to the last pasted
   * element, which lands about where the button was. The page only scrolls if
   * none of it is in view, and then only as far as it takes to show it: a
   * focus() that scrolls on its own centers an element that's just out of
   * view, which reads as the page jumping.
   */
  async function paste(): Promise<void> {
    const pasted = candidates.value;

    if (pasted.length === 0 || options.atLimit.value) {
      return;
    }

    const kept = options.relate(pasted);
    craft().cp?.clearCopiedElements?.();
    await nextTick();

    const item = options.container.value?.querySelector<HTMLElement>(
      `.elements-stage [data-id="${kept[kept.length - 1]}"]`
    );
    const fallback = options.fallbackFocus.value;
    const target =
      (item?.matches('[tabindex="0"]')
        ? item
        : item?.querySelector<HTMLElement>(
            'craft-checkbox, craft-button, button, a[href], [tabindex="0"]'
          )) ?? (fallback?.isConnected ? fallback : null);

    if (!target) {
      return;
    }

    target.focus({preventScroll: true});

    const rect = target.getBoundingClientRect();

    if (rect.bottom <= 0 || rect.top >= window.innerHeight) {
      target.scrollIntoView({block: 'nearest'});
    }
  }

  return {candidates, label, paste};
}

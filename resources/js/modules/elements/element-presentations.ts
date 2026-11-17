/**
 * One cache for the data Vue draws element chips and cards from, shared by
 * every component on the page.
 *
 * Components seed it with what the server rendered, and read from it. When an
 * element is saved or a draft deleted, in this tab or another one, the entries
 * that components are drawing are refetched, with one request for every
 * component that draws the element the same way, and every one of them
 * re-renders.
 */

import {actionClient} from '@craftcms/ui';
import {reactive, toValue, watch, type MaybeRefOrGetter} from 'vue';
import {tryOnScopeDispose} from '@vueuse/core';
import {escapeRegex} from '@/common/craft/strings';
import {onElementChanged} from '@/modules/elements/element-instances';
import type {ElementPresentation} from '@/modules/ui/element-select/types';

/** How a component draws its elements; each way is cached separately. */
export interface PresentationQuery {
  elementType: string;
  viewMode: string;
  thumbSize?: string | null;
}

/** An element a component draws, in the site it draws it in. */
export interface PresentedElement {
  id: number;
  siteId: number | null;
}

interface Consumer {
  query: () => PresentationQuery;
  elements: () => PresentedElement[];
}

const cache = reactive(new Map<string, ElementPresentation>());
/**
 * Each entry's latest write or refetch. A refetch's answer is only cached if
 * nothing has written, refetched or evicted the entry since it was sent.
 */
const generations = new Map<string, number>();
let lastGeneration = 0;
const consumers = new Set<Consumer>();
const changedIds = new Set<number>();
/** Whether a response left saves out, so every drawn element may have changed. */
let everythingChanged = false;
let stopListening: (() => void) | null = null;
/** Numbers each component, to make the DOM IDs in its cards its own. */
let lastInstance = 0;

function siteKey(siteId: unknown): number | null {
  return Number(siteId) || null;
}

function groupKey(query: PresentationQuery, siteId: number | null): string {
  return [
    query.elementType,
    query.viewMode,
    query.thumbSize ?? '',
    siteId,
  ].join('|');
}

function entryKey(
  query: PresentationQuery,
  siteId: number | null,
  id: number
): string {
  return `${groupKey(query, siteId)}|${id}`;
}

function nextGeneration(key: string): number {
  generations.set(key, ++lastGeneration);
  return lastGeneration;
}

/** The entries components are drawing. */
function usedKeys(): Set<string> {
  const used = new Set<string>();
  consumers.forEach((consumer) => {
    const query = consumer.query();
    consumer
      .elements()
      .forEach(({id, siteId}) => used.add(entryKey(query, siteId, id)));
  });
  return used;
}

/** Refetches the changed elements that components are drawing, one request per way they're drawn. */
async function refetchChanged(): Promise<void> {
  const ids = new Set(changedIds);
  const everything = everythingChanged;
  changedIds.clear();
  everythingChanged = false;

  const groups = new Map<
    string,
    {query: PresentationQuery; siteId: number | null; ids: Set<number>}
  >();
  consumers.forEach((consumer) => {
    const query = consumer.query();
    consumer.elements().forEach(({id, siteId}) => {
      if (!everything && !ids.has(id)) {
        return;
      }

      const key = groupKey(query, siteId);
      const group = groups.get(key) ?? {query, siteId, ids: new Set()};
      group.ids.add(id);
      groups.set(key, group);
    });
  });

  await Promise.all(
    [...groups.values()].map(({query, siteId, ids}) =>
      fetchInto(query, siteId, [...ids])
    )
  );
}

/** Fetches elements drawn one way in one site, and caches them. */
async function fetchInto(
  query: PresentationQuery,
  siteId: number | null,
  ids: number[]
): Promise<void> {
  const sent = new Map(
    ids.map((id) => {
      const key = entryKey(query, siteId, id);
      return [key, nextGeneration(key)];
    })
  );
  const {data} = await actionClient.post<{
    elements?: ElementPresentation[];
  }>('relational-fields/structured-elements', {
    elementType: query.elementType,
    elementIds: ids,
    siteId,
    viewMode: query.viewMode,
    thumbSize: query.thumbSize ?? null,
  });

  data.elements?.forEach((element) => {
    const key = entryKey(query, siteId, element.id);
    if (sent.has(key) && generations.get(key) === sent.get(key)) {
      cache.set(key, element);
    }
  });
}

function listen(): void {
  stopListening ??= onElementChanged(
    (id) => {
      // `onElementChanged()` hands over everything that changed together in
      // one task, so it's all refetched together.
      if (changedIds.size === 0 && !everythingChanged) {
        queueMicrotask(() => {
          refetchChanged().catch((error: unknown) => {
            console.warn('Couldn’t refresh elements:', error);
          });
        });
      }
      if (id === null) {
        everythingChanged = true;
      } else {
        changedIds.add(id);
      }
    },
    {includeSelf: true}
  );
}

/** Drops the entries no component draws any more. */
function prune(): void {
  const used = usedKeys();

  [...new Set([...cache.keys(), ...generations.keys()])]
    .filter((key) => !used.has(key))
    .forEach((key) => {
      cache.delete(key);
      generations.delete(key);
    });

  if (consumers.size === 0) {
    stopListening?.();
    stopListening = null;
  }
}

/**
 * Draws `elements` through the shared cache, for as long as the calling
 * component lives.
 */
export function useElementPresentations(
  query: MaybeRefOrGetter<PresentationQuery>,
  elements: MaybeRefOrGetter<PresentedElement[]>
): {
  /** The cached presentation of an element, if there is one. */
  get(id: number, siteId: unknown): ElementPresentation | undefined;
  /**
   * Caches what the server rendered. Only a render the component received
   * after it mounted replaces what's cached: the one it mounted with may be
   * older, if it was held back, as a hidden tab's fields are.
   */
  seed(
    presentations: ElementPresentation[],
    fallbackSiteId: unknown,
    options?: {replace?: boolean}
  ): void;
  /** Fetches elements nothing has rendered yet, such as pasted ones. */
  load(elements: PresentedElement[]): Promise<void>;
} {
  const consumer: Consumer = {
    query: () => toValue(query),
    elements: () => toValue(elements),
  };
  consumers.add(consumer);
  listen();

  // Removed elements are dropped as soon as nothing draws them.
  watch(
    () => {
      const query = consumer.query();
      return consumer
        .elements()
        .map(({id, siteId}) => entryKey(query, siteId, id))
        .join(',');
    },
    () => prune()
  );

  tryOnScopeDispose(() => {
    consumers.delete(consumer);
    prune();
  });

  // Components share cards' data, but not their DOM IDs: the server gives
  // each card its own, which its HTML refers to, so each component gets a
  // copy with them made its own.
  const instance = ++lastInstance;
  const copies = new WeakMap<ElementPresentation, ElementPresentation>();
  function withOwnIds(presentation: ElementPresentation): ElementPresentation {
    const domId = presentation.cardAttributes?.id;
    if (typeof domId !== 'string' || domId === '') {
      return presentation;
    }

    let copy = copies.get(presentation);
    if (!copy) {
      const ownId = `${domId}-${instance}`;
      // Not where it starts a longer ID, such as `card-12` in `card-123`.
      const references = new RegExp(`${escapeRegex(domId)}(?!\\w)`, 'g');
      copy = Object.fromEntries(
        Object.entries(presentation).map(([key, value]) => [
          key,
          key.endsWith('Html') && typeof value === 'string'
            ? value.replace(references, ownId)
            : value,
        ])
      ) as ElementPresentation;
      copy.cardAttributes = {...presentation.cardAttributes, id: ownId};
      copies.set(presentation, copy);
    }

    return copy;
  }

  return {
    get: (id, siteId) => {
      const presentation = cache.get(
        entryKey(toValue(query), siteKey(siteId), id)
      );
      return presentation && withOwnIds(presentation);
    },
    seed: (presentations, fallbackSiteId, {replace = false} = {}) => {
      // Only elements a component draws are kept: a render can include
      // relations the user has removed but not saved.
      const used = usedKeys();
      presentations.forEach((presentation) => {
        const key = entryKey(
          toValue(query),
          siteKey(presentation.siteId ?? fallbackSiteId),
          presentation.id
        );
        if (!used.has(key)) {
          return;
        }
        if (replace) {
          cache.set(key, presentation);
          nextGeneration(key);
        } else if (!cache.has(key)) {
          // Filling a gap leaves the generation alone, so a refetch already
          // on its way, which is newer, still lands.
          cache.set(key, presentation);
        }
      });
    },
    load: async (elements) => {
      const bySite = new Map<number | null, number[]>();
      elements.forEach(({id, siteId}) => {
        const site = siteKey(siteId);
        bySite.set(site, [...(bySite.get(site) ?? []), id]);
      });

      await Promise.all(
        [...bySite].map(([siteId, ids]) =>
          fetchInto(toValue(query), siteId, ids)
        )
      );
    },
  };
}

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
import {reactive, toValue, type MaybeRefOrGetter} from 'vue';
import {tryOnScopeDispose} from '@vueuse/core';
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

/**
 * How long changes are gathered into one refetch. `onElementChanged()` calls
 * back once per element, so elements saved together arrive a moment apart.
 */
const BATCH_MS = 10;

const cache = reactive(new Map<string, ElementPresentation>());
const consumers = new Set<Consumer>();
const changedIds = new Set<number>();
let stopListening: (() => void) | null = null;

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

/** Refetches the changed elements that components are drawing, one request per way they're drawn. */
async function refetchChanged(): Promise<void> {
  const ids = new Set(changedIds);
  changedIds.clear();

  const groups = new Map<
    string,
    {query: PresentationQuery; siteId: number | null; ids: Set<number>}
  >();
  consumers.forEach((consumer) => {
    const query = consumer.query();
    consumer.elements().forEach(({id, siteId}) => {
      if (!ids.has(id)) {
        return;
      }

      const key = groupKey(query, siteId);
      const group = groups.get(key) ?? {query, siteId, ids: new Set()};
      group.ids.add(id);
      groups.set(key, group);
    });
  });

  await Promise.all(
    [...groups.values()].map(async ({query, siteId, ids}) => {
      const {data} = await actionClient.post<{
        elements?: ElementPresentation[];
      }>('relational-fields/structured-elements', {
        elementType: query.elementType,
        elementIds: [...ids],
        siteId,
        viewMode: query.viewMode,
        thumbSize: query.thumbSize ?? null,
      });

      data.elements
        ?.filter((element) => ids.has(element.id))
        .forEach((element) =>
          cache.set(entryKey(query, siteId, element.id), element)
        );
    })
  );
}

function listen(): void {
  stopListening ??= onElementChanged(
    (id) => {
      if (changedIds.size === 0) {
        setTimeout(() => {
          refetchChanged().catch((error: unknown) => {
            console.warn('Couldn’t refresh elements:', error);
          });
        }, BATCH_MS);
      }
      changedIds.add(id);
    },
    {includeSelf: true}
  );
}

/** Drops the entries no component draws any more. */
function prune(): void {
  const used = new Set<string>();
  consumers.forEach((consumer) => {
    const query = consumer.query();
    consumer
      .elements()
      .forEach(({id, siteId}) => used.add(entryKey(query, siteId, id)));
  });

  [...cache.keys()]
    .filter((key) => !used.has(key))
    .forEach((key) => cache.delete(key));

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
  /** Caches what the server rendered, which is never older than what's cached. */
  seed(presentations: ElementPresentation[], fallbackSiteId: unknown): void;
} {
  const consumer: Consumer = {
    query: () => toValue(query),
    elements: () => toValue(elements),
  };
  consumers.add(consumer);
  listen();

  tryOnScopeDispose(() => {
    consumers.delete(consumer);
    prune();
  });

  return {
    get: (id, siteId) =>
      cache.get(entryKey(toValue(query), siteKey(siteId), id)),
    seed: (presentations, fallbackSiteId) => {
      presentations.forEach((presentation) =>
        cache.set(
          entryKey(
            toValue(query),
            siteKey(presentation.siteId ?? fallbackSiteId),
            presentation.id
          ),
          presentation
        )
      );
    },
  };
}

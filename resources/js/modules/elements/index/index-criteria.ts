import type {ContentIndexData} from './composables/useContentIndexData';

type IndexCriteriaState = Pick<
  ContentIndexData,
  'canHaveDrafts' | 'status' | 'drafts' | 'trashed' | 'siteId'
>;

export type IndexQueryCriteria = {
  baseCriteria: Record<string, unknown>;
  criteria: Record<string, unknown>;
};

/**
 * The criteria of the query an index page lists, for the requests that
 * rebuild it on the server: exporting, and running element actions. They
 * match the page's own query (`ContentIndexViewModel::statusMenuCriteria()`),
 * so Trashed offers its actions and Drafts finds drafts of published elements.
 */
export function indexQueryCriteria(
  elementIndex: IndexCriteriaState
): IndexQueryCriteria {
  return {
    baseCriteria: {
      status: null,
      drafts: elementIndex.canHaveDrafts ? null : false,
      draftOf: false,
      savedDraftsOnly: true,
      siteId: elementIndex.siteId,
    },
    criteria: {
      status: elementIndex.status || null,
      ...(elementIndex.trashed ? {trashed: true} : {}),
      ...(elementIndex.drafts ? {drafts: true, draftOf: null} : {}),
      ...(elementIndex.status && elementIndex.canHaveDrafts
        ? {drafts: false}
        : {}),
    },
  };
}

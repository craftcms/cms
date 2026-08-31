import type {
  IndexQueryParams,
  IndexQueryValue,
} from '@/modules/elements/composables/useElementIndexVisits';

export type Criteria = IndexQueryParams;

interface BaseSource {
  key: string;
  label: string;
  criteria?: Criteria;
  defaultSort?: string[];
  sites?: number[];
  data?: Record<string, IndexQueryValue>;
  /** Set when the source is a structure; gates the structure view mode. */
  structureId?: number | null;
}

interface SourceNative extends BaseSource {
  type: 'native';
}

interface SourceCustom extends BaseSource {
  type: 'custom';
  tableAttributes?: Array<string>;
  condition?: {
    class: string;
    elementType: string;
    fieldContext: string;
  } | null;
}

/** A selectable source (i.e. anything that isn't a heading). */
export type SourceItem = SourceNative | SourceCustom;

export interface SourceHeading {
  type: 'heading';
  heading: string;
  /** Sources that fall under this heading once the list is normalized. */
  children: SourceItem[];
}

export type Source = SourceItem | SourceHeading;

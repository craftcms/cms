export type Criteria = Record<string, any>;

interface BaseSource {
  key: string;
  label: string;
  criteria?: Criteria;
  defaultSort?: string[];
  sites?: number[];
  data?: Record<string, any>;
}

interface SourceNative extends BaseSource {
  type: 'native';
}

interface SourceCustom extends BaseSource {
  type: 'custom';
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

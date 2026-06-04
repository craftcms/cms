export type Criteria = Record<string, any>;

interface SourceCustom {
  type: 'custom';
  key: string;
  label: string;
  criteria: Criteria;
  defaultSort: string[];
}

interface SourceHeading {
  key: string;
  type: 'heading';
  heading: string;
}

export type Source = SourceCustom | SourceHeading;

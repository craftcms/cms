export interface SelectOption {
  label?: string;
  value?: string;
  optgroup?: string;
  data?: Record<string, any> | null;
}

export interface Suggestion {
  name: string;
  hint: string;
}

export interface SuggestionGroup {
  label: string;
  data: Array<Suggestion>;
}

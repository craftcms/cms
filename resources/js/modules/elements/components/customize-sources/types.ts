import type {FormPayload} from '@/modules/forms/types';

export type SourceType = 'native' | 'custom' | 'heading';

export interface SourceRow {
  /**
   * Null only for a source whose project config carries no key — the server
   * can't build a Form for it, and store() can't save it either.
   */
  key: string | null;
  type: SourceType;
  /** Tracks the label/heading control so the sidebar updates as you type. */
  label: string;
  page: string;
  form: FormPayload | null;
  /** Settings are built on first select and kept, as the legacy modal did. */
  mounted: boolean;
}

export interface PageRow {
  name: string;
  icon: string | null;
}

export interface SourcesResponse {
  multiPage: boolean;
  elementTypeName: string;
  pageSettings: Record<string, {icon?: string | null}>;
  sources: Array<{
    key: string | null;
    type: SourceType;
    label: string | null;
    heading: string | null;
    page: string | null;
    form: FormPayload | null;
  }>;
}

/**
 * Mirrors ElementSources::pageNameId() — pages are compared by a normalized
 * form of their name, not the raw string.
 */
export function pageNameId(name: string): string {
  return name.replace(/[^\p{L}\p{N}\p{M}]/gu, '').toLowerCase();
}

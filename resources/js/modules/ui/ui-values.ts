import type {UiChangeKind, UiValue} from './types';

/** One mounted Craft UI. Paths are absolute within its current values. */
export interface UiValueSource {
  readonly root: HTMLElement;
  getValue(path: readonly string[]): UiValue;
}

export interface UiValueChangeDetail {
  ui: UiValueSource;
  path: string[];
  scope: string[];
  value: UiValue;
  kind: UiChangeKind;
}

const sources = new Set<UiValueSource>();

export const uiValues = {
  /** Includes only UIs currently mounted in the control panel. */
  active(): readonly UiValueSource[] {
    return [...sources];
  },
};

export function registerUiValueSource(source: UiValueSource): () => void {
  sources.add(source);

  return () => sources.delete(source);
}

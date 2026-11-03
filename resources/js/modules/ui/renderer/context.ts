import {inject, type InjectionKey, type Ref, type Slots} from 'vue';
import type {UiControlBehavior} from '../runtime';
import type {NestedUiPayload, UiControlPayload, UiValue} from '../types';

/**
 * What the nearest UiRenderer shares with the nodes and controls it renders.
 * State is read-only; changes go through the actions.
 */
export interface UiRendererContext {
  /** Replaces the whole UI with `message`, for a node that failed to render. */
  fail(message: string): void;
  /** Writes a control's value. The caller still reports the change. */
  setValue(path: string[], value: UiValue): void;
  /** Records the nested UIs a control now holds, such as a table's rows. */
  synchronizeStructure(
    control: Pick<UiControlPayload, 'path'>,
    uis: NestedUiPayload[]
  ): void;
  /** Lets a control say how its value compares for change detection. */
  registerBehavior(path: string[], behavior: UiControlBehavior): () => void;
  /** Hides the errors below `path` until new errors arrive. */
  clearChildErrors(path: string[]): void;
  childErrorsCleared(path: string[]): boolean;
  /** Slots overriding the control at a dotted path. */
  overrides: Readonly<Slots>;
  /** Whether the UI is disabled, submitting, or refreshing. */
  pending: Readonly<Ref<boolean>>;
  /** Delta groups the server reports as modified, as dotted paths. */
  modifiedGroups: Readonly<Ref<ReadonlySet<string>>>;
  /**
   * Dotted paths of every control changed since the UI was last reset. A field
   * holding nested UIs badges when one lands at or below it — see FieldNode.
   */
  changedPaths: Readonly<Ref<ReadonlySet<string>>>;
  /** Control paths, as JSON, whose changes have a refresh in flight. */
  refreshingFields: Readonly<Ref<ReadonlySet<string>>>;
}

export const UiRendererKey: InjectionKey<UiRendererContext> =
  Symbol('UiRenderer');

/** The surrounding UiRenderer, for nodes that only render inside one. */
export function useUiRenderer(): UiRendererContext {
  const context = inject(UiRendererKey, undefined);

  if (!context) {
    throw new Error('UI nodes must be rendered inside a UiRenderer.');
  }

  return context;
}

/** The surrounding UiRenderer, for controls that also render on their own. */
export function useOptionalUiRenderer(): UiRendererContext | undefined {
  return inject(UiRendererKey, undefined);
}

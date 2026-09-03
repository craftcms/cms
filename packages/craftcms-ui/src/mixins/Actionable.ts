import type {LitElement} from 'lit';
import {property, state} from 'lit/decorators.js';
import {
  type ActionFeedback,
  type BaseAction,
  type FeedbackData,
  runAction,
} from '@src/actions';
import {type AsyncState, AsyncStates} from '@src/types';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/** The action surface the mixin adds to a host element. */
export interface ActionableHost {
  /** The action to run when the host is activated. */
  action: BaseAction | null;
  feedback: ActionFeedback | null;
  feedbackDuration: number;
  /** Idle / loading / success / error for the in-flight action. */
  actionState: AsyncState;
  /** Transient message surfaced during feedback (e.g. an error). */
  feedbackMessage: string | null;
  /** Run {@link action} now, driving state + emitting `craft-state-change`. */
  triggerAction(): Promise<void>;
  setActionState(state: AsyncState, detail?: FeedbackData): void;
}

/**
 * Adds a declarative `action` to any Lit element: clicking the host runs the
 * action through {@link runAction}, tracking idle/loading/success/error state,
 * surfacing feedback, and emitting a bubbling `craft-state-change` event so an
 * ancestor (menu, toolbar) can react. This is the shared implementation behind
 * `craft-action-item` and `craft-button` — components read `actionState` /
 * `feedbackMessage` to render their own spinners/checks.
 *
 * The host's own `disabled` (if any) is respected: a disabled host swallows the
 * click without running the action.
 */
export const Actionable = <T extends Constructor<LitElement>>(Base: T) => {
  class ActionableElement extends Base {
    @property({type: Object}) action: BaseAction | null = null;
    @property({type: Object}) feedback: ActionFeedback | null = null;
    @property({type: Number, attribute: 'feedback-duration'})
    feedbackDuration: number = 1000;

    // Reflected so it can be used as a CSS styling hook
    // (e.g. `:host([action-state='loading'])`), matching the built-in
    // `loading` attribute's centered-spinner treatment.
    @property({reflect: true, attribute: 'action-state'})
    actionState: AsyncState = AsyncStates.Idle;

    @state() feedbackMessage: string | null = null;

    override connectedCallback() {
      super.connectedCallback();
      this.addEventListener('click', this.#handleActionClick);
    }

    override disconnectedCallback() {
      this.removeEventListener('click', this.#handleActionClick);
      super.disconnectedCallback();
    }

    #handleActionClick = (event: Event) => {
      if (!this.action) {
        return;
      }

      // Respect a host-provided `disabled` (craft-button, craft-action-item).
      if ((this as unknown as {disabled?: boolean}).disabled) {
        event.preventDefault();
        return;
      }

      void this.triggerAction();
    };

    setActionState(state: AsyncState, detail: FeedbackData = {}) {
      this.actionState = state;
      this.feedbackMessage = detail.message ?? null;

      this.dispatchEvent(
        new CustomEvent('craft-state-change', {
          bubbles: true,
          composed: true,
          detail: {
            state,
            actionType: this.action?.type,
            ...detail,
          },
        })
      );
    }

    async triggerAction() {
      const action = this.action;

      if (!action) {
        return;
      }

      // Loading state is only meaningful while a network request is in flight;
      // clipboard/event actions resolve synchronously.
      const showsLoading = action.type === 'http' || action.type === 'download';

      if (showsLoading) {
        this.setActionState(AsyncStates.Loading, this.feedback?.loading);
      }

      try {
        await runAction(action);
        this.setActionState(AsyncStates.Success, this.feedback?.success);
      } catch (error) {
        this.setActionState(AsyncStates.Error, {
          message: error instanceof Error ? error.message : undefined,
          ...(this.feedback?.error ?? {}),
        });
      } finally {
        setTimeout(() => {
          this.setActionState(AsyncStates.Idle);
        }, this.feedbackDuration);
      }
    }
  }

  // Cast through named types only (`ActionableHost` + `T`). Returning the
  // anonymous `ActionableElement` class directly would leak Lit's protected
  // members into consumers' declaration emit (TS4094).
  return ActionableElement as unknown as Constructor<ActionableHost> & T;
};

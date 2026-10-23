import {actionClient, t} from '@craftcms/ui';
import {nextTick, shallowRef, toValue, type MaybeRefOrGetter} from 'vue';
import {firstFocusableWithin} from '@/common/utils/dom';
import {visitControls} from './runtime';
import type {UiPayload, UiValues} from './types';

export interface UiFormResponse {
  ui: UiPayload;
  title?: string | null;
  submitLabel?: string | null;
}

/** What a form needs from the `UiRenderer` drawing it. */
export interface UiFormRenderer {
  canSubmit(): boolean;
  currentValues(): UiValues;
}

export interface UiFormOptions {
  /** Where the form's UI is fetched from. */
  modalUrl: MaybeRefOrGetter<string>;
  /** Where its values are submitted. */
  actionUrl: MaybeRefOrGetter<string>;
  actionMethod?: MaybeRefOrGetter<'post' | 'delete' | undefined>;
  /** Sent with both requests. */
  params?: MaybeRefOrGetter<UiValues | undefined>;
}

/**
 * A server-built form: fetches its UI, submits its values, and maps a failed
 * submission's errors back onto the controls that caused them.
 *
 * Shared by `UiModal`, which shows the form in a modal, and the command
 * palette, which shows it in place of its results.
 */
export function useUiForm(options: UiFormOptions) {
  const form = shallowRef<UiFormResponse | null>(null);
  const errors = shallowRef<UiPayload['errors']>([]);
  const loading = shallowRef(false);

  /** Fetches the form. False, after showing why, if it couldn't be. */
  async function load(): Promise<boolean> {
    try {
      const {data} = await actionClient.get<UiFormResponse>(
        toValue(options.modalUrl),
        {params: toValue(options.params) ?? {}}
      );
      errors.value = data.ui.errors ?? [];
      form.value = data;

      return true;
    } catch (error: any) {
      Craft.cp?.displayError?.(
        error?.response?.data?.message ?? t('A server error occurred.')
      );

      return false;
    }
  }

  /**
   * Submits the form's current values. Resolves with the response data, or
   * null if it failed — in which case the errors are shown and focus moves to
   * the first invalid control within `body`.
   */
  async function submit(
    renderer: UiFormRenderer | null,
    body: HTMLElement | null
  ): Promise<Record<string, unknown> | null> {
    if (!renderer || loading.value || !renderer.canSubmit()) {
      return null;
    }

    loading.value = true;
    errors.value = [];

    try {
      const values = {...renderer.currentValues(), ...toValue(options.params)};
      const actionUrl = toValue(options.actionUrl);
      const {data} =
        toValue(options.actionMethod) === 'delete'
          ? await actionClient.delete<Record<string, unknown>>(actionUrl, {
              data: values,
            })
          : await actionClient.post<Record<string, unknown>>(actionUrl, values);

      return data ?? {};
    } catch (error: any) {
      const responseErrors: Record<string, string | string[]> =
        error?.response?.data?.errors ?? {};
      const controlPaths: string[][] = [];
      visitControls(form.value?.ui.nodes ?? [], (control) =>
        controlPaths.push(control.path)
      );
      controlPaths.sort((a, b) => b.length - a.length);
      errors.value = Object.entries(responseErrors).map(([path, messages]) => {
        const segments = path.split('.');

        return {
          path:
            controlPaths.find((candidate) =>
              candidate.every((part, index) => segments[index] === part)
            ) ?? segments,
          messages: Array.isArray(messages) ? messages : [messages],
        };
      });
      Craft.cp?.displayError?.(
        error?.response?.data?.message ?? t('A server error occurred.')
      );

      await nextTick();
      const invalid = body?.querySelector<HTMLElement>('[aria-invalid="true"]');
      if (invalid) {
        firstFocusableWithin(invalid)?.focus();
      }

      return null;
    } finally {
      loading.value = false;
    }
  }

  return {form, errors, loading, load, submit};
}

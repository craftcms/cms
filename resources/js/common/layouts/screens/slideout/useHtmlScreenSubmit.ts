import {readonly, ref, toValue, type MaybeRefOrGetter, type Ref} from 'vue';
import {t} from '@craftcms/ui/utilities/translate';
import type {ScreenPageProps} from '@/common/composables/screen';
import {submitScreenForm} from '@/common/slideouts/submitScreenForm';
import {showMessagesFromResponse} from '@/modules/messages';

/**
 * Submits a screen rendered through `cp/Screen`, which has no Vue form — just
 * server HTML with real, namespaced inputs — so the shell posts it itself.
 *
 * Validation errors land in `errors`, for the shell's error summary; a
 * successful save goes to `onSaved`.
 */
export function useHtmlScreenSubmit({
  form,
  action,
  namespace,
  containerId,
  errors,
  onSaved,
}: {
  form: MaybeRefOrGetter<HTMLFormElement | null>;
  action: MaybeRefOrGetter<string | null>;
  namespace: MaybeRefOrGetter<string | null | undefined>;
  containerId: MaybeRefOrGetter<string | undefined>;
  errors: Ref<Record<string, string> | null>;
  onSaved: (data: ScreenPageProps | undefined) => void;
}) {
  const submitting = ref(false);

  async function submit(): Promise<void> {
    const formEl = toValue(form);
    const screenAction = toValue(action);

    if (!formEl || !screenAction) {
      return;
    }

    submitting.value = true;
    errors.value = null;

    const result = await submitScreenForm(formEl, {
      action: screenAction,
      namespace: toValue(namespace),
      containerId: toValue(containerId),
    });

    submitting.value = false;

    if (!result.ok) {
      if (result.fatal) {
        throw result.fatal;
      }

      errors.value = result.errors ?? {
        error: result.message ?? t('Couldn’t save.'),
      };

      return;
    }

    showMessagesFromResponse(result.data);

    onSaved(result.data);
  }

  return {submitting: readonly(submitting), submit};
}

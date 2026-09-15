import {computed, inject, onBeforeUnmount, watch} from 'vue';
import {actionClient, appendBodyHtml, appendHeadHtml} from '@craftcms/ui';
import ConditionsController from '@actions/ConditionsController';
import {useFetch} from '@/common/composables/useFetch';
import type {FormValues} from '@/modules/forms/types';
import {ConditionEditor, type RulePayload} from './types';

export function useConditionRuleRequest(id: string, fallbackMessage: string) {
  const editor = inject(ConditionEditor)!;
  const request = useFetch<{
    rule: RulePayload;
    headHtml: string;
    bodyHtml: string;
  }>(ConditionsController.rule().url, {
    method: 'post',
    immediate: false,
    axiosInstance: actionClient,
    transform: async (data) => {
      await appendHeadHtml(data.headHtml);
      await appendBodyHtml(data.bodyHtml);

      return data;
    },
  });

  const error = computed(() => {
    if (!request.isError.value) return;

    const failure = request.error.value;

    return typeof failure === 'object' &&
      failure !== null &&
      'message' in failure &&
      typeof failure.message === 'string'
      ? failure.message || fallbackMessage
      : fallbackMessage;
  });

  watch(
    () => !request.isLoading.value && !request.isError.value,
    (valid) => editor.status(id, valid),
    {flush: 'sync'}
  );

  onBeforeUnmount(() => {
    request.abort();
    editor.status(id, true);
  });

  async function execute(rule: FormValues): Promise<RulePayload | undefined> {
    const data = await request.execute({
      config: editor.payload().config,
      value: editor.value(),
      rule,
      editable: editor.editable(),
    });

    return data?.rule;
  }

  return {execute, isLoading: request.isLoading, error};
}

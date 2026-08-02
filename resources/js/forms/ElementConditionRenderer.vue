<script setup lang="ts">
  import {actionClient, appendBodyHtml, appendHeadHtml, t} from '@craftcms/ui';
  import {nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef} from 'vue';
  import {
    conditionsFromForm,
    type ConditionConfig,
    useConditionBuilder,
  } from '@/modules/elements/composables/useConditionBuilder';

  const props = withDefaults(
    defineProps<{
      id?: string;
      name?: string;
      conditionClass: string;
      builderConfig?: Record<string, unknown>;
      renderUrl: string;
      sortable?: boolean;
      addRuleLabel?: string;
      modelValue?: ConditionConfig | null;
      readonly?: boolean;
    }>(),
    {
      builderConfig: () => ({}),
      modelValue: null,
      readonly: false,
      sortable: true,
    }
  );
  const emit = defineEmits<{
    (event: 'update:modelValue', value: ConditionConfig | null): void;
  }>();

  const {conditions} = useConditionBuilder({initialState: props.modelValue});
  const form = useTemplateRef<HTMLFormElement>('form');
  const builder = useTemplateRef<HTMLElement>('builder');
  const builderHtml = ref('');
  const loading = ref(true);
  const error = ref<string>();
  const abortController = new AbortController();

  onMounted(renderBuilder);
  onBeforeUnmount(() => abortController.abort());

  async function renderBuilder(): Promise<void> {
    try {
      const response = await actionClient.post<string>(
        props.renderUrl,
        {
          config: JSON.stringify({
            ...props.builderConfig,
            class: props.conditionClass,
            id: props.id,
            name: 'condition',
            mainTag: 'div',
            sortable: props.sortable,
            forProjectConfig: true,
            addRuleLabel: props.addRuleLabel,
          }),
          condition: {
            ...conditions.value,
            class: conditions.value?.class ?? props.conditionClass,
            config: JSON.stringify(props.builderConfig),
            conditionRules: conditions.value?.conditionRules ?? [],
          },
        },
        {
          headers: {Accept: 'text/html', 'HX-Request': 'true'},
          signal: abortController.signal,
        }
      );
      const template = document.createElement('template');

      template.innerHTML = response.data;

      for (const head of template.content.querySelectorAll<HTMLTemplateElement>(
        'template.hx-head-html'
      )) {
        await appendHeadHtml(head.innerHTML);
        head.remove();
      }

      for (const body of template.content.querySelectorAll<HTMLTemplateElement>(
        'template.hx-body-html'
      )) {
        await appendBodyHtml(body.innerHTML);
        body.remove();
      }

      builderHtml.value = template.innerHTML;
      await nextTick();
      (window as any).htmx?.process(builder.value);
      (window as any).Craft?.initUiElements(builder.value);
    } catch (exception) {
      if (abortController.signal.aborted) {
        return;
      }

      const reason =
        exception instanceof Error ? exception.message : t('Unknown error.');

      error.value = t(
        'Element Condition option “conditionRules” could not be rendered for Form output: {reason}',
        {reason}
      );
    } finally {
      loading.value = false;
    }
  }

  function updateConditions(): void {
    if (!form.value || props.readonly) {
      return;
    }

    conditions.value = conditionsFromForm(form.value);
    emit('update:modelValue', conditions.value);
  }
</script>

<template>
  <form
    ref="form"
    @input="updateConditions"
    @change="updateConditions"
    @htmx:afterSwap="updateConditions"
    @submit.prevent
  >
    <craft-spinner v-if="loading"></craft-spinner>
    <p v-if="error" role="alert">{{ error }}</p>
    <fieldset
      v-if="builderHtml"
      class="contents"
      :disabled="readonly"
      :inert="readonly || undefined"
    >
      <div ref="builder" :id="id" v-html="builderHtml" />
    </fieldset>
  </form>
</template>

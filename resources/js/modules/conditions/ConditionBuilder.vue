<script setup lang="ts">
  import {
    computed,
    onMounted,
    onBeforeUnmount,
    provide,
    reactive,
    ref,
    shallowRef,
    shallowReactive,
    triggerRef,
    watch,
  } from 'vue';
  import {useEventListener} from '@vueuse/core';
  import {actionClient, t} from '@craftcms/ui';
  import ConditionsController from '@actions/ConditionsController';
  import {useFetch} from '@/common/composables/useFetch';
  import type {FormPayload, FormValue} from '@/modules/forms/types';
  import {canonical} from '@/modules/forms/runtime';
  import {
    ConditionEditor,
    type BuilderPayload,
    type ConditionConfig,
    type GroupConfig,
    type GroupDraft,
    type RuleConfig,
    type RulePayload,
  } from './types';
  import ConditionGroup from './ConditionGroup.vue';

  const props = withDefaults(
    defineProps<{
      payload: BuilderPayload;
      value?: ConditionConfig;
      name?: string;
      editable?: boolean;
      autofocus?: boolean;
      errors?: FormPayload['errors'];
    }>(),
    {editable: true}
  );
  const emit = defineEmits<{
    change: [value: ConditionConfig];
    valid: [valid: boolean];
  }>();

  const root = ref<HTMLElement>();
  const group = shallowRef(createDraft(props.payload));
  const rules = shallowReactive({...props.payload.rules});
  const ruleEditors = new Map<
    string,
    {snapshot: () => RulePayload; canSubmit: () => boolean}
  >();

  const invalid = reactive(new Set<string>());

  const value = computed(() =>
    conditionConfig(props.payload, group.value, rules)
  );
  const inputs = computed(() =>
    props.name && props.editable ? hiddenInputs(props.name, value.value) : []
  );
  const hostForm = computed(() => root.value?.closest('form'));

  const validation = useFetch<{valid: boolean}>(
    ConditionsController.validate().url,
    {
      method: 'post',
      immediate: false,
      axiosInstance: actionClient,
    }
  );
  const errors = computed<FormPayload['errors']>(() => {
    const response = validation.error.value as {
      errors?: Record<string, string[]>;
    } | null;

    return [
      ...(props.errors ?? []),
      ...Object.entries(response?.errors ?? {}).map(([path, messages]) => ({
        path: path.split('.'),
        messages,
      })),
    ];
  });

  onBeforeUnmount(validation.abort);

  provide(ConditionEditor, {
    payload: () => props.payload,
    rules,
    errors: () => errors.value,
    editable: () => props.editable && !validation.isLoading.value,
    changed: () => {
      triggerRef(group);
      emit('change', value.value);
    },
    status: (id, valid) => (valid ? invalid.delete(id) : invalid.add(id)),
    registerRule: (id, rule) =>
      rule ? ruleEditors.set(id, rule) : ruleEditors.delete(id),
    value: () => value.value,
  });

  watch(
    () => invalid.size === 0 && !validation.isLoading.value,
    (valid) => emit('valid', valid),
    {immediate: true}
  );

  watch(
    () => props.value,
    (incoming) => {
      if (incoming && canonical(incoming) !== canonical(value.value)) {
        group.value = createDraft({...props.payload, value: incoming});
      }
    }
  );

  watch(
    () => props.payload,
    (payload, previous) => {
      if (canonical(payload.value) !== canonical(previous.value)) {
        Object.assign(rules, payload.rules);
        group.value = createDraft(payload);

        return;
      }

      for (const [id, rule] of Object.entries(payload.rules)) {
        if (rules[id]) {
          rules[id] = {...rule, config: rules[id].config};
        }
      }
    }
  );

  useEventListener(
    hostForm,
    'submit',
    (event) => {
      if (
        invalid.size ||
        [...ruleEditors.values()].some((rule) => !rule.canSubmit())
      ) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }
    },
    {capture: true}
  );

  onMounted(() => {
    if (props.autofocus)
      root.value
        ?.querySelector<HTMLElement>('craft-action-menu craft-button')
        ?.focus();
  });

  async function validate(): Promise<boolean> {
    if (
      invalid.size ||
      validation.isLoading.value ||
      [...ruleEditors.values()].some((rule) => !rule.canSubmit())
    ) {
      return false;
    }

    const submitted = canonical(value.value);
    const response = await validation.execute({
      config: props.payload.config,
      value: value.value,
    });

    return response?.valid === true && submitted === canonical(value.value);
  }

  function isGroup(config: GroupConfig | RuleConfig): config is GroupConfig {
    return typeof config.class !== 'string' && Array.isArray(config.rules);
  }

  function createDraft(payload: BuilderPayload): GroupDraft {
    function group(config: GroupConfig): GroupDraft {
      return shallowReactive({
        kind: 'group',
        id: crypto.randomUUID(),
        operator: config.operator,
        rules: shallowReactive(
          config.rules.map((rule) => {
            if (isGroup(rule)) {
              return group(rule);
            }

            const resolved = rule.uid && payload.rules[rule.uid];

            if (!resolved) {
              throw new Error(`Missing condition rule payload: ${rule.uid}`);
            }

            return shallowReactive({
              kind: 'rule' as const,
              id: resolved.config.uid,
            });
          })
        ),
      });
    }

    const rules = payload.value.conditionRules ?? [];

    return group(Array.isArray(rules) ? {operator: 'and', rules} : rules);
  }

  function groupConfig(
    group: GroupDraft,
    rules: Record<string, RulePayload>
  ): GroupConfig {
    return {
      operator: group.operator,
      rules: group.rules.flatMap<RuleConfig | GroupConfig>((rule) => {
        if (rule.kind === 'rule') {
          return [rules[rule.id]!.config];
        }

        const config = groupConfig(rule, rules);

        return config.rules.length ? [config] : [];
      }),
    };
  }

  function conditionConfig(
    payload: BuilderPayload,
    group: GroupDraft,
    rules: Record<string, RulePayload>
  ): ConditionConfig {
    return {...payload.value, conditionRules: groupConfig(group, rules)};
  }

  /** Native form adapters submit the same portable tree as the Vue hosts. */
  function hiddenInputs(
    name: string,
    value: FormValue
  ): Array<{name: string; value: string}> {
    if (Array.isArray(value) || (value !== null && typeof value === 'object')) {
      return Object.entries(value).flatMap(([key, child]) =>
        hiddenInputs(`${name}[${key}]`, child)
      );
    }

    return [
      {
        name,
        value:
          value === true ? '1' : value === false ? '0' : String(value ?? ''),
      },
    ];
  }

  defineExpose({
    validate,
    currentValue: () => value.value,
    snapshot: (): BuilderPayload => ({
      ...props.payload,
      value: {
        ...props.payload.value,
        conditionRules: groupConfig(group.value, rules),
      },
      rules: Object.fromEntries(
        [...ruleEditors].map(([id, rule]) => [id, rule.snapshot()])
      ),
    }),
  });
</script>

<template>
  <div ref="root" class="condition-main">
    <ConditionGroup :group="group" root />

    <craft-callout
      v-if="validation.isError.value"
      role="alert"
      variant="danger"
      class="mt-2"
    >
      {{ t('Couldn’t apply the condition. Check the rules and try again.') }}
    </craft-callout>

    <input
      v-for="input in inputs"
      :key="input.name"
      type="hidden"
      :name="input.name"
      :value="input.value"
    />
  </div>
</template>

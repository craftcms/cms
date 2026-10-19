<script setup lang="ts">
  /**
   * A Table column's settings, as a slideout panel.
   *
   * Only the chrome: `TableColumnsControl` teleports the settings into it.
   */
  import {setFocusWithin} from '@craftcms/garnish';
  import {t} from '@craftcms/ui/utilities/translate';
  import {
    nextTick,
    onBeforeUnmount,
    onMounted,
    useTemplateRef,
    watch,
  } from 'vue';
  import {useForm} from '@inertiajs/vue3';
  import CpContainer from '@/common/components/CpContainer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSlideout} from '@/common/slideouts';
  import {takeColumnSettingsContext} from './columnSettingsSlideout';

  const props = defineProps<{
    contextId: string;
  }>();
  const context = takeColumnSettingsContext(props.contextId);

  const slideout = useSlideout();
  const target = useTemplateRef<HTMLElement>('target');

  /** Backs the Apply button, and the unsaved-changes prompt. */
  const form = useForm({settings: context.settings()});
  watch(context.settings, (settings) => (form.settings = settings));

  useAppLayout(() => ({
    title: context.title(),
    form,
    submitButtonLabel: t('Apply'),
    defaultFormActions: [],
    onSave: apply,
  }));

  onMounted(async () => {
    context.mount(target.value);

    // The panel placed focus before the settings were teleported in.
    await nextTick();
    if (target.value) {
      setFocusWithin(target.value);
    }
  });
  onBeforeUnmount(() => context.mount(null));

  function apply(): void {
    context.apply();
    slideout?.close({force: true});
  }
</script>

<template>
  <CpContainer class="py-lg">
    <div ref="target" class="grid gap-md"></div>
  </CpContainer>
</template>

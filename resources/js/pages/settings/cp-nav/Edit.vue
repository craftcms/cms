<script setup lang="ts">
  import {computed} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import {useForm} from '@inertiajs/vue3';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import CpContainer from '@/common/components/CpContainer.vue';
  import NavBuilder from '@/modules/cp-nav/components/NavBuilder.vue';
  import type {NavNode} from '@/modules/cp-nav/tree';

  const props = defineProps<{
    isDefault: boolean;
    tree: NavNode[];
    /** The nav with nothing customized, for resetting the builder to. */
    defaultTree: NavNode[];
    readOnly: boolean;
    saveUrl: string;
  }>();

  const form = useForm<{nav: NavNode[]}>({nav: props.tree});
  const {announce} = useAnnouncer();

  const {save} = useSettingsSave(form, () => ({
    url: props.saveUrl,
    method: 'put',
  }));

  const isCustomized = computed(
    () => JSON.stringify(form.nav) !== JSON.stringify(props.defaultTree)
  );

  useAppLayout(() => ({
    form,
    onSave: save,
    // Kept when there's nothing to reset, so it never vanishes while focused.
    formAdditionalButtons: props.readOnly
      ? []
      : [
          {
            label: t('Reset'),
            variant: ButtonVariant.Plain,
            disabled: !isCustomized.value,
            disabledReason: isCustomized.value
              ? null
              : t('Nothing has been customized.'),
            onClick: reset,
          },
        ],
  }));

  /** Only resets the builder; nothing changes until the navigation is saved. */
  function reset(): void {
    if (
      form.isDirty &&
      !window.confirm(
        t('Reset the navigation? Your unsaved changes will be lost.')
      )
    ) {
      return;
    }

    form.nav = props.defaultTree;
    announce(t('Navigation reset. Save to keep it.'));
  }
</script>

<template>
  <div class="py-3">
    <CpContainer>
      <p class="mb-lg">
        {{
          isDefault
            ? t(
                'The navigation everyone gets. User group navigations build on it.'
              )
            : t(
                'Starts from the default navigation. Only what you change here differs for this group.'
              )
        }}
      </p>

      <NavBuilder v-model="form.nav" :read-only="readOnly" />
    </CpContainer>
  </div>
</template>

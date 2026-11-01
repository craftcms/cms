<script setup lang="ts">
  import {computed} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import {useForm} from '@inertiajs/vue3';
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
    // Only resets the builder; nothing changes until the navigation is saved.
    formAdditionalButtons:
      !props.readOnly && isCustomized.value
        ? [
            {
              label: t('Reset'),
              variant: ButtonVariant.Plain,
              onClick: () => (form.nav = props.defaultTree),
            },
          ]
        : [],
  }));
</script>

<template>
  <div class="py-3">
    <CpContainer>
      <p v-if="isDefault" class="mb-lg">
        {{
          t(
            'The navigation everyone gets, unless their user group has one of its own.'
          )
        }}
      </p>

      <NavBuilder v-model="form.nav" :read-only="readOnly" />
    </CpContainer>
  </div>
</template>

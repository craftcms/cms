<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {show} from '@actions/ReleasesController';
  import CpLink from '@/common/components/CpLink.vue';
  import type {AttachedRelease} from '@/modules/elements/composables/useElementEditor';

  defineProps<{release: AttachedRelease; canEdit: boolean; editing: boolean}>();
  const emit = defineEmits<{
    'start-editing': [];
  }>();
</script>

<template>
  <craft-callout
    :variant="release.status === 'scheduled' ? 'info' : 'warning'"
    :icon="editing ? 'edit' : 'lock'"
    rounded="none"
    appearance="fill"
  >
    <template v-if="release.status === 'scheduled'">
      {{
        t(
          'This draft belongs to the scheduled release “{name}”. Saved changes will be included when the release publishes.',
          {name: release.name}
        )
      }}
    </template>
    <template v-else>
      {{
        t(
          'This draft belongs to “{name}”. Saved changes will be included when the release publishes.',
          {name: release.name}
        )
      }}
    </template>

    <div slot="action" class="flex flex-wrap gap-2">
      <CpLink
        :href="show({release: release.id}).url"
        appearance="button"
        variant="outline"
        size="small"
      >
        {{ t('View release') }}
      </CpLink>
      <craft-button
        v-if="canEdit && !editing"
        type="button"
        variant="outline"
        size="small"
        inherit
        @click="emit('start-editing')"
      >
        {{ t('Start editing') }}
      </craft-button>
    </div>
  </craft-callout>
</template>

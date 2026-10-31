<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';
  import {computed} from 'vue';
  import {show} from '@actions/ReleasesController';
  import type {AttachedRelease} from '@/modules/elements/composables/useElementEditor';

  const props = defineProps<{
    release: AttachedRelease;
    canEdit: boolean;
    editing: boolean;
  }>();
  const emit = defineEmits<{
    'start-editing': [];
  }>();

  /** The release's name, linked to its page. */
  const nameLink = computed(
    () =>
      `<a href="${escapeHtml(show({release: props.release.id}).url)}">${escapeHtml(props.release.name)}</a>`
  );
</script>

<template>
  <craft-callout
    :variant="release.status === 'scheduled' ? 'info' : 'warning'"
    :icon="editing ? 'edit' : 'lock'"
    rounded="none"
    appearance="fill"
  >
    <!-- Translated strings with the linked name as HTML. -->
    <span
      v-if="release.status === 'scheduled'"
      v-html="
        t(
          'This draft belongs to the scheduled release “{name}”. Saved changes will be included when the release publishes.',
          {name: nameLink}
        )
      "
    ></span>
    <span
      v-else
      v-html="
        t(
          'This draft belongs to “{name}”. Saved changes will be included when the release publishes.',
          {name: nameLink}
        )
      "
    ></span>

    <div v-if="canEdit && !editing" slot="action" class="flex flex-wrap gap-2">
      <craft-button
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

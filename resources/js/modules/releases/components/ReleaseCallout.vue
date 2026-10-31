<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';
  import {computed} from 'vue';
  import {show} from '@actions/ReleasesController';
  import type {AttachedRelease} from '@/modules/elements/composables/useElementEditor';

  const props = defineProps<{
    release: AttachedRelease;
    /** Whether the draft is locked until the user chooses to edit it. */
    locked: boolean;
    /** Whether to offer the Start editing button while it's locked. */
    canStartEditing: boolean;
  }>();
  const emit = defineEmits<{
    'start-editing': [];
    dismiss: [];
  }>();

  /** The release's name, linked to its page. */
  const nameLink = computed(
    () =>
      `<a href="${escapeHtml(show({release: props.release.id}).url)}">${escapeHtml(props.release.name)}</a>`
  );

  const message = computed(() => {
    const scheduled = props.release.status === 'scheduled';
    const params = {name: nameLink.value};

    if (props.release.blocking) {
      return scheduled
        ? t(
            'This draft is blocking the scheduled release “{name}”, which will fail to publish until the draft is ready.',
            params
          )
        : t(
            'This draft is blocking “{name}”, which can’t be published until the draft is ready.',
            params
          );
    }

    if (props.locked) {
      return t(
        'This draft is scheduled to publish with “{name}”. If edits leave it unready, the release will fail to publish.',
        params
      );
    }

    return scheduled
      ? t(
          'This draft belongs to the scheduled release “{name}”. Saved changes will be included when the release publishes.',
          params
        )
      : t(
          'This draft belongs to “{name}”. Saved changes will be included when the release publishes.',
          params
        );
  });

  const icon = computed(() => {
    if (props.locked) {
      return 'lock';
    }

    return props.release.blocking ? 'triangle-exclamation' : 'calendar';
  });
</script>

<template>
  <craft-callout
    :variant="release.blocking ? 'warning' : 'info'"
    :icon="icon"
    rounded="none"
    appearance="fill"
  >
    <!-- Translated strings with the linked name as HTML. -->
    <span v-html="message"></span>

    <craft-button
      v-if="locked && canStartEditing"
      slot="action"
      type="button"
      variant="outline"
      size="small"
      inherit
      @click="emit('start-editing')"
    >
      {{ t('Start editing') }}
    </craft-button>
    <craft-button
      v-else-if="!locked"
      slot="action"
      type="button"
      icon="xmark-large"
      :aria-label="t('Dismiss')"
      variant="plain"
      size="small"
      flush="inline-end"
      inherit
      @click="emit('dismiss')"
    ></craft-button>
  </craft-callout>
</template>

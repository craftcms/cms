<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';
  import {computed} from 'vue';
  import {show} from '@actions/ReleasesController';
  import type {AttachedRelease} from '@/modules/elements/composables/useElementEditor';

  const props = defineProps<{
    release: AttachedRelease;
  }>();
  const emit = defineEmits<{
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
</script>

<template>
  <craft-callout
    :variant="release.blocking ? 'warning' : 'info'"
    :icon="release.blocking ? 'triangle-exclamation' : 'calendar'"
    rounded="none"
    appearance="fill"
  >
    <!-- Translated strings with the linked name as HTML. -->
    <span v-html="message"></span>

    <craft-button
      slot="action"
      type="button"
      variant="plain"
      size="small"
      icon
      inherit
      @click="emit('dismiss')"
    >
      <craft-icon name="xmark" :label="t('Dismiss')"></craft-icon>
    </craft-button>
  </craft-callout>
</template>

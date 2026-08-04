<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import {Disclosure, DisclosureButton, DisclosurePanel} from '@headlessui/vue';
  import {computed} from 'vue';

  const props = defineProps<{
    version: string;
    date?: string | null;
    critical?: boolean;
    notes?: string | null;
  }>();

  // Format date for display
  function formatDate(date: string | null): string {
    if (!date) {
      return '';
    }
    return new Date(date).toLocaleDateString(undefined, {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  }

  // Determine if release should auto-expand
  const shouldAutoExpand = computed(() => {
    if (props.critical) {
      return true;
    }
    if (props.notes && props.notes.includes('<blockquote')) {
      return true;
    }
    return false;
  });

  // Transform release notes - adjust heading levels (h3->h4, h4->h5, h5->h6)
  const transformedNotes = computed(() => {
    if (!props.notes) {
      return '';
    }
    return props.notes.replace(
      /(<\/?h)(3|4|5)\b/g,
      (m, pre, num) => `${pre}${parseInt(num) + 1} class="h${num}"`
    );
  });

  // Whether this release has notes to display
  const hasNotes = computed(() => !!props.notes);
</script>

<template>
  <Disclosure :default-open="shouldAutoExpand" v-slot="{open}">
    <div class="release" :class="{'release--critical': critical}">
      <!-- Clickable header when notes exist -->
      <DisclosureButton v-if="hasNotes" class="release-trigger">
        <craft-icon
          :name="open ? 'chevron-down' : 'chevron-right'"
          style="font-size: 0.8em"
        ></craft-icon>
        <div class="release-info">
          <strong class="release-version">{{ version }}</strong>
          <span v-if="critical" class="release-badge">{{ t('Critical') }}</span>
          <span v-if="date" class="release-date">{{ formatDate(date) }}</span>
        </div>
      </DisclosureButton>

      <!-- Static header when no notes -->
      <div v-else class="release-header-static">
        <div class="release-info">
          <strong class="release-version">{{ version }}</strong>
          <span v-if="critical" class="release-badge">{{ t('Critical') }}</span>
          <span v-if="date" class="release-date">{{ formatDate(date) }}</span>
        </div>
      </div>

      <!-- Release notes panel -->
      <DisclosurePanel
        v-if="hasNotes"
        class="release-notes prose"
        v-html="transformedNotes"
      ></DisclosurePanel>
    </div>
  </Disclosure>
</template>

<style scoped lang="scss">
  .release {
    --_border-color: var(--c-color-neutral-border-quiet);
    --_bg-color: var(--c-color-neutral-fill-quiet);

    background-color: var(--_bg-color);
    border: 1px solid var(--_border-color);
    color: var(--c-color-neutral-on-quiet);
    border-radius: var(--c-radius-md);
    overflow: hidden;
  }

  .release--critical {
    --_border-color: var(--c-color-danger-border-quiet);
    --_bg-color: var(--c-color-danger-fill-quiet);
  }

  .release-trigger,
  .release-header-static {
    display: grid;
    grid-template-columns: 1.5rem 1fr;
    align-items: center;
    text-align: left;
    background-color: transparent;
    padding-inline: var(--c-spacing-md) var(--c-spacing-lg);
    padding-block: var(--c-spacing-md);
    width: 100%;
    border: none;
    cursor: pointer;

    &:hover {
      background-color: var(--c-color-neutral-fill-normal);
    }
  }

  .release-header-static {
    grid-template-columns: 1fr;
    cursor: default;

    &:hover {
      background-color: transparent;
    }
  }

  .release-info {
    display: flex;
    gap: var(--c-spacing-sm);
    align-items: baseline;
  }

  .release-version {
    font-size: 1.1em;
  }

  .release-badge {
    background: var(--c-color-danger-fill-loud);
    color: var(--c-color-danger-on-loud);
    padding: 0.125em 0.5em;
    border-radius: var(--c-radius-sm);
    font-size: 0.75em;
    font-weight: 600;
    text-transform: uppercase;
  }

  .release-date {
    color: var(--c-text-quiet);
    font-size: 0.875em;
  }

  .release-notes {
    padding: var(--c-spacing-md);
    border-top: 1px solid var(--_border-color);
    background-color: var(--c-surface-overlay);
  }

  .release-notes:deep(blockquote) {
    margin-inline-start: 1.5em;
    margin-block: 1em;
  }
</style>

<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import useCraftData from '@/common/composables/useCraftData';
  import {useAssistantPanel} from '@/modules/ai/assistant/assistant';
  import {useWidgetColor} from '../useWidgetColor';

  /** Links to Craft's documentation, support, GitHub, and community. */
  defineProps<{
    data: {
      links: Array<{
        url: string;
        label: string;
        icon: string;
        /** Such as `brands`, for a brand's icon. */
        iconFamily?: string;
      }>;
    };
  }>();

  const color = useWidgetColor();
  const {currentUser} = useCraftData();
  const assistant = useAssistantPanel();

  /** Whether the user can use the AI assistant, which the widget can open. */
  const assistantEnabled = computed(
    () => currentUser.value?.aiAssistant ?? false
  );
</script>

<template>
  <craft-card :data-color="color">
    <slot name="header" />
    <div class="body">
      <ul class="support-links">
        <li v-for="link in data.links" :key="link.url">
          <a
            :href="link.url"
            target="_blank"
            rel="noopener"
            class="support-link"
          >
            <craft-icon
              :name="link.icon"
              :family="link.iconFamily"
              class="support-link__icon cp-item-icon"
            ></craft-icon>
            <span class="support-link__label">{{ link.label }}</span>
            <craft-icon
              name="arrow-up-right-from-square"
              class="support-link__external"
              :label="t('Opens in a new window')"
            ></craft-icon>
          </a>
        </li>
      </ul>
      <craft-button
        v-if="assistantEnabled"
        type="button"
        icon="sparkles"
        class="support-ask-ai"
        @click="assistant.show()"
        >{{ t('Ask AI') }}</craft-button
      >
    </div>
  </craft-card>
</template>

<style scoped>
  .support-links {
    display: flex;
    flex-direction: column;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  /* Each row reaches past the list's sides by its own padding, so its hover
     fill lines its text up with the rest of the card. */
  .support-link {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    margin-inline: calc(var(--c-spacing-sm) * -1);
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-sm);
    border-radius: var(--c-radius-md);
    color: inherit;
    /* Only the label is underlined, as links are elsewhere: on hover, or
       always with the “Underline links” preference. */
    text-decoration: none;

    &:hover {
      background-color: color-mix(in oklab, currentColor 6%, transparent);
    }

    &:hover .support-link__label {
      text-decoration-line: var(--c-link-decoration-hover);
    }
  }

  .support-link__icon {
    flex: none;
  }

  .support-link__label {
    flex: 1;
    color: var(--c-text-link);
    text-decoration-line: var(--c-link-decoration);
  }

  .support-link__external {
    --c-icon-size: var(--c-size-icon-sm);
    flex: none;
    opacity: 0.5;
  }

  .support-ask-ai {
    margin-block-start: var(--c-spacing-md);
    width: 100%;
  }
</style>

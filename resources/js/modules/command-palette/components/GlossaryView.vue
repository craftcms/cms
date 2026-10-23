<script setup lang="ts">
  /**
   * A Craft CMS glossary term's definition, for the palette's glossary
   * results, with links to the docs it points to and its page on
   * craftcms.com.
   */
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import type {CommandPaletteViewProps} from '../types';

  const props = defineProps<CommandPaletteViewProps>();

  defineEmits<{
    (event: 'back'): void;
  }>();

  interface DocsLink {
    title: string;
    url: string;
  }

  /** The definition's paragraphs, which the plain-text summary separates with blank lines. */
  const paragraphs = computed(() =>
    String(props.view.definition ?? '')
      .split(/\n\s*\n/)
      .map((paragraph) => paragraph.trim())
      .filter(Boolean)
  );

  const docsLinks = computed(
    () => (props.view.docsLinks as DocsLink[] | undefined) ?? []
  );
  const url = computed(() =>
    typeof props.view.url === 'string' ? props.view.url : null
  );
</script>

<template>
  <div class="command-palette-glossary">
    <p v-for="(paragraph, index) in paragraphs" :key="index" class="m-0">
      {{ paragraph }}
    </p>

    <section v-if="docsLinks.length" class="command-palette-glossary__docs">
      <h3 class="command-palette-glossary__heading">
        {{ t('Documentation') }}
      </h3>
      <ul class="command-palette-glossary__links">
        <li v-for="link in docsLinks" :key="link.url">
          <a :href="link.url" target="_blank" rel="noopener">
            {{ link.title }}
            <craft-icon
              name="arrow-up-right-from-square"
              aria-hidden="true"
            ></craft-icon>
            <span class="sr-only">{{ t('Opens in a new window') }}</span>
          </a>
        </li>
      </ul>
    </section>

    <p v-if="url" class="m-0">
      <a :href="url" target="_blank" rel="noopener">
        {{ t('View on craftcms.com') }}
        <craft-icon
          name="arrow-up-right-from-square"
          aria-hidden="true"
        ></craft-icon>
        <span class="sr-only">{{ t('Opens in a new window') }}</span>
      </a>
    </p>
  </div>
</template>

<style scoped>
  .command-palette-glossary {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  .command-palette-glossary__heading {
    margin-block: 0 var(--c-spacing-xs);
    margin-inline: 0;
    font-size: var(--c-text-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--c-text-quiet);
  }

  .command-palette-glossary__links {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xs);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .command-palette-glossary craft-icon {
    margin-inline-start: var(--c-spacing-xs);
    vertical-align: -0.125em;
  }
</style>

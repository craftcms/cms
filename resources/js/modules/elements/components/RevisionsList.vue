<script setup lang="ts">
  /**
   * The drafts-and-revisions list in the editor's Revisions tab, grouped by
   * `revisionSections()`. Its "View all revisions" footer goes in the panel's
   * header instead, via `ElementRevisionsActions`.
   */
  import {computed} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import {t} from '@craftcms/ui';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
  import {revisionSections} from '@/modules/elements/revision-sections';

  const props = defineProps<{
    payload: ElementEditPayload;
  }>();

  const sections = computed(() => revisionSections(props.payload));

  const isEmpty = computed(() => sections.value.groups.length === 0);
</script>

<template>
  <div class="py-lg px-(--cp-container-padding)">
    <p v-if="isEmpty" class="text-sm text-neutral-text-quiet">
      {{ t('No drafts or revisions.') }}
    </p>

    <div v-else class="grid gap-3">
      <div
        v-for="group in sections.groups"
        :key="group.id"
        class="revision-group"
      >
        <h3 v-if="group.label" class="mb-1 text-xs font-bold">
          {{ group.label }}
        </h3>
        <ul class="grid gap-2">
          <li
            class="revision-item"
            v-for="item in group.items"
            :key="item.id"
            :active="item.active || null"
            :aria-current="item.active ? 'true' : undefined"
          >
            <craft-icon
              v-show="item.active"
              name="check"
              class="revision-item__icon"
            ></craft-icon>
            <div class="revision-item__label">
              <!-- The one you're looking at isn't worth a link back to itself. -->
              <CpLink v-if="item.href && !item.active" :href="item.href">
                {{ item.label }}
              </CpLink>
              <span v-else>{{ item.label }}</span>
            </div>
            <div v-if="item.description" class="revision-item__description">
              {{ item.description }}
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .revision-group {
    padding-inline-start: calc(var(--c-spacing) * 6);
  }

  .revision-item {
    position: relative;
  }

  .revision-item__icon {
    position: absolute;
    inset-inline-start: calc(var(--c-spacing) * -6);
    inset-block-start: calc(1lh / 4);
  }

  .revision-item__label {
    font-weight: bold;
  }

  .revision-item__description {
    font-size: var(--c-text-sm);
  }
</style>

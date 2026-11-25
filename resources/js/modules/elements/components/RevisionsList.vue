<script setup lang="ts">
  /**
   * The drafts-and-revisions list in the editor's Versions panel, grouped by
   * `revisionSections()`. Its "View all revisions" footer goes beside the
   * revisions' heading instead, as "View all".
   */
  import '@craftcms/ui/components/tooltip/tooltip';
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed, useId} from 'vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
  import {revisionSections} from '@/modules/elements/revision-sections';

  const props = defineProps<{
    payload: ElementEditPayload;
  }>();

  const sections = computed(() => revisionSections(props.payload));

  const isEmpty = computed(() => sections.value.groups.length === 0);

  /**
   * The link to every revision, which the server adds when it sent only some.
   * The revisions are the last group whenever it does.
   */
  const viewAllHref = computed(
    () => sections.value.footer.find((item) => item.href)?.href ?? null
  );

  const idPrefix = useId();

  function compareId(groupIndex: number, itemIndex: number): string {
    return `${idPrefix}-compare-${groupIndex}-${itemIndex}`;
  }
</script>

<template>
  <div class="py-lg px-(--cp-container-padding)">
    <p v-if="isEmpty" class="text-sm">
      {{ t('No drafts or revisions.') }}
    </p>

    <div v-else class="grid gap-3">
      <template v-for="(group, index) in sections.groups" :key="group.id">
        <hr v-if="index > 0" />
        <div class="revision-group">
          <div v-if="group.label" class="revision-group__heading">
            <h3 class="cp-h6">
              {{ group.label }}
            </h3>
            <CpLink
              v-if="viewAllHref && index === sections.groups.length - 1"
              :href="viewAllHref"
              class="text-xs"
            >
              {{ t('View all') }}
            </CpLink>
          </div>
          <ul class="grid gap-2">
            <li
              class="revision-item"
              v-for="(item, itemIndex) in group.items"
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
              <template v-if="item.compareHref">
                <CpButtonLink
                  :id="compareId(index, itemIndex)"
                  :href="item.compareHref"
                  class="revision-item__compare"
                  icon=""
                  size="small"
                  flush="inline-end"
                  :variant="ButtonVariant.Plain"
                >
                  <craft-icon
                    name="code-compare"
                    class="revision-item__compare-icon"
                    :label="`${t('Compare changes')} ${item.label}`"
                  ></craft-icon>
                </CpButtonLink>
                <craft-tooltip :for="compareId(index, itemIndex)">{{
                  t('Compare changes')
                }}</craft-tooltip>
              </template>
            </li>
          </ul>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .revision-group {
    padding-inline-start: calc(var(--c-spacing) * 6);
  }

  .revision-group__heading {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: var(--c-spacing-sm);
    margin-block-end: var(--c-spacing-xs);
  }

  .revision-item {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    column-gap: var(--c-spacing-sm);
  }

  .revision-item__label,
  .revision-item__description {
    grid-column: 1;
  }

  .revision-item__compare {
    grid-column: 2;
    grid-row: 1 / span 2;
    align-self: center;
  }

  .revision-item__compare-icon {
    color: var(--c-field-modified-indicator);
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

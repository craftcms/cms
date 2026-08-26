<script setup lang="ts">
  /**
   * The drafts-and-revisions list in the editor's Revisions tab.
   *
   * Fed by the same payload as the breadcrumb switcher (`ElementContextMenu`):
   * a flat list where `heading` rows stand in for the nesting the action menu
   * has no shape for. Here there's room for real groups, so the flat list is
   * folded back into them — a leading unlabeled group for "Current", then one
   * per heading. Anything past the `hr` is the "View all revisions" footer.
   */
  import {computed} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import {t} from '@craftcms/ui';
  import type {ElementContextMenuItem} from '@/modules/elements/composables/useElementEditor';

  const props = defineProps<{
    /** `payload.contextMenu.items`, or an empty list when the element has none. */
    items: Array<ElementContextMenuItem>;
  }>();

  interface RevisionItem {
    id: string | number;
    label: string;
    description?: string;
    active?: boolean;
    href?: string;
  }

  interface RevisionGroup {
    id: string;
    label?: string;
    items: Array<RevisionItem>;
  }

  function toItem(item: ElementContextMenuItem, index: number): RevisionItem {
    return {
      // Hrefs are unique per draft/revision; the index only covers the
      // theoretical item with no href.
      id: item.href ?? `item-${index}`,
      label: item.label ?? '',
      description: item.description,
      href: item.href,
      active: item.selected,
    };
  }

  const sections = computed(() => {
    const groups: Array<RevisionGroup> = [];
    const footer: Array<RevisionItem> = [];
    let group: RevisionGroup | null = null;
    let inFooter = false;

    props.items.forEach((item, index) => {
      // The rule separates the list proper from the "View all revisions" link
      // the server appends when there are more revisions than it sent.
      if (item.type === 'hr') {
        inFooter = true;
        group = null;

        return;
      }

      if (item.type === 'heading') {
        group = {id: `group-${index}`, label: item.label, items: []};
        groups.push(group);

        return;
      }

      if (inFooter) {
        footer.push(toItem(item, index));

        return;
      }

      // "Current" arrives before any heading.
      if (!group) {
        group = {id: `group-${index}`, items: []};
        groups.push(group);
      }

      group.items.push(toItem(item, index));
    });

    return {groups, footer};
  });

  const isEmpty = computed(() => sections.value.groups.length === 0);
</script>

<template>
  <p v-if="isEmpty" class="text-sm text-text-quiet">
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

  <div v-for="item in sections.footer" :key="item.id" class="ml-6 mt-4">
    <CpLink :href="item.href!">
      {{ item.label }}
      <craft-icon name="circle-arrow-right"></craft-icon>
    </CpLink>
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

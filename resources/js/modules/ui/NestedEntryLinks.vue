<script setup lang="ts">
  /**
   * Bézier lines between the nested elements two versions of a field share,
   * such as Matrix entries, so a reordered entry can be followed from one side
   * to the other. They stay faint until the element on either end is hovered
   * or focused. Lays itself over its positioned parent, which holds both.
   */
  import {t} from '@craftcms/ui';
  import {
    useEventListener,
    useMutationObserver,
    useResizeObserver,
  } from '@vueuse/core';
  import {computed, onMounted, shallowRef, useTemplateRef} from 'vue';
  import {
    nestedEntryLinks,
    type NestedEntryChanges,
    type NestedEntryLink,
    type NestedEntryScope,
  } from './nestedEntryLinks';

  const props = defineProps<{
    /** The versions of each field to connect, read whenever the layout changes. */
    scopes: () => NestedEntryScope[];
  }>();

  const svg = useTemplateRef<SVGSVGElement>('svg');
  const links = shallowRef<NestedEntryLink[]>([]);
  const changes = shallowRef<NestedEntryChanges>({
    moved: 0,
    added: 0,
    removed: 0,
  });
  let frame = 0;

  const parent = () => svg.value?.parentElement ?? null;
  let generation = 0;

  /** What the pointer is over, or what has focus, within the parent. */
  const active = shallowRef<Element | null>(null);

  function isActive(link: NestedEntryLink): boolean {
    const target = active.value;

    return target !== null && link.items.some((item) => item.contains(target));
  }
  /** The links, with highlighted ones last so they're drawn over the rest. */
  const orderedLinks = computed(() => [
    ...links.value.filter((link) => !isActive(link)),
    ...links.value.filter(isActive),
  ]);

  useEventListener(parent, 'pointerover', (event: PointerEvent) => {
    active.value = event.target as Element;
  });
  useEventListener(parent, 'pointerleave', () => (active.value = null));
  useEventListener(parent, 'focusin', (event: FocusEvent) => {
    active.value = event.target as Element;
  });
  useEventListener(parent, 'focusout', () => (active.value = null));

  /**
   * Redraws once the chips have rendered: a chip is drawn from its own box,
   * which it only has once it's rendered, and rendering doesn't change the
   * layout around it, so nothing else would ask for a redraw.
   */
  function update(): void {
    const current = ++generation;
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(async () => {
      await Promise.all(
        [...(parent()?.querySelectorAll('craft-chip') ?? [])].map(
          (chip) =>
            (chip as HTMLElement & {updateComplete?: Promise<unknown>})
              .updateComplete
        )
      );

      if (!svg.value || current !== generation) {
        return;
      }

      ({links: links.value, changes: changes.value} = nestedEntryLinks(
        svg.value.getBoundingClientRect(),
        props.scopes()
      ));
    });
  }

  onMounted(() => {
    update();
    void customElements.whenDefined('craft-chip').then(update);
  });
  useResizeObserver(parent, update);
  useEventListener(window, 'resize', update);
  useMutationObserver(
    parent,
    (records) => {
      if (records.some((record) => !svg.value?.contains(record.target))) {
        update();
      }
    },
    {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['style', 'data-collapsed'],
    }
  );

  defineExpose({update});

  /** What the lines show, for screen readers. */
  const description = computed(() =>
    [
      changes.value.moved
        ? t('{count, plural, =1{# item moved.} other{# items moved.}}', {
            count: changes.value.moved,
          })
        : '',
      changes.value.added
        ? t('{count, plural, =1{# item added.} other{# items added.}}', {
            count: changes.value.added,
          })
        : '',
      changes.value.removed
        ? t('{count, plural, =1{# item removed.} other{# items removed.}}', {
            count: changes.value.removed,
          })
        : '',
    ]
      .filter(Boolean)
      .join(' ')
  );
</script>

<template>
  <svg ref="svg" class="nested-entry-links" aria-hidden="true">
    <g
      v-for="link in orderedLinks"
      :key="link.path"
      :class="{'nested-entry-links__active': isActive(link)}"
    >
      <path :d="link.path" />
      <circle
        v-for="([cx, cy], end) in link.ends"
        :key="end"
        :cx="cx"
        :cy="cy"
        r="3"
      />
    </g>
  </svg>
  <p v-if="description" class="sr-only">{{ description }}</p>
</template>

<style scoped>
  .nested-entry-links {
    position: absolute;
    inset: 0;
    inline-size: 100%;
    block-size: 100%;
    overflow: visible;
    pointer-events: none;
    z-index: 1;
  }

  .nested-entry-links g {
    --link-color: color-mix(in srgb, currentcolor 20%, transparent);
  }

  .nested-entry-links g.nested-entry-links__active {
    --link-color: var(--c-field-modified-indicator, #7c3cf4);
  }

  .nested-entry-links path {
    fill: none;
    stroke: var(--link-color);
    stroke-width: 1.5;
  }

  .nested-entry-links .nested-entry-links__active path {
    stroke-width: 2;
  }

  .nested-entry-links circle {
    fill: var(--link-color);
  }

  @media (forced-colors: active) {
    .nested-entry-links g {
      --link-color: GrayText;
    }

    .nested-entry-links g.nested-entry-links__active {
      --link-color: Highlight;
    }
  }
</style>

<script setup lang="ts">
import { ref } from "vue";
import DynamicHtmlRenderer from "@/common/components/DynamicHtmlRenderer.vue";
import ChipListDemo from "./ChipListDemo.vue";
import CpContainer from "@/common/components/CpContainer.vue";

defineProps<{
  lists: Record<string, Array<{ id: number; label: string }>>;
  sections: Array<{ title: string; description: string; html: string }>;
}>();

const variants = [
  { label: "Stacked", props: {} },
  { label: "Inline", props: { inline: true } },
  { label: "Selectable", props: { selectable: true } },
  { label: "Sortable", props: { sortable: true } },
];

// Narrowing the page is the quickest way to see how each chip truncates.
const width = ref(720);
</script>

<template>
  <CpContainer>
    <div class="pane mb-xl flex items-center gap-md">
      <label for="chip-width" class="font-bold">Container width</label>
      <input id="chip-width" v-model.number="width" type="range" min="120" max="1200" step="10" />
      <span>{{ width }}px</span>
    </div>

    <div :style="{ maxWidth: `${width}px` }" class="border border-quiet">
      <p v-if="!Object.keys(lists).length && !sections.length">
        There are no entries, users, or assets to render chips for.
      </p>

      <article v-for="(elements, name) in lists" :key="name" class="mb-xl">
        <h2 class="text-lg">{{ name }} relation field</h2>
        <p class="text-sm mb-md"><code>ElementChips</code>, as a relation field renders it.</p>
        <div class="pane grid gap-lg">
          <section v-for="variant in variants" :key="variant.label">
            <h3 class="mb-sm">{{ variant.label }}</h3>
            <ChipListDemo :elements="elements" v-bind="variant.props" />
          </section>
        </div>
      </article>

      <article v-for="section in sections" :key="section.title" class="mb-xl">
        <h2 class="text-lg">{{ section.title }}</h2>
        <p class="text-sm mb-md">{{ section.description }}</p>
        <div class="pane">
          <DynamicHtmlRenderer :html="section.html" />
        </div>
      </article>
    </div>
  </CpContainer>
</template>

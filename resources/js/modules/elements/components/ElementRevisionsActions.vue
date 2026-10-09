<script setup lang="ts">
  import {computed} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
  import {revisionSections} from '@/modules/elements/revision-sections';

  // The panel's own props come through as well; none of them belong on the link.
  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    payload: ElementEditPayload;
  }>();

  /** The "View all revisions" link the server adds when it sent only some. */
  const links = computed(() =>
    revisionSections(props.payload).footer.filter((item) => item.href)
  );
</script>

<template>
  <CpLink
    v-for="item in links"
    :key="item.id"
    :href="item.href!"
    class="text-sm me-md"
  >
    {{ item.label }}
    <craft-icon name="circle-arrow-right" />
  </CpLink>
</template>

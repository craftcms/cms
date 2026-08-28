<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {ref} from 'vue';
  import VarDump from '@/common/components/VarDump.vue';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';

  defineProps<{
    data: any;
  }>();

  const {announcement} = useAnnouncer();
  const open = ref(false);
</script>

<template>
  <div
    class="cp:fixed cp:bottom-2 cp:right-2 cp:flex cp:gap-2 cp:justify-end cp:items-center cp:p-2"
  >
    <div
      class="cp:bg-blue-50 cp:border cp:border-blue-500 cp:py-1 cp:px-4 cp:rounded"
    >
      {{ announcement ?? 'No announcement' }}
    </div>

    <div>
      <VarDump
        v-if="open"
        :data="data"
        class="max-h-[50vh] max-w-[600px] cp:overflow-scroll cp:absolute cp:transform cp:-translate-full"
      />
      <craft-button v-if="open" icon type="button" @click="open = false">
        <craft-icon :label="t('Close Debug panel')" name="x"></craft-icon>
      </craft-button>
      <craft-button v-else type="button" @click="open = true" icon>
        <craft-icon name="code" :label="t('Show debug variables')"></craft-icon>
      </craft-button>
    </div>
  </div>
</template>

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
  <div class="fixed bottom-2 right-2 flex gap-2 justify-end items-center p-2">
    <div class="bg-blue-50 border border-blue-500 py-1 px-4 rounded">
      {{ announcement ?? 'No announcement' }}
    </div>

    <div>
      <VarDump
        v-if="open"
        :data="data"
        class="max-h-[50vh] max-w-[600px] overflow-scroll absolute transform -translate-full"
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

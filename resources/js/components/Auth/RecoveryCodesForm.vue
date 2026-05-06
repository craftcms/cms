<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {Form} from '@inertiajs/vue3';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';

  defineProps<{
    action: string;
    returnUrl?: string;
  }>();
</script>

<template>
  <Form :action="action" #default="{processing, errors}" method="post">
    <div class="flex gap-2 items-end">
      <input
        type="hidden"
        name="redirect"
        :value="returnUrl"
        v-if="returnUrl"
      />
      <CraftInput
        :label="t('Recovery Code')"
        id="recovery-code"
        name="code"
        class="w-full"
        :error="errors.code"
      />

      <craft-button type="submit" :loading="processing" variant="primary">
        {{ t('Verify') }}
      </craft-button>
    </div>
  </Form>
</template>

<style scoped lang="scss"></style>

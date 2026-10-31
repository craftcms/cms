<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import AuthBase from '@/common/layouts/AuthBase.vue';

  defineProps<{
    email: string;
  }>();
</script>

<template>
  <AuthBase :title="t('Verify Email')">
    <craft-pane>
      <craft-callout variant="danger">
        {{ t('The email address {email} is already in use.', {email}) }}
      </craft-callout>
    </craft-pane>
  </AuthBase>
</template>

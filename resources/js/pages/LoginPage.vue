<script setup lang="ts">
  import {usePage} from '@inertiajs/vue3';
  import useCraftData from '@/composables/useCraftData';
  import AuthBase from '@/layout/AuthBase.vue';

  defineProps<{
    logo?: string;
    errors?: Record<string, string[]>;
    authFormData?: Record<string, string>;
  }>();
  const page = usePage<{
    username?: string;
  }>();
  const {general} = useCraftData();
</script>

<template>
  <AuthBase>
    <craft-login-form
      show-reset-password
      show-remember-me
      :username="page.props.username"
      :use-email-as-username="general.useEmailAsUsername"
    >
    </craft-login-form>
  </AuthBase>
</template>

<style scoped lang="scss"></style>

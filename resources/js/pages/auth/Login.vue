<script setup lang="ts">
  import {usePage} from '@inertiajs/vue3';
  import useCraftData from '@/common/composables/useCraftData';
  import AuthBase from '@/common/layouts/AuthBase.vue';
  import {attemptLogin} from '@actions/Auth/LoginController';
  import '@/modules/auth/components/login/login-form.js';

  defineProps<{
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
      :action="attemptLogin().url"
      show-reset-password
      show-remember-me
      :username="page.props.username"
      :use-email-as-username="general.useEmailAsUsername ? '' : null"
    >
    </craft-login-form>
  </AuthBase>
</template>

<style scoped lang="scss"></style>

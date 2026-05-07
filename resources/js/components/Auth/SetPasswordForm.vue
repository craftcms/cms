<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import {Form, usePage} from '@inertiajs/vue3';
  import PasswordController from '@actions/Users/PasswordController';
  import FlashMessages from '@/components/FlashMessages.vue';

  const emit = defineEmits<{
    (e: 'change:view', view: 'login' | 'set-password'): void;
  }>();
  defineProps<{
    usernameProps: {
      label: string;
      type: string;
    };
  }>();

  const page = usePage<{
    errors?: {
      loginName?: string;
      password?: string;
      rememberMe?: string;
    } | null;
    flash: {
      success: string;
      error: string;
    };
  }>();
</script>

<template>
  <Form
    :action="
      PasswordController.sendPasswordResetEmail[
        '/actions/users/send-password-reset-email'
      ]()
    "
    :reset-on-success="['loginName']"
    method="post"
    #default="{processing, errors}"
  >
    <div class="grid gap-3">
      <FlashMessages />
      <CraftInput
        :label="usernameProps.label"
        :type="usernameProps.type"
        name="loginName"
        autocomplete="username"
        :autocapitalize="false"
        :required="true"
        :error="errors?.loginName"
      />
    </div>

    <div class="mt-4">
      <craft-button
        type="submit"
        variant="primary"
        :loading="processing"
        class="w-full"
        >{{ t('Reset password') }}</craft-button
      >
    </div>
  </Form>

  <hr class="my-4" />
  <craft-button
    type="button"
    appearance="none"
    @click="emit('change:view', 'login')"
  >
    <craft-icon name="arrow-left" slot="prefix"></craft-icon>
    {{ t('Back to sign in') }}
  </craft-button>
</template>

<style scoped lang="scss"></style>

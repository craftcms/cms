<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftInputPassword from '@craftcms/ui/vue/CraftInputPassword.vue';
  import {usePage} from '@inertiajs/vue3';

  withDefaults(
    defineProps<{
      errors?: {
        email?: string;
        username?: string;
        password?: string;
      };
    }>(),
    {
      errors: () => ({
        email: '',
        username: '',
        password: '',
      }),
    }
  );

  const page = usePage<{
    useEmailAsUsername: boolean;
  }>();
  const showUsername = computed(() => !page.props.useEmailAsUsername);

  const username = defineModel<string>('username', {default: ''});
  const email = defineModel<string>('email', {default: ''});
  const password = defineModel<string>('password', {default: ''});
</script>

<template>
  <CraftInput
    v-if="showUsername"
    :label="t('Username')"
    id="account-username"
    name="username"
    v-model="username"
    :error="errors?.username"
    maxlength="255"
    required
  />
  <CraftInput
    :label="t('Email')"
    id="account-email"
    name="email"
    v-model="email"
    maxlength="255"
    autocomplete="email"
    :error="errors?.email"
    required
    type="email"
  />
  <CraftInputPassword
    :label="t('Password')"
    id="account-password"
    name="password"
    v-model="password"
    :error="errors?.password"
    required
    autocomplete="new-password"
  />
</template>

<style scoped lang="scss"></style>

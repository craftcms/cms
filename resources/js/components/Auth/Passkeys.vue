<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {
    startAuthentication,
    platformAuthenticatorIsAvailable,
  } from '@simplewebauthn/browser';
  import {useHttp} from '@inertiajs/vue3';
  import PasskeyController from '@actions/Auth/PasskeyController';
  import {onMounted, ref} from 'vue';
  import type {LoginResponse} from '@/types/auth';

  interface LoginBody {
    requestOptions: string | null;
    authResponse: string | null;
  }

  const emit = defineEmits<{
    (e: 'success', response: LoginResponse): void;
    (e: 'error', error: any): void;
  }>()
  const requestOptions = useHttp<any, {options: string}>();
  const login = useHttp<LoginBody, LoginResponse>({
    requestOptions: null,
    authResponse: null,
  });
  const state = ref<'idle' | 'loading' | 'success' | 'error' | 'initializing'>(
    'initializing'
  );

  onMounted(async () => {
    if (state.value !== 'initializing') {
      return;
    }

    try {
      await platformAuthenticatorIsAvailable();
      state.value = 'idle';
    } catch (error) {
      // fail silently
      console.error(error);
    }
  });

  async function loginWithPasskey() {
    if (state.value === 'loading') {
      return;
    }

    state.value = 'loading';

    try {
      const {options} = await requestOptions.post(
        PasskeyController.requestOptions[
          '/actions/auth/passkey-request-options'
        ]().url
      );
      const authResponse = await startAuthentication({
        optionsJSON: JSON.parse(options),
      });
      login.requestOptions = options;
      login.authResponse = JSON.stringify(authResponse);

      const loginResponse = await login.post(
        PasskeyController.login['/actions/users/login-with-passkey']().url
      );

      state.value = 'success';
      console.log(loginResponse);
      emit('success', loginResponse);
    } catch (error) {
      state.value = 'error';
      emit('error', error);
    } finally {
      state.value = 'idle';
    }
  }
</script>

<template>
  <craft-button
    v-if="state !== 'initializing'"
    type="button"
    @click="loginWithPasskey()"
    :loading="state === 'loading'"
    >{{ t('Sign in with a passkey') }}</craft-button
  >
</template>

<style scoped lang="scss"></style>

<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import Passkeys from '@/components/Auth/Passkeys.vue';
  import SetPasswordForm from '@/components/Auth/SetPasswordForm.vue';
  import LoginForm from '@/components/Auth/LoginForm.vue';
  import TotpForm from '@/components/Auth/TotpForm.vue';
  import RecoveryCodesForm from '@/components/Auth/RecoveryCodesForm.vue';
  import {computed, ref} from 'vue';
  import {useUrlSearchParams} from '@vueuse/core';
  import useCraftData from '@/composables/useCraftData';
  import {browserSupportsWebAuthn} from '@simplewebauthn/browser';
  import {router, usePage} from '@inertiajs/vue3';
  import type {LoginResponse} from '@/types/auth';

  type View = 'credentials' | 'two-factor' | 'set-password';
  type AuthMethod = {name: string; handle: string};

  const props = withDefaults(
    defineProps<{
      context?: 'modal' | 'page';
      showPasskeyBtn?: boolean;
    }>(),
    {showPasskeyBtn: true}
  );

  const emit = defineEmits<{
    (e: 'confirmed', expiresAt: number | false): void;
  }>();

  const page = usePage<{username?: string}>();
  const params = useUrlSearchParams();
  const {general} = useCraftData();

  const localView = ref<View>('credentials');
  console.log(lo);
  const currentMethodHandle = ref<string>('');
  const encryptedReturnUrl = ref<string>('');
  const otherMethods = ref<AuthMethod[]>([]);

  const view = computed<View>({
    get() {
      if (props.context === 'page') {
        return ((params.view as View) || 'credentials');
      }
      return localView.value;
    },
    set(value: View) {
      if (props.context === 'page') {
        params.view = value;
      } else {
        localView.value = value;
      }
    },
  });

  const usernameProps = computed(() => {
    if (general.useEmailAsUsername) {
      return {label: t('Email'), type: 'email'};
    }
    return {label: t('Username or Email'), type: 'text'};
  });

  const showPasskeys = computed(
    () => props.showPasskeyBtn && browserSupportsWebAuthn()
  );

  const initialUsername = computed(() => page.props.username ?? '');

  function setView(value: View) {
    if (props.context === 'page') {
      router.reload({
        only: ['flash'],
        onSuccess: () => {
          view.value = value;
        },
      });
    } else {
      view.value = value;
    }
  }

  function handleCredentialSuccess(response: LoginResponse) {
    if (response.requiresTwoFactor && response.authMethodHandle) {
      currentMethodHandle.value = response.authMethodHandle;
      encryptedReturnUrl.value = response.encryptedReturnUrl ?? '';
      otherMethods.value = response.otherMethods ?? [];
      setView('two-factor');
      return;
    }
    handleAuthComplete(response);
  }

  function handleAuthComplete(response: LoginResponse) {
    if (props.context === 'modal') {
      emit('confirmed', response.elevatedSessionExpiresAt ?? false);
    } else {
      window.location.href = response.returnUrl ?? '/';
    }
  }

  function switchMethod(handle: string) {
    currentMethodHandle.value = handle;
  }
</script>

<template>
  <template v-if="view === 'credentials'">
    <div class="grid gap-3">
      <LoginForm
        :show-username="context !== 'modal'"
        :show-password-reset="context === 'page'"
        :show-remember-checkbox="context === 'page'"
        :username-props="usernameProps"
        :initial-username="initialUsername"
        @success="handleCredentialSuccess"
        @change:view="setView('set-password')"
      />
      <Passkeys
        v-if="showPasskeys"
        @success="handleAuthComplete"
      />
    </div>
  </template>

  <template v-else-if="view === 'two-factor'">
    <div class="grid gap-3">
      <TotpForm
        v-if="currentMethodHandle === 'totp'"
        :inline="true"
        :return-url="encryptedReturnUrl"
        @success="handleAuthComplete"
      />
      <RecoveryCodesForm
        v-else-if="currentMethodHandle === 'recovery-codes'"
        :inline="true"
        :return-url="encryptedReturnUrl"
        @success="handleAuthComplete"
      />
      <div v-if="otherMethods.length > 0" class="text-center">
        <template
          v-for="method in otherMethods"
          :key="method.handle"
        >
          <craft-button
            v-if="method.handle !== currentMethodHandle"
            type="button"
            appearance="none"
            @click="switchMethod(method.handle)"
            >{{ t('Use {method} instead', {method: method.name}) }}</craft-button
          >
        </template>
      </div>
    </div>
  </template>

  <SetPasswordForm
    v-else-if="view === 'set-password'"
    :username-props="usernameProps"
    @change:view="setView('credentials')"
  />
</template>

<style scoped lang="scss"></style>

<script setup lang="ts">
  /**
   * The session timeout prompts: a warning shortly before the session expires,
   * and a sign-in form once it has, so work on the page isn't lost.
   */
  import {actionClient, ConfigService, t} from '@craftcms/ui';
  import {logout} from '@routes/cp';
  import {useIntervalFn} from '@vueuse/core';
  import {computed, nextTick, shallowRef, useTemplateRef, watch} from 'vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import Modal from '@/common/components/Modal.vue';
  import '@/modules/auth/components/login/login-form.js';
  import type CraftLoginForm from '@/modules/auth/components/login/login-form';
  import {sessionTimeoutManager} from './manager';

  const {state} = sessionTimeoutManager;
  const config = ConfigService.getInstance();
  const loginName = String(
    config.get('userEmail') ?? config.get('username') ?? ''
  );
  const now = shallowRef(Date.now());
  const renewing = shallowRef(false);
  const alternativeLoginMethods =
    shallowRef<CraftCms.Cms.View.HtmlFragment | null>(null);
  const logoutForm = useTemplateRef<HTMLFormElement>('logoutForm');
  const loginForm = useTemplateRef<CraftLoginForm | null>('loginForm');

  const {pause, resume} = useIntervalFn(() => (now.value = Date.now()), 1000, {
    immediate: false,
  });

  const remaining = computed(() =>
    state.expiresAt === null
      ? 0
      : Math.max(0, Math.round(state.expiresAt - now.value / 1000))
  );

  /** “1 minute, 30 seconds”, in the user's language. */
  function duration(seconds: number): string {
    const locale = document.documentElement.lang || undefined;
    const unit = (value: number, name: 'minute' | 'second') =>
      new Intl.NumberFormat(locale, {
        style: 'unit',
        unit: name,
        unitDisplay: 'long',
      }).format(value);
    const minutes = Math.floor(seconds / 60);
    const parts = [
      ...(minutes ? [unit(minutes, 'minute')] : []),
      ...(seconds % 60 || !minutes ? [unit(seconds % 60, 'second')] : []),
    ];

    return new Intl.ListFormat(locale, {type: 'unit'}).format(parts);
  }

  watch(
    () => state.phase,
    async (phase) => {
      if (phase === 'warning') {
        now.value = Date.now();
        resume();
      } else {
        pause();
      }

      if (phase === 'expired') {
        alternativeLoginMethods.value = null;
        actionClient
          .get<{
            alternativeLoginMethods: CraftCms.Cms.View.HtmlFragment | null;
          }>('users/login-methods', {params: {loginName}})
          .then(({data}) => {
            alternativeLoginMethods.value = data.alternativeLoginMethods;
          })
          .catch(() => {});
        await nextTick();
        loginForm.value?.focus();
      }
    }
  );

  async function keepSignedIn(): Promise<void> {
    if (renewing.value) {
      return;
    }

    renewing.value = true;

    try {
      await sessionTimeoutManager.renew();
    } finally {
      renewing.value = false;
    }
  }

  function onSignedIn(event: Event): void {
    event.preventDefault();
    void sessionTimeoutManager.signedIn();
  }

  function onBeforeHide(event: Event): void {
    // Dismissing the warning is taken as wanting to stay signed in.
    event.preventDefault();
    void keepSignedIn();
  }
</script>

<template>
  <div class="contents">
    <craft-dialog
      :label="t('Your session is about to expire')"
      no-close
      .opened="state.phase === 'warning'"
      @craft-before-hide="onBeforeHide"
    >
      <p class="m-0">
        {{
          t('Your session will expire in {time}.', {time: duration(remaining)})
        }}
      </p>
      <craft-button
        slot="footer"
        type="button"
        @click="logoutForm?.requestSubmit()"
      >
        {{ t('Sign out now') }}
      </craft-button>
      <craft-button
        slot="footer"
        type="button"
        variant="primary"
        autofocus
        .loading="renewing"
        @click="keepSignedIn"
      >
        {{ t('Keep me signed in') }}
      </craft-button>
    </craft-dialog>

    <Modal
      :is-active="state.phase === 'expired'"
      :label="t('Your session has ended.')"
      :dismissible="false"
      width="sm"
    >
      <craft-pane>
        <div class="grid gap-2 mb-md">
          <h1 class="m-0!">{{ t('Your session has ended.') }}</h1>
          <div class="text-md">
            {{ t('Sign back in to continue.') }}
          </div>
        </div>

        <craft-login-form
          ref="loginForm"
          tabindex="0"
          :action="config.getCpUrl('login')"
          :static-email="loginName"
          :use-email-as-username="config.get('useEmailAsUsername', false)"
          @craft:login:success="onSignedIn"
        >
          <HtmlFragmentRenderer
            v-if="alternativeLoginMethods"
            class="grid gap-1 pt-1"
            slot="alternative-methods"
            :fragment="alternativeLoginMethods"
          />
        </craft-login-form>
      </craft-pane>
    </Modal>

    <form ref="logoutForm" :action="logout().url" method="post" hidden>
      <input
        type="hidden"
        :name="config.get('csrfTokenName')"
        :value="config.get('csrfTokenValue')"
      />
    </form>
  </div>
</template>

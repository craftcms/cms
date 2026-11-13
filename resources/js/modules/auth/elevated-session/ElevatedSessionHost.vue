<script setup lang="ts">
  import {ConfigService} from '@craftcms/ui';
  import {t} from '@craftcms/ui/utilities/translate';
  import {nextTick, onBeforeUnmount, useTemplateRef, watch} from 'vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import Modal from '@/common/components/Modal.vue';
  import {elevatedSessionManager} from './manager';
  import '@/modules/auth/components/login/login-form.js';
  import type CraftLoginForm from '@/modules/auth/components/login/login-form';

  const {state} = elevatedSessionManager;
  let previouslyFocused: HTMLElement | null = null;
  const loginForm = useTemplateRef<CraftLoginForm | null>('loginForm');

  watch(
    () => state.active,
    async (active) => {
      if (active) {
        previouslyFocused =
          document.activeElement instanceof HTMLElement
            ? document.activeElement
            : null;
        await nextTick();
        loginForm.value?.focus();
      } else {
        previouslyFocused?.focus();
        previouslyFocused = null;
      }
    }
  );

  onBeforeUnmount(() => {
    if (state.active) {
      elevatedSessionManager.cancel();
    }
  });

  function handleSuccess(event: Event): void {
    event.preventDefault();
    elevatedSessionManager.confirm();
  }

  function close(): void {
    if (state.active) {
      elevatedSessionManager.cancel();
    }
  }
</script>

<template>
  <Modal
    :is-active="state.active"
    :label="t('Confirm your identity.')"
    width="sm"
    @close="close"
  >
    <craft-pane data-elevated-session-dialog>
      <div class="grid gap-2 mb-3">
        <h1 class="m-0!">
          {{ t('Confirm your identity.') }}
        </h1>
        <div class="text-md">
          {{ t('You must reverify your identity before proceeding.') }}
        </div>
      </div>

      <craft-login-form
        ref="loginForm"
        tabindex="0"
        :action="ConfigService.getInstance().getCpUrl('login')"
        :static-email="state.loginName"
        .useEmailAsUsername="
          ConfigService.getInstance().get('useEmailAsUsername', false)
        "
        @craft:login:success="handleSuccess"
      >
        <HtmlFragmentRenderer
          v-if="state.alternativeLoginMethods"
          class="grid gap-1 pt-1"
          slot="alternative-methods"
          :fragment="state.alternativeLoginMethods"
        />
      </craft-login-form>
    </craft-pane>
  </Modal>
</template>

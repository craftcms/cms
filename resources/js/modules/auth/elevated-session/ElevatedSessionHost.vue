<script setup lang="ts">
  import {getCpUrl} from '@/common/api/actionClient';
  import {releaseFocusWithin, trapFocusWithin} from '@craftcms/garnish';
  import {t} from '@craftcms/ui/utilities/translate';
  import {nextTick, onBeforeUnmount, useTemplateRef, watch} from 'vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import Modal from '@/common/components/Modal.vue';
  import Pane from '@/common/components/Pane.vue';
  import {elevatedSessionManager} from './manager';
  import '@/modules/auth/components/login/login-form.js';
  import type CraftLoginForm from '@/modules/auth/components/login/login-form';

  const {state} = elevatedSessionManager;
  const useEmailAsUsername = window.Craft.useEmailAsUsername ?? false;
  let previouslyFocused: HTMLElement | null = null;
  let dialog: Element | null = null;
  const loginForm = useTemplateRef<CraftLoginForm | null>('loginForm');

  watch(
    () => state.active,
    async (active) => {
      if (active) {
        previouslyFocused = document.activeElement as HTMLElement | null;
        await nextTick();
        loginForm.value?.focus();
      } else {
        if (dialog) {
          releaseFocusWithin(dialog);
          dialog = null;
        }
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

  function opened(element: Element): void {
    dialog = element;
    trapFocusWithin(element);
  }
</script>

<template>
  <Modal :is-active="state.active" width="sm" @close="close" @opened="opened">
    <Pane
      data-elevated-session-dialog
      role="dialog"
      aria-modal="true"
      aria-labelledby="elevated-session-title"
      aria-describedby="elevated-session-description"
    >
      <div class="grid gap-2 mb-3">
        <h1 id="elevated-session-title" class="m-0!">
          {{ t('Confirm your identity.') }}
        </h1>
        <div id="elevated-session-description" class="text-md">
          {{ t('You must reverify your identity before proceeding.') }}
        </div>
      </div>

      <craft-login-form
        ref="loginForm"
        tabindex="0"
        :action="getCpUrl('login')"
        :static-email="state.loginName"
        :use-email-as-username="useEmailAsUsername"
        @craft:login:success="handleSuccess"
      >
        <HtmlFragmentRenderer
          v-if="state.alternativeLoginMethods"
          slot="alternative-methods"
          :fragment="state.alternativeLoginMethods"
        />
      </craft-login-form>
    </Pane>
  </Modal>
</template>

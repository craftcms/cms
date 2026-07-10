<script setup lang="ts">
  import {ConfigService} from '@craftcms/cp';
  import {releaseFocusWithin, trapFocusWithin} from '@craftcms/garnish';
  import {t} from '@craftcms/cp/utilities/translate';
  import {nextTick, onBeforeUnmount, watch} from 'vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import Modal from '@/common/components/Modal.vue';
  import Pane from '@/common/components/Pane.vue';
  import {elevatedSessionManager} from './manager';
  import '@/modules/auth/components/login/login-form.js';

  const {state} = elevatedSessionManager;
  let previouslyFocused: HTMLElement | null = null;
  let dialog: Element | null = null;

  watch(
    () => state.active,
    async (active) => {
      if (active) {
        previouslyFocused = document.activeElement as HTMLElement | null;
        await nextTick();
        document
          .querySelector<HTMLElement>(
            '[data-elevated-session-dialog] craft-login-form'
          )
          ?.focus();
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

  function initializeAlternativeLoginMethods(element: HTMLElement): void {
    Craft.initUiElements(element);
  }
</script>

<template>
  <Modal :is-active="state.active" width="md" @close="close" @opened="opened">
    <Pane
      data-elevated-session-dialog
      role="dialog"
      aria-modal="true"
      aria-labelledby="elevated-session-title"
      aria-describedby="elevated-session-description"
      class="p-6"
    >
      <div class="flex items-start gap-4 mb-5">
        <div>
          <h1 id="elevated-session-title" class="text-xl">
            {{ t('Confirm your identity.') }}
          </h1>
          <p id="elevated-session-description">
            {{ t('You must reverify your identity before proceeding.') }}
          </p>
        </div>
        <craft-button
          icon
          appearance="plain"
          type="button"
          class="ml-auto"
          @click="close"
        >
          <craft-icon name="xmark" :label="t('Cancel')"></craft-icon>
        </craft-button>
      </div>

      <craft-login-form
        tabindex="0"
        :action="ConfigService.getInstance().getCpUrl('login')"
        :static-email="state.loginName"
        :use-email-as-username="
          ConfigService.getInstance().get('useEmailAsUsername', false)
        "
        @craft:login:success="handleSuccess"
      >
        <HtmlFragmentRenderer
          v-if="state.alternativeLoginMethods"
          slot="alternative-methods"
          :fragment="state.alternativeLoginMethods"
          @rendered="initializeAlternativeLoginMethods"
        />
      </craft-login-form>
    </Pane>
  </Modal>
</template>

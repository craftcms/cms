<script setup lang="ts">
  /**
   * A full-page CP shell that keeps the global chrome and nothing else.
   *
   * Everything `PageScreen` owns *outside* the main column — the global
   * header, the CP sidebar, flash messages, the footer, the slideout and
   * elevated-session hosts — is identical here. Everything it renders *inside*
   * it — the breadcrumb bar, the page header, the content/details columns, the
   * screen-owned `<form>` — is the page's business instead: this shell hands
   * the whole main region over to its default slot.
   *
   * Selected per page with `<AppLayout :shell="EditorScreen">`. Inside a
   * slideout the panel's own shell still wins, exactly as it does for
   * `PageScreen`.
   *
   * Contract: the page renders the `#main` landmark the skip link targets, and
   * owns its own `<form>` if it has one — the form-related `ScreenProps` are
   * accepted (so the same page can pass them through to other shells) but
   * unused here.
   */
  import {t} from '@craftcms/ui/utilities/translate';
  import {ButtonVariant} from '@craftcms/ui';
  import {computed, provide, watch} from 'vue';
  import {Head, usePage} from '@inertiajs/vue3';
  import CpSidebar from '@/common/components/CpSidebar.vue';
  import DebugPanel from '@/common/components/DebugPanel.vue';
  import FlashMessages from '@/common/components/FlashMessages.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import LiveRegion from '@/common/components/LiveRegion.vue';
  import PassthroughScreen from './PassthroughScreen.vue';
  import SlideoutHost from '@/common/slideouts/SlideoutHost.vue';
  import SystemInfo from '@/common/components/SystemInfo.vue';
  import UserMenu from '@/common/components/UserMenu.vue';
  import ElevatedSessionHost from '@/modules/auth/elevated-session/ElevatedSessionHost.vue';
  import {useActionRedirect} from '@/common/composables/useActionRedirect';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import {useAppendHtml} from '@/common/composables/useAppendHtml';
  import {useFlash} from '@/common/composables/useFlash';
  import {useGlobalSidebar} from '@/common/composables/useGlobalSidebar';
  import {provideLayoutSlotRegistry} from '@/common/composables/layoutSlots';
  import {
    provideScreenContext,
    ScreenShellKey,
  } from '@/common/composables/screen';
  import type {ScreenEmits, ScreenProps} from './types';

  // Declared but never emitted: `AppLayout` binds `@save` to whichever shell it
  // renders, and an undeclared listener would fall through to this shell's
  // (multi-root) template as an attribute. A page using this shell owns its
  // form, so it handles submission itself.
  defineEmits<ScreenEmits>();

  const props = defineProps<ScreenProps>();

  defineSlots<{
    /** The entire main region — see this component's contract. */
    default?: () => any;
    /** Global page footer, as in `PageScreen`. */
    footer?: () => any;
  }>();

  // Provided for parity with the other shells: a component nested under this
  // one can still teleport into the footer outlet, and anything reaching for
  // the registry resolves this shell's rather than the ambient one.
  provideLayoutSlotRegistry();
  provideScreenContext('page');

  // A page rendering `<AppLayout>` inline inside this shell shouldn't stack a
  // second one — it renders transparently instead.
  provide(ScreenShellKey, PassthroughScreen);

  const page = usePage<{title: string}>();

  const pageTitle = computed(() => props.title?.trim() ?? page.props.title);

  const skipLinks = computed(() => [
    {label: t('Skip to main section'), url: '#main'},
    ...(props.additionalSkipLinks ?? []),
  ]);

  const {
    isLargeScreen,
    sidebar: globalSidebar,
    toggle: toggleSidebar,
    close: closeSidebar,
    icon: sidebarIcon,
    width: sidebarWidth,
  } = useGlobalSidebar();

  // Announce flash messages to screen readers.
  const {announce} = useAnnouncer();
  const {errorFlash, successFlash} = useFlash();
  watch(successFlash, (newMessage) => announce(newMessage));
  watch(errorFlash, (newMessage) => announce(newMessage));

  useAppendHtml();

  // Bridge `@craftcms/ui` action redirects into Inertia SPA visits.
  useActionRedirect();
</script>

<template>
  <Head :title="pageTitle" />
  <LiveRegion />
  <div class="cp">
    <header class="cp__header">
      <a
        v-for="link in skipLinks"
        :key="link.url"
        :href="link.url"
        class="skip-link skip-link--global"
        >{{ link.label }}</a
      >
      <div class="flex gap-2 p-2">
        <craft-button
          icon
          type="button"
          :variant="ButtonVariant.Plain"
          @click="toggleSidebar"
          v-if="!isLargeScreen"
          ref="sidebarToggle"
        >
          <craft-icon
            :name="sidebarIcon"
            :label="t('Toggle menu')"
          ></craft-icon>
        </craft-button>
        <SystemInfo v-if="isLargeScreen" />

        <div class="ml-auto"></div>
        <craft-button icon :variant="ButtonVariant.Plain" type="button">
          <craft-icon name="search" :label="t('Search')"></craft-icon>
        </craft-button>
        <UserMenu />
      </div>
      <FlashMessages />
    </header>
    <div class="cp__sidebar">
      <CpSidebar
        :mode="globalSidebar.mode"
        :visibility="globalSidebar.visibility"
        @close="closeSidebar"
      />
    </div>
    <div class="cp__main">
      <slot></slot>
    </div>
    <div class="cp__footer">
      <footer>
        <LayoutSlotOutlet name="footer">
          <slot name="footer"></slot>
        </LayoutSlotOutlet>
      </footer>
    </div>
  </div>

  <DebugPanel v-if="debug" :data="debug" />
  <ElevatedSessionHost />
  <!-- Hosted here rather than in `AppLayout`: exactly one full-page shell
    exists per page, so panels can't be double-rendered by a page that also
    renders `<AppLayout>` inline. -->
  <SlideoutHost />
</template>

<style scoped lang="css">
  .cp {
    display: grid;
  }

  .cp__main {
    /* The page's own layout can hang container queries off this, as
       `.content-layout` does in `PageScreen`. */
    container-type: size;
  }

  .cp__header {
    --c-color-focus-outline: var(--color-blue-300);
    color: var(--color-slate-200);
    background-color: var(--color-slate-950);
  }

  @media screen and (min-width: 1024px) {
    .cp {
      grid-template-columns: v-bind(sidebarWidth) minmax(0, 1fr);
      grid-template-areas: 'header header' 'sidebar main';
      grid-template-rows: auto 1fr;
      min-height: 100vh;
      position: fixed;
      inset: 0;
      width: 100%;
      height: 100%;
    }

    .cp__header {
      grid-area: header;
    }

    .cp__sidebar {
      grid-area: sidebar;
    }

    .cp__main {
      grid-area: main;
      overflow: auto;
    }
  }
</style>

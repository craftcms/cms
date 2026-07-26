<script setup lang="ts">
  import {computed, onBeforeUnmount, ref, watch} from 'vue';
  import {useEventListener} from '@vueuse/core';
  import {router, useForm, usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import Pane from '@/common/components/Pane.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {expandFormData} from '@/common/utils/forms';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import SaveUserController from '@actions/Users/SaveUserController';

  defineOptions({
    inheritAttrs: false,
  });

  type UserProfilePageProps = CraftCms.Cms.Http.ViewModels.UserProfileViewModel;

  const props = usePage<UserProfilePageProps>().props;

  // The profile form's fields (including custom fields) are server-rendered
  // and injected below, so the Inertia form starts empty and the payload is
  // read from the DOM at submit time.
  const form = useForm<Record<string, any>>({});

  const contentEl = ref<HTMLElement | null>(null);

  const activeTab = ref<string | null>(props.tabMenu[0]?.containerId ?? null);

  function selectTab(containerId: string) {
    activeTab.value = containerId;

    for (const tab of props.tabMenu) {
      document
        .getElementById(tab.containerId)
        ?.classList.toggle('hidden', tab.containerId !== containerId);
    }
  }

  // A fresh fragment (e.g. after a successful save) renders with the first
  // tab visible again, so keep the tab bar in sync with it, and re-baseline
  // the unsaved-changes tracking against the new markup.
  watch(
    () => props.formFragment,
    () => {
      activeTab.value = props.tabMenu[0]?.containerId ?? null;
      changeBaseline = null;
    }
  );

  function profileData(): Record<string, any> {
    const formEl = hostForm.value;
    const data = formEl ? expandFormData(new FormData(formEl)) : {};

    return {...data, userId: props.userId};
  }

  // Unsaved-changes guard, mirroring Craft.cp's confirm-unload behavior on
  // the legacy profile screen. The fields are injected HTML rather than Vue
  // state, so dirtiness is a serialized-form comparison against a baseline
  // captured on first interaction (the fragment injects asynchronously, so
  // there is no reliable "after render" moment to snapshot it earlier).
  const hostForm = computed(() => contentEl.value?.closest('form') ?? null);
  let changeBaseline: string | null = null;

  function serializeHostForm(): string {
    const formEl = hostForm.value;
    if (!formEl) {
      return '';
    }

    const params = new URLSearchParams();
    for (const [key, value] of new FormData(formEl).entries()) {
      params.append(key, typeof value === 'string' ? value : value.name);
    }

    return params.toString();
  }

  function captureChangeBaseline() {
    changeBaseline ??= serializeHostForm();
  }

  function hasUnsavedChanges(): boolean {
    return (
      !form.processing &&
      changeBaseline !== null &&
      serializeHostForm() !== changeBaseline
    );
  }

  // Both events fire before an input's value can change, so the baseline
  // always reflects the pre-edit state.
  useEventListener(hostForm, 'focusin', captureChangeBaseline);
  useEventListener(hostForm, 'pointerdown', captureChangeBaseline);

  useEventListener(window, 'beforeunload', (event) => {
    if (hasUnsavedChanges()) {
      event.preventDefault();
    }
  });

  const removeNavigationGuard = router.on('before', (event) => {
    const visit = event.detail.visit;

    if (
      visit.method === 'get' &&
      !visit.prefetch &&
      hasUnsavedChanges() &&
      !window.confirm(t('Any changes will be lost if you leave this page.'))
    ) {
      event.preventDefault();
    }
  });
  onBeforeUnmount(removeNavigationGuard);

  function emailChanged(): boolean {
    const email = profileData().email;

    return (
      typeof email === 'string' &&
      email.trim() !== '' &&
      email.trim() !== (props.email ?? '')
    );
  }

  const {save} = useSettingsSave(
    form,
    () =>
      SaveUserController['/{cpTrigger?}/{actionTrigger?}/users/save-user'](),
    {
      transform: () => profileData(),
      passwordConfirmation: {
        required: () => emailChanged(),
      },
    }
  );

  useAppLayout({form, onSave: save});
</script>

<template>
  <LayoutSlot v-if="props.tabMenu.length > 1" name="tabs">
    <craft-button-group role="tablist">
      <craft-button
        v-for="tab in props.tabMenu"
        :key="tab.containerId"
        :id="tab.tabId"
        type="button"
        role="tab"
        appearance="outline"
        :active="activeTab === tab.containerId ? 'true' : null"
        :aria-selected="activeTab === tab.containerId"
        :aria-controls="tab.containerId"
        @click="selectTab(tab.containerId)"
      >
        {{ tab.label }}
        <craft-icon
          v-if="tab.hasErrors"
          name="circle-exclamation"
          slot="suffix"
        />
      </craft-button>
    </craft-button-group>
  </LayoutSlot>

  <Pane appearance="raised">
    <div ref="contentEl">
      <HtmlFragmentRenderer :fragment="props.formFragment" />
    </div>
  </Pane>

  <LayoutSlot v-if="props.detailsFragment" name="details">
    <HtmlFragmentRenderer :fragment="props.detailsFragment" />
  </LayoutSlot>
</template>

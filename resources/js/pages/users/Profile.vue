<script setup lang="ts">
  import {computed, onBeforeUnmount, ref, watch} from 'vue';
  import {useEventListener} from '@vueuse/core';
  import {router, useForm, usePage} from '@inertiajs/vue3';
  import {type CraftTabs, t} from '@craftcms/ui';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
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

  // The panels are the server-rendered `<section id="{containerId}">`s inside
  // the pane, so the strip runs in <craft-tabs>' external-panel mode: the tabs
  // name their panel by id and the component drives it in place.
  const tabsEl = ref<CraftTabs | null>(null);
  const selectedTab = ref(0);

  // Mirror the component's own selection back, so re-selecting the first tab
  // below is a real change Vue will push down to the attribute.
  function onTabSelected(event: Event) {
    selectedTab.value = (event.target as CraftTabs).selectedIndex;
  }

  // A fresh fragment (e.g. after a successful save) renders with the first
  // tab visible again, so keep the tab bar in sync with it, and re-baseline
  // the unsaved-changes tracking against the new markup.
  watch(
    () => props.formFragment,
    () => {
      selectedTab.value = 0;
      changeBaseline = null;
    }
  );

  // The replacement sections come back with the same ids but as new elements,
  // dropping the roles and visibility the strip put on them.
  function onFragmentReady() {
    tabsEl.value?.refresh();
  }

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
    <craft-tabs
      ref="tabsEl"
      :selected-index="selectedTab"
      @selected-changed="onTabSelected"
    >
      <craft-tab
        v-for="tab in props.tabMenu"
        :key="tab.containerId"
        :id="tab.tabId"
        slot="tab"
        :controls="tab.containerId"
      >
        {{ tab.label }}
        <craft-icon
          v-if="tab.hasErrors"
          name="circle-exclamation"
          :label="t('This tab contains errors')"
        />
      </craft-tab>
    </craft-tabs>
  </LayoutSlot>

  <craft-pane appearance="raised">
    <div ref="contentEl">
      <HtmlFragmentRenderer
        :fragment="props.formFragment"
        @ready="onFragmentReady"
      />
    </div>
  </craft-pane>

  <LayoutSlot v-if="props.detailsFragment" name="details">
    <HtmlFragmentRenderer :fragment="props.detailsFragment" />
  </LayoutSlot>
</template>

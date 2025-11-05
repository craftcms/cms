<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {computed, defineAsyncComponent, provide, ref} from 'vue';
  import DeveloperSupportScreen from '@/widgets/support/DeveloperSupportScreen.vue';
  import SupportResources from '@/widgets/support/SupportResources.vue';

  const props = withDefaults(
    defineProps<{
      bundleUrl?: string;
      id?: string;
      issueParams?: string;
      issueTitlePrefix?: string;
    }>(),
    {bundleUrl: '', issueParams: '{}', widgetId: '', issueTitlePrefix: ''}
  );
  provide('support-widget:bundle-url', props.bundleUrl);
  provide('support-widget:issue-params', JSON.parse(props.issueParams));
  provide('support-widget:issue-title-prefix', props.issueTitlePrefix);

  const body = ref('');

  const screens = [
    {
      id: 'help',
      icon: 'life-ring',
      title: t('app', 'Get help'),
      description: t('app', 'How-to’s and other questions'),
      submitText: t('app', 'Ask on Stack Exchange'),
      component: defineAsyncComponent(
        () => import('@/widgets/support/HelpScreen.vue')
      ),
    },
    {
      id: 'feedback',
      icon: 'bullhorn',
      title: t('app', 'Give feedback'),
      description: t('app', 'Bug reports and feature requests'),
      submitText: t('app', 'Post on GitHub'),
      component: defineAsyncComponent(
        () => import('@/widgets/support/FeedbackScreen.vue')
      ),
    },
  ];

  const activeScreenId = ref<string | null>(null);
  const dialogActive = ref(false);
  const activeScreen = computed(
    () => screens.find((screen) => screen.id === activeScreenId.value) ?? null
  );
  const dialogTitle = computed(() => {
    if (activeScreenId.value === 'developerSupport') {
      return t('app', 'Contact Developer Support');
    }

    return activeScreen.value?.submitText ?? '';
  });

  function openDialog(screenId: string) {
    if (!dialogActive.value) {
      dialogActive.value = true;
    }

    activeScreenId.value = screenId;
  }

  function reset() {
    activeScreenId.value = null;
    dialogActive.value = false;
    body.value = '';
  }

  function cancel() {
    dialogActive.value = false;
    body.value = '';
  }
</script>

<template>
  <div class="tw:grid">
    <template v-for="screen in screens" :key="screen.id">
      <craft-button
        appearance="plain"
        :aria-controls="`cs-screen-${screen.id}`"
        :aria-expanded="activeScreenId === screen.id"
        @click="openDialog(screen.id)"
        v-if="screen.title"
      >
        <div class="tw:grid tw:justify-items-center tw:py-4 tw:gap-2">
          <craft-icon
            :name="screen.icon"
            style="font-size: 2.75rem"
          ></craft-icon>
          <div class="tw:text-center">
            <h2 class="tw:text-lg">{{ screen.title }}</h2>
            <p class="tw:m-0">{{ screen.description }}</p>
          </div>
        </div>
      </craft-button>
    </template>

    <craft-dialog
      :label="dialogTitle"
      :open="dialogActive"
      light-dismiss
      @wa-after-hide="reset"
    >
      <div class="@container">
        <div>
          <template v-for="screen in screens" :key="`screen-${screen.id}`">
            <component
              :is="screen.component"
              v-if="activeScreenId === screen.id"
              @click:cancel="cancel"
              @click:support="activeScreenId = 'developerSupport'"
              v-model="body"
            />
          </template>

          <DeveloperSupportScreen
            v-if="activeScreenId === 'developerSupport'"
            @click:cancel="cancel"
            v-model="body"
          />
        </div>
      </div>
      <SupportResources />
    </craft-dialog>
  </div>
</template>

<style scoped lang="scss"></style>

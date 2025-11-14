<script setup lang="ts">
  import {Deferred, Head} from '@inertiajs/vue3';
  import {t} from '@craftcms/cp';
  import backgroundUrl from '../../images/install/installer-bg.png';
  import {computed, reactive, ref} from 'vue';
  import AccountFields from '@/components/install/AccountFields.vue';
  import SiteFields from '@/components/install/SiteFields.vue';
  import {useInstall} from '@/composables/useInstall';
  import accountBg from '../../images/install/account.png';
  import siteBg from '../../images/install/site.png';
  import dbBg from '../../images/install/db.png';
  import DbFields from '@/components/install/DbFields.vue';
  import axios from 'axios';
  import InstallingScreen from '@/components/install/InstallingScreen.vue';
  import Pane from '@/components/Pane.vue';
  import Modal from '@/components/Modal.vue';

  const backgroundImageUrl = computed(() => `url(${backgroundUrl})`);
  const {
    dotSteps,
    current,
    currentId,
    goTo,
    goToNext,
    goToPrevious,
    isCurrent,
  } = useInstall();

  const props = defineProps<{
    dbConfig: {
      driver: 'mysql' | 'pgsql';
      url: string | null;
      host: string | null;
      port: string | null;
      database: string | null;
      username: string | null;
      password: string | null;
      prefix: string | null;
    };
    localeOptions?: Array<{id: string; name: string; selected: boolean}>;
    licenseHtml?: string;
    defaultSystemName: string;
    defaultSiteUrl: string;
    defaultSiteLanguage: string;
  }>();
  const state = ref<'idle' | 'loading' | 'error'>('idle');

  function beginInstall() {
    goTo('license');
  }

  const errors = reactive<Record<string, any>>({
    account: {},
    db: {},
    site: {},
  });

  const formData = reactive<Record<string, object>>({
    account: {
      username: '',
      email: '',
      password: '',
    },
    db: {
      driver: props.dbConfig.driver,
      host: props.dbConfig.host,
      port: props.dbConfig.port,
      database: props.dbConfig.database,
      user: props.dbConfig.username,
      password: props.dbConfig.password,
      prefix: props.dbConfig.prefix,
    },
    site: {
      name: props.defaultSystemName,
      baseUrl: props.defaultSiteUrl,
      language: props.defaultSiteLanguage,
    },
  });

  const modalActive = computed(() => !isCurrent('start'));

  async function handleSubmit(e: Event) {
    if (state.value === 'loading') {
      return;
    }

    errors[currentId.value!] = null;

    const form = e.currentTarget as HTMLFormElement;
    try {
      state.value = 'loading';
      await axios.post(form.action, formData[currentId.value!]);
      goToNext();
      state.value = 'idle';
    } catch (e: any) {
      errors[currentId.value!] = e.response.data.errors;
      state.value = 'error';
    }
  }

  const illustrationSrc = computed(() => {
    switch (currentId.value) {
      case 'account':
        return accountBg;
      case 'db':
        return dbBg;
      case 'site':
        return siteBg;
    }
  });
</script>

<template>
  <Head :title="t('app', 'Install Craft CMS')" />

  <div class="install">
    <template v-if="isCurrent('start')">
      <craft-button
        type="button"
        @click="beginInstall"
        variant="primary"
        class="begin-button"
      >
        {{ t('app', 'Install Craft CMS') }}
        <craft-icon name="arrow-right" slot="suffix"></craft-icon>
      </craft-button>
    </template>

    <Modal :is-active="modalActive">
      <template v-if="isCurrent('license')">
        <Pane class="tw:max-w-[80ch] tw:mx-auto">
          <Deferred data="licenseHtml">
            <template #fallback>
              <div class="tw:flex tw:justify-center">
                <craft-spinner></craft-spinner>
              </div>
            </template>

            <div class="license" v-html="licenseHtml"></div>
          </Deferred>

          <template #actions>
            <div class="tw:flex tw:justify-center tw:w-full">
              <craft-button
                type="button"
                variant="primary"
                @click="goTo('account')"
              >
                {{ t('app', 'Got it') }}
              </craft-button>
            </div>
          </template>
        </Pane>
      </template>

      <template v-else-if="isCurrent('installing')">
        <InstallingScreen :data="formData" @success="goToNext()" />
      </template>
      <template v-else-if="isCurrent('complete')">
        <div
          class="tw:p-8 tw:grid tw:justify-center tw:content-center tw:gap-4"
        >
          <h2>{{ t('app', 'Craft is installed! 🎉') }}</h2>
        </div>
        <InstallingScreen :data="formData" @success="goToNext()" />
      </template>

      <template v-else>
        <Pane as="form" :action="current.action" @submit.prevent="handleSubmit">
          <div class="tw:grid tw:md:grid-cols-2 tw:gap-4 tw:items-center">
            <div class="tw:aspect-[352/455] tw:w-1/2 tw:md:w-3/4 tw:mx-auto">
              <img loading="lazy" :src="illustrationSrc" alt="" width="368" />
            </div>
            <div>
              <h2 class="tw:mb-4">{{ current.heading }}</h2>
              <div class="tw:grid tw:gap-3 tw:md:max-w-xs">
                <AccountFields
                  v-if="isCurrent('account')"
                  v-model="formData.account"
                  :errors="errors.account"
                />
                <DbFields v-if="isCurrent('db')" v-model="formData.db" />
                <Deferred data="localeOptions">
                  <template #fallback>
                    <craft-spinner></craft-spinner>
                  </template>

                  <SiteFields
                    v-if="isCurrent('site')"
                    v-model="formData.site"
                    :localeOptions="localeOptions"
                    :errors="errors.site"
                  />
                </Deferred>
              </div>
            </div>
          </div>

          <template #actions>
            <div class="tw:grid tw:grid-cols-3 tw:items-center tw:gap-2">
              <craft-button
                type="button"
                @click="goToPrevious"
                appearance="plain"
                class="tw:justify-self-start"
              >
                {{ t('app', 'Back') }}
                <craft-icon name="arrow-left" slot="prefix"></craft-icon>
              </craft-button>
              <nav class="tw:justify-self-center">
                <ul class="tw:flex tw:gap-2">
                  <li v-for="(step, id) in dotSteps" :key="id">
                    <button
                      class="dot"
                      type="button"
                      @click="goTo(id)"
                      :class="{
                        'dot--active': isCurrent(id),
                      }"
                    >
                      <span class="tw:sr-only">
                        {{ step.label }}
                      </span>
                    </button>
                  </li>
                </ul>
              </nav>
              <craft-button
                class="tw:justify-self-end"
                type="submit"
                variant="primary"
                :loading="state === 'loading'"
              >
                {{ current.submitLabel ?? t('app', 'Next') }}
                <craft-icon name="arrow-right" slot="suffix"></craft-icon>
              </craft-button>
            </div>
          </template>
        </Pane>
      </template>
    </Modal>
  </div>
</template>

<style scoped lang="scss">
  .install {
    background-image: v-bind(backgroundImageUrl);
    background-position: 50% 50%;
    background-repeat: no-repeat;
    background-size: cover;
    height: 100vh;
    display: grid;
    place-items: center;
    justify-items: center;
  }

  .begin-button {
    border-radius: var(--c-radius-lg);
    font-size: calc(19rem / 16);
    min-height: calc(50rem / 16);
    padding-block: 0;
    padding-inline: 1.5em;
    box-shadow:
      inset 0 1px #fff3,
      inset 0 -1px #0002,
      0 0 0 1px #21377066,
      0 0 1px 2px #21377055,
      0 10px 10px -10px #213770,
      0 10px 20px -10px #213770;
  }

  .dot {
    appearance: none;
    border: 1px solid var(--c-color-neutral-border-subtle);
    background-color: var(--c-color-neutral-bg-subtle);
    border-radius: var(--c-radius-full);
    padding: 0;
    width: 0.6rem;
    height: 0.6rem;
    cursor: pointer;
  }

  .dot--active {
    background-color: var(--c-color-accent-bg-emphasis);
    border: 1px solid var(--c-color-accent-border-emphasis);
  }

  .license {
    font-size: calc(13rem / 16);
    font-family: var(--c-font-mono);
    padding: var(--c-spacing-lg);

    :deep(* + *) {
      margin-block: 1em;
    }

    :deep(ol) {
      list-style-type: decimal;
      padding-inline-start: 2.25em;
    }
  }
</style>

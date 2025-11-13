<script setup lang="ts">
  import {Head, usePage} from '@inertiajs/vue3';
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

  type InstallScreens =
    | 'start'
    | 'license'
    | 'db'
    | 'account'
    | 'site'
    | 'installing';

  const backgroundImageUrl = computed(() => `url(${backgroundUrl})`);
  const {
    steps,
    index,
    dotSteps,
    current,
    currentId,
    goTo,
    goToNext,
    goToPrevious,
    isCurrent,
  } = useInstall();

  const {props} = usePage<{
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

    const form = e.currentTarget as HTMLFormElement;
    try {
      state.value = 'loading';
      await axios.post(form.action, formData[currentId.value!]);
      goToNext();
      state.value = 'idle';
    } catch (e: any) {
      errors[currentId.value!] = e.response.data.errors;
      state.value = 'error';
      console.log('error', e);
    }
  }
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

    <div class="modal" v-if="modalActive">
      <div class="screen">
        <template v-if="isCurrent('license')">
          <div class="license tw:p-8 tw:pb-0" v-html="props.licenseHtml"></div>

          <div
            class="tw:mt-4 tw:flex tw:justify-center tw:border-t tw:border-t-gray-300 tw:py-3 tw:px-4"
          >
            <craft-button
              type="button"
              variant="primary"
              @click="goTo('account')"
            >
              {{ t('app', 'Got it') }}
            </craft-button>
          </div>
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
          <form :action="current.action" @submit.prevent="handleSubmit">
            <div
              class="tw:grid tw:grid-cols-2 tw:gap-4 tw:items-center tw:py-4 tw:px-10"
            >
              <div class="illustration">
                <img
                  :src="accountBg"
                  alt=""
                  width="368"
                  v-if="isCurrent('account')"
                />
                <img :src="dbBg" alt="" width="368" v-if="isCurrent('db')" />
                <img
                  :src="siteBg"
                  alt=""
                  width="368"
                  v-if="isCurrent('site')"
                />
              </div>
              <div>
                <h2 class="tw:mb-4">{{ current.heading }}</h2>
                <div class="tw:grid tw:gap-3">
                  <AccountFields
                    v-if="isCurrent('account')"
                    v-model="formData.account"
                    :errors="errors.account"
                  />
                  <DbFields v-if="isCurrent('db')" v-model="formData.db" />
                  <SiteFields
                    v-if="isCurrent('site')"
                    v-model="formData.site"
                    :errors="errors.site"
                  />
                </div>
              </div>
            </div>

            <div
              class="tw:flex tw:justify-between tw:items-center tw:border-t tw:border-t-gray-300 tw:py-3 tw:px-4"
            >
              <craft-button
                type="button"
                @click="goToPrevious"
                appearance="plain"
              >
                {{ t('app', 'Back') }}
                <craft-icon name="arrow-left" slot="prefix"></craft-icon>
              </craft-button>
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
              <craft-button
                type="submit"
                variant="primary"
                :loading="state === 'loading'"
              >
                {{ t('app', 'Next') }}
                <craft-icon name="arrow-right" slot="suffix"></craft-icon>
              </craft-button>
            </div>
          </form>
        </template>
      </div>
    </div>
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

  .illustration {
    aspect-ratio: 352 / 455;
  }

  .modal {
    position: fixed;
    inset: 0;
    display: grid;
    overflow: hidden;
    height: 100%;
    width: 100%;
  }

  .screen {
    background-color: var(--c-bg-overlay);
    place-self: center;
    //padding: var(--c-spacing-lg) var(--c-spacing-xl);
    overflow-y: scroll;
    min-height: 30vh;
    width: calc(800rem / 16);
    max-width: calc(100vw - (var(--c-spacing-lg) * 2));
    max-height: calc(100vh - (var(--c-spacing-lg) * 2));
    border-radius: var(--c-radius-xl);
    box-shadow: var(--c-modal-shadow);
    display: grid;
    align-content: center;
    justify-content: center;
    transition: height 0.2s ease-in-out;
  }

  .dot {
    appearance: none;
    border: 1px solid var(--c-color-neutral-border-subtle);
    background-color: var(--c-color-neutral-bg-subtle);
    border-radius: var(--c-radius-full);
    padding: 0;
    width: 0.75rem;
    height: 0.75rem;
  }

  .dot--active {
    background-color: var(--c-color-accent-bg-emphasis);
    border: 1px solid var(--c-color-accent-border-emphasis);
  }

  .license {
    font-size: calc(13rem / 16);
    font-family: var(--c-font-mono);

    :deep(* + *) {
      margin-block: 1em;
    }

    :deep(ol) {
      list-style-type: decimal;
      padding-inline-start: 2.25em;
    }
  }
</style>

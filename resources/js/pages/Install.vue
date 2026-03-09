<script setup lang="ts">
  import {Deferred, Head} from '@inertiajs/vue3';
  import {t} from '@craftcms/cp';
  import backgroundUrl from '../../images/install/installer-bg.png';
  import {computed, reactive, ref, watchEffect} from 'vue';
  import AccountFields from '@/components/install/AccountFields.vue';
  import SiteFields from '@/components/install/SiteFields.vue';
  import {useInstall} from '@/composables/useInstall';
  import accountBg from '../../images/install/account.png';
  import siteBg from '../../images/install/site.png';
  import dbBg from '../../images/install/db.png';
  import DbFields from '@/components/install/DbFields.vue';
  import axios from 'axios';
  import InstallingScreen from '@/components/install/InstallingScreen.vue';
  import Pane from '@/components/Pane/Pane.vue';
  import Modal from '@/components/Modal.vue';
  import StepScreen from '@/components/install/StepScreen.vue';

  const backgroundImageUrl = computed(() => `url(${backgroundUrl})`);
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
    showDbScreen: boolean;
  }>();

  const {
    dotSteps,
    current,
    currentId,
    goTo,
    goToNext,
    goToPrevious,
    isCurrent,
    possibleSteps,
  } = useInstall();

  const state = ref<'idle' | 'loading' | 'error'>('idle');

  watchEffect(() => {
    possibleSteps.value.db.hidden = props.showDbScreen;
  });

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
      username: props.dbConfig.username,
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
</script>

<template>
  <Head :title="t('Install Craft CMS')" />

  <div class="install">
    <template v-if="isCurrent('start')">
      <craft-button
        type="button"
        @click="beginInstall"
        variant="primary"
        class="begin-button"
      >
        {{ t('Install Craft CMS') }}
        <craft-icon name="arrow-right" slot="suffix"></craft-icon>
      </craft-button>
    </template>

    <Modal :is-active="modalActive" :overlay="false">
      <!-- License screen -->
      <template v-if="isCurrent('license')">
        <Pane class="max-w-[80ch] mx-auto">
          <Deferred data="licenseHtml">
            <template #fallback>
              <div class="flex justify-center">
                <craft-spinner></craft-spinner>
              </div>
            </template>

            <div class="license" v-html="licenseHtml"></div>
          </Deferred>

          <template #actions>
            <div class="flex justify-center w-full">
              <craft-button
                type="button"
                variant="primary"
                @click="goTo('account')"
              >
                {{ t('Got it') }}
              </craft-button>
            </div>
          </template>
        </Pane>
      </template>

      <!-- Installing -->
      <template v-else-if="isCurrent('installing')">
        <InstallingScreen :data="formData" @success="goToNext()" />
      </template>

      <!-- Form screens -->
      <template v-else>
        <div class="max-w-[80ch]">
          <Pane
            as="form"
            :action="current.action"
            @submit.prevent="handleSubmit"
          >
            <StepScreen
              :illustration-src="accountBg"
              :heading="current.heading"
              v-if="isCurrent('account')"
              class="screen"
            >
              <AccountFields
                v-if="isCurrent('account')"
                v-model="formData.account"
                :errors="errors.account"
              />
            </StepScreen>
            <StepScreen
              :illustration-src="dbBg"
              :heading="current.heading"
              v-if="isCurrent('db')"
              class="screen"
            >
              <DbFields v-model="formData.db" :errors="errors.db" />
            </StepScreen>

            <StepScreen
              :illustration-src="siteBg"
              :heading="current.heading"
              v-if="isCurrent('site')"
              class="screen"
            >
              <Deferred data="localeOptions">
                <template #fallback>
                  <craft-spinner></craft-spinner>
                </template>

                <SiteFields
                  v-model="formData.site"
                  :localeOptions="localeOptions"
                  :errors="errors.site"
                />
              </Deferred>
            </StepScreen>

            <template #actions>
              <div class="grid grid-cols-3 items-center gap-2">
                <craft-button
                  type="button"
                  @click="goToPrevious"
                  appearance="plain"
                  class="justify-self-start"
                >
                  {{ t('Back') }}
                  <craft-icon name="arrow-left" slot="prefix"></craft-icon>
                </craft-button>
                <ul class="flex gap-2 justify-center">
                  <li v-for="(step, id) in dotSteps" :key="id">
                    <span
                      class="dot"
                      :class="{
                        'dot--active': isCurrent(id),
                      }"
                    >
                      <span class="sr-only">
                        {{ step.label }}
                      </span>
                    </span>
                  </li>
                </ul>
                <craft-button
                  class="justify-self-end"
                  type="submit"
                  variant="primary"
                  :loading="state === 'loading'"
                >
                  {{ current.submitLabel ?? t('Next') }}
                  <craft-icon name="arrow-right" slot="suffix"></craft-icon>
                </craft-button>
              </div>
            </template>
          </Pane>
        </div>
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
    display: inline-block;
    appearance: none;
    border: 1px solid var(--c-color-neutral-border-quiet);
    background-color: var(--c-color-neutral-fill-quiet);
    border-radius: var(--c-radius-full);
    padding: 0;
    width: calc(10rem / 16);
    height: calc(10rem / 16);
    flex-shrink: 0;
  }

  .dot--active {
    background-color: var(--c-color-accent-fill-loud);
    border: 1px solid var(--c-color-accent-border-loud);
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

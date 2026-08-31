<script setup lang="ts">
  import {Deferred, Head, useHttp} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import backgroundUrl from '@public/images/install/installer-bg.png';
  import {computed, reactive, watchEffect} from 'vue';
  import AccountFields from '@/modules/install/components/AccountFields.vue';
  import SiteFields from '@/modules/install/components/SiteFields.vue';
  import {useInstall} from '@/modules/install/composables/useInstall';
  import accountBg from '@public/images/install/account.png';
  import siteBg from '@public/images/install/site.png';
  import dbBg from '@public/images/install/db.png';
  import DbFields from '@/modules/install/components/DbFields.vue';
  import InstallingScreen from '@/modules/install/components/InstallingScreen.vue';
  import Modal from '@/common/components/Modal.vue';
  import StepScreen from '@/modules/install/components/StepScreen.vue';

  type DbDriver = 'mysql' | 'mariadb' | 'pgsql' | 'sqlite';
  type InstallFormStep = 'account' | 'db' | 'site';
  type DbDefaults = {
    host?: string;
    port?: string;
    database: string;
    username?: string;
    prefix?: string;
  };

  const backgroundImageUrl = computed(() => `url(${backgroundUrl})`);
  const props = defineProps<{
    dbConfig: {
      driver: DbDriver;
    };
    dbDefaults: Record<DbDriver, DbDefaults>;
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

  watchEffect(() => {
    const dbStep = possibleSteps.value.db;
    if (dbStep) dbStep.hidden = !props.showDbScreen;
  });

  function beginInstall() {
    goTo('license');
  }

  const errors = reactive<Record<InstallFormStep, Record<string, string>>>({
    account: {},
    db: {},
    site: {},
  });

  const formData = useHttp({
    account: {
      username: '',
      email: '',
      password: '',
    },
    db: {
      driver: props.dbConfig.driver,
      host: undefined,
      port: undefined,
      database: undefined,
      username: undefined,
      password: undefined,
      prefix: undefined,
    },
    site: {
      name: props.defaultSystemName,
      baseUrl: props.defaultSiteUrl,
      language: props.defaultSiteLanguage,
    },
  });

  const modalActive = computed(() => !isCurrent('start'));

  function handleSubmit(evt: Event) {
    if (formData.processing) {
      return;
    }

    const step = currentId.value;
    if (step !== 'account' && step !== 'db' && step !== 'site') {
      throw new Error(`The ${step} installer step has no form.`);
    }
    errors[step] = {};

    if (!(evt.currentTarget instanceof HTMLFormElement)) {
      throw new Error('Expected an installer form submission.');
    }
    const form = evt.currentTarget;
    formData
      .transform((data) => data[step])
      .post(form.action, {
        onSuccess: () => {
          goToNext();
        },
        onError: (validationErrors) => {
          errors[step] = validationErrors;
        },
      });
  }
</script>

<template>
  <Head :title="t('Install Craft CMS')" />

  <div class="install">
    <template v-if="isCurrent('start')">
      <craft-button
        type="button"
        @click="beginInstall"
        variant="accent"
        class="begin-button"
      >
        {{ t('Install Craft CMS') }}
        <craft-icon name="arrow-right" slot="suffix"></craft-icon>
      </craft-button>
    </template>

    <Modal :is-active="modalActive" :overlay="false" width="2xl">
      <!-- License screen -->
      <template v-if="isCurrent('license')">
        <craft-pane class="max-w-[80ch] mx-auto">
          <Deferred data="licenseHtml">
            <template #fallback>
              <div class="flex justify-center">
                <craft-spinner></craft-spinner>
              </div>
            </template>

            <div class="license" v-html="licenseHtml"></div>
          </Deferred>

          <div slot="actions" class="flex justify-center w-full">
            <craft-button
              type="button"
              variant="accent"
              @click="goTo('account')"
            >
              {{ t('Got it') }}
            </craft-button>
          </div>
        </craft-pane>
      </template>

      <!-- Installing -->
      <template v-else-if="isCurrent('installing')">
        <InstallingScreen :data="formData" @success="goToNext()" />
      </template>

      <!-- Form screens -->
      <template v-else>
        <!--
          `craft-pane` can't be rendered as a `<form>` the way the old Vue
          `Pane` could (its `as` prop is gone), so the form wraps the pane
          instead. The footer's submit button is still a light-DOM descendant
          of the form, so submission behaves the same.
        -->
        <form :action="current.action" @submit.prevent="handleSubmit">
          <craft-pane>
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
              <DbFields
                v-model="formData.db"
                :defaults="dbDefaults"
                :errors="errors.db"
              />
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
                  :locale-options="
                    (localeOptions ?? []).map((o) => ({
                      id: o.id,
                      label: o.name,
                      value: o.id,
                    }))
                  "
                  :errors="errors.site"
                />
              </Deferred>
            </StepScreen>

            <div
              slot="footer-content"
              class="grid grid-cols-3 items-center gap-2 w-full"
            >
              <craft-button
                type="button"
                @click="goToPrevious"
                variant="plain"
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
                variant="accent"
                :loading="formData.processing"
              >
                {{ current.submitLabel ?? t('Next') }}
                <craft-icon name="arrow-right" slot="suffix"></craft-icon>
              </craft-button>
            </div>
          </craft-pane>
        </form>
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

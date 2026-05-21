<script setup lang="ts">
  import MixedInput from '@/components/form/MixedInput.vue';
  import ModalForm from '@/components/ModalForm.vue';
  import Select from '@/components/form/Select.vue';
  import type {
    MixedInputPart,
    MixedInputToken,
  } from '@/components/form/MixedInput.vue';
  import {destroy, store, update} from '@actions/Settings/RoutesController';
  import {useForm} from '@inertiajs/vue3';
  import {t} from '@craftcms/cp';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import {computed, shallowRef, watch} from 'vue';

  interface RouteData {
    uid: string;
    siteUid: string | null;
    uriParts: Array<MixedInputPart>;
    template: string;
  }

  interface RouteSiteOption {
    uid: string;
    name: string;
  }

  interface RouteFormData {
    uriParts: Array<MixedInputPart>;
    template: string;
    siteUid: string | null;
  }

  const props = defineProps<{
    isActive: boolean;
    route: RouteData | null;
    tokens: Record<string, string>;
    sites: Array<RouteSiteOption>;
    isMultiSite: boolean;
  }>();

  const emit = defineEmits<{
    close: [];
  }>();

  const mixedInput = shallowRef<InstanceType<typeof MixedInput> | null>(null);

  const form = useForm<RouteFormData>({
    uriParts: [''],
    template: '',
    siteUid: null,
  });

  const formSiteUid = computed({
    get: () => form.siteUid ?? '',
    set: (siteUid: string) => {
      form.siteUid = siteUid || null;
    },
  });

  const title = computed(() =>
    props.route ? t('Edit Route') : t('Create a new route')
  );

  const tokenOptions = computed<Array<MixedInputToken>>(() =>
    Object.entries(props.tokens).map(([name, value]) => ({name, value}))
  );

  const siteOptions = computed(() => [
    {label: t('Global'), value: ''},
    ...props.sites.map((site) => ({
      label: site.name,
      value: site.uid,
    })),
  ]);

  watch(
    () => props.isActive,
    (isActive) => {
      if (isActive) {
        resetForm();
        return;
      }

      form.clearErrors();
    }
  );

  function isToken(part: MixedInputPart): part is [string, string] {
    return Array.isArray(part);
  }

  function copyUriParts(parts: Array<MixedInputPart>): Array<MixedInputPart> {
    return parts.map((part) => (isToken(part) ? [part[0], part[1]] : part));
  }

  function resetForm() {
    form.clearErrors();
    form.uriParts = copyUriParts(
      props.route?.uriParts.length ? props.route.uriParts : ['']
    );
    form.template = props.route?.template ?? '';
    form.siteUid = props.route?.siteUid ?? null;
  }

  function closeModal() {
    emit('close');
  }

  function addUriToken(token: MixedInputToken) {
    mixedInput.value?.addToken(token);
  }

  function handleUriTokenClick(event: MouseEvent, token: MixedInputToken) {
    if (event.detail === 0) {
      addUriToken(token);
    }
  }

  function normalizedUriParts(): Array<MixedInputPart> {
    const parts = copyUriParts(form.uriParts);

    if (typeof parts[0] === 'string') {
      parts[0] = parts[0].replace(/^\/+/, '');
    }

    return parts.filter((part) =>
      typeof part === 'string' ? part !== '' : true
    );
  }

  function saveRoute() {
    const uriParts = normalizedUriParts();

    form.clearErrors();

    form
      .transform((data) => ({
        ...data,
        uriParts,
        siteUid: data.siteUid || null,
      }))
      .submit(props.route ? update(props.route.uid) : store(), {
        preserveScroll: true,
        onSuccess: () => {
          closeModal();
        },
      });
  }

  function deleteRoute() {
    if (!props.route) {
      return;
    }

    if (!confirm(t('Are you sure you want to delete this route?'))) {
      return;
    }

    form.delete(destroy(props.route.uid).url, {
      preserveScroll: true,
      onSuccess: () => {
        closeModal();
      },
    });
  }
</script>

<template>
  <ModalForm
    :is-active="isActive"
    :title="title"
    :loading="form.processing"
    :submit-label="t('Save')"
    width="md"
    @close="closeModal"
    @submit="saveRoute"
  >
    <template #header>
      <div class="route-modal-header">
        <h1>
          {{ title }}
        </h1>
      </div>
    </template>

    <div class="route-modal">
      <div class="route-uri-field">
        <div class="route-uri-field__label">
          {{ t('If the URI looks like this') }}:
        </div>

        <div class="route-uri-field__controls">
          <MixedInput
            ref="mixedInput"
            v-model="form.uriParts"
            class="route-uri-input"
            :invalid="!!form.errors.uriParts"
            :disabled="form.processing"
            :aria-label="t('URI')"
          >
            <template v-if="form.errors.uriParts" #error>
              <ul class="error-list">
                <li>{{ form.errors.uriParts }}</li>
              </ul>
            </template>
          </MixedInput>

          <Select
            v-if="isMultiSite"
            class="route-site-select"
            id="route-site"
            name="siteUid"
            v-model="formSiteUid"
            :options="siteOptions"
            :disabled="form.processing"
            :aria-label="t('Site')"
          />
        </div>

        <div class="route-token-picker">
          <h3>{{ t('Add a token') }}</h3>
          <button
            v-for="token in tokenOptions"
            :key="token.name"
            type="button"
            class="route-token route-token--button"
            :disabled="form.processing"
            @mousedown.prevent="addUriToken(token)"
            @click="handleUriTokenClick($event, token)"
          >
            {{ token.name }}
          </button>
        </div>
      </div>

      <CraftInput
        :label="t('Load this template')"
        id="route-template"
        name="template"
        v-model="form.template"
        dir="ltr"
        :disabled="form.processing"
        :error="form.errors.template"
        required
      />
    </div>

    <template #footer>
      <div class="route-modal-footer">
        <craft-button
          v-if="route"
          type="button"
          appearance="plain"
          class="route-delete-button"
          :disabled="form.processing"
          @click="deleteRoute"
        >
          {{ t('Delete') }}
        </craft-button>

        <div class="route-modal-footer__actions">
          <craft-button type="reset" appearance="plain" @click="closeModal">
            {{ t('Cancel') }}
          </craft-button>
          <craft-button
            type="submit"
            variant="primary"
            :loading="form.processing"
          >
            {{ t('Save') }}
          </craft-button>
        </div>
      </div>
    </template>
  </ModalForm>
</template>

<style scoped lang="scss">
  .route-token {
    align-items: center;
    background: var(--c-color-neutral-fill-normal);
    border: 0;
    border-radius: var(--c-radius-sm);
    color: var(--c-color-neutral-on-normal);
    display: inline-flex;
    font-family: var(--c-font-mono);
    font-size: var(--c-text-sm);
    gap: 0.25rem;
    line-height: 1.3;
    padding: 0.125rem 0.4rem;
  }

  .route-token--button {
    appearance: none;
    cursor: pointer;
  }

  .route-token--button:focus {
    box-shadow: 0 0 0 1px var(--c-surface-default);
    outline: 2px solid var(--c-text-link);
    outline-offset: 1px;
  }

  .route-modal-header {
    background: var(--c-color-neutral-fill-normal);
    border-bottom: 1px solid var(--c-color-neutral-border-quiet);
    padding: 24px;
  }

  .route-modal-header h1 {
    font-size: 18px;
    font-weight: 700;
    line-height: 1.25;
    margin: 0;
  }

  :deep(.cp-pane:has(.route-modal) .cp-pane__body) {
    padding: 0;
  }

  :deep(.content.w-md:has(.route-modal)) {
    width: 500px;
  }

  .route-modal {
    display: grid;
    gap: 22px;
    padding: 22px 24px 24px;
    width: 100%;
  }

  .route-uri-field {
    display: grid;
    gap: 10px;
  }

  .route-uri-field__label {
    font-weight: 600;
  }

  .route-uri-field__controls {
    align-items: flex-start;
    display: flex;
    gap: 8px;
  }

  .route-uri-input {
    flex: 1 1 auto;
    min-width: 0;
  }

  .route-site-select {
    flex: 0 0 134px;
  }

  .route-site-select :deep(select) {
    min-height: var(--c-size-control-md);
    width: 100%;
  }

  .route-token-picker {
    background: var(--c-surface-default);
    border-radius: var(--c-radius-lg);
    box-shadow: var(--c-shadow-sunken);
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    justify-content: center;
    padding: 13px 24px 14px;
  }

  .route-token-picker h3 {
    flex-basis: 100%;
    font-size: var(--c-text-sm);
    font-weight: 600;
    margin: 0 0 3px;
    text-align: center;
  }

  .route-token-picker .route-token {
    font-family: inherit;
    font-size: 12px;
    line-height: 16px;
    padding: 2px 7px;
  }

  .route-modal-footer {
    align-items: center;
    background: var(--c-color-neutral-fill-normal);
    border-top: 1px solid var(--c-color-neutral-border-quiet);
    display: flex;
    gap: var(--c-spacing-md);
    justify-content: space-between;
    min-height: 44px;
    padding: 6px 24px;
  }

  .route-modal-footer__actions {
    display: flex;
    gap: 8px;
    margin-left: auto;
  }

  .route-delete-button {
    color: var(--c-text-default);
  }

  @media (max-width: 720px) {
    .route-site-select {
      flex-basis: auto;
    }

    .route-uri-field__controls {
      display: grid;
    }
  }
</style>

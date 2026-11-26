<script setup lang="ts">
  /**
   * Shows an upload side by side with an image it looks like a copy of, with
   * the details that tell them apart, and the same choices as its upload
   * notification. With several matches, tabs switch between them, and Replace
   * replaces the one being shown.
   */
  import {t} from '@craftcms/ui';
  import {useElementSize, useObjectUrl} from '@vueuse/core';
  import {
    computed,
    onBeforeUnmount,
    shallowRef,
    useTemplateRef,
    watch,
  } from 'vue';
  import Modal from '@/common/components/Modal.vue';
  import type {ConflictResolution} from '@/modules/assets/assetMover';
  import '@/modules/assets/asset-conflict-resolution.ce';
  import {formatBytes} from './file-upload-notification.ce';
  import type {UploadDuplicate} from './upload-duplicates-notification.ce';

  interface Detail {
    label: string;
    value: string;
    /** Whether it differs from the new upload's. */
    different?: boolean;
  }

  const props = defineProps<{
    isActive: boolean;
    file: File;
    /** Where the file is being uploaded to. */
    location: string;
    duplicates: UploadDuplicate[];
  }>();
  const emit = defineEmits<{
    (event: 'close'): void;
    (event: 'closed'): void;
    (event: 'choose', choice: ConflictResolution, assetId: number): void;
  }>();

  const previewUrl = useObjectUrl(() => props.file);
  const previewFailed = shallowRef(false);
  const dimensions = shallowRef<{width: number; height: number}>();
  const selectedIndex = shallowRef(0);
  const duplicate = computed(() => props.duplicates[selectedIndex.value]!);

  const {width} = useElementSize(useTemplateRef('content'));

  /**
   * Whether the images are side by side, split by a hairline under a header
   * row of their labels. Narrower than that, they stack.
   */
  const ruled = computed(() => width.value >= 500);

  const title = computed(() =>
    t('Compare “{filename}”', {filename: props.file.name})
  );

  function extension(filename: string): string {
    return filename.includes('.')
      ? filename.split('.').pop()!.toLowerCase()
      : '';
  }

  function formatDimensions(
    width: number | null | undefined,
    height: number | null | undefined
  ): string {
    return width && height ? `${width} × ${height}` : t('Unknown');
  }

  function loaded(event: Event): void {
    const image = event.target as HTMLImageElement;
    dimensions.value = {
      width: image.naturalWidth,
      height: image.naturalHeight,
    };
  }

  const uploadDetails = computed<Detail[]>(() => [
    {label: t('Filename'), value: props.file.name},
    {
      label: t('Dimensions'),
      value: formatDimensions(
        dimensions.value?.width,
        dimensions.value?.height
      ),
    },
    {label: t('File size'), value: formatBytes(props.file.size)},
    {
      label: t('File type'),
      value: extension(props.file.name).toUpperCase() || t('Unknown'),
    },
    {label: t('Location'), value: props.location},
  ]);

  function duplicateDetails(duplicate: UploadDuplicate): Detail[] {
    const details: Detail[] = [
      {
        label: t('Filename'),
        value: duplicate.filename,
        different: duplicate.filename !== props.file.name,
      },
      {
        label: t('Dimensions'),
        value: formatDimensions(duplicate.width, duplicate.height),
        different: Boolean(
          dimensions.value &&
          duplicate.width &&
          duplicate.height &&
          (duplicate.width !== dimensions.value.width ||
            duplicate.height !== dimensions.value.height)
        ),
      },
      {
        label: t('File size'),
        value:
          duplicate.size === null ? t('Unknown') : formatBytes(duplicate.size),
        different:
          duplicate.size !== null && duplicate.size !== props.file.size,
      },
      {
        label: t('File type'),
        value: duplicate.extension.toUpperCase() || t('Unknown'),
        different:
          duplicate.extension.toLowerCase() !== extension(props.file.name),
      },
      {
        label: t('Location'),
        value: duplicate.location,
        different: duplicate.location !== props.location,
      },
    ];

    if (duplicate.dateCreated) {
      details.push({label: t('Uploaded'), value: duplicate.dateCreated});
    }

    if (duplicate.uploader) {
      details.push({label: t('Uploaded by'), value: duplicate.uploader});
    }

    return details;
  }

  /** Which details differ for the match being shown. */
  const differences = computed(() => {
    const different = duplicateDetails(duplicate.value)
      .filter((detail) => detail.different)
      .map((detail) => detail.label);

    return different.length
      ? t('These details differ: {details}', {details: different.join(', ')})
      : t('None of these details differ.');
  });

  /**
   * Says which details differ once it's open, and again when they change,
   * such as when another match is shown. The dialog makes the page behind it
   * inert, so this has its own live region rather than the page’s announcer,
   * and clears it once it's been read.
   */
  const status = shallowRef('');
  const opened = shallowRef(false);
  let clearStatus: ReturnType<typeof setTimeout> | undefined;

  watch([opened, differences], () => {
    if (!opened.value) {
      return;
    }
    status.value = differences.value;
    clearTimeout(clearStatus);
    clearStatus = setTimeout(() => (status.value = ''), 5000);
  });

  onBeforeUnmount(() => clearTimeout(clearStatus));

  function selectTab(event: Event): void {
    selectedIndex.value = (
      event.target as HTMLElement & {selectedIndex: number}
    ).selectedIndex;
  }

  function choose(event: Event): void {
    emit(
      'choose',
      (event as CustomEvent<ConflictResolution>).detail,
      duplicate.value.id
    );
  }
</script>

<template>
  <Modal
    :is-active="isActive"
    :label="title"
    width="2xl"
    :hold-height="false"
    @close="emit('close')"
    @opened="opened = true"
    @closed="emit('closed')"
  >
    <craft-pane :label="title" :class="{'upload-duplicates--ruled': ruled}">
      <craft-button
        slot="header-actions"
        type="button"
        icon="xmark-large"
        :aria-label="t('Close')"
        variant="plain"
        size="small"
        flush="inline-end"
        @click="emit('close')"
      ></craft-button>

      <div ref="content" class="upload-duplicates" data-upload-duplicates-modal>
        <p class="sr-only" role="status">{{ status }}</p>
        <div v-if="ruled" class="upload-duplicates__heads" aria-hidden="true">
          <span>{{ t('New upload') }}</span>
          <span>{{
            duplicates.length > 1 ? t('Matching images') : t('Existing image')
          }}</span>
        </div>
        <div class="upload-duplicates__columns">
          <section aria-labelledby="upload-duplicates-new">
            <h2
              id="upload-duplicates-new"
              class="upload-duplicates__heading"
              :class="{'sr-only': ruled}"
            >
              {{ t('New upload') }}
            </h2>
            <figure class="upload-duplicates__preview">
              <img
                v-if="previewUrl && !previewFailed"
                :src="previewUrl"
                :alt="file.name"
                @load="loaded"
                @error="previewFailed = true"
              />
              <figcaption v-else>{{ t('Preview not available.') }}</figcaption>
            </figure>
            <dl class="upload-duplicates__details">
              <div v-for="detail in uploadDetails" :key="detail.label">
                <dt>{{ detail.label }}</dt>
                <dd>{{ detail.value }}</dd>
              </div>
            </dl>
          </section>

          <section aria-labelledby="upload-duplicates-existing">
            <h2
              id="upload-duplicates-existing"
              class="upload-duplicates__heading"
              :class="{'sr-only': ruled}"
            >
              {{
                duplicates.length > 1
                  ? t('Matching images')
                  : t('Existing image')
              }}
            </h2>
            <component
              :is="duplicates.length > 1 ? 'craft-tabs' : 'div'"
              v-bind="
                duplicates.length > 1
                  ? {label: t('Matching images'), '.syncLocationHash': false}
                  : {}
              "
              @craft-tab-show="selectTab"
            >
              <template v-for="match in duplicates" :key="match.id">
                <craft-tab v-if="duplicates.length > 1" slot="tab">{{
                  match.title
                }}</craft-tab>
                <div slot="panel">
                  <div class="upload-duplicates__panel">
                    <figure class="upload-duplicates__preview">
                      <img
                        v-if="match.thumbUrl"
                        :src="match.thumbUrl"
                        :alt="match.title"
                      />
                      <figcaption v-else>
                        {{ t('Preview not available.') }}
                      </figcaption>
                    </figure>
                    <a
                      class="upload-duplicates__link"
                      :href="match.url"
                      target="_blank"
                      rel="noopener"
                      >{{ match.title
                      }}<craft-icon
                        name="arrow-up-right-from-square"
                        class="ms-sm"
                      ></craft-icon
                      ><span class="sr-only">{{
                        t('Opens in a new window')
                      }}</span></a
                    >
                    <dl class="upload-duplicates__details">
                      <div
                        v-for="detail in duplicateDetails(match)"
                        :key="detail.label"
                      >
                        <dt>{{ detail.label }}</dt>
                        <dd>
                          {{ detail.value }}
                          <craft-badge
                            v-if="detail.different"
                            fill="warning"
                            size="small"
                            no-prefix
                            >{{ t('Different') }}</craft-badge
                          >
                        </dd>
                      </div>
                    </dl>
                  </div>
                </div>
              </template>
            </component>
          </section>
        </div>
      </div>

      <craft-asset-conflict-resolution
        slot="actions"
        class="upload-duplicates__choices"
        :multiple="duplicates.length > 1"
        @craft-conflict-resolve="choose"
      ></craft-asset-conflict-resolution>
    </craft-pane>
  </Modal>
</template>

<style scoped>
  .upload-duplicates {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    overflow-wrap: anywhere;
  }

  .upload-duplicates__heads {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    column-gap: calc(var(--cp-container-padding) * 2 + 1px);
    margin-block-start: calc(var(--c-spacing-lg) * -1);
    margin-inline: calc(var(--c-spacing-lg) * -1);
    box-sizing: content-box;
    min-block-size: var(--c-size-control-sm);
    align-items: center;
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-lg);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
    background: var(--c-surface-raised);
    font-weight: 600;
  }

  .upload-duplicates__columns {
    display: grid;
    gap: var(--c-spacing-xl);
  }

  .upload-duplicates--ruled .upload-duplicates__columns {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    column-gap: calc(var(--cp-container-padding) * 2 + 1px);
  }

  .upload-duplicates--ruled::part(body) {
    background: linear-gradient(
        var(--c-color-neutral-border-quiet),
        var(--c-color-neutral-border-quiet)
      )
      center / 1px 100% no-repeat;
  }

  .upload-duplicates__heading {
    margin-block: 0 var(--c-spacing-sm);
    margin-inline: 0;
    font-size: var(--c-text-base);
  }

  .upload-duplicates__panel {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .upload-duplicates__preview {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    block-size: 16rem;
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
    background: var(--c-surface-raised);
    color: var(--c-text-quiet);
  }

  .upload-duplicates__preview img {
    max-inline-size: 100%;
    max-block-size: 100%;
    object-fit: contain;
  }

  .upload-duplicates__link {
    font-weight: 600;
  }

  .upload-duplicates__details {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xs);
    margin: 0;
  }

  .upload-duplicates__details dt {
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }

  .upload-duplicates__details dd {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-xs);
    align-items: center;
    margin: 0;
  }

  .upload-duplicates__choices {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-sm);
    justify-content: flex-end;
  }
</style>

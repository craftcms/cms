<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/cp';
  import ProjectConfigDiff from './ProjectConfigDiff.vue';
  import {useProjectConfig} from '@/composables/useProjectConfig';
  import Pane from '@/components/Pane/Pane.vue';
  import {Form} from '@inertiajs/vue3';
  import {discard, rebuild} from '@actions/Utilities/ProjectConfigController';
  import SyncConfigButton from '@/components/utilities/ProjectConfig/SyncConfigButton.vue';
  import TransitionFade from '@/components/TransitionFade.vue';

  const props = defineProps<{
    readOnly: boolean;
    invert: boolean;
    yamlExists: boolean;
    areChangesPending: boolean;
    entireConfig: string;
  }>();

  const {isDownloading, isDiscarding, discardChanges, downloadConfig} =
    useProjectConfig(props);

  const sectionTitle = computed(() => {
    if (!props.yamlExists) {
      return t('Generate YAML Files');
    }
    return props.invert ? t('Update YAML Files') : t('Apply YAML Changes');
  });

  const sectionDescription = computed(() => {
    if (!props.yamlExists) {
      return t(
        'Save the loaded project config data to YAML files in your {folder} folder.',
        {folder: '<code>config/project/</code>'}
      );
    }
    return props.invert
      ? t(
          'Update your project config YAML files to reflect the latest changes in the loaded project config.'
        )
      : t(
          'Apply changes in your project config YAML files to the loaded project config.'
        );
  });

  const noteMessage = computed(() => {
    return props.invert
      ? t(
          "Make sure you're not overwriting changes in the YAML files that were made on another environment."
        )
      : t(
          'Make sure you\'ve followed the <a href="{url}" target="_blank">Environment Setup</a> instructions before applying project config YAML changes.',
          {
            url: 'https://craftcms.com/docs/5.x/system/project-config.html#environment-setup',
          }
        );
  });
</script>

<template>
  <div class="project-config-utility">
    <!-- YAML Changes Section -->
    <section class="config-section">
      <h2>{{ sectionTitle }}</h2>
      <p v-html="sectionDescription" class="mb-3"></p>

      <div class="mt-3">
        <!-- YAML exists -->
        <template v-if="yamlExists">
          <!-- Changes pending -->
          <template v-if="areChangesPending">
            <!-- Diff viewer -->
            <ProjectConfigDiff :invert="invert" />

            <!-- Note/warning -->
            <craft-callout
              variant="info"
              v-html="noteMessage"
              class="my-2"
            ></craft-callout>

            <!-- Action buttons for invert mode (Update YAML) -->
            <div v-if="invert" class="buttons">
              <craft-button
                type="button"
                variant="secondary"
                :loading="isDiscarding"
                @click="discardChanges"
              >
                {{ t('Update YAML files') }}
              </craft-button>
              <SyncConfigButton :label="t('Apply YAML changes')" />
            </div>

            <!-- Action buttons for normal mode (Apply YAML) -->
            <div v-else class="buttons">
              <SyncConfigButton
                :label="t('Apply changes only')"
                variant="default"
              />
              <SyncConfigButton :force="true" />

              <craft-button
                v-if="!readOnly"
                type="button"
                :loading="isDiscarding"
                @click="discardChanges"
              >
                {{ t('Discard changes') }}
              </craft-button>
            </div>
          </template>

          <!-- No changes pending -->
          <template v-else>
            <craft-callout variant="success" icon="circle-check" class="my-3">
              {{
                t("There aren't any pending project config changes to apply.")
              }}
            </craft-callout>
            <div class="buttons">
              <SyncConfigButton
                :force="true"
                :label="t('Reapply everything')"
              />
            </div>
          </template>
        </template>

        <!-- YAML doesn't exist - Generate button -->
        <template v-else>
          <div class="buttons">
            <Form :action="discard()" #default="{processing}">
              <craft-button
                type="submit"
                variant="secondary"
                :loading="processing"
              >
                {{ t('Generate') }}
              </craft-button>
            </Form>
          </div>
        </template>
      </div>
    </section>

    <!-- Rebuild Config Section -->
    <template v-if="!readOnly">
      <hr />
      <section class="config-section">
        <h2>{{ t('Rebuild the Config') }}</h2>
        <p>
          {{
            t(
              'Rebuild the project config based on the data stored throughout the database.'
            )
          }}
        </p>
        <div class="buttons">
          <Form
            :action="rebuild()"
            method="post"
            #default="{processing, recentlySuccessful}"
          >
            <div class="flex gap-2 items-center">
              <craft-button
                type="submit"
                variant="default"
                :loading="processing"
              >
                {{ t('Rebuild') }}
              </craft-button>

              <TransitionFade>
                <template v-if="recentlySuccessful">
                  <craft-callout
                    variant="success"
                    icon="circle-check"
                    appearance="plain"
                    class="p-0"
                  >
                    {{ t('Config rebuilt.') }}
                  </craft-callout>
                </template>
              </TransitionFade>
            </div>
          </Form>
        </div>
      </section>
    </template>

    <!-- Loaded Config Section -->
    <hr />
    <section class="config-section">
      <h2>{{ t('Loaded Project Config Data') }}</h2>
      <Pane variant="code" tabindex="0" class="my-3">
        <pre><code>{{ entireConfig }}</code></pre>
      </Pane>
      <div class="buttons">
        <craft-button
          type="button"
          :loading="isDownloading"
          @click="downloadConfig"
        >
          <craft-icon name="download" slot="prefix"></craft-icon>
          {{ t('Download') }}
        </craft-button>
      </div>
    </section>
  </div>
</template>

<style scoped lang="scss">
  .project-config-utility {
    padding: var(--c-spacing-lg);
  }

  .config-viewer {
    max-height: 500px;
    overflow: auto;
    background-color: var(--c-color-neutral-fill-quiet);
    padding: var(--c-spacing-md);
    border-radius: var(--c-radius-md);
    border: 1px solid var(--c-color-neutral-border-quiet);
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 0.9rem;
  }

  .buttons {
    display: flex;
    gap: var(--c-spacing-sm);
    flex-wrap: wrap;
    align-items: center;
    margin-block-start: var(--c-spacing-md);
  }
</style>

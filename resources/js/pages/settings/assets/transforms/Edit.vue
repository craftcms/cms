<script setup lang="ts">
  import Pane from '@/common/components/Pane.vue';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import useCraftData from '@/common/composables/useCraftData';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import AppLayout from '@/common/layouts/AppLayout.vue';
  import {store as saveTransform} from '@actions/Settings/ImageTransformsController';
  import {t, toHandle} from '@craftcms/ui';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftInputColor from '@craftcms/ui/vue/CraftInputColor.vue';
  import CraftInputHandle from '@craftcms/ui/vue/CraftInputHandle.vue';
  import CraftSelect from '@craftcms/ui/vue/CraftSelect.vue';
  import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
  import {useForm} from '@inertiajs/vue3';
  import type {BaseOption} from '@/common/types';
  import {computed} from 'vue';
  import cropImageUrl from '/images/transforms/crop.svg';
  import fitImageUrl from '/images/transforms/fit.svg';
  import letterboxImageUrl from '/images/transforms/letterbox.svg';
  import stretchImageUrl from '/images/transforms/stretch.svg';

  interface QualityOption extends Omit<BaseOption, 'value'> {
    value: number;
  }

  const props = defineProps<{
    transform: CraftCms.Cms.Image.Data.ImageTransform;
    modeOptions: Array<BaseOption>;
    positionOptions: Array<BaseOption>;
    interlaceOptions: Array<BaseOption>;
    formatOptions: Array<BaseOption>;
    qualityOptions: Array<QualityOption>;
  }>();

  const {readOnly} = useCraftData();

  const form = useForm({
    transformId: props.transform.id,
    name: props.transform.name ?? '',
    handle: props.transform.handle ?? '',
    width: props.transform.width ?? '',
    height: props.transform.height ?? '',
    mode: props.transform.mode,
    position: props.transform.position,
    quality: props.transform.quality ?? '',
    interlace: props.transform.interlace,
    format: props.transform.format ?? '',
    fill:
      props.transform.fill && props.transform.fill !== 'transparent'
        ? props.transform.fill.replace(/^#/, '')
        : '',
    upscale: props.transform.upscale,
  });

  const modeImageUrls: Record<string, string> = {
    crop: cropImageUrl,
    fit: fitImageUrl,
    letterbox: letterboxImageUrl,
    stretch: stretchImageUrl,
  };

  const modeOptions = computed(() =>
    props.modeOptions.map((option) => ({
      ...option,
      imageUrl: modeImageUrls[option.value],
    }))
  );

  const positionLabel = computed(() =>
    form.mode === 'letterbox' ? t('Image Position') : t('Default Focal Point')
  );

  const shouldShowFillColor = computed(() => form.mode === 'letterbox');
  const shouldShowPosition = computed(
    () => form.mode === 'crop' || form.mode === 'letterbox'
  );

  function matchingQualityOptionValue(quality: string | number | null): string {
    const numericQuality = Number(quality);

    if (!numericQuality) {
      return '0';
    }

    const matchedValue = props.qualityOptions.reduce((matchedValue, option) => {
      return numericQuality >= option.value ? option.value : matchedValue;
    }, 10);

    return String(matchedValue);
  }

  const qualityPickerValue = computed({
    get: () => matchingQualityOptionValue(form.quality),
    set(value: string) {
      const numericValue = Number(value);

      form.quality = numericValue ? String(numericValue) : '';
    },
  });

  const handleGenerator = useInputGenerator(
    () => form.name,
    (value) => (form.handle = toHandle(value))
  );

  if (props.transform.id) {
    handleGenerator.stop();
  }

  const {save} = useSettingsSave(form, saveTransform);
</script>

<template>
  <AppLayout :form="form" @save="save">
    <Pane appearance="raised">
      <div class="grid gap-3">
        <CraftInput
          :label="t('Name')"
          id="name"
          name="name"
          v-model="form.name"
          :error="form.errors.name"
          :disabled="readOnly"
          required
          autofocus
        />

        <CraftInputHandle
          :label="t('Handle')"
          id="handle"
          name="handle"
          v-model="form.handle"
          :error="form.errors.handle"
          :disabled="readOnly"
          required
          @change="handleGenerator.markDirty()"
        />

        <div class="field">
          <div class="heading">
            <label id="mode-label">{{ t('Mode') }}</label>
          </div>

          <div
            class="mode-options"
            role="radiogroup"
            aria-labelledby="mode-label"
          >
            <label
              v-for="option in modeOptions"
              :key="option.value"
              class="mode-option"
              :class="{'mode-option--selected': form.mode === option.value}"
            >
              <img
                class="mode-option__image"
                :src="option.imageUrl"
                width="113"
                height="75"
                alt=""
              />
              <span class="mode-option__label">
                <input
                  v-model="form.mode"
                  type="radio"
                  name="mode"
                  :value="option.value"
                  :disabled="readOnly"
                />
                {{ option.label }}
              </span>
            </label>
          </div>

          <ul v-if="form.errors.mode" class="error-list">
            <li>{{ form.errors.mode }}</li>
          </ul>
        </div>

        <CraftInputColor
          v-if="shouldShowFillColor"
          id="fill"
          name="fill"
          :label="t('Fill Color')"
          v-model="form.fill"
          :error="form.errors.fill"
          :disabled="readOnly"
        />

        <div v-if="shouldShowPosition" class="field">
          <div class="heading">
            <label id="position-label">{{ positionLabel }}</label>
          </div>

          <div
            class="position-grid"
            role="radiogroup"
            aria-labelledby="position-label"
          >
            <label
              v-for="option in positionOptions"
              :key="option.value"
              class="position-option"
              :class="{
                'position-option--selected': form.position === option.value,
                'position-option--disabled': readOnly,
              }"
              :title="option.label"
            >
              <input
                v-model="form.position"
                type="radio"
                name="position"
                :value="option.value"
                :disabled="readOnly"
                :aria-label="option.label"
              />
              <span class="position-option__marker"></span>
            </label>
          </div>

          <ul v-if="form.errors.position" class="error-list">
            <li>{{ form.errors.position }}</li>
          </ul>
        </div>

        <CraftInput
          :label="t('Width')"
          id="width"
          name="width"
          v-model="form.width"
          :error="form.errors.width"
          :disabled="readOnly"
          inputmode="numeric"
          size="5"
        />

        <CraftInput
          :label="t('Height')"
          id="height"
          name="height"
          v-model="form.height"
          :error="form.errors.height"
          :disabled="readOnly"
          inputmode="numeric"
          size="5"
        />

        <CraftSwitch
          :label="t('Allow Upscaling')"
          id="upscale"
          name="upscale"
          v-model="form.upscale"
          :error="form.errors.upscale"
          :disabled="readOnly"
        />

        <div class="quality-field">
          <CraftSelect
            :label="t('Quality')"
            id="quality-picker"
            v-model="qualityPickerValue"
            :error="form.errors.quality"
            :disabled="readOnly"
          >
            <select slot="input">
              <option value="0">{{ t('Auto') }}</option>
              <option
                v-for="option in qualityOptions"
                :key="option.value"
                :value="String(option.value)"
              >
                {{ option.label }}
              </option>
            </select>
          </CraftSelect>

          <CraftInput
            v-show="qualityPickerValue !== '0'"
            id="quality"
            name="quality"
            v-model="form.quality"
            :error="form.errors.quality"
            :disabled="readOnly"
            type="number"
            min="1"
            max="100"
            size="5"
          />
        </div>

        <CraftSelect
          :label="t('Interlacing')"
          id="interlace"
          name="interlace"
          v-model="form.interlace"
          :error="form.errors.interlace"
          :disabled="readOnly"
        >
          <select slot="input">
            <option
              v-for="option in interlaceOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </CraftSelect>

        <CraftSelect
          :label="t('Image Format')"
          :help-text="t('The image format that transformed images should use.')"
          id="format"
          name="format"
          v-model="form.format"
          :error="form.errors.format"
          :disabled="readOnly"
        >
          <select slot="input">
            <option
              v-for="option in formatOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </CraftSelect>
      </div>
    </Pane>
  </AppLayout>
</template>

<style scoped>
  .field > .heading {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 5px;
    min-block-size: calc(22rem / 16);
    margin-block: -5px 5px;
  }

  .field > .heading > label {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 5px;
    font-weight: 700;
  }

  .mode-options {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-lg);
  }

  .mode-option {
    display: grid;
    gap: var(--c-spacing-xs);
    inline-size: 113px;
    cursor: pointer;
    text-align: center;
  }

  .mode-option__image {
    inline-size: 113px;
    block-size: 75px;
    border: 1px solid transparent;
    border-radius: var(--c-radius-md);
  }

  .mode-option--selected .mode-option__image {
    border-color: var(--c-color-accent-border-loud);
  }

  .mode-option__label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--c-spacing-xs);
  }

  .quality-field {
    display: flex;
    align-items: end;
    gap: var(--c-spacing-sm);
  }

  .position-grid {
    display: grid;
    grid-template-columns: repeat(3, 2rem);
    gap: var(--c-spacing-xs);
    inline-size: max-content;
  }

  .position-option {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    inline-size: 2rem;
    block-size: 2rem;
    border: 1px solid var(--c-form-control-border-color);
    border-radius: var(--c-radius-sm);
    background-color: var(--c-form-control-fill);
    cursor: pointer;
  }

  .position-option--disabled {
    cursor: default;
    opacity: 0.65;
  }

  .position-option input {
    position: absolute;
    inset: 0;
    margin: 0;
    opacity: 0;
  }

  .position-option__marker {
    inline-size: 0.5rem;
    block-size: 0.5rem;
    border-radius: 50%;
    background-color: var(--c-text-quiet);
    opacity: 0.4;
  }

  .position-option--selected {
    border-color: var(--c-color-accent-border-loud);
    background-color: var(--c-color-accent-fill-quiet);
  }

  .position-option--selected .position-option__marker {
    background-color: var(--c-color-accent-on-quiet);
    opacity: 1;
  }

  .position-option input:focus-visible + .position-option__marker {
    box-shadow: 0 0 0 3px var(--c-color-accent-border-normal);
  }
</style>

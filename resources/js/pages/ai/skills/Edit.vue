<script setup lang="ts">
  /**
   * Creates or edits one of the AI assistant’s skills. The AI Guidance & Skills
   * utility opens it in a slideout.
   */
  import {ref, useId, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import {useForm} from '@inertiajs/vue3';
  import {
    store,
    update,
  } from '@/actions/CraftCms/Cms/Ai/Http/Controllers/SkillsController';
  import CpContainer from '@/common/components/CpContainer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import type {AgentSkillData} from '@/modules/ai/skills/types';
  import type CraftInput from '@craftcms/ui/components/input/input';
  import '@craftcms/ui/components/input/input';
  import {instructionsToolbarButtons} from '@/modules/markdown-field/toolbarPresets';
  import {fieldId} from '@/modules/ui/runtime';
  import '@/modules/markdown-field/markdown-field';

  const props = defineProps<{
    skill: AgentSkillData | null;
  }>();

  const titleId = useId();
  const descriptionId = useId();
  const bodyId = useId();

  const form = useForm({
    title: props.skill?.title ?? '',
    description: props.skill?.description ?? '',
    body: props.skill?.body ?? '',
  });

  // Set once a new skill is saved, so saving again updates it: from the
  // response in a slideout, and from the edit page the server sends a page to,
  // which keeps this component.
  const skillId = ref(props.skill?.id ?? null);

  watch(
    () => props.skill?.id,
    (id) => {
      skillId.value = id ?? skillId.value;
    }
  );

  const {save} = useSettingsSave(
    form,
    () => (skillId.value ? update({skillId: skillId.value}) : store()),
    {
      onSuccess: (data?: {skill?: AgentSkillData}) => {
        skillId.value = data?.skill?.id ?? skillId.value;
        form.defaults();
      },
    }
  );

  useAppLayout(() => ({
    title: skillId.value ? t('Edit Skill') : t('New Skill'),
    form,
    onSave: save,
  }));

  function onTitleChanged(event: CustomEvent<{initialize?: boolean}>): void {
    if (event.detail?.initialize) {
      return;
    }

    form.title = String((event.target as CraftInput).modelValue ?? '');
  }

  function onDescriptionChanged(
    event: CustomEvent<{initialize?: boolean}>
  ): void {
    if (event.detail?.initialize) {
      return;
    }

    form.description = String((event.target as CraftInput).modelValue ?? '');
  }
</script>

<template>
  <CpContainer class="py-lg">
    <craft-field-group>
      <craft-field
        :id="fieldId(['title'])"
        :label="t('Title')"
        .hasErrors="Boolean(form.errors.title)"
        width="full"
        required
      >
        <craft-input
          slot="input"
          .modelValue="form.title"
          maxlength="255"
          required
          :aria-invalid="form.errors.title ? 'true' : undefined"
          @model-value-changed="onTitleChanged"
        >
          <input :id="titleId" slot="input" />
        </craft-input>
        <ul v-if="form.errors.title" slot="feedback" class="error-list">
          <li>{{ form.errors.title }}</li>
        </ul>
      </craft-field>

      <craft-field
        :id="fieldId(['description'])"
        :label="t('Description')"
        :help-text="
          t(
            'What the skill does and when to use it, in a sentence. The AI assistant reads it to decide when to follow the skill.'
          )
        "
        .hasErrors="Boolean(form.errors.description)"
        width="full"
        required
      >
        <craft-input
          slot="input"
          .modelValue="form.description"
          maxlength="255"
          required
          :aria-invalid="form.errors.description ? 'true' : undefined"
          @model-value-changed="onDescriptionChanged"
        >
          <input :id="descriptionId" slot="input" />
        </craft-input>
        <ul v-if="form.errors.description" slot="feedback" class="error-list">
          <li>{{ form.errors.description }}</li>
        </ul>
      </craft-field>

      <craft-field
        :id="fieldId(['body'])"
        :label="t('Instructions')"
        :help-text="
          t(
            'What the AI assistant should do, and how. The assistant follows them when a request matches the skill, or when it’s chosen with /.'
          )
        "
        .hasErrors="Boolean(form.errors.body)"
        required
      >
        <craft-markdown-field
          show-toolbar
          sanitize-html
          .toolbarButtons="instructionsToolbarButtons"
          :id="bodyId"
          slot="input"
          :label="t('Instructions')"
          :rows="16"
          required
          :aria-invalid="form.errors.body ? 'true' : undefined"
          .value="form.body"
          @input="form.body = ($event.target as HTMLTextAreaElement).value"
        />
        <ul v-if="form.errors.body" slot="feedback" class="error-list">
          <li>{{ form.errors.body }}</li>
        </ul>
      </craft-field>
    </craft-field-group>
  </CpContainer>
</template>

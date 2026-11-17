<script setup lang="ts">
  /**
   * The AI Assistant screen of the user’s account: their own guidance, which the
   * assistant follows in every conversation.
   */
  import {useId} from 'vue';
  import {t} from '@craftcms/ui';
  import {useForm} from '@inertiajs/vue3';
  import {update} from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AccountController';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import UserScreen from '@/modules/user/components/UserScreen.vue';
  import {instructionsToolbarButtons} from '@/modules/markdown-field/toolbarPresets';
  import {fieldId} from '@/modules/ui/runtime';
  import '@/modules/markdown-field/markdown-field';

  defineOptions({
    inheritAttrs: false,
  });

  const props = defineProps<{
    guidance: string;
  }>();

  const guidanceId = useId();

  const form = useForm({guidance: props.guidance});
  const {save} = useSettingsSave(form, update);

  // The screen has no page to return to, so Save already keeps the user here.
  useAppLayout({form, onSave: save, defaultFormActions: []});
</script>

<template>
  <UserScreen>
    <craft-field-group>
      <craft-field
        :id="fieldId(['guidance'])"
        :label="t('Personal guidance')"
        :help-text="
          t(
            'How the AI assistant should work and write for you, such as the language to reply in. It follows this in every conversation, along with the guidance for the site.'
          )
        "
        .hasErrors="Boolean(form.errors.guidance)"
      >
        <craft-markdown-field
          show-toolbar
          sanitize-html
          .toolbarButtons="instructionsToolbarButtons"
          :id="guidanceId"
          slot="input"
          :label="t('Personal guidance')"
          :rows="8"
          :aria-invalid="form.errors.guidance ? 'true' : undefined"
          .value="form.guidance"
          @input="form.guidance = ($event.target as HTMLTextAreaElement).value"
        />
        <ul v-if="form.errors.guidance" slot="feedback" class="error-list">
          <li>{{ form.errors.guidance }}</li>
        </ul>
      </craft-field>
    </craft-field-group>
  </UserScreen>
</template>

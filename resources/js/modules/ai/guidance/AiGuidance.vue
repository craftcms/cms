<script setup lang="ts">
  /**
   * The Guidance page of the AI Guidance & Skills utility: the guidance for every
   * site and for each site the user can edit, saved together.
   */
  import {useId} from 'vue';
  import {t} from '@craftcms/ui';
  import {useForm} from '@inertiajs/vue3';
  import {store} from '@/actions/CraftCms/Cms/Ai/Http/Controllers/GuidanceController';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import CpLink from '@/common/components/CpLink.vue';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import type {AiGuidanceScope} from '@/modules/ai/guidance/types';
  import {instructionsToolbarButtons} from '@/modules/markdown-field/toolbarPresets';
  import {fieldId} from '@/modules/ui/runtime';
  import '@/modules/markdown-field/markdown-field';

  const props = defineProps<{
    scopes: Array<AiGuidanceScope>;
    /** The account screen where the user writes their own guidance, if they have it. */
    personalGuidanceUrl?: string | null;
    subnav: Array<CraftCms.Cms.Cp.Data.NavItem>;
  }>();

  const idPrefix = useId();

  /** Single-site installs only have the guidance for all sites. */
  const isMultiSite = props.scopes.length > 1;

  const form = useForm({
    guidance: props.scopes.map(({siteId, heading, body, editable}) => ({
      siteId,
      body,
      // Not saved; they label the field and lock it.
      heading,
      editable,
    })),
  });

  const {save} = useSettingsSave(form, store, {
    // Only the guidance the user can edit is sent, as the server turns away the rest.
    transform: (data) => ({
      guidance: data.guidance
        .filter(({editable}) => editable)
        .map(({siteId, body}) => ({siteId, body})),
    }),
    onSuccess: () => form.defaults(),
  });

  // The utility has no page to return to, so Save already keeps the user here.
  useAppLayout({
    form,
    onSave: save,
    defaultFormActions: [],
    subnav: props.subnav,
  });

  function helpText(siteId: number | null): string {
    if (!isMultiSite) {
      return t(
        'How AI should work and write, such as your voice, style, and terms.'
      );
    }

    return siteId === null
      ? t(
          'How AI should work and write in every site, such as your voice, style, and terms.'
        )
      : t(
          'What’s particular to this site, such as its language’s conventions. AI follows it along with the guidance for all sites.'
        );
  }

  /** The indexes of the guidance the user can edit, as the server numbers it. */
  const editableIndexes = props.scopes.flatMap(({editable}, index) =>
    editable ? [index] : []
  );

  /** The path of a field’s errors, as the server numbers the guidance it was sent. */
  function errorPath(index: number): string[] | null {
    const position = editableIndexes.indexOf(index);

    return position === -1 ? null : ['guidance', String(position), 'body'];
  }

  function error(index: number): string | undefined {
    const path = errorPath(index);

    return path
      ? (form.errors as Record<string, string | undefined>)[path.join('.')]
      : undefined;
  }

  /** The id the error summary links a field’s errors to. */
  function errorTargetId(index: number): string | undefined {
    const path = errorPath(index);

    return path ? fieldId(path) : undefined;
  }
</script>

<template>
  <div>
    <p class="mb-4">
      {{
        isMultiSite
          ? t(
              'The AI assistant and MCP clients follow this guidance when they work with content: the guidance for all sites, and the guidance for the site the content is in.'
            )
          : t(
              'The AI assistant and MCP clients follow this guidance when they work with content.'
            )
      }}
      <CpLink v-if="personalGuidanceUrl" :href="personalGuidanceUrl">{{
        t('Add personal guidance on your account.')
      }}</CpLink>
    </p>

    <craft-field-group>
      <craft-field
        v-for="(scope, index) in form.guidance"
        :id="errorTargetId(index)"
        :key="scope.siteId ?? 'all'"
        :label="scope.heading"
        :help-text="helpText(scope.siteId)"
        .hasErrors="Boolean(error(index))"
      >
        <craft-markdown-field
          show-toolbar
          sanitize-html
          .toolbarButtons="instructionsToolbarButtons"
          :id="`${idPrefix}-${index}`"
          slot="input"
          :label="scope.heading"
          :rows="4"
          :readonly="!scope.editable"
          :aria-invalid="error(index) ? 'true' : undefined"
          .value="scope.body"
          @input="scope.body = ($event.target as HTMLTextAreaElement).value"
        />
        <span v-if="!scope.editable" slot="tip">{{
          scope.siteId === null
            ? t('You need permission to edit every site.')
            : t('You don’t have permission to edit this site.')
        }}</span>
        <ul v-if="error(index)" slot="feedback" class="error-list">
          <li>{{ error(index) }}</li>
        </ul>
      </craft-field>
    </craft-field-group>
  </div>
</template>

<script setup lang="ts">
  /**
   * The Skills page of the AI Guidance & Skills utility: a card for each skill,
   * whose screen opens in a slideout. Skills save themselves, so the page has
   * no Save button.
   */
  import {nextTick, ref, useTemplateRef} from 'vue';
  import {t} from '@craftcms/ui';
  import {
    destroy,
    index,
  } from '@/actions/CraftCms/Cms/Ai/Http/Controllers/SkillsController';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {openSlideout} from '@/common/slideouts';
  import {http, isHttpError} from '@craftcms/ui/utilities/api/http';
  import {showMessagesFromResponse} from '@/modules/messages';
  import type {AgentSkillData} from '@/modules/ai/skills/types';

  const props = defineProps<{
    skills: Array<AgentSkillData>;
    createSkillUrl: string;
    subnav: Array<CraftCms.Cms.Cp.Data.NavItem>;
  }>();

  useAppLayout({subnav: props.subnav});

  const newSkillButton = useTemplateRef<HTMLElement>('newSkillButton');

  const skills = ref(props.skills.map((skill) => ({...skill})));

  async function refreshSkills(): Promise<void> {
    const response = await http.request<{skills: AgentSkillData[]}>({
      url: index().url,
      method: 'get',
    });

    skills.value = response.data.skills;
  }

  /** Opens a skill’s screen, or the new skill screen, in a slideout. */
  function openEditor(url: string, event: MouseEvent): void {
    void openSlideout(url, {
      opener: event.currentTarget as HTMLElement,
      onSaved: () => void refreshSkills(),
    });
  }

  async function deleteSkill(skill: AgentSkillData): Promise<void> {
    if (
      !window.confirm(
        t('Are you sure you want to delete the “{name}” skill?', {
          name: skill.title,
        })
      )
    ) {
      return;
    }

    const route = destroy({skillId: skill.id});

    try {
      const response = await http.request({
        url: route.url,
        method: route.method,
      });
      showMessagesFromResponse(response.data);
    } catch (error) {
      const message = isHttpError<{message?: string}>(error)
        ? error.response?.data?.message
        : undefined;
      Craft.cp?.displayError?.(message ?? t('Couldn’t delete the skill.'));

      // Someone else may have deleted it already.
      await refreshSkills();

      return;
    }

    skills.value = skills.value.filter(({id}) => id !== skill.id);

    // The focused Delete button is gone with its skill.
    await nextTick();
    newSkillButton.value?.focus();
  }
</script>

<template>
  <div class="grid gap-3">
    <!-- A translated string with fixed markup for the key. -->
    <p
      v-html="
        t(
          'Instructions for kinds of tasks. The AI assistant follows a skill when a request matches it, and users can choose one by typing {key} in the chat.',
          {key: '<kbd>/</kbd>'}
        )
      "
    ></p>

    <!-- New skill comes after the skills, or under the message when there are none, as admin tables' does. -->
    <craft-empty v-if="skills.length === 0" :label="t('No skills exist yet.')">
      <craft-button
        ref="newSkillButton"
        type="button"
        variant="dashed"
        icon="plus"
        @click="openEditor(createSkillUrl, $event)"
      >
        {{ t('New skill') }}
      </craft-button>
    </craft-empty>

    <template v-else>
      <ul class="grid gap-3" role="list">
        <li v-for="skill in skills" :key="skill.id">
          <craft-card>
            <span slot="label" class="font-medium">{{ skill.title }}</span>

            <div slot="actions" class="flex gap-1">
              <craft-button
                type="button"
                variant="plain"
                icon
                size="small"
                @click="openEditor(skill.editUrl, $event)"
              >
                <craft-icon
                  name="pencil"
                  :label="t('Edit “{name}”', {name: skill.title})"
                ></craft-icon>
              </craft-button>
              <craft-button
                type="button"
                variant="plain"
                icon
                size="small"
                @click="deleteSkill(skill)"
              >
                <craft-icon
                  name="trash"
                  :label="t('Delete “{name}”', {name: skill.title})"
                ></craft-icon>
              </craft-button>
            </div>

            <p class="skill-description">{{ skill.description }}</p>
          </craft-card>
        </li>
      </ul>
      <craft-button
        ref="newSkillButton"
        class="justify-self-start"
        type="button"
        variant="dashed"
        icon="plus"
        @click="openEditor(createSkillUrl, $event)"
      >
        {{ t('New skill') }}
      </craft-button>
    </template>
  </div>
</template>

<style scoped>
  .skill-description {
    overflow-wrap: anywhere;
  }
</style>

import {t} from '@craftcms/ui';
import type {TextExpanderOption} from '@craftcms/ui/components/text-expander/text-expander';
import AssistantController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AssistantController';
import {assistantApi} from '@/bootstrap/assistant';

/**
 * The type of the items for skills chosen with `/`. The server puts their
 * instructions in the model’s instructions, rather than resolving them with a
 * context type.
 */
export const SKILL_CONTEXT_TYPE = 'craft:skill';

function addChosenSkill(option: Readonly<TextExpanderOption>): void {
  const skillId = (option.data as {skillId?: unknown} | undefined)?.skillId;

  if (typeof skillId !== 'number') {
    return;
  }

  assistantApi.addContext({
    type: SKILL_CONTEXT_TYPE,
    key: `${SKILL_CONTEXT_TYPE}:${skillId}`,
    label: option.label,
    icon: 'layer-group',
    data: {skillId},
    // Kept with the message, so later messages follow the skill too.
    reference: () => ({skillId}),
  });
}

// `/` for the skills the user can use, which adds the chosen one to the context.
assistantApi.registerTextExpander({
  id: 'craft:skills',
  trigger: '/',
  label: t('Skills'),
  source: () => AssistantController.skills.url(),
  limit: 10,
  select: addChosenSkill,
});

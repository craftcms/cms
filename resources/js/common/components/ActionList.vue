<script setup lang="ts">
  /**
   * Renders a list of `ActionItem` descriptors as real controls.
   *
   * The point is that one set of descriptors can appear in two shapes: buttons
   * where there's room for them, menu items where there isn't. `craft-button`
   * and `craft-action-item` both take the same declarative `action` /
   * `feedback` primitives (the `Actionable` mixin), so an action behaves
   * identically either way and only its rendering changes.
   *
   * This is deliberately not `ActionMenu`, which owns a whole menu. This
   * renders items a caller places itself — `ActionMenu` uses it for the items
   * inside its own `content` slot.
   */
  import {computed, type Component} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import type {
    ActionItem,
    ActionItemButton,
    ActionItemLink,
    ActionItems,
  } from '@/common/types';

  /** One descriptor flattened to what the elements are actually given. */
  interface RenderedAction {
    kind: 'hr' | 'heading' | 'display' | 'link' | 'button';
    is?: Component;
    href?: string;
    external?: boolean;
    label?: string;
    onClick?: (event: Event) => void;
    /**
     * Everything optional, with the unset keys left out entirely. Binding an
     * `undefined` would overwrite the element's own default rather than leave
     * it alone: Vue assigns it as a property, and Lit reflects the result — so
     * an unset `variant` would land as `variant=""` instead of `neutral`.
     *
     * `.`-prefixed keys are set as DOM properties, which `action`, `feedback`
     * and `shortcut` need since they're objects rather than strings.
     */
    attrs: Record<string, unknown>;
  }

  const {
    actions = [],
    as = 'craft-button',
    size,
  } = defineProps<{
    actions?: ActionItems;
    /** The element each action renders as. */
    as?: 'craft-button' | 'craft-action-item';
    /** Passed to `craft-button`; menu items take their size from the menu. */
    size?: 'small' | 'medium' | 'large';
  }>();

  function defined(attrs: Record<string, unknown>): Record<string, unknown> {
    return Object.fromEntries(
      Object.entries(attrs).filter(([, value]) => value !== undefined)
    );
  }

  /**
   * Whether this list is a choice rather than a set of commands. The checkmark
   * gutter goes on every item once any of them claims to be current, so the
   * labels line up instead of the selected one sitting alone in its own
   * indent.
   */
  const selectable = computed(() =>
    actions
      .flatMap((action) => (action.type === 'group' ? action.items : [action]))
      .some((action) => 'selected' in action && action.selected != null)
  );

  /**
   * `hr` only means something in a list of menu items — between buttons it's a
   * stray rule — so it's dropped when rendering as buttons rather than asking
   * every caller to keep two lists.
   */
  const rendered = computed<Array<RenderedAction>>(() =>
    actions.flatMap(renderAction)
  );

  function renderAction(action: ActionItem): Array<RenderedAction> {
    if (action.type === 'hr') {
      return as === 'craft-action-item' ? [{kind: 'hr', attrs: {}}] : [];
    }

    if (action.type === 'display') {
      return [{kind: 'display', is: action.is, attrs: {}}];
    }

    if (action.type === 'group') {
      // The heading and its items come back as siblings rather than nested in
      // a container: a menu's roving focus and search filter walk the item
      // list, and a wrapper would hide the grouped items from both.
      return [
        ...(action.heading && as === 'craft-action-item'
          ? [{kind: 'heading' as const, label: action.heading, attrs: {}}]
          : []),
        ...action.items.flatMap(renderAction),
      ];
    }

    return renderControl(action);
  }

  function renderControl(
    action: ActionItemButton | ActionItemLink
  ): Array<RenderedAction> {
    {
      const isItem = as === 'craft-action-item';
      const marks = isItem && selectable.value;
      const attrs = defined({
        icon: action.icon,
        // `craft-action-item` draws the checkmark gutter for its `checkbox`
        // type only; `craft-button` has no such notion.
        type: marks ? 'checkbox' : undefined,
        checked: marks ? Boolean(action.selected) : undefined,
        variant: action.variant,
        'icon-color': action.iconColor,
        '.action': action.action,
        '.feedback': action.feedback,
        // Menu affordances; `craft-button` renders neither.
        '.shortcut': isItem ? action.shortcut : undefined,
        'data-keywords': isItem ? action.keywords : undefined,
      });

      if (action.type === 'link') {
        return [
          {
            kind: 'link',
            href: action.href,
            external: action.external,
            label: action.label,
            onClick: action.onClick,
            attrs: defined({...attrs, size}),
          },
        ];
      }

      return [
        {
          kind: 'button',
          label: action.label,
          onClick: action.onClick,
          attrs: defined({
            ...attrs,
            disabled: action.disabled,
            size: isItem ? undefined : size,
          }),
        },
      ];
    }
  }
</script>

<template>
  <template v-for="(action, index) in rendered" :key="index">
    <hr v-if="action.kind === 'hr'" class="action-list__separator" />

    <!-- `role="presentation"`: it labels its items visually but must never
      take focus or be matched by the menu's item selector. -->
    <div
      v-else-if="action.kind === 'heading'"
      class="action-list__heading"
      role="presentation"
    >
      {{ action.label }}
    </div>

    <component v-else-if="action.kind === 'display'" :is="action.is" />

    <!-- Routed through `CpLink` so a link action is an Inertia visit. The
      element keeps its `href`, so the anchor in its shadow root still gives
      real link semantics; `CpLink` adds the handler on the host that cancels
      the navigation in favour of a visit. -->
    <CpLink
      v-else-if="action.kind === 'link'"
      v-bind="action.attrs"
      :as="as"
      :href="action.href!"
      :inertia="!action.external"
      appearance="button"
      @click="action.onClick"
    >
      {{ action.label }}
    </CpLink>

    <component
      v-else
      type="button"
      v-bind="action.attrs"
      :is="as"
      @click="action.onClick"
    >
      {{ action.label }}
    </component>
  </template>
</template>

<style scoped lang="css">
  /* `craft-action-menu` styles the separators it generates itself inline, and
     its shadow `::slotted()` rule only zeroes the margin — so a slotted rule
     has to bring its own line to match. */
  .action-list__separator {
    margin: 0;
    border: 0;
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  /* Matching what `craft-action-menu` applies to the headings it generates
     itself, which it has to set inline for the same reason: the shadow
     `::slotted()` rule can't reach inside the slotted content. */
  .action-list__heading {
    padding: var(--c-spacing-xs) var(--c-spacing-md);
    color: var(--c-text-subtle);
    font-size: var(--c-text-xs);
    font-weight: 600;
  }
</style>

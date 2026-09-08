<script setup lang="ts">
  /**
   * Renders a list of `ActionItem` descriptors as real controls.
   *
   * One set of descriptors, three presentations: buttons where there's room
   * for them, menu items where there isn't, and nav items when the same list
   * is the navigation. `craft-button`, `craft-action-item` and
   * `craft-nav-item` all take the same declarative `action` / `feedback`
   * primitives (the `Actionable` mixin), so an action behaves identically
   * whichever it is and only its rendering changes.
   *
   * The nav presentation is the one that nests: it draws `subnav` recursively,
   * where the flat presentations ignore it. That's the whole reason the
   * descriptor carries children — so a nav and the menu standing in for it are
   * the same description, not two that drifted.
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
    ActionItemGroup,
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
    mode = 'trail',
    iconOnly = false,
    depth = 0,
  } = defineProps<{
    actions?: ActionItems;
    /** The element each action renders as. */
    as?: 'craft-button' | 'craft-action-item' | 'craft-nav-item';
    /** Passed to `craft-button`; menu items take their size from the menu. */
    size?: 'small' | 'medium' | 'large';
    /**
     * Nav only. Flyouts and indentation do different jobs: a flyout is for
     * *getting* somewhere, indentation is for *being* somewhere. `trail`
     * expands the branch you're in and flyouts the rest; `flyout` and `inline`
     * force one or the other.
     */
    mode?: 'trail' | 'flyout' | 'inline';
    /** Nav only. Collapsed to a rail: labels become tooltips, all flyout. */
    iconOnly?: boolean;
    /**
     * Nav only. Nothing about the behaviour depends on it — only the bullet
     * that stands in for a missing icon below the root, which keeps a subnav's
     * labels aligned with its parent's.
     */
    depth?: number;
  }>();

  const isNav = computed(() => as === 'craft-nav-item');

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
    const isItem = as === 'craft-action-item';
    const marks = isItem && selectable.value;
    const attrs = defined({
      icon: action.icon,
      // `craft-action-item` draws the checkmark gutter for its `checkbox`
      // type only; the others have no such notion.
      type: marks ? 'checkbox' : undefined,
      checked: marks ? Boolean(action.selected) : undefined,
      variant: action.variant,
      'icon-color': action.iconColor,
      '.action': action.action,
      '.feedback': action.feedback,
      // Menu affordances; neither of the others renders them.
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

  // ---------------------------------------------------------------------------
  // Nav presentation
  // ---------------------------------------------------------------------------

  type NavItem = ActionItemGroup | ActionItemButton | ActionItemLink;

  /** The nav draws every descriptor that stands for somewhere in the tree. */
  const navActions = computed<Array<NavItem>>(() =>
    actions.filter(
      (action): action is NavItem =>
        action.type !== 'hr' && action.type !== 'display'
    )
  );

  /**
   * A group keeps its children in `items`; anything else keeps them in
   * `subnav`. Two keys for the same idea, because a group is a heading over a
   * run of siblings while a subnav hangs off the thing above it.
   */
  function childrenOf(action: NavItem): ActionItems {
    return action.type === 'group' ? action.items : (action.subnav ?? []);
  }

  function isGroup(action: NavItem): boolean {
    return action.type === 'group';
  }

  function labelOf(action: NavItem): string {
    return action.type === 'group' ? (action.heading ?? '') : action.label;
  }

  function hrefOf(action: NavItem): string | undefined {
    return action.type === 'link' ? action.href : undefined;
  }

  function isSelected(action: NavItem): boolean {
    return action.type !== 'group' && Boolean(action.selected);
  }

  /** Whether the selection is this item or anywhere beneath it. */
  function onTrail(action: NavItem): boolean {
    return (
      isSelected(action) ||
      childrenOf(action).some(
        (child) =>
          child.type !== 'hr' &&
          child.type !== 'display' &&
          onTrail(child as NavItem)
      )
    );
  }

  function expanded(action: NavItem): boolean {
    if (isGroup(action)) {
      return true;
    }

    return mode === 'inline' || (mode === 'trail' && onTrail(action));
  }

  function subnavDisplay(action: NavItem): 'inline' | 'flyout' {
    return expanded(action) ? 'inline' : 'flyout';
  }

  /**
   * Expanded branches start open. A group has no toggle, so this is ignored
   * there; `craft-nav-item` re-reads it when it changes, which is what lets
   * the open branch follow the selection across an Inertia visit.
   */
  function initialState(action: NavItem): 'open' | 'closed' {
    return expanded(action) ? 'open' : 'closed';
  }

  /**
   * Below the root, an item with no icon takes a bullet in the icon slot so
   * its label lines up with the icon-bearing items around it. A group is a
   * heading rather than a destination, so it doesn't take one.
   */
  function bulleted(action: NavItem): boolean {
    return depth > 0 && !action.icon && !isGroup(action);
  }

  /**
   * Whether the descriptor owns its own click.
   *
   * `CpLink` takes its props from Inertia's `Link`, which declares `onClick` —
   * so a handler passed through it is swallowed as a prop rather than reaching
   * the DOM. An element index's sources arrive this way: a real href, so the
   * item is a focusable anchor, but selecting one is a partial visit that
   * keeps the list's scroll and state rather than a navigation.
   */
  function ownsClick(action: NavItem): boolean {
    return action.type === 'link' && Boolean(action.onClick);
  }

  /**
   * Handlers ride in with the attributes rather than as `@click`, because a
   * template handler has to be a plain member expression: Vue compiles
   * anything else — a ternary picking between a function and `undefined`, say
   * — as an inline statement, so it evaluates the expression on click and
   * throws the function away.
   */
  function navAttrs(action: NavItem): Record<string, unknown> {
    if (action.type === 'group') {
      return defined({
        group: true,
        'subnav-display': subnavDisplay(action),
        'initial-state': initialState(action),
      });
    }

    return defined({
      icon: action.icon,
      'icon-only': iconOnly || undefined,
      'subnav-display': subnavDisplay(action),
      'initial-state': initialState(action),
      onClick: action.onClick,
      onMousedown: action.onMousedown,
      ...action.attrs,
    });
  }
</script>

<template>
  <!-- The nav nests, so it's drawn recursively rather than flattened. -->
  <template v-if="isNav">
    <template
      v-for="(action, index) in navActions"
      :key="hrefOf(action) ?? labelOf(action) ?? index"
    >
      <!-- Straight to the element when the descriptor owns its click, or when
        there's nowhere to go: a group heads its children rather than being
        somewhere to follow, so it renders as a static item. -->
      <craft-nav-item
        v-if="!hrefOf(action) || ownsClick(action)"
        v-bind="navAttrs(action)"
        :href="hrefOf(action)"
        :active.prop="isSelected(action)"
        :indicator.prop="Boolean(action.indicator)"
      >
        <span v-if="bulleted(action)" class="nav-bullet" slot="icon"></span>

        {{ labelOf(action) }}

        <craft-nav-list v-if="childrenOf(action).length" slot="subnav">
          <ActionList
            :actions="childrenOf(action)"
            as="craft-nav-item"
            :mode="mode"
            :depth="isGroup(action) ? depth : depth + 1"
          />
        </craft-nav-list>
      </craft-nav-item>

      <CpLink
        v-else
        v-bind="navAttrs(action)"
        as="craft-nav-item"
        :href="hrefOf(action)!"
        :active.prop="isSelected(action)"
        :indicator.prop="Boolean(action.indicator)"
        :external.prop="action.type === 'link' && action.external"
        :inertia="!(action.type === 'link' && action.external)"
      >
        <span v-if="bulleted(action)" class="nav-bullet" slot="icon"></span>

        {{ labelOf(action) }}

        <craft-nav-list v-if="childrenOf(action).length" slot="subnav">
          <ActionList
            :actions="childrenOf(action)"
            as="craft-nav-item"
            :mode="mode"
            :depth="depth + 1"
          />
        </craft-nav-list>
      </CpLink>
    </template>
  </template>

  <template v-else v-for="(action, index) in rendered" :key="index">
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

    <!-- Routed through `CpLink` so a link action is an Inertia visit, unless
      the descriptor owns its click (see `ownsClick`). The element keeps its
      `href`, so the anchor in its shadow root still gives real link semantics;
      `CpLink` adds the handler on the host that cancels the navigation in
      favour of a visit. -->
    <component
      v-else-if="action.kind === 'link' && action.onClick"
      v-bind="action.attrs"
      :is="as"
      :href="action.href"
      @click="action.onClick"
    >
      {{ action.label }}
    </component>

    <CpLink
      v-else-if="action.kind === 'link'"
      v-bind="action.attrs"
      :as="as"
      :href="action.href!"
      :inertia="!action.external"
      appearance="button"
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

  .nav-bullet {
    --nav-item-indicator-size: calc(4rem / 16);
    display: inline-flex;
    width: var(--nav-item-indicator-size);
    border-radius: var(--c-radius-full);
    aspect-ratio: 1;
    background-color: currentcolor;
  }

  .nav-bullet[active] {
    --nav-item-indicator-size: calc(6rem / 16);
  }
</style>

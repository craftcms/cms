<script setup lang="ts">
  /**
   * Renders a list of `ActionItem` descriptors as real controls.
   *
   * One set of descriptors, three presentations: buttons where there's room
   * for them, menu items where there isn't, and nav items when the same list
   * is the navigation.
   *
   * `craft-button` and `craft-action-item` each take the declarative `action`
   * / `feedback` primitives, so an action behaves the same either way and only
   * its rendering changes. `craft-nav-item` doesn't take them at all, so a nav
   * descriptor carrying one does nothing — they're left off there rather than
   * passed and ignored.
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
   * An item that brought its own SVG rather than naming an icon — a plugin's
   * `icon.svg`. It goes in the same slot the named icon would fill.
   */
  function iconSvgOf(action: NavItem): string | undefined {
    return isGroup(action) ? undefined : action.iconSvg;
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
   * What a nav item renders as.
   *
   * Straight to the element when there's nothing for `CpLink` to do: a group
   * heads its children rather than being somewhere to follow, and a descriptor
   * that owns its click handles its own navigation. Everything else goes
   * through `CpLink`, which turns the href into an Inertia visit.
   */
  function navIs(action: NavItem): Component | string {
    return !hrefOf(action) || ownsClick(action) ? 'craft-nav-item' : CpLink;
  }

  /**
   * Everything the item is given, so the two cases differ in their bindings
   * rather than in a second copy of the markup — the label, the icon and the
   * recursive subnav are the same either way, and were drifting apart.
   */
  function navBindings(action: NavItem): Record<string, unknown> {
    const shared = {
      ...navAttrs(action),
      href: hrefOf(action),
      '.active': isSelected(action),
      '.indicator': Boolean(action.indicator),
    };

    if (navIs(action) === 'craft-nav-item') {
      return shared;
    }

    const external = action.type === 'link' && Boolean(action.external);

    return {
      ...shared,
      as: 'craft-nav-item',
      '.external': external,
      inertia: !external,
    };
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
        // Collapsed, a heading has no room for its name and becomes the rule
        // between the runs it separates — but only if it's told it's collapsed.
        'icon-only': iconOnly || undefined,
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
    <component
      :is="navIs(action)"
      v-for="(action, index) in navActions"
      :key="hrefOf(action) || labelOf(action) || index"
      v-bind="navBindings(action)"
    >
      <craft-icon
        v-if="iconSvgOf(action)"
        class="nav-icon"
        slot="icon"
        v-html="iconSvgOf(action)"
      ></craft-icon>

      {{ labelOf(action) }}

      <craft-nav-list v-if="childrenOf(action).length" slot="subnav">
        <ActionList
          :actions="childrenOf(action)"
          as="craft-nav-item"
          :mode="mode"
          :icon-only="iconOnly && expanded(action)"
        />
      </craft-nav-list>
    </component>
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
</style>

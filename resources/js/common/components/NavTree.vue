<script setup lang="ts">
  /**
   * One level of the CP navigation, rendered recursively.
   *
   * Renders `craft.nav` in `MainNav`. The server only supplies two levels so
   * far — one level plus whatever a plugin hands over — but the deeper tree
   * the navigation map will produce is exercised by fixtures in
   * `NavTree.stories.ts`.
   *
   * Flyouts and indentation do different jobs. A flyout is for *getting*
   * somewhere: it reaches any branch from wherever you are without disturbing
   * the sidebar. Indentation is for *being* somewhere: the branch you're in
   * stays expanded so its shape, and your place in it, is on screen.
   *
   * So the trail to the selected item renders inline and open, and every other
   * branch flyouts on hover. Depth doesn't come into it — only one branch is
   * ever expanded, so the column never has to hold more than one level of
   * nesting per level of trail.
   *
   * A `group` is a heading, not a destination: it never opens a flyout of its
   * own, so its children render wherever the group itself landed — inline in
   * the sidebar, or inside its parent's flyout.
   */
  import CpLink from '@/common/components/CpLink.vue';

  type NavNode = CraftCms.Cms.Cp.Data.ActionItem;

  const {
    items,
    mode = 'trail',
    iconOnly = false,
    depth = 0,
  } = defineProps<{
    items: Array<NavNode>;
    /**
     * `trail` expands the branch you're in and flyouts the rest. `flyout` and
     * `inline` force one or the other, which is mostly useful for comparing
     * them in Storybook.
     */
    mode?: 'trail' | 'flyout' | 'inline';
    /** Collapsed to a rail: labels become tooltips and every subnav flyouts. */
    iconOnly?: boolean;
    /**
     * How deep this level sits. Nothing about the behaviour depends on it —
     * only the bullet that stands in for a missing icon below the root, which
     * is what keeps a subnav's labels aligned with its parent's.
     */
    depth?: number;
  }>();

  /** `subnav` is `false` when the server hasn't resolved this branch yet. */
  function childrenOf(item: NavNode): Array<NavNode> {
    return Array.isArray(item.subnav) ? item.subnav : [];
  }

  /** Whether the selection is this item or anywhere beneath it. */
  function onTrail(item: NavNode): boolean {
    return item.selected || childrenOf(item).some(onTrail);
  }

  function expanded(item: NavNode): boolean {
    if (item.group) {
      return true;
    }

    return mode === 'inline' || (mode === 'trail' && onTrail(item));
  }

  function subnavDisplay(item: NavNode): 'inline' | 'flyout' {
    return expanded(item) ? 'inline' : 'flyout';
  }

  /**
   * Expanded branches start open. A group has no toggle, so this is ignored
   * there; `craft-nav-item` re-reads it when it changes, which is what lets
   * the open branch follow the selection across an Inertia visit.
   */
  function initialState(item: NavNode): 'open' | 'closed' {
    return expanded(item) ? 'open' : 'closed';
  }

  /**
   * Below the root, an item with no icon takes a bullet in the icon slot so
   * its label lines up with the icon-bearing items around it. A group is a
   * heading rather than a destination, so it doesn't take one.
   */
  function bulleted(item: NavNode): boolean {
    return depth > 0 && !item.icon && !item.group;
  }
</script>

<template>
  <template v-for="item in items" :key="item.href ?? item.label ?? ''">
    <!-- A group heads its children rather than being somewhere to go, so it
      has no href and renders as a static, non-collapsible heading. -->
    <craft-nav-item
      v-if="item.group || !item.href"
      :group="item.group || undefined"
      :icon="item.icon || undefined"
      :icon-only="iconOnly || undefined"
      :subnav-display="subnavDisplay(item)"
      :initial-state="initialState(item)"
      :active.prop="item.selected"
      :indicator.prop="!!item.badgeCount"
    >
      <span v-if="bulleted(item)" class="nav-bullet" slot="icon"></span>

      {{ item.label }}

      <craft-nav-list v-if="childrenOf(item).length" slot="subnav">
        <NavTree :items="childrenOf(item)" :mode="mode" :depth="depth + 1" />
      </craft-nav-list>
    </craft-nav-item>

    <CpLink
      v-else
      as="craft-nav-item"
      :href="item.href"
      :icon="item.icon || undefined"
      :icon-only="iconOnly || undefined"
      :subnav-display="subnavDisplay(item)"
      :initial-state="initialState(item)"
      :active.prop="item.selected"
      :indicator.prop="!!item.badgeCount"
      :external.prop="item.external"
      :inertia="!item.external"
    >
      <span v-if="bulleted(item)" class="nav-bullet" slot="icon"></span>

      {{ item.label }}

      <craft-nav-list v-if="childrenOf(item).length" slot="subnav">
        <NavTree :items="childrenOf(item)" :mode="mode" :depth="depth + 1" />
      </craft-nav-list>
    </CpLink>
  </template>
</template>

<style scoped lang="scss">
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

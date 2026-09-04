<script setup lang="ts">
  /**
   * One level of the CP navigation, rendered recursively.
   *
   * PROTOTYPE. Nothing on the server produces a tree this deep yet — the nav
   * prop is one level plus whatever a plugin hands over — so this is driven by
   * fixtures (see `NavTree.stories.ts`) while the shape of the navigation map
   * is still being settled.
   *
   * Depth runs out of horizontal room long before it runs out of levels, so an
   * item at or past `flyoutFromDepth` puts its children in a flyout beside it
   * instead of indenting them. Above that, levels nest inline behind a
   * disclosure toggle. A collapsed rail flyouts from the top, since it has no
   * room to indent at all.
   *
   * A `group` is a heading, not a destination: it never opens a flyout of its
   * own and never advances the depth, so its children render inline wherever
   * the group itself landed.
   */
  import CpLink from '@/common/components/CpLink.vue';

  type NavNode = CraftCms.Cms.Cp.Data.ActionItem;

  const {
    items,
    depth = 0,
    iconOnly = false,
    flyoutFromDepth = 1,
  } = defineProps<{
    items: Array<NavNode>;
    /** How deep this level sits. The root renders at 0. */
    depth?: number;
    /** Collapsed to a rail: labels become tooltips and every subnav flyouts. */
    iconOnly?: boolean;
    /** The first depth whose children move into a flyout. */
    flyoutFromDepth?: number;
  }>();

  /** `subnav` is `false` when the server hasn't resolved this branch yet. */
  function childrenOf(item: NavNode): Array<NavNode> {
    return Array.isArray(item.subnav) ? item.subnav : [];
  }

  function isFlyout(item: NavNode): boolean {
    return !item.group && depth >= flyoutFromDepth;
  }

  // A group renders inline where it stands, so it doesn't start a new level.
  function childDepth(item: NavNode): number {
    return item.group ? depth : depth + 1;
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
      :subnav-display="isFlyout(item) ? 'flyout' : 'inline'"
      :active.prop="item.selected"
      :indicator.prop="!!item.badgeCount"
    >
      {{ item.label }}

      <craft-nav-list v-if="childrenOf(item).length" slot="subnav">
        <NavTree
          :items="childrenOf(item)"
          :depth="childDepth(item)"
          :flyout-from-depth="flyoutFromDepth"
        />
      </craft-nav-list>
    </craft-nav-item>

    <CpLink
      v-else
      as="craft-nav-item"
      :href="item.href"
      :icon="item.icon || undefined"
      :icon-only="iconOnly || undefined"
      :subnav-display="isFlyout(item) ? 'flyout' : 'inline'"
      :active.prop="item.selected"
      :indicator.prop="!!item.badgeCount"
      :external.prop="item.external"
      :inertia="!item.external"
    >
      {{ item.label }}

      <craft-nav-list v-if="childrenOf(item).length" slot="subnav">
        <NavTree
          :items="childrenOf(item)"
          :depth="childDepth(item)"
          :flyout-from-depth="flyoutFromDepth"
        />
      </craft-nav-list>
    </CpLink>
  </template>
</template>

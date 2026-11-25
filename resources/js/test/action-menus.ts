/**
 * Announces every action menu under `root` as opening, the way `craft-popover`
 * does before it opens, so lazy `ActionMenu`s render their items. They appear
 * after Vue's next update.
 */
export function openActionMenus(root: ParentNode): void {
  root
    .querySelectorAll('craft-action-menu')
    .forEach((menu) =>
      menu.dispatchEvent(new CustomEvent('craft-before-show'))
    );
}

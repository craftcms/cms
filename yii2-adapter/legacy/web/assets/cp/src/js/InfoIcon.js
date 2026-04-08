/** global: Craft */
/** global: Garnish */
/**
 * Info icon class
 */
Craft.InfoIcon = Garnish.Base.extend({
  /**
   *
   * @param HTMLElement icon
   */
  init: function (icon) {
    this.icon = document.createElement('craft-info-icon');
    if (icon.classList.contains('disabled')) {
      this.icon.setAttribute('disabled', '');
    }
    this.icon.innerHTML = icon.innerHTML;

    icon.replaceWith(this.icon);
  },
});

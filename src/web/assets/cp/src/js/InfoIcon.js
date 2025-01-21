/** global: Craft */
/** global: Garnish */
/**
 * Info icon class
 */
Craft.InfoIcon = Garnish.Base.extend({
  $container: null,
  $icon: null,
  $liveRegion: null,
  content: null,
  hud: null,

  init: function (icon) {
    if ($(icon).hasClass('disabled')) {
      return;
    }

    this.$icon = $(icon);
    this.$liveRegion = $('<span/>', {
      role: 'status',
      class: 'visually-hidden',
    });

    if (this.$icon.data('infoicon')) {
      console.warn('Double-instantiating an info icon on an element');
      this.content = this.$icon.data('infoicon').content;
      this.$icon.data('infoicon').destroy();
    } else {
      this.content = this.$icon.html();
      this.$icon
        .html('')
        .attr({
          tabindex: 0,
          role: 'button',
          type: 'button',
          'aria-label': Craft.t('app', 'More info'),
        })
        .wrap(
          $('<span/>', {
            class: 'infoicon-container',
          })
        );

      this.$container = this.$icon.parent();
      this.$container.append(this.$liveRegion);
    }

    this.$icon.data('infoicon', this);

    if (
      this.$icon[0].previousSibling &&
      this.$icon[0].previousSibling.nodeType === Node.TEXT_NODE
    ) {
      // Make sure it's in a .nowrap container
      const $parent = this.$icon.parent();
      if (!$parent.hasClass('nowrap')) {
        // Find the last word in the text
        const m = this.$icon[0].previousSibling.nodeValue.match(/[^\s\-]+\s*$/);
        if (m) {
          this.$icon[0].previousSibling.nodeValue =
            this.$icon[0].previousSibling.nodeValue.substring(0, m.index);
          $('<span/>', {
            class: 'nowrap',
            html: m[0].replace(/\s+$/, '') + ' ',
          })
            .insertAfter(this.$icon[0].previousSibling)
            .append(this.$icon);
        }
      }
    }

    this.addListener(this.$icon, 'click', (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      this.showHud();
    });

    this.addListener(this.$icon, 'keydown', (ev) => {
      if (
        !(this.hud && this.hud.showing) &&
        [Garnish.SPACE_KEY, Garnish.RETURN_KEY].includes(ev.keyCode)
      ) {
        ev.preventDefault();
        ev.stopPropagation();
        this.showHud();
      }
    });
  },

  showHud: function (ev) {
    if (!this.hud) {
      this.hud = new Garnish.HUD(this.$icon, this.content, {
        hudClass: 'hud info-hud',
        closeOtherHUDs: false,
        onShow: () => {
          Garnish.uiLayerManager.registerShortcut(Garnish.SPACE_KEY, () => {
            this.hud.hide();
          });

          this.$liveRegion.html('');

          setTimeout(() => {
            this.$liveRegion.html(this.content);
          }, 200);
        },
        onHide: () => {
          this.$liveRegion.html('');
        },
      });
      Craft.initUiElements(this.hud.$body);
    } else {
      this.hud.show();
    }
  },

  destroy: function () {
    this.$icon.removeData('infoicon');
    this.base();
  },
});

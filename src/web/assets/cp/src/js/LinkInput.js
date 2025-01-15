/** global: Craft */
/** global: Garnish */
const punycode = require('punycode/');

/**
 * Link Input
 */
Craft.LinkInput = Garnish.Base.extend(
  {
    /** @type Craft.LinkField */
    field: null,

    /** @type {jQuery} */
    $container: null,
    /** @type {jQuery} */
    $field: null,
    /** @type {jQuery|null} */
    $chip: null,
    /** @type {jQuery|null} */
    $textInput: null,
    /** @type {jQuery} */
    $hiddenInput: null,
    /** @type {Garnish.DisclosureMenu} */
    menu: null,

    init: function (container, settings) {
      this.$container = $(container);
      this.setSettings(settings, Craft.LinkInput.defaults);

      this.$container.data('linkInput', this);
      this.field = this.$container
        .closest('[data-link-field]')
        .parent()
        .data('linkField');
      this.$chip = this.$container.children('.chip');
      this.$textInput = this.$container.children('.text');
      this.$hiddenInput = this.$container.children('input[type=hidden]');

      if (this.$chip.length) {
        this.menu = this.$chip
          .find('.action-btn')
          .disclosureMenu()
          .data('disclosureMenu');
        this.initChip();
      } else {
        this.initTextInput();
      }

      this.addListener(this.$container, 'click', (ev) => {
        if (
          this.$chip?.length &&
          !['A', 'BUTTON'].includes(ev.target.nodeName)
        ) {
          this.switchToTextInput();
          this.$textInput.focus();
        }
      });
    },

    hasPrefix: function (value) {
      value = value.toLowerCase();
      for (const prefix of this.settings.prefixes) {
        if (Craft.startsWith(value, prefix, true)) {
          return true;
        }
      }
      return false;
    },

    ensurePrefix: function (value) {
      if (this.settings.prefixes.length && !this.hasPrefix(value)) {
        return this.settings.prefixes[0] + value;
      }
      return value;
    },

    removePrefix: function (value) {
      for (const prefix of this.settings.prefixes) {
        value = Craft.removeLeft(value, prefix, true);
      }
      return value;
    },

    removeFirstPrefix: function (value) {
      if (this.settings.prefixes.length) {
        return Craft.removeLeft(value, this.settings.prefixes[0], true);
      }
      return value;
    },

    createChip: function (value) {
      const label = this.removePrefix(value);
      const menuId = `menu-${Math.floor(Math.random() * 1000000)}`;

      this.reset();
      this.$chip = $(`
<div class="chip small">
  <div class="chip-content">
    <a href="${Craft.escapeHtml(value)}" rel="noopener" target="_blank">
      ${Craft.escapeHtml(label)}
    </a>
    <div class="chip-actions">
      <button class="btn action-btn" type="button" aria-controls="${menuId}"
          aria-label="${Craft.t('app', 'Actions')}"
          data-disclosure-trigger data-icon="ellipsis"></button>
      <div id="${menuId}" class="menu menu--disclosure"></div>
    </div>
  </div>
</div>
`).prependTo(this.$container);

      this.menu = this.$chip
        .find('.action-btn')
        .disclosureMenu()
        .data('disclosureMenu');
      this.initChip();
    },

    createTextInput: function (value) {
      this.reset();
      this.$textInput = Craft.ui
        .createTextInput(this.settings.inputAttributes)
        .attr('name', this.settings.inputName)
        .val(value)
        .prependTo(this.$container);
      this.initTextInput();
      this.$textInput.trigger('input');
    },

    switchToTextInput: function () {
      // only remove the first prefix, if set; otherwise the wrong prefix will get added back.
      const value = this.removeFirstPrefix(this.$hiddenInput.val());
      this.createTextInput(value);
    },

    initTextInput: function () {
      this.addListener(this.$textInput, 'input', () => {
        const value = this.normalize(this.$textInput.val());
        this.$hiddenInput.val(value);
        this.field.updateLabel(this.removePrefix(value));
      });

      this.addListener(this.$textInput, 'blur', () => {
        this.maybeSwitchToChip();
      });
      this.addListener(this.$textInput, 'keydown', (ev) => {
        if (ev.keyCode === Garnish.ESC_KEY && this.maybeSwitchToChip()) {
          ev.stopPropagation();
          this.$chip.find('a').focus();
        }
      });
    },

    normalize: function (value) {
      value = Craft.trim(value);
      if (!value) {
        return '';
      }
      const prefixed = this.ensurePrefix(value);
      return this.validate(prefixed) ? prefixed : value;
    },

    validate: function (value) {
      value = punycode.toASCII(value);
      return !!value.match(new RegExp(this.settings.pattern, 'i'));
    },

    maybeSwitchToChip: function () {
      if (!this.$textInput?.length) {
        return;
      }

      const value = this.normalize(this.$textInput.val());
      if (value && this.validate(value)) {
        this.createChip(value);
        return true;
      }

      return false;
    },

    initChip: function () {
      const viewAction = this.menu.addItem({
        label: Craft.t('app', 'View in a new tab'),
        icon: 'share',
      });
      const editAction = this.menu.addItem({
        label: Craft.t('app', 'Edit'),
        icon: 'pencil',
      });
      this.menu.addHr();
      this.menu.addGroup();
      const removeAction = this.menu.addItem({
        label: 'Remove',
        icon: 'xmark',
        destructive: true,
      });

      this.addListener(viewAction, 'activate', () => {
        window.open(this.$chip.find('a').attr('href'));
      });

      this.addListener(editAction, 'activate', () => {
        this.switchToTextInput();
        this.$textInput.focus();
      });

      this.addListener(removeAction, 'activate', () => {
        this.createTextInput('');
        this.$textInput.focus();
      });
    },

    reset: function () {
      this.$textInput?.remove();
      this.$chip?.remove();
      this.menu?.destroy();
      this.$textInput = this.$chip = this.menu = null;
    },
  },
  {
    defaults: {
      prefixes: null,
      pattern: null,
      textInputAttributes: {},
    },
  }
);

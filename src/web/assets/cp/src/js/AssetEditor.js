/** global: Craft */
/** global: Garnish */
/**
 * Asset index class
 */
Craft.AssetEditor = Craft.BaseElementEditor.extend({
  $filenameInput: null,

  originalBasename: null,
  originalExtension: null,
  reloadIndex: false,

  init: function (element, settings) {
    this.on('updateForm', () => {
      this.addListener(
        this.$sidebar.find('.preview-thumb-container .edit-btn'),
        'click',
        'showImageEditor'
      );
      this.addListener(
        this.$sidebar.find('.preview-thumb-container .preview-btn'),
        'click',
        'showImagePreview'
      );

      this.$filenameInput = this.$sidebar.find('.filename');
      this.addListener(this.$filenameInput, 'focus', 'selectFilename');
    });

    this.on('closeSlideout', () => {
      if (this.reloadIndex) {
        if (this.settings.elementIndex) {
          this.settings.elementIndex.updateElements();
        } else if (this.settings.input) {
          this.settings.input.refreshThumbnail(this.$element.data('id'));
        }
      }
    });

    this.base(element, settings);

    this.settings.validators.push(() => this.validateExtension());
  },

  showImageEditor: function () {
    new Craft.AssetImageEditor(this.$element.data('id'), {
      onSave: () => {
        this.reloadIndex = true;
        this.load();
      },
    });
  },

  showImagePreview: function () {
    var settings = {};

    if (this.$element.data('image-width')) {
      settings.startingWidth = this.$element.data('image-width');
      settings.startingHeight = this.$element.data('image-height');
    }

    new Craft.PreviewFileModal(this.$element.data('id'), null, settings);
  },

  selectFilename: function () {
    if (typeof this.$filenameInput[0].selectionStart === 'undefined') {
      return;
    }

    const {basename, extension} = this._parseFilename();

    if (this.originalBasename === null) {
      this.originalBasename = basename;
      this.originalExtension = extension;
    }

    this.$filenameInput[0].selectionStart = 0;
    this.$filenameInput[0].selectionEnd = basename.length;

    // Prevent the selection from changing by the mouseup event
    this.$filenameInput.one('mouseup.keepselection', (ev) => {
      ev.preventDefault();
    });
    setTimeout(() => {
      this.$filenameInput.off('mouseup.keepselection');
    }, 500);
  },

  validateExtension: function () {
    if (this.originalBasename === null) {
      return true;
    }

    const {basename, extension} = this._parseFilename();

    if (extension === this.originalExtension) {
      return true;
    }

    // No extension?
    if (!extension) {
      // If filename changed as well, assume removal of extension a mistake
      if (this.originalFilename !== basename) {
        this.$filenameInput.val(
          `${Craft.rtrim(basename, '.')}.${this.originalExtension}`
        );
        return true;
      }

      // If filename hasn't changed, make sure they want to remove extension
      return confirm(
        Craft.t(
          'app',
          'Are you sure you want to remove the extension “.{ext}”?',
          {
            ext: this.originalExtension,
          }
        )
      );
    }

    // If the extension has changed, make sure it's intentional
    return confirm(
      Craft.t(
        'app',
        'Are you sure you want to change the extension from “.{oldExt}” to “.{newExt}”?',
        {
          oldExt: this.originalExtension,
          newExt: extension,
        }
      )
    );
  },

  _parseFilename: function () {
    const parts = this.$filenameInput.val().split('.');
    const extension = parts.length > 1 ? parts.pop() : null;
    const basename = parts.join('.');
    return {basename, extension};
  },
});

// Register it!
Craft.registerElementEditorClass('craft\\elements\\Asset', Craft.AssetEditor);

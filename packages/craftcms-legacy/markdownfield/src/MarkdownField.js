import EasyMDE from 'easymde';
import CodeMirror from 'codemirror';
import 'easymde/dist/easymde.min.css';
import './MarkdownField.scss';

(function ($) {
  Craft.MarkdownField = Garnish.Base.extend(
    {
      settings: null,
      editor: null,
      assetSelectorModal: null,
      previewRequestId: 0,
      previewTimeout: null,

      init: function (fieldId, settings) {
        this.setSettings(settings, Craft.MarkdownField.defaults);

        this.$textarea = $('#' + fieldId);

        if (!this.$textarea.length) {
          return;
        }

        const rows = parseInt(this.$textarea.attr('rows') || 8, 10);
        const toolbarItems = this.toolbarItems();

        this.editor = new EasyMDE({
          autoDownloadFontAwesome: false,
          autoRefresh: {
            delay: 300,
          },
          autosave: {
            enabled: false,
          },
          element: this.$textarea[0],
          forceSync: true,
          indentWithTabs: false,
          minHeight: `${Math.max(rows, 4) * 1.5}em`,
          previewImagesInEditor: false,
          previewRender: (plainText, preview) => {
            this.renderPreview(plainText, preview);

            return null;
          },
          promptURLs: true,
          sideBySideFullscreen: false,
          spellChecker: false,
          status: false,
          syncSideBySidePreviewScroll: true,
          toolbar:
            this.settings.toolbar && toolbarItems.length ? toolbarItems : false,
          uploadImage: false,
        });

        this.editor.codemirror.addKeyMap({
          'Cmd-S': () => this.passSaveShortcut(),
          'Ctrl-S': () => this.passSaveShortcut(),
        });

        Craft.MarkdownField.registerInstance(this);
      },

      toolbarItems: function () {
        const selectedButtons = new Set(this.settings.toolbarButtons ?? []);
        if (!this.settings.assetSources?.length) {
          selectedButtons.delete('asset');
        }

        const toolbarItems = [];

        for (const group of this.toolbarButtonGroups()) {
          const groupItems = group.filter((item) =>
            selectedButtons.has(item.name)
          );

          if (!groupItems.length) {
            continue;
          }

          if (toolbarItems.length) {
            toolbarItems.push('|');
          }

          toolbarItems.push(...groupItems);
        }

        return toolbarItems;
      },

      toolbarButtonGroups: function () {
        return [
          [
            this.toolbarButton(
              'bold',
              EasyMDE.toggleBold,
              'bold',
              Craft.t('app', 'Bold')
            ),
            this.toolbarButton(
              'italic',
              EasyMDE.toggleItalic,
              'italic',
              Craft.t('app', 'Italic')
            ),
            this.toolbarButton(
              'strikethrough',
              EasyMDE.toggleStrikethrough,
              'strikethrough',
              Craft.t('app', 'Strikethrough')
            ),
            this.toolbarButton(
              'heading',
              EasyMDE.toggleHeadingSmaller,
              'heading',
              Craft.t('app', 'Heading')
            ),
            this.toolbarButton(
              'heading-smaller',
              EasyMDE.toggleHeadingSmaller,
              'heading',
              Craft.t('app', 'Smaller Heading')
            ),
            this.toolbarButton(
              'heading-bigger',
              EasyMDE.toggleHeadingBigger,
              'heading',
              Craft.t('app', 'Bigger Heading')
            ),
            this.toolbarButton(
              'heading-1',
              EasyMDE.toggleHeading1,
              'heading',
              Craft.t('app', 'Big Heading')
            ),
            this.toolbarButton(
              'heading-2',
              EasyMDE.toggleHeading2,
              'heading',
              Craft.t('app', 'Medium Heading')
            ),
            this.toolbarButton(
              'heading-3',
              EasyMDE.toggleHeading3,
              'heading',
              Craft.t('app', 'Small Heading')
            ),
          ],
          [
            this.toolbarButton(
              'quote',
              EasyMDE.toggleBlockquote,
              'quotes-left',
              Craft.t('app', 'Quote')
            ),
            this.toolbarButton(
              'code',
              EasyMDE.toggleCodeBlock,
              'code',
              Craft.t('app', 'Code')
            ),
            this.toolbarButton(
              'unordered-list',
              EasyMDE.toggleUnorderedList,
              'list-ul',
              Craft.t('app', 'Bulleted List')
            ),
            this.toolbarButton(
              'ordered-list',
              EasyMDE.toggleOrderedList,
              'list-ol',
              Craft.t('app', 'Numbered List')
            ),
            this.toolbarButton(
              'check-list',
              EasyMDE.toggleCheckList,
              'list-check',
              Craft.t('app', 'Check List')
            ),
            this.toolbarButton(
              'clean-block',
              EasyMDE.cleanBlock,
              'eraser',
              Craft.t('app', 'Clean Block')
            ),
          ],
          [
            this.toolbarButton(
              'link',
              EasyMDE.drawLink,
              'link',
              Craft.t('app', 'Link')
            ),
            this.toolbarButton(
              'asset',
              () => this.openAssetSelector(),
              'paperclip',
              Craft.t('app', 'Asset')
            ),
            this.toolbarButton(
              'image',
              EasyMDE.drawImage,
              'image',
              Craft.t('app', 'Image')
            ),
            this.toolbarButton(
              'table',
              EasyMDE.drawTable,
              'table',
              Craft.t('app', 'Table')
            ),
            this.toolbarButton(
              'horizontal-rule',
              EasyMDE.drawHorizontalRule,
              'minus',
              Craft.t('app', 'Horizontal Rule')
            ),
          ],
          [
            this.toolbarButton(
              'preview',
              EasyMDE.togglePreview,
              'eye',
              Craft.t('app', 'Preview')
            ),
            this.toolbarButton(
              'side-by-side',
              EasyMDE.toggleSideBySide,
              'split',
              Craft.t('app', 'Side-by-side Preview')
            ),
            this.toolbarButton(
              'fullscreen',
              EasyMDE.toggleFullScreen,
              'expand',
              Craft.t('app', 'Full Screen')
            ),
            this.toolbarButton(
              'guide',
              'https://www.markdownguide.org/basic-syntax/',
              'circle-question',
              Craft.t('app', 'Markdown Guide')
            ),
          ],
          [
            this.toolbarButton(
              'undo',
              EasyMDE.undo,
              'rotate-left',
              Craft.t('app', 'Undo')
            ),
            this.toolbarButton(
              'redo',
              EasyMDE.redo,
              'rotate-right',
              Craft.t('app', 'Redo')
            ),
          ],
        ];
      },

      toolbarButton: function (name, action, icon, title) {
        return {
          name,
          action,
          className: `markdown-field-toolbar-button markdown-field-toolbar-${name}`,
          icon: this.settings.toolbarIcons?.[icon] ?? undefined,
          title,
          noDisable: [
            'preview',
            'side-by-side',
            'fullscreen',
            'guide',
            'undo',
            'redo',
          ].includes(name),
          noMobile: ['side-by-side', 'fullscreen'].includes(name),
        };
      },

      renderPreview: function (plainText, preview) {
        const requestId = ++this.previewRequestId;

        clearTimeout(this.previewTimeout);

        this.previewTimeout = setTimeout(async () => {
          try {
            const {data} = await Craft.sendActionRequest(
              'POST',
              this.settings.previewAction,
              {
                data: {
                  markdown: plainText,
                  flavor: this.settings.flavor,
                },
              }
            );

            if (requestId === this.previewRequestId) {
              preview.innerHTML = data.html;
            }
          } catch (error) {
            if (requestId === this.previewRequestId) {
              preview.textContent = Craft.t(
                'app',
                'Couldn’t render Markdown preview.'
              );
            }
          }
        }, this.settings.previewDelay);
      },

      syncEditor: function () {
        if (!this.isConnected()) {
          return;
        }

        this.editor?.codemirror?.save();
      },

      passSaveShortcut: function () {
        this.syncEditor();

        return CodeMirror.Pass;
      },

      isConnected: function () {
        return this.$textarea?.[0]?.isConnected ?? false;
      },

      openAssetSelector: function () {
        if (!this.assetSelectorModal) {
          this.assetSelectorModal = Craft.createElementSelectorModal(
            this.settings.assetElementType,
            {
              closeOtherModals: false,
              criteria: this.settings.assetCriteria,
              hideOnSelect: true,
              modalTitle: Craft.t('app', 'Choose an asset'),
              multiSelect: false,
              sources: this.settings.assetSources,
              onSelect: (assets) => {
                if (assets.length) {
                  this.insertAsset(assets[0]);
                }
              },
            }
          );

          return;
        }

        this.assetSelectorModal.show();
      },

      insertAsset: function (asset) {
        const $element = asset.$element || $();
        const siteId = asset.siteId || $element.data('site-id');
        const ref = `{${this.settings.assetRefHandle}:${asset.id}@${siteId}:url}`;
        const label = this.escapeMarkdownLabel(
          String($element.data('alt') || asset.label || '')
        );
        const markdown =
          $element.data('kind') === 'image'
            ? `![${label}](${ref})`
            : `[${label || ref}](${ref})`;

        this.editor.codemirror.replaceSelection(markdown);
        this.editor.codemirror.focus();
      },

      escapeMarkdownLabel: function (label) {
        return label.replace(/([\\[\\]\\\\])/g, '\\$1');
      },
    },
    {
      instances: [],
      saveShortcutRegistered: false,

      registerInstance: function (instance) {
        this.instances = this.connectedInstances();
        this.instances.push(instance);

        if (this.saveShortcutRegistered) {
          return;
        }

        Craft.cp.on('beforeSaveShortcut', () => {
          this.instances = this.connectedInstances();

          for (const instance of this.instances) {
            instance.syncEditor();
          }
        });

        this.saveShortcutRegistered = true;
      },

      connectedInstances: function () {
        return this.instances.filter((instance) => instance.isConnected());
      },

      defaults: {
        flavor: 'gfm',
        previewAction: 'app/render-markdown',
        previewDelay: 250,
        toolbar: true,
        assetElementType: null,
        assetRefHandle: 'asset',
        assetCriteria: {},
        assetSources: [],
        toolbarButtons: [
          'bold',
          'italic',
          'heading',
          'quote',
          'code',
          'unordered-list',
          'ordered-list',
          'link',
          'asset',
          'image',
          'table',
          'preview',
          'side-by-side',
          'fullscreen',
        ],
        toolbarIcons: {},
      },
    }
  );
})(jQuery);

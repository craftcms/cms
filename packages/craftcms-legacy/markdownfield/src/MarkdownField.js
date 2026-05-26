import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';
import './MarkdownField.scss';

(function ($) {
  Craft.MarkdownField = Garnish.Base.extend(
    {
      settings: null,
      editor: null,
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
      },

      toolbarItems: function () {
        const selectedButtons = new Set(this.settings.toolbarButtons ?? []);
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
    },
    {
      defaults: {
        flavor: 'gfm',
        previewAction: 'app/render-markdown',
        previewDelay: 250,
        toolbar: true,
        toolbarButtons: [
          'bold',
          'italic',
          'heading',
          'quote',
          'code',
          'unordered-list',
          'ordered-list',
          'link',
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

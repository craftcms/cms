import './updates.scss';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.UpdatesUtility = Garnish.Base.extend({
    $body: null,
    showUpdates: false,
    criticalUpdateAvailable: false,
    allowUpdates: null,
    installableUpdates: null,
    availableUpdatesCount: null,

    init: function () {
      this.$body = $('#content');

      var $graphic = $('#graphic'),
        $status = $('#status');

      this.installableUpdates = [];
      this.availableUpdatesCount = 0;

      var data = {
        forceRefresh: true,
        includeDetails: true,
      };

      Craft.cp.checkForUpdates(
        true,
        true,
        (data) => {
          this.allowUpdates = data.allowUpdates;

          // Craft CMS update?
          if (data.updates.cms) {
            this.processUpdate(data.updates.cms, false);
          }

          // Plugin updates?
          if (data.updates.plugins && data.updates.plugins.length) {
            for (var i = 0; i < data.updates.plugins.length; i++) {
              this.processUpdate(data.updates.plugins[i], true);
            }
          }

          if (this.showUpdates) {
            $graphic.remove();
            $status.remove();

            if (this.availableUpdatesCount > 0) {
              // Add the page title
              var headingText = Craft.t(
                'app',
                '{num, number} {num, plural, =1{Available Update} other{Available Updates}}',
                {
                  num: this.availableUpdatesCount,
                }
              );

              $('#header h1').text(headingText);
            }

            if (this.allowUpdates && this.installableUpdates.length > 1) {
              this.createUpdateForm(
                Craft.t('app', 'Update all'),
                this.installableUpdates
              ).insertAfter($('#header > .flex:last'));
            }
          } else {
            $graphic.removeClass('spinner').addClass('success');
            $status.text(Craft.t('app', 'You’re all up to date!'));
          }
        },
        () => {
          debugger;
          $graphic.removeClass('spinner').addClass('error');
          $status.text(Craft.t('app', 'Unable to fetch updates at this time.'));
        }
      );
    },

    processUpdate: function (updateInfo, isPlugin) {
      if (updateInfo.releases.length || updateInfo.abandoned) {
        this.showUpdates = true;
        var update = new Update(this, updateInfo, isPlugin);
        if (update.installable) {
          this.installableUpdates.push(update);
        }
        if (update.available) {
          this.availableUpdatesCount++;
        }
      }
    },

    createUpdateForm: function (label, updates) {
      var $form = $('<form/>', {
        method: 'post',
      });

      $form.append(Craft.getCsrfInput());
      $form.append(
        $('<input/>', {
          type: 'hidden',
          name: 'action',
          value: 'updater',
        })
      );
      $form.append(
        $('<input/>', {
          type: 'hidden',
          name: 'return',
          value: 'utilities/updates',
        })
      );

      for (var i = 0; i < updates.length; i++) {
        $form.append(
          $('<input/>', {
            type: 'hidden',
            name: 'install[' + updates[i].updateInfo.handle + ']',
            value: updates[i].updateInfo.latestVersion,
          })
        );
        $form.append(
          $('<input/>', {
            type: 'hidden',
            name: 'packageNames[' + updates[i].updateInfo.handle + ']',
            value: updates[i].updateInfo.packageName,
          })
        );
      }

      $form.append(
        $('<button/>', {
          type: 'submit',
          text: label,
          class: 'btn submit',
        })
      );

      return $form;
    },
  });

  var Update = Garnish.Base.extend({
    updateInfo: null,
    isPlugin: null,
    installable: false,
    available: false,

    $container: null,
    $header: null,
    $contents: null,
    $releaseContainer: null,
    $showAllLink: null,

    licenseHud: null,
    $licenseSubmitBtn: null,
    licenseSubmitAction: null,

    init: function (updatesPage, updateInfo, isPlugin) {
      this.updatesPage = updatesPage;
      this.updateInfo = updateInfo;
      this.isPlugin = isPlugin;
      this.installable = this.available =
        this.updateInfo.status !== 'phpIssue' &&
        !!this.updateInfo.releases.length;

      this.createPane();
      this.initReleases();
      this.createHeading();
      this.createCta();

      // Is the plugin abandoned?
      if (this.updateInfo.abandoned) {
        $(
          '<blockquote class="note"><p>' +
            this.updateInfo.statusText +
            '</p></blockquote>'
        ).insertBefore(this.$releaseContainer);
      }
      // Any ineligible releases?
      else if (this.updateInfo.status !== 'eligible') {
        $(
          '<blockquote class="note ineligible"><p>' +
            this.updateInfo.statusText +
            '</p></blockquote>'
        ).insertBefore(this.$releaseContainer);

        if (this.updateInfo.latestVersion === null) {
          this.installable = false;
          this.available = false;
        }

        if (this.updateInfo.status === 'expired') {
          this.installable = false;
        }
      }
    },

    createPane: function () {
      this.$container = $('<div class="update"/>').appendTo(
        this.updatesPage.$body
      );
      this.$header = $('<div class="update-header"/>').appendTo(
        this.$container
      );
      this.$contents = $('<div class="readable"/>').appendTo(this.$container);
      this.$releaseContainer = $('<div class="releases"/>').appendTo(
        this.$contents
      );
    },

    createHeading: function () {
      $('<div class="readable left"/>')
        .appendTo(this.$header)
        .append($('<h2/>', {text: this.updateInfo.name}));
    },

    createCta: function () {
      const $buttonContainer = $('<div class="buttons right"/>').appendTo(
        this.$header
      );

      const menuId = `menu-${Math.floor(Math.random() * 1000000)}`;
      const $actionBtn = $('<button/>', {
        type: 'button',
        class: 'btn menubtn action-btn hairline-dark m',
        'aria-label': Craft.t('app', 'Actions'),
        'aria-controls': menuId,
        title: Craft.t('app', 'Actions'),
        'data-disclosure-trigger': '',
      }).appendTo($buttonContainer);
      $('<div/>', {
        id: menuId,
        class: 'menu menu--disclosure',
      }).appendTo($buttonContainer);

      const disclosureMenu = $actionBtn.disclosureMenu().data('disclosureMenu');

      disclosureMenu.addItems([
        {
          icon: 'clipboard',
          label: Craft.t('app', 'Copy plugin handle'),
          onActivate: () => {
            Craft.ui.createCopyTextPrompt({
              label: Craft.t('app', 'Plugin Handle'),
              value: this.updateInfo.handle,
            });
          },
        },
        {
          icon: 'clipboard',
          label: Craft.t('app', 'Copy package name'),
          onActivate: () => {
            Craft.ui.createCopyTextPrompt({
              label: Craft.t('app', 'Package Name'),
              value: this.updateInfo.packageName,
            });
          },
        },
      ]);

      if (
        !this.updatesPage.allowUpdates ||
        !this.updateInfo.latestVersion ||
        this.updateInfo.ctaUrl === false
      ) {
        return;
      }

      if (typeof this.updateInfo.ctaUrl !== 'undefined') {
        $('<a/>', {
          class: 'btn submit',
          text: this.updateInfo.ctaText,
          href: this.updateInfo.ctaUrl,
          target: '_blank',
        }).insertBefore($actionBtn);
      } else {
        this.updatesPage
          .createUpdateForm(this.updateInfo.ctaText, [this])
          .insertBefore($actionBtn);
      }

      if (typeof this.updateInfo.altCtaUrl !== 'undefined') {
        $('<a/>', {
          class: 'btn hairline',
          text: this.updateInfo.altCtaText,
          href: this.updateInfo.altCtaUrl,
        }).insertBefore($actionBtn);
      } else if (typeof this.updateInfo.altCtaText !== 'undefined') {
        const $form = this.updatesPage
          .createUpdateForm(this.updateInfo.altCtaText, [this])
          .insertBefore($actionBtn);
        $form.find('button').removeClass('submit').addClass('hairline');
      }
    },

    initReleases: function () {
      for (var i = 0; i < this.updateInfo.releases.length; i++) {
        new Release(this, this.updateInfo.releases[i]);
      }
    },
  });

  var Release = Garnish.Base.extend(
    {
      update: null,
      releaseInfo: null,
      notesId: null,

      $container: null,
      $accordionTrigger: null,

      init: function (update, releaseInfo) {
        this.update = update;
        this.releaseInfo = releaseInfo;
        this.notesId = 'notes-' + Math.floor(Math.random() * 1000000);
        this.triggerId = `${this.notesId}-trigger`;

        this.createContainer();
        this.createHeading();

        if (this.releaseInfo.notes) {
          this.createReleaseNotes();
          new Craft.Accordion(this.$accordionTrigger);
        }
      },

      createContainer: function () {
        this.$container = $('<div class="pane release"/>').appendTo(
          this.update.$releaseContainer
        );

        if (this.releaseInfo.critical) {
          this.$container.addClass('release--critical');
        }
      },

      createHeading: function () {
        const $headingContainer = $('<h3/>', {
          class: 'release-heading',
        }).appendTo(this.$container);
        let $headingContents;

        if (this.releaseInfo.notes) {
          $headingContents = $('<a/>', {
            id: this.triggerId,
            class: 'release-info fieldtoggle',
            'aria-controls': this.notesId,
            'aria-expanded': 'false',
            tabindex: '0',
            role: 'button',
          });
          this.$accordionTrigger = $headingContents;
        } else {
          $headingContents = $('<div/>', {class: 'release-info'});
        }

        $headingContents.appendTo($headingContainer);

        // Title text
        const accordionTitle = $('<span/>', {
          text: this.releaseInfo.version,
        }).appendTo($headingContents);
        if (this.releaseInfo.critical) {
          $('<strong/>', {
            class: 'release-badge',
            text: Craft.t('app', 'Critical'),
          }).appendTo($headingContents);
        }
        if (this.releaseInfo.date) {
          $('<span/>', {
            class: 'release-date',
            text: Craft.formatDate(this.releaseInfo.date),
          }).appendTo($headingContents);
        }
      },

      createReleaseNotes: function () {
        var $notes = $('<div/>', {
          id: this.notesId,
          role: 'region',
          'aria-labelledby': this.triggerId,
        })
          .appendTo(this.$container)
          .append(
            $('<div/>', {class: 'release-notes'}).html(
              this.releaseInfo.notes.replace(
                /(<\/?h)(3|4|5)\b/g,
                (m, pre, num) => `${pre}${parseInt(num) + 1} class="h${num}"`
              )
            )
          );

        // Auto-expand if this is a critical release, or there are any tips/warnings in the release notes
        if (this.releaseInfo.critical || $notes.find('blockquote').length) {
          if (!this.$accordionTrigger) return;

          this.$accordionTrigger
            .addClass('expanded')
            .attr('aria-expanded', 'true');
        } else {
          $notes.addClass('hidden');
        }
      },
    },
    {
      maxInitialUpdateHeight: 500,
    }
  );
})(jQuery);

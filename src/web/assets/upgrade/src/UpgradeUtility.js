import './upgrade.scss';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.UpgradeUtility = Garnish.Base.extend({
    version: null,
    installedPlugins: null,

    $body: null,
    $graphic: null,
    $status: null,

    init: function (version, installedPlugins) {
      this.version = version;
      this.installedPlugins = installedPlugins;

      this.$body = $('#content');
      this.$graphic = $('#graphic');
      this.$status = $('#status');

      Craft.sendApiRequest('GET', `upgrade-info?cmsConstraint=^${version}.0`)
        .then((data) => {
          if (data.cms.latestVersion) {
            this.showUpgradeInfo(data);
          } else {
            this.displayError();
          }
        })
        .catch(() => {
          this.displayError();
        });
    },

    showUpgradeInfo: function (data) {
      this.$graphic.remove();
      this.$status.remove();

      const handles = data.plugins.map((info) => info.handle);
      const missingPluginInfo = this.installedPlugins.filter((info) => {
        return !handles.includes(info.handle);
      });

      data.plugins.push(
        ...missingPluginInfo.map((info) => Object.assign(info, {unknown: true}))
      );

      const $pluginIntro = $('<div class="readable"/>')
        .append(
          $('<h2/>', {
            text: Craft.t('app', 'Plugins'),
          })
        )
        .appendTo(this.$body);

      if (data.plugins.length) {
        $pluginIntro.append(
          $('<p/>', {
            text: Craft.t(
              'app',
              'All plugins must be compatible with Craft {version} before you can upgrade.',
              {
                version: this.version,
              }
            ),
          })
        );

        const $table = $(
          '<table id="plugins" class="data fullwidth"/>'
        ).appendTo(this.$body);
        $(`
          <thead>
            <tr>
              <th>${Craft.t('app', 'Plugin')}</th>
              <th>${Craft.t('app', 'Status')}</th>
              <th>${Craft.t('app', 'Notes')}</th>
            </tr>
          </thead>
        `).appendTo($table);

        const $tbody = $('<tbody/>').appendTo($table);
        for (const plugin of data.plugins) {
          const $tr = $('<tr/>').appendTo($tbody);
          const $th = $('<th/>').appendTo($tr);
          // const $infoContainer = $('<div class="plugin-info"/>').appendTo($th);
          if (plugin.icon) {
            $(`<div class="plugin-icon">${plugin.icon}</div>`).appendTo($th);
          }
          if (plugin.unknown) {
            $('<span/>', {
              class: 'plugin-name',
              text: plugin.name,
            }).appendTo($th);
          } else {
            $('<a/>', {
              class: 'plugin-name',
              href: `https://plugins.craftcms.com/${plugin.handle}?cmsConstraint=^${this.version}.0`,
              text: plugin.name,
            }).appendTo($th);
          }
          const $devContainer = $('<div class="plugin-developer"/>').appendTo(
            $th
          );
          if (plugin.developerUrl) {
            $('<a/>', {
              href: plugin.developerUrl,
              text: plugin.developerName,
            }).appendTo($devContainer);
          } else {
            $devContainer.text(plugin.developerName);
          }

          const $tdStatus = $('<td/>').appendTo($tr);
          let noteHtml = '';

          if (plugin.abandoned) {
            $('<div/>', {
              class: 'plugin-status plugin-abandoned',
              text: Craft.t('app', 'Abandoned'),
            }).appendTo($tdStatus);
            if (plugin.replacement) {
              noteHtml = Craft.t(
                'app',
                'The developer recommends using <a href="{url}">{name}</a> instead.',
                {
                  url: `https://plugins.craftcms.com/${plugin.replacement.handle}`,
                  name: plugin.replacement.name,
                }
              );
            }
          } else if (plugin.latestVersion) {
            $('<div/>', {
              class: 'plugin-status plugin-ready',
              text: Craft.t('app', 'Ready'),
            }).appendTo($tdStatus);
            $('<div/>', {
              class: 'plugin-version',
              text: plugin.latestVersion,
            }).appendTo($tdStatus);
            if (
              plugin.phpConstraint &&
              plugin.phpConstraint != data.cms.phpConstraint
            ) {
              noteHtml = Craft.t('app', 'Requires PHP {version}', {
                version: plugin.phpConstraint,
              });
            }
          } else if (plugin.unknown) {
            $('<div/>', {
              class: 'plugin-status',
              text: plugin.isInstalled
                ? Craft.t('app', 'Unknown')
                : Craft.t('app', 'Not installed'),
            }).appendTo($tdStatus);
          } else {
            $('<div/>', {
              class: 'plugin-status plugin-not-ready',
              text: Craft.t('app', 'Not ready'),
            }).appendTo($tdStatus);
          }

          if (plugin.note) {
            noteHtml = Craft.escapeHtml(plugin.note);
          }

          $('<td/>', {
            class: 'plugin-note',
            html: noteHtml,
          }).appendTo($tr);
        }
      } else {
        $pluginIntro.append(
          $('<p/>', {
            text: Craft.t('app', 'No plugins are installed.'),
          })
        );
      }

      $('<div class="readable centeralign pane"/>')
        .append(
          $('<h2/>', {
            text: Craft.t('app', 'Ready to upgrade?'),
          })
        )
        .append(
          $('<p/>', {
            html: Craft.t('app', 'View the <a>upgrade guide</a>').replace(
              '<a>',
              `<a class="go" href="https://craftcms.com/docs/${this.version}.x/upgrade.html">`
            ),
          })
        )
        .appendTo(this.$body);

      Craft.initUiElements(this.$body);
    },

    displayError: function () {
      this.$graphic.addClass('error');
      this.$status.text(
        Craft.t('app', 'Unable to fetch upgrade info at this time.')
      );
    },
  });
})(jQuery);

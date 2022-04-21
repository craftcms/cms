import './deprecator.scss';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  /**
   * Deprecator class
   */
  var Deprecator = Garnish.Base.extend({
    $clearAllBtn: null,
    $table: null,
    tracesModal: null,
    $tracesModalBody: null,

    init: function () {
      this.$clearAllBtn = $('#clearall');
      this.$table = $('#deprecationerrors');
      this.$noLogsMessage = $('#nologs');

      this.addListener(this.$clearAllBtn, 'click', 'clearAllLogs');
      this.addListener(
        this.$table.find('.viewtraces'),
        'click',
        'viewLogTraces'
      );
      this.addListener(this.$table.find('.delete'), 'click', 'deleteLog');
    },

    clearAllLogs: function () {
      Craft.sendActionRequest(
        'POST',
        'utilities/delete-all-deprecation-errors'
      );
      this.onClearAll();
    },

    viewLogTraces: function (ev) {
      const $spinner = $('<div class="spinner spinner-absolute"/>');
      if (!this.tracesModal) {
        var $container = $('<div id="traces" class="modal"/>')
          .append($spinner)
          .appendTo(Garnish.$bod);
        this.$tracesModalBody = $('<div class="body" tabindex="0"/>').appendTo(
          $container
        );

        this.tracesModal = new Garnish.Modal($container, {
          resizable: true,
        });
      } else {
        this.tracesModal.$container.append($spinner);
        this.$tracesModalBody.empty();
        this.tracesModal.show();
      }

      var data = {
        logId: $(ev.currentTarget).closest('tr').data('id'),
      };

      Craft.sendActionRequest(
        'POST',
        'utilities/get-deprecation-error-traces-modal',
        {data}
      )
        .then((response) => {
          this.tracesModal.$container.find('.spinner').remove();
          this.$tracesModalBody.html(response.data.html);
        })
        .catch(({response}) => {
          this.tracesModal.$container.find('.spinner').remove();
        });
    },

    deleteLog: function (ev) {
      var $tr = $(ev.currentTarget).closest('tr');

      var data = {
        logId: $tr.data('id'),
      };

      Craft.sendActionRequest('POST', 'utilities/delete-deprecation-error', {
        data,
      }).finally(() => {
        console.log('response', response);
      });

      if ($tr.siblings().length) {
        $tr.remove();
      } else {
        this.onClearAll();
      }
    },

    onClearAll: function () {
      this.$clearAllBtn.parent().remove();
      this.$table.remove();
      this.$noLogsMessage.removeClass('hidden');
    },
  });

  new Deprecator();
})(jQuery);

(function($) {

    var $modalContainerDiv = null;

    var IndexingManager = Blocks.Base.extend({

        $startIndexingButton: null,
        $sourceCheckboxes: null,
        $sourceProgressBars: null,
        $indexReport: null,
        $obsoleteEntryList: null,
        $finishIndexButton: null,

        sessionId: null,
        queue: null,

        modal: null,

        missingFolders: [],

        init: function()
        {
            this.$startIndexingButton = $('#start-index');
            this.$sourceCheckboxes = $('input[type=checkbox].indexing');
            this.$sourceProgressBars = $('td.index-progress');
            this.$indexReport = $('#index-report');
            this.$obsoleteEntryList = $('#index-obsolete');
            this.$finishIndexButton = $('#finish-index');

            this.$indexReport.hide();

            this.addListener(this.$startIndexingButton, 'click', 'startIndexing');
        },

        startIndexing: function ()
        {
            var checkedSources = this.$sourceCheckboxes.filter(':checked');
            if (this.$startIndexingButton.hasClass('disabled'))
            {
                return;
            }
            if (checkedSources.length == 0)
            {
                this.$startIndexingButton.removeClass('disabled');
                return;
            }

            this.$startIndexingButton.addClass('disabled');

            checkedSources.prop('disabled', true);

            Blocks.postActionRequest('assetIndexing/getSessionId', $.proxy(function(data){
                this.sessionId = data.sessionId;
                this.missingFolders = [];
                this.queue = new AjaxQueueManager(10, this.displayIndexingReport, this);

                var _t = this;

                checkedSources.each(function () {
                    _t.$sourceProgressBars.html('');
                    var progress_bar = $(this).parents('tr').find('td.index-progress');

                    var params = {
                        sourceId: $(this).attr('source_id'),
                        session: _t.sessionId
                    };

                    _t.queue.addItem(Blocks.getActionUrl('assetIndexing/startIndex'), params, $.proxy(function (data) {

                        progress_bar.attr('total', data.total).attr('current', 0);
                        for (var i = 0; i < data.total; i++) {
                            params = {
                                session: this.sessionId,
                                sourceId: data.sourceId,
                                offset: i
                            };

                            this.queue.addItem(Blocks.getActionUrl('assetIndexing/performIndex'), params, function () {
                                progress_bar.attr('current', parseInt(progress_bar.attr('current'), 10) + 1);
                                progress_bar.html(progress_bar.attr('current') + ' / ' + progress_bar.attr('total'));
                            });
                        }

                        for (var folder_id in data.missingFolders) {
                            this.missingFolders.push({folder_id: folder_id, folder_name: data.missingFolders[folder_id]});
                        }
                    }, _t));
                });
                this.queue.startQueue();
            }, this));
        },

        /**
         * Display Indexing report after all is done
         */
        displayIndexingReport: function () {

            this.$startIndexingButton.removeClass('disabled');
            this.$sourceProgressBars.html('');

            var sources = [];
            this.$sourceCheckboxes.filter(':checked').each(function () {
                sources.push($(this).attr('source_id'));
            });

            if ($modalContainerDiv == null) {
                $modalContainerDiv = $('<div class="modal index-report"></div>').addClass().appendTo(Blocks.$body);
            }

            if (this.modal == null) {
                this.modal = new Blocks.ui.Modal();
                this.modal.sessionId = this.sessionId;
                this.modal.IndexingManager = this;
            }

            var params = {
                sessionId: this.sessionId,
                command: JSON.stringify({command: 'statistics'}),
                sources: sources.join(",")
            };

            $.post(Blocks.getActionUrl('assetIndexing/finishIndex'), params, $.proxy(function (data) {
                var html = '';

                if (typeof data.files != "undefined" || this.missingFolders.length > 0) {
                    html += '<p>' + Blocks.t('The following items were found in the database that do not have a physical match.') +  '</p>';

                    if (this.missingFolders.length > 0) {
                        html += '<div class="report-part"><strong>' + Blocks.t('Folders') + '</strong>';
                        for (var i = 0; i < this.missingFolders.length; i++) {
                            html += '<div><label><input type="checkbox" checked="checked" class="delete_folder" value="' + this.missingFolders[i].folder_id + '" /> ' + this.missingFolders[i].folder_name + '</label></div>';
                        }
                        html += '</div>'
                    }

                    if (typeof data.files != "undefined") {
                        html += '<div class="report-part"><strong>' + Blocks.t('Files') + '</strong>';
                        for (var file_id in data.files) {
                            html += '<div><label><input type="checkbox" checked="checked" class="delete_file" value="' + file_id + '" /> ' + data.files[file_id] + '</label></div>';
                        }
                        html += '</div>'
                    }

                    html += '<footer class="footer"><ul class="right">';
                    html += '<li><input type="button" class="btn cancel" value="' + Blocks.t('Cancel') + '"></li>';
                    html += '<li><input type="button" class="btn submit delete" value="' + Blocks.t('Delete') + '"></li>';
                    html += '</ul></footer>';

                    $modalContainerDiv.empty().append(html);
                    this.modal.setContainer($modalContainerDiv);

                    this.modal.show();
                    this.modal.removeListener(Blocks.ui.Modal.$shade, 'click');

                    this.modal.addListener(this.modal.$container.find('.btn.cancel'), 'click', function () {
                        this.IndexingManager.$sourceCheckboxes.filter(':checked').prop('checked', false).prop('disabled', false);
                        this.hide();
                    });

                    this.modal.addListener(this.modal.$container.find('.btn.delete'), 'click', function () {

                        var command = {};
                        command.command = 'delete';
                        command.folderIds = [];
                        command.fileIds = [];

                        this.$container.find('input.delete_folder:checked').each(function (){
                            command.folderIds.push($(this).val());
                        });

                        this.$container.find('input.delete_file:checked').each(function (){
                            command.fileIds.push($(this).val());
                        });

                        var sources = [];
                        this.IndexingManager.$sourceCheckboxes.filter(':checked').each(function () {
                            sources.push($(this).attr('source_id'));
                        });

                        var params = {
                            sessionId: this.sessionId,
                            command: JSON.stringify(command),
                            sources: sources.join(",")
                        };

                        $.post(Blocks.getActionUrl('assetIndexing/finishIndex'), params, $.proxy(function(data) {
                            this.hide();
                            this.IndexingManager.$sourceCheckboxes.filter(':checked').prop('checked', false).prop('disabled', false);
                        }, this));
                    });
                } else {
                    this.$sourceCheckboxes.filter(':checked').prop('checked', false).prop('disabled', false);
                }


            }, this));

        }


    });

    var indexingManager = new IndexingManager();

})(jQuery);

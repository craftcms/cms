(function($) {

    var IndexingManager = Blocks.Base.extend({

        $startIndexingButton: null,
        $sourceCheckboxes: null,
        $sourceProgressBars: null,
        $indexReport: null,
        $obsoleteEntryList: null,
        $finishIndexButton: null,

        sessionId: null,
        queue: null,

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
            var _t = this;

            $.post(Blocks.actionUrl+'assetIndexing/getSessionId', function(data){
                _t.sessionId = data.session_id;
                _t.queue = new AjaxQueueManager(10, _t.displayIndexingReport, _t);


                checkedSources.each(function () {
                    _t.$sourceProgressBars.html('');
                    var progress_bar = $(this).parents('tr').find('td.index-progress');

                    var params = {
                        source_id: $(this).attr('source_id'),
                        session: _t.sessionId
                    };

                    _t.queue.addItem(Blocks.actionUrl+'assetIndexing/startIndex', params, function (data) {

                        progress_bar.attr('total', data.total).attr('current', 0);
                        for (var i = 0; i < data.total; i++) {
                            params = {
                                session: _t.sessionId,
                                source_id: data.source_id,
                                offset: i
                            };

                            _t.queue.addItem(Blocks.actionUrl+'assetIndexing/performIndex', params, function () {
                                progress_bar.attr('current', parseInt(progress_bar.attr('current'), 10) + 1);
                                progress_bar.html(progress_bar.attr('current') + ' / ' + progress_bar.attr('total'));
                            });
                        }
                    });
                });
                _t.queue.startQueue();
            });
        },

        /**
         * Display Indexing report after all is done
         */
        displayIndexingReport: function () {
            console.log(this);
            this.$startIndexingButton.removeClass('disabled');
            this.$sourceProgressBars.html('');
            return;
            $('input.assets-index.disabled').removeClass('disabled');
            $('div.progress-bar').remove();
            var sources = [];
            sources_to_index.each(function () {
                sources.push($(this).attr('id'));
            });

            var params = {
                ACT: Assets.actions.finish_index,
                session: session,
                command: JSON.stringify({command: 'statistics'}),
                sources: sources.join(",")
            };

            $.post(Assets.siteUrl, params, function (data) {
                data = JSON.parse(data);
                if (typeof data.folders != "undefined" || typeof data.files != "undefined") {
                    $('div#assets-dialog div#index-message').html(Assets.lang.index_stale_entries_message);

                    var html = ''
                    if (typeof data.folders != "undefined") {
                        html += '<div class="index-data-container"><strong>' + Assets.lang.index_folders + '</strong>';
                        for (var folder_id in data.folders) {
                            html += '<div><label><input type="checkbox" checked="checked" class="delete_folder" value="' + folder_id + '" /> ' + data.folders[folder_id] + '</label></div>';
                        }
                        html += '</div>'
                    }

                    if (typeof data.files != "undefined") {
                        html += '<div class="index-data-container"><strong>' + Assets.lang.index_files + '</strong>';
                        for (var file_id in data.files) {
                            html += '<div><label><input type="checkbox" checked="checked" class="delete_file" value="' + file_id + '" /> ' + data.files[file_id] + '</label></div>';
                        }
                        html += '</div>'
                    }

                    html += '<br /><input type="button" class="submit" value="' + Assets.lang._delete + '" onclick="deleteSelectedFiles();"/>';
                    $('#index-status-report').empty().append(html);

                } else {
                    $('div#assets-dialog div#index-status-report').empty();
                    $('div#index-message').html(Assets.lang.index_complete);
                }
                $('div#assets-dialog').show();
            });
        }


    });

    var indexingManager = new IndexingManager();

})(jQuery);

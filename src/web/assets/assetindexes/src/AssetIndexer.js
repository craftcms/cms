"use strict";
var SessionStatus;
(function (SessionStatus) {
    SessionStatus[SessionStatus["ACTIONREQUIRED"] = 0] = "ACTIONREQUIRED";
    SessionStatus[SessionStatus["ACTIVE"] = 1] = "ACTIVE";
    SessionStatus[SessionStatus["QUEUE"] = 2] = "QUEUE";
})(SessionStatus || (SessionStatus = {}));
var IndexingActions;
(function (IndexingActions) {
    IndexingActions["STOP"] = "asset-indexes/stop-indexing-session";
})(IndexingActions || (IndexingActions = {}));
;
var IndexerStatus;
(function (IndexerStatus) {
    IndexerStatus[IndexerStatus["STOPPED"] = 0] = "STOPPED";
    IndexerStatus[IndexerStatus["RUNNING"] = 1] = "RUNNING";
})(IndexerStatus || (IndexerStatus = {}));
;
/**
 * Actual classes start here
 */
// Asset Indexer
// =====================================================================================
class AssetIndexer {
    /**
     * @param $element The indexing session table
     * @param sessions Existing indexing sessions
     */
    constructor($indexingSessionTable, sessions) {
        this.indexingSessions = {};
        this.$indexingSessionTable = $indexingSessionTable;
        this.indexingSessions = {};
        this.status = IndexerStatus.STOPPED;
        for (const session of sessions) {
            this.updateIndexingSessionData(session);
        }
    }
    /**
     * Update indexing session store
     * @param session
     */
    updateIndexingSessionData(sessionData) {
        const indexingSession = this.createSessionFromModel(sessionData);
        this.indexingSessions[indexingSession.getSessionId()] = indexingSession;
        this.renderIndexingSessionRow(indexingSession);
    }
    /**
     * Return a rendered indexing session row based on its id
     * @param sessionId
     */
    renderIndexingSessionRow(session) {
        let $row;
        if (!this.indexingSessions[session.getSessionId()]) {
            this.$indexingSessionTable.find('tr[data-session-id="' + session.getSessionId() + '"]').remove();
            if (this.$indexingSessionTable.find('tbody tr').length == 0) {
                this.$indexingSessionTable.addClass('hidden');
            }
            return;
        }
        $row = session.getIndexingSessionRowHtml();
        const $existing = this.$indexingSessionTable.find('tr[data-session-id="' + session.getSessionId() + '"]');
        if ($existing.length > 0) {
            $existing.replaceWith($row);
        }
        else {
            this.$indexingSessionTable.find('tbody').append($row);
        }
        this.$indexingSessionTable.removeClass('hidden');
    }
    /**
     * Remove an indexing session
     * @param sessionId
     * @protected
     */
    discardIndexingSession(sessionId) {
        const session = this.indexingSessions[sessionId];
        delete this.indexingSessions[sessionId];
        this.renderIndexingSessionRow(session);
    }
    /**
     * Process an indexing response.
     *
     * @param response
     * @param textStatus
     */
    processResponse(response, textStatus) {
        if (textStatus === 'success' && response.error) {
            alert(response.error);
            return;
        }
        if (response.session) {
            const session = this.createSessionFromModel(response.session);
            this.indexingSessions[session.getSessionId()] = session;
            this.renderIndexingSessionRow(session);
        }
        if (response.stop) {
            this.discardIndexingSession(response.stop);
        }
    }
    stopIndexingSession(sessionId) {
        Craft.postActionRequest(IndexingActions.STOP, { sessionId: sessionId }, this.processResponse.bind(this));
    }
    /**
     * Create a session from the data model.
     *
     * @param sessionData
     * @private
     */
    createSessionFromModel(sessionData) {
        return new AssetIndexingSession(sessionData, this);
    }
}
class AssetIndexingSession {
    constructor(model, indexer) {
        this.indexingSessionData = model;
        this.indexer = indexer;
    }
    /**
     * Get the session id
     */
    getSessionId() {
        return this.indexingSessionData.id;
    }
    getSessionStatus() {
        if (this.indexingSessionData.queueId) {
            return SessionStatus.QUEUE;
        }
        if (this.indexingSessionData.actionRequired) {
            return SessionStatus.ACTIONREQUIRED;
        }
        return SessionStatus.ACTIVE;
    }
    /**
     * Create row html as a JQuery object based on an indexing sessions
     * @param session
     * @private
     */
    getIndexingSessionRowHtml() {
        console.log(this.indexingSessionData);
        const $tr = $('<tr class="indexingSession" data-session-id="' + this.getSessionId() + '">');
        $tr.data('session-id', this.indexingSessionData.id).data('as-queue', this.indexingSessionData.queueId ? this.indexingSessionData.queueId : null);
        $tr.append('<td>' + this.indexingSessionData.dateCreated + '</td>');
        $tr.append('<td>' + this.indexingSessionData.dateUpdated + '</td>');
        const $progressCell = $('<td class="progress"></td>').data('total', this.indexingSessionData.totalEntries).data('processed', this.indexingSessionData.processedEntries).css('position', 'relative');
        const progressBar = new Craft.ProgressBar($progressCell, false);
        progressBar.setItemCount(this.indexingSessionData.totalEntries);
        progressBar.setProcessedItemCount(this.indexingSessionData.processedEntries);
        progressBar.updateProgressBar();
        progressBar.showProgressBar();
        $tr.append($progressCell.data('progressBar', progressBar));
        $tr.append('<td>' + this.getSessionStatusMessage() + '</td>');
        const $actions = this.getActionButtons();
        $('<td></td>').append($actions).appendTo($tr);
        return $tr;
    }
    /**
     * Get action buttons for an indexing session
     * @param session
     * @private
     */
    getActionButtons() {
        if (this.getSessionStatus() === SessionStatus.QUEUE) {
            return $();
        }
        const $buttons = $('<div class="buttons"></div>');
        if (this.getSessionStatus() == SessionStatus.ACTIONREQUIRED) {
            const reviewMessage = Craft.t('app', 'Review');
            $buttons.append($('<button />', {
                type: 'button',
                'class': 'btn submit',
                title: reviewMessage,
                "aria-label": reviewMessage,
            }).text(reviewMessage)).on('click', ev => {
                const $container = $(ev.target).parent();
                if ($container.hasClass('disabled')) {
                    return;
                }
                $container.addClass('disabled');
                // review indexing session.
            });
        }
        const discardMessage = Craft.t('app', 'Discard');
        $buttons.append($('<button />', {
            type: 'button',
            'class': 'btn submit',
            title: discardMessage,
            "aria-label": discardMessage,
        }).text(discardMessage)).on('click', ev => {
            if ($buttons.hasClass('disabled')) {
                return;
            }
            $buttons.addClass('disabled');
            this.indexer.stopIndexingSession(this.getSessionId());
        });
        return $buttons;
    }
    /**
     * Get the session status verbose message
     *
     * @param status
     */
    getSessionStatusMessage() {
        switch (this.getSessionStatus()) {
            case SessionStatus.ACTIONREQUIRED:
                return Craft.t('app', 'Waiting for review');
                break;
            case SessionStatus.ACTIVE:
                return Craft.t('app', 'Active');
                break;
            case SessionStatus.QUEUE:
                return Craft.t('app', 'Running in background');
                break;
        }
    }
}

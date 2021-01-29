"use strict";
var SessionStatus;
(function (SessionStatus) {
    SessionStatus[SessionStatus["STOPPED"] = 0] = "STOPPED";
    SessionStatus[SessionStatus["RUNNING"] = 1] = "RUNNING";
    SessionStatus[SessionStatus["QUEUE"] = 2] = "QUEUE";
})(SessionStatus || (SessionStatus = {}));
var IndexingActions;
(function (IndexingActions) {
    IndexingActions["STOP"] = "asset-indexes/stop-indexing-session";
})(IndexingActions || (IndexingActions = {}));
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
        this.runningSessions = {};
        this.$indexingSessionTable = $indexingSessionTable;
        this.indexingSessions = {};
        this.runningSessions = {};
        for (const session of sessions) {
            this.updateIndexingSessionData(session);
        }
        if (sessions.length > 0) {
            this.$indexingSessionTable.removeClass('hidden');
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
    }
    /**
     * Remove an indexing session
     * @param sessionId
     * @protected
     */
    discardIndexingSession(sessionId) {
        const session = this.indexingSessions[sessionId];
        delete this.indexingSessions[sessionId];
        delete this.runningSessions[sessionId];
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
            this.runningSessions[session.getSessionId()] = true;
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
        return new AssetIndexingSession(sessionData, this.getSessionStatus(sessionData), this);
    }
    /**
     * Get session stat
     * @param session
     * @private
     */
    getSessionStatus(sessionData) {
        if (sessionData.queueId) {
            return SessionStatus.QUEUE;
        }
        return this.runningSessions[sessionData.id] ? SessionStatus.RUNNING : SessionStatus.STOPPED;
    }
}
class AssetIndexingSession {
    constructor(model, status, indexer) {
        this.indexingSessionData = model;
        this.status = status;
        this.indexer = indexer;
    }
    /**
     * Get the session id
     */
    getSessionId() {
        return this.indexingSessionData.id;
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
        if (this.status === SessionStatus.QUEUE) {
            return $();
        }
        const $buttons = $('<div class="buttons"></div>');
        let startStopMessage;
        switch (this.status) {
            case SessionStatus.RUNNING:
                startStopMessage = Craft.t('app', 'Stop');
                break;
            case SessionStatus.STOPPED:
                startStopMessage = Craft.t('app', 'Start');
                const endMessage = Craft.t('app', 'Cancel');
                $buttons.append($('<button />', {
                    type: 'button',
                    'class': 'btn submit',
                    title: endMessage,
                    "aria-label": endMessage,
                }).text(endMessage)).on('click', ev => {
                    const $container = $(ev.target).parent();
                    if ($container.hasClass('disabled')) {
                        return;
                    }
                    $container.addClass('disabled');
                    this.indexer.stopIndexingSession(this.getSessionId());
                });
                break;
        }
        $buttons.prepend($('<button />', {
            type: 'button',
            'class': 'btn submit',
            title: startStopMessage,
            "aria-label": startStopMessage,
        }).text(startStopMessage));
        return $buttons;
    }
    /**
     * Get the session status verbose message
     *
     * @param status
     */
    getSessionStatusMessage() {
        switch (this.status) {
            case SessionStatus.STOPPED:
                return Craft.t('app', 'Waiting');
                break;
            case SessionStatus.RUNNING:
                return Craft.t('app', 'Running');
                break;
            case SessionStatus.QUEUE:
                return Craft.t('app', 'Running in background');
                break;
        }
    }
}

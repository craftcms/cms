"use strict";
var SessionStatus;
(function (SessionStatus) {
    SessionStatus[SessionStatus["ACTIONREQUIRED"] = 0] = "ACTIONREQUIRED";
    SessionStatus[SessionStatus["ACTIVE"] = 1] = "ACTIVE";
    SessionStatus[SessionStatus["QUEUE"] = 2] = "QUEUE";
    SessionStatus[SessionStatus["WAITING"] = 3] = "WAITING";
    SessionStatus[SessionStatus["CLI"] = 4] = "CLI";
})(SessionStatus || (SessionStatus = {}));
var IndexingActions;
(function (IndexingActions) {
    IndexingActions["STOP"] = "asset-indexes/stop-indexing-session";
    IndexingActions["PROCESS"] = "asset-indexes/process-indexing-session";
    IndexingActions["OVERVIEW"] = "asset-indexes/indexing-session-overview";
    IndexingActions["FINISH"] = "asset-indexes/finish-indexing-session";
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
        this._currentIndexingSession = null;
        this.indexingSessions = {};
        this.$indexingSessionTable = $indexingSessionTable;
        this.indexingSessions = {};
        let reviewSessionId = 0;
        for (const sessionModel of sessions) {
            let session = this.createSessionFromModel(sessionModel);
            if (session.getSessionStatus() === SessionStatus.ACTIONREQUIRED && !reviewSessionId) {
                reviewSessionId = session.getSessionId();
            }
            if (!reviewSessionId
                && this._currentIndexingSession == null
                && session.getSessionStatus() !== SessionStatus.QUEUE
                && session.getSessionStatus() !== SessionStatus.CLI
                && session.getSessionStatus() !== SessionStatus.ACTIONREQUIRED) {
                this._currentIndexingSession = session.getSessionId();
            }
            this.updateIndexingSessionData(session);
        }
        if (this._currentIndexingSession) {
            this.performIndexingStep();
        }
    }
    get currentIndexingSession() {
        return this._currentIndexingSession;
    }
    /**
     * Update indexing session store
     * @param session
     */
    updateIndexingSessionData(indexingSession) {
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
        if (this._currentIndexingSession === sessionId) {
            this._currentIndexingSession = null;
        }
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
        if (textStatus === 'success' && response.session) {
            const session = this.createSessionFromModel(response.session);
            this.indexingSessions[session.getSessionId()] = session;
            this.renderIndexingSessionRow(session);
            if (!this._currentIndexingSession) {
                this._currentIndexingSession = session.getSessionId();
            }
            if (session.getSessionStatus() === SessionStatus.ACTIONREQUIRED) {
                this.reviewSession(session);
                return;
            }
            this.performIndexingStep();
        }
        if (textStatus === 'success' && response.stop) {
            this.discardIndexingSession(response.stop);
        }
    }
    getReviewData(session) {
        Craft.postActionRequest(IndexingActions.OVERVIEW, { sessionId: session.getSessionId() }, this.processResponse.bind(this));
    }
    reviewSession(session) {
        let $confirmBody = $('<div></div>');
        const missingEntries = session.getMissingEntries();
        const missingFiles = Object.entries(missingEntries.files);
        const missingFolders = Object.entries(missingEntries.folders);
        const skippedFiles = session.getSkippedEntries();
        if (skippedFiles.length) {
            let skippedFilesList = '';
            for (const skippedFile of skippedFiles) {
                skippedFilesList += `<li>${skippedFile}</li>`;
            }
            $confirmBody.append(`
                <h2>${Craft.t('app', 'Skipped files')}</h2>
                <p>${Craft.t('app', 'The following items were not indexed.')}</p>
                <ul>
                    ${skippedFilesList}
                </ul>
            `);
        }
        const haveMissingItems = missingFiles.length || missingFolders.length;
        if (haveMissingItems) {
            let itemText = '';
            if (missingFiles.length) {
                itemText += 'files';
            }
            if (missingFiles.length && missingFolders.length) {
                itemText += ' and ';
            }
            if (missingFolders.length) {
                itemText += 'folders';
            }
            const translationParams = { items: itemText };
            let missingEntries = '';
            for (const [id, uri] of missingFolders) {
                missingEntries += `<li><label><input type="checkbox" checked="checked" name="deleteFolder[]" value="${id}"> ${uri}</label></li>`;
            }
            for (const [id, uri] of missingFiles) {
                missingEntries += `<li><label><input type="checkbox" checked="checked" name="deleteAsset[]" value="${id}"> ${uri}</label></li>`;
            }
            $confirmBody.append($(`
                <h2>${Craft.t('app', 'Missing {items}', translationParams)}</h2>
                <p>${Craft.t('app', 'The following {items} could not be found. Should they be deleted from the index?', translationParams)}</p>
                <ul>
                    ${missingEntries}
                </ul>
            `));
        }
        const $modal = $('<form class="modal fitted confirmmodal"/>').appendTo(Garnish.$bod);
        const $body = $('<div class="body"/>').appendTo($modal).html($confirmBody.html());
        const $footer = $('<footer class="footer"/>').appendTo($modal);
        const $buttons = $('<div class="buttons right"/>').appendTo($footer);
        const modal = new Garnish.Modal($modal, {
            hideOnEsc: false,
            hideOnShadeClick: false,
        });
        if (haveMissingItems) {
            let $cancelBtn = $('<button/>', {
                type: 'button',
                class: 'btn',
                text: Craft.t('app', 'Keep them'),
            })
                .appendTo($buttons);
            $('<button/>', {
                type: 'submit',
                class: 'btn submit',
                text: Craft.t('app', 'Delete them'),
            }).appendTo($buttons);
        }
        else {
            $('<button/>', {
                type: 'submit',
                class: 'btn submit',
                text: Craft.t('app', 'OK'),
            }).appendTo($buttons);
        }
        Craft.initUiElements($body);
        modal.updateSizeAndPosition();
        $modal.on('submit', (ev) => {
            ev.preventDefault();
            modal.settings.onHide = $.noop;
            modal.hide();
            const postData = Garnish.getPostData($body);
            const postParams = Craft.expandPostArray(postData);
            postParams.sessionId = session.getSessionId();
            Craft.postActionRequest(IndexingActions.FINISH, postParams, this.processResponse.bind(this));
        });
    }
    performIndexingStep() {
        if (this._currentIndexingSession) {
            Craft.postActionRequest(IndexingActions.PROCESS, { sessionId: this._currentIndexingSession }, this.processResponse.bind(this));
            return;
        }
        else {
            for (const session of Object.values(this.indexingSessions)) {
                if (session.getSessionStatus() !== SessionStatus.QUEUE && session.getSessionStatus() !== SessionStatus.CLI) {
                    this._currentIndexingSession = session.getSessionId();
                    break;
                }
            }
        }
        if (this._currentIndexingSession) {
            this.performIndexingStep();
        }
    }
    stopIndexingSession(session) {
        Craft.postActionRequest(IndexingActions.STOP, { sessionId: session.getSessionId() }, this.processResponse.bind(this));
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
        if (this.indexingSessionData.isCli) {
            return SessionStatus.CLI;
        }
        if (this.indexingSessionData.queueId) {
            return SessionStatus.QUEUE;
        }
        if (this.indexingSessionData.actionRequired) {
            return SessionStatus.ACTIONREQUIRED;
        }
        if (this.indexer.currentIndexingSession === this.indexingSessionData.id) {
            return SessionStatus.ACTIVE;
        }
        return SessionStatus.WAITING;
    }
    /**
     * Create row html as a JQuery object based on an indexing sessions
     * @param session
     * @private
     */
    getIndexingSessionRowHtml() {
        const $tr = $('<tr class="indexingSession" data-session-id="' + this.getSessionId() + '">');
        $tr.append('<td>' + Object.values(this.indexingSessionData.indexedVolumes).join(', ') + '</td>');
        $tr.append('<td>' + this.indexingSessionData.dateCreated + '</td>');
        $tr.append('<td>' + this.indexingSessionData.dateUpdated + '</td>');
        const $progressCell = $('<td class="progress"></td>').css('position', 'relative');
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
        if (this.getSessionStatus() === SessionStatus.QUEUE || this.getSessionStatus() === SessionStatus.CLI) {
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
                this.indexer.getReviewData(this);
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
            this.indexer.stopIndexingSession(this);
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
            case SessionStatus.WAITING:
                return Craft.t('app', 'Waiting');
                break;
            case SessionStatus.QUEUE:
                return Craft.t('app', 'Running in background');
                break;
            case SessionStatus.CLI:
                return Craft.t('app', 'Running via CLI');
                break;
        }
    }
    /**
     * Return a list of missing entries for this session
     */
    getMissingEntries() {
        return this.indexingSessionData.missingEntries;
    }
    /**
     * Return a list of skipped entries for this session
     */
    getSkippedEntries() {
        return this.indexingSessionData.skippedEntries;
    }
}

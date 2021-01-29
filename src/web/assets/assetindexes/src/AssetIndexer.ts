// @TODO: once webpack stuff is in, move things out to imports.
// Set up interfaces and types
interface ProgressBarInterface {
    new($element: JQuery, displaySteps?: boolean): ProgressBarInterface

    $progressBar: JQuery

    setItemCount(count: number): void

    setProcessedItemCount(count: number): void

    updateProgressBar(): void

    showProgressBar(): void
}

enum SessionStatus {
    STOPPED,
    RUNNING,
    QUEUE
}

enum IndexingActions {
    STOP = 'asset-indexes/stop-indexing-session'
};

// Declare existing variables, mock the things we'll use.
declare var Craft: {
    ProgressBar: ProgressBarInterface,
    t(category: string, message: string): string,
    postActionRequest(action: string, data?: object, callback?: (response: object, textStatus: string) => void): void
};

declare var Garnish: any;

type AssetIndexingSessionModel = {
    readonly id: number,
    readonly totalEntries: number,
    readonly processedEntries: number,
    readonly dateCreated: string,
    readonly dateUpdated: string,
    readonly queueId?: number,
}

type CraftResponse = {
    session?: AssetIndexingSessionModel
    stop?: number
    error?: string
}

/**
 * Actual classes start here
 */

// Asset Indexer
// =====================================================================================
class AssetIndexer {
    private $indexingSessionTable: JQuery;

    private indexingSessions: {
        [key: number]: AssetIndexingSession
    } = {}

    private runningSessions: {
        [key: number]: boolean
    } = {}

    /**
     * @param $element The indexing session table
     * @param sessions Existing indexing sessions
     */
    constructor($indexingSessionTable: JQuery, sessions: AssetIndexingSessionModel[]) {
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
    public updateIndexingSessionData(sessionData: AssetIndexingSessionModel) {
        const indexingSession = this.createSessionFromModel(sessionData);
        this.indexingSessions[indexingSession.getSessionId()] = indexingSession;
        this.renderIndexingSessionRow(indexingSession);
    }

    /**
     * Return a rendered indexing session row based on its id
     * @param sessionId
     */
    protected renderIndexingSessionRow(session: AssetIndexingSession) {
        let $row: JQuery;

        if (!this.indexingSessions[session.getSessionId()]) {
            this.$indexingSessionTable.find('tr[data-session-id="' + session.getSessionId() + '"]').remove();
            return;
        }

        $row = session.getIndexingSessionRowHtml();

        const $existing = this.$indexingSessionTable.find('tr[data-session-id="' + session.getSessionId() + '"]');

        if ($existing.length > 0) {
            $existing.replaceWith($row);
        } else {
            this.$indexingSessionTable.find('tbody').append($row);
        }
    }

    /**
     * Remove an indexing session
     * @param sessionId
     * @protected
     */
    protected discardIndexingSession(sessionId: number): void {
        const session = this.indexingSessions[sessionId];
        delete this.indexingSessions[sessionId];
        delete this.runningSessions[sessionId];

        this.renderIndexingSessionRow(session)
    }

    /**
     * Process an indexing response.
     *
     * @param response
     * @param textStatus
     */
    public processResponse(response: CraftResponse, textStatus: string): void {
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

    public stopIndexingSession(sessionId: number): void {
        Craft.postActionRequest(IndexingActions.STOP, {sessionId: sessionId}, this.processResponse.bind(this));
    }

    /**
     * Create a session from the data model.
     *
     * @param sessionData
     * @private
     */
    private createSessionFromModel(sessionData: AssetIndexingSessionModel): AssetIndexingSession {
        return new AssetIndexingSession(sessionData, this.getSessionStatus(sessionData), this);
    }

    /**
     * Get session stat
     * @param session
     * @private
     */
    private getSessionStatus(sessionData: AssetIndexingSessionModel): SessionStatus {
        if (sessionData.queueId) {
            return SessionStatus.QUEUE;
        }

        return this.runningSessions[sessionData.id] ? SessionStatus.RUNNING : SessionStatus.STOPPED;
    }
}

class AssetIndexingSession {
    private readonly indexingSessionData: AssetIndexingSessionModel
    private readonly status: SessionStatus
    private readonly indexer: AssetIndexer

    constructor(model: AssetIndexingSessionModel, status: SessionStatus, indexer: AssetIndexer) {
        this.indexingSessionData = model;
        this.status = status;
        this.indexer = indexer;
    }

    /**
     * Get the session id
     */
    public getSessionId(): number {
        return this.indexingSessionData.id;
    }

    /**
     * Create row html as a JQuery object based on an indexing sessions
     * @param session
     * @private
     */
    public getIndexingSessionRowHtml(): JQuery {
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
    public getActionButtons(): JQuery {
        if (this.status === SessionStatus.QUEUE) {
            return $();
        }

        const $buttons = $('<div class="buttons"></div>');
        let startStopMessage: string;

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
    public getSessionStatusMessage(): string {
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

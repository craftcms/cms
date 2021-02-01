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
    ACTIONREQUIRED,
    ACTIVE,
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
    readonly indexedVolumes: string,
    readonly totalEntries: number,
    readonly processedEntries: number,
    readonly dateCreated: string,
    readonly dateUpdated: string,
    readonly queueId?: number,
    readonly actionRequired: boolean
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

    private currentIndexingSession: number | null;

    private indexingSessions: {
        [key: number]: AssetIndexingSession
    } = {}

    /**
     * @param $element The indexing session table
     * @param sessions Existing indexing sessions
     */
    constructor($indexingSessionTable: JQuery, sessions: AssetIndexingSessionModel[]) {
        this.$indexingSessionTable = $indexingSessionTable;
        this.indexingSessions = {};
        this.currentIndexingSession = null;

        for (const session of sessions) {
            this.updateIndexingSessionData(session);
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

            if (this.$indexingSessionTable.find('tbody tr').length == 0) {
                this.$indexingSessionTable.addClass('hidden');
            }

            return;
        }

        $row = session.getIndexingSessionRowHtml();

        const $existing = this.$indexingSessionTable.find('tr[data-session-id="' + session.getSessionId() + '"]');

        if ($existing.length > 0) {
            $existing.replaceWith($row);
        } else {
            this.$indexingSessionTable.find('tbody').append($row);
        }

        this.$indexingSessionTable.removeClass('hidden');
    }

    /**
     * Remove an indexing session
     * @param sessionId
     * @protected
     */
    protected discardIndexingSession(sessionId: number): void {
        const session = this.indexingSessions[sessionId];
        delete this.indexingSessions[sessionId];

        if (this.currentIndexingSession === sessionId) {
            this.currentIndexingSession = null;
        }

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
            this.renderIndexingSessionRow(session);

            if (!this.currentIndexingSession) {
                this.currentIndexingSession = session.getSessionId();
            }

            this.performIndexingStep();
        }

        if (response.stop) {
            this.discardIndexingSession(response.stop);
        }
    }

    public performIndexingStep(): void
    {

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
        return new AssetIndexingSession(sessionData, this);
    }
}

class AssetIndexingSession {
    private readonly indexingSessionData: AssetIndexingSessionModel
    private readonly indexer: AssetIndexer

    constructor(model: AssetIndexingSessionModel,indexer: AssetIndexer) {
        this.indexingSessionData = model;
        this.indexer = indexer;
    }

    /**
     * Get the session id
     */
    public getSessionId(): number {
        return this.indexingSessionData.id;
    }

    public getSessionStatus(): SessionStatus {
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
    public getIndexingSessionRowHtml(): JQuery {
        const $tr = $('<tr class="indexingSession" data-session-id="' + this.getSessionId() + '">');
        $tr.data('session-id', this.indexingSessionData.id).data('as-queue', this.indexingSessionData.queueId ? this.indexingSessionData.queueId : null);
        $tr.append('<td>' + this.indexingSessionData.indexedVolumes + '</td>');
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
    public getSessionStatusMessage(): string {
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

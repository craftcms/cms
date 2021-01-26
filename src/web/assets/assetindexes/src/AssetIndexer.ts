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

// Declare existing variables, mock the things we'll use.
declare var Craft: {
    ProgressBar: ProgressBarInterface,
    t(category: string, message: string): string
};

declare var Garnish: any;

type AssetIndexingSessionModel = {
    readonly id: number,
    readonly totalEntries: number,
    readonly processedEntries: number,
    readonly started: string,
    readonly updated: string,
    readonly queueId?: number,
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
    private runningSessions: number[] = [];

    /**
     * @param $element The indexing session table
     * @param sessions Existing indexing sessions
     */
    constructor($indexingSessionTable: JQuery, sessions: AssetIndexingSessionModel[]) {
        this.$indexingSessionTable = $indexingSessionTable;
        this.indexingSessions = {};

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
        const indexingSession = new AssetIndexingSession(sessionData, this.getSessionStatus(sessionData));
        this.indexingSessions[indexingSession.getSessionId()] = indexingSession;
        this.renderIndexingSessionRow(indexingSession);
    }

    /**
     * Get indexing session data by its id
     * @param id
     */
    public getIndexingSessionData(id: number): AssetIndexingSession | null {
        return this.indexingSessions[id] ? this.indexingSessions[id] : null;
    }

    /**
     * Return a rendered indexing session row based on its id
     * @param sessionId
     */
    public renderIndexingSessionRow(session: AssetIndexingSession) {
        let $row: JQuery;
        if (session) {
            $row = session.getIndexingSessionRowHtml();
        } else {
            return;
        }

        const $existing = this.$indexingSessionTable.find('tr[data-session-id="' + session.getSessionId() + '"]');

        if ($existing.length > 0) {
            $existing.replaceWith($row);
        } else {
            this.$indexingSessionTable.find('tbody').append($row);
        }
    }

    public processResponse(response: any) {
        console.log(response);
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

        return this.runningSessions.includes(sessionData.id) ? SessionStatus.RUNNING : SessionStatus.STOPPED;
    }
}

class AssetIndexingSession {
    private readonly indexingSessionData: AssetIndexingSessionModel
    private readonly status: SessionStatus

    constructor(model: AssetIndexingSessionModel, status: SessionStatus) {
        this.indexingSessionData = model;
        this.status = status;
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
        const $tr = $('<tr class="indexingSession">');
        $tr.data('session-id', this.indexingSessionData.id).data('as-queue', this.indexingSessionData.queueId ? this.indexingSessionData.queueId : null);
        $tr.append('<td>' + this.indexingSessionData.started + '</td>');
        $tr.append('<td>' + this.indexingSessionData.updated + '</td>');

        const $progressCell = $('<td class="progress"></td>').data('total', this.indexingSessionData.totalEntries).data('processed', this.indexingSessionData.processedEntries).css('position', 'relative');
        const progressBar = new Craft.ProgressBar($progressCell, false);
        progressBar.setItemCount(this.indexingSessionData.totalEntries);
        progressBar.setProcessedItemCount(this.indexingSessionData.processedEntries)
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
                }).text(endMessage));
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
    public getSessionStatusMessage(): string
    {
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

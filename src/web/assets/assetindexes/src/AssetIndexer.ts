enum SessionStatus {
  ACTIONREQUIRED,
  ACTIVE,
  WAITING,
}

enum IndexingActions {
  START = 'asset-indexes/start-indexing',
  STOP = 'asset-indexes/stop-indexing-session',
  PROCESS = 'asset-indexes/process-indexing-session',
  OVERVIEW = 'asset-indexes/indexing-session-overview',
  FINISH = 'asset-indexes/finish-indexing-session',
}

type StringHash = {
  [key: number]: string;
};

type AssetIndexingSessionModel = {
  readonly id: number;
  readonly indexedVolumes: StringHash;
  readonly totalEntries: number;
  readonly processedEntries: number;
  readonly dateCreated: string;
  readonly actionRequired: boolean;
  readonly skippedEntries: string[];
  readonly missingEntries: StringHash;
  readonly processIfRootEmpty: boolean;
  readonly listEmptyFolders: boolean;
};

type CraftResponse = {
  message?: string;
  errors?: Array<string>;
  session?: AssetIndexingSessionModel;
  stop?: number;
  error?: string;
  skipDialog?: boolean;
};

type ConcurrentTask = {
  sessionId: number;
  action: string;
  params: any;
  callback?: () => void;
};

/**
 * Actual classes start here
 */

// Asset Indexer
// =====================================================================================
export class AssetIndexer {
  private $indexingSessionTable: JQuery;
  private _currentIndexingSession: number | null = null;
  private _maxConcurrentConnections: number;
  private _currentConnectionCount = 0;
  private _tasksWaiting: ConcurrentTask[] = [];
  private _priorityTasks: ConcurrentTask[] = [];
  private _prunedSessionIds: number[] = [];
  private _currentlyReviewing = false;

  private indexingSessions: {
    [key: number]: AssetIndexingSession;
  } = {};

  /**
   * @param $element The indexing session table
   * @param sessions Existing indexing sessions
   */
  constructor(
    $indexingSessionTable: JQuery,
    sessions: AssetIndexingSessionModel[],
    maxConcurrentConnections: number = 3
  ) {
    this._maxConcurrentConnections = maxConcurrentConnections;
    this.$indexingSessionTable = $indexingSessionTable;
    this.indexingSessions = {};
    let reviewSessionId: number = 0;

    for (const sessionModel of sessions) {
      let session = this.createSessionFromModel(sessionModel);

      if (
        session.getSessionStatus() === SessionStatus.ACTIONREQUIRED &&
        !reviewSessionId
      ) {
        reviewSessionId = session.getSessionId();
      }

      if (
        !reviewSessionId &&
        this._currentIndexingSession == null &&
        session.getSessionStatus() !== SessionStatus.ACTIONREQUIRED
      ) {
        this._currentIndexingSession = session.getSessionId();
      }

      this.updateIndexingSessionData(session);
    }

    if (this._currentIndexingSession) {
      this.performIndexingStep();
    }
  }

  get currentIndexingSession(): number | null {
    return this._currentIndexingSession;
  }

  /**
   * Update indexing session store
   * @param session
   */
  public updateIndexingSessionData(indexingSession: AssetIndexingSession) {
    this.indexingSessions[indexingSession.getSessionId()] = indexingSession;
    this.renderIndexingSessionRow(indexingSession);
  }

  /**
   * Return a rendered indexing session row based on its id
   * @param sessionId
   */
  protected renderIndexingSessionRow(session: AssetIndexingSession) {
    let $row: JQuery;

    if (session === undefined) {
      return;
    }

    if (
      !this.indexingSessions[session.getSessionId()] ||
      this._prunedSessionIds.includes(session.getSessionId())
    ) {
      this.$indexingSessionTable
        .find('tr[data-session-id="' + session.getSessionId() + '"]')
        .remove();

      if (this.$indexingSessionTable.find('tbody tr').length == 0) {
        this.$indexingSessionTable.addClass('hidden');
      }

      return;
    }

    $row = session.getIndexingSessionRowHtml();

    const $existing = this.$indexingSessionTable.find(
      'tr[data-session-id="' + session.getSessionId() + '"]'
    );

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

    if (this._currentIndexingSession === sessionId) {
      this._currentIndexingSession = null;
    }

    this.renderIndexingSessionRow(session);

    this.runTasks();
  }

  /**
   * Process a failed indexing response.
   *
   * @param response
   */
  public processFailureResponse(response): void {
    const responseData: CraftResponse = response.data;
    this._currentConnectionCount--;
    this._updateCurrentIndexingSession();

    alert(responseData.message);

    if (responseData.stop) {
      this.discardIndexingSession(responseData.stop);
    }

    // A mere error shall not stop the party.
    this.runTasks();
    return;
  }

  /**
   * Process a successful indexing response.
   *
   * @param response
   */
  public processSuccessResponse(response): void {
    const responseData: CraftResponse = response.data;
    this._currentConnectionCount--;

    if (responseData.session) {
      const session = this.createSessionFromModel(responseData.session);
      this.indexingSessions[session.getSessionId()] = session;
      this.renderIndexingSessionRow(session);

      this._updateCurrentIndexingSession();

      if (
        session.getSessionStatus() === SessionStatus.ACTIONREQUIRED &&
        !responseData.skipDialog
      ) {
        if (
          !this._prunedSessionIds.includes(
            this._currentIndexingSession as number
          )
        ) {
          this.reviewSession(session);
        } else {
          this.runTasks();
        }
      } else if (
        !this._prunedSessionIds.includes(this._currentIndexingSession as number)
      ) {
        this.performIndexingStep();
      } else {
        this.runTasks();
      }
    }

    this._updateCurrentIndexingSession();

    if (responseData.stop) {
      this.discardIndexingSession(responseData.stop);
    }
  }

  public getReviewData(session: AssetIndexingSession): void {
    const task: ConcurrentTask = {
      sessionId: session.getSessionId(),
      action: IndexingActions.OVERVIEW,
      params: {sessionId: session.getSessionId()},
      callback: () => {
        this.renderIndexingSessionRow(session);
      },
    };

    this.enqueueTask(task);
  }

  public reviewSession(session: AssetIndexingSession): void {
    if (this._currentlyReviewing) {
      return;
    }

    this._currentlyReviewing = true;
    this.pruneWaitingTasks(session.getSessionId());
    let $confirmBody = $('<div></div>');

    const missingEntries = session.getMissingEntries() as {
      files: StringHash;
      folders: StringHash;
    };
    const missingFiles = missingEntries.files
      ? Object.entries(missingEntries.files)
      : [];
    const missingFolders = missingEntries.folders
      ? Object.entries(missingEntries.folders)
      : [];
    const skippedFiles = session.getSkippedEntries();

    if (skippedFiles.length) {
      let skippedFilesList = '';

      for (const skippedFile of skippedFiles) {
        skippedFilesList += `<li>${skippedFile}</li>`;
      }

      $confirmBody.append(`
                <h2>${Craft.t('app', 'Skipped files')}</h2>
                <p>${Craft.t(
                  'app',
                  'The following items were not indexed.'
                )}</p>
                <ul>
                    ${skippedFilesList}
                </ul>
            `);
    }

    const haveMissingItems = missingFiles.length || missingFolders.length;

    if (haveMissingItems) {
      if (missingFolders.length) {
        let missingEntries = '';
        for (const [id, uri] of missingFolders) {
          missingEntries += `<li><label><input type="checkbox" checked="checked" name="deleteFolder[]" value="${id}"> ${uri}</label></li>`;
        }

        const translationParams = {items: 'folders'};
        let missingItemsHeading = this._getMissingItemsHeading(
          'folders',
          translationParams,
          session
        );
        let missingItemsCopy = this._getMissingItemsCopy(
          'folders',
          translationParams,
          session
        );

        $confirmBody.append(
          $(`
                <h2>${missingItemsHeading}</h2>
                <p>${missingItemsCopy}</p>
                <ul>
                    ${missingEntries}
                </ul>
            `)
        );
      }

      if (missingFiles.length) {
        let missingEntries = '';
        for (const [id, uri] of missingFiles) {
          missingEntries += `<li><label><input type="checkbox" checked="checked" name="deleteAsset[]" value="${id}"> ${uri}</label></li>`;
        }

        const translationParams = {items: 'files'};
        let missingItemsHeading = this._getMissingItemsHeading(
          'files',
          translationParams,
          session
        );
        let missingItemsCopy = this._getMissingItemsCopy(
          'files',
          translationParams,
          session
        );

        $confirmBody.append(
          $(`
                <h2>${missingItemsHeading}</h2>
                <p>${missingItemsCopy}</p>
                <ul>
                    ${missingEntries}
                </ul>
            `)
        );
      }
    }

    const $modal = $('<form class="modal fitted confirmmodal"/>').appendTo(
      Garnish.$bod
    );
    const $body = $('<div class="body"/>')
      .appendTo($modal)
      .html($confirmBody.html());
    const $footer = $('<footer class="footer"/>').appendTo($modal);
    const $buttons = $('<div class="buttons right"/>').appendTo($footer);

    const modal = new Garnish.Modal($modal, {
      hideOnEsc: false,
      hideOnShadeClick: false,
      onHide: () => {
        this._currentlyReviewing = false;
      },
    });

    if (haveMissingItems) {
      let $cancelBtn = $('<button/>', {
        type: 'button',
        class: 'btn',
        text: Craft.t('app', 'Keep them'),
      })
        .on('click', (ev) => {
          ev.preventDefault();
          this.stopIndexingSession(session);
          modal.hide();
        })
        .appendTo($buttons);

      $('<button/>', {
        type: 'submit',
        class: 'btn submit',
        text: Craft.t('app', 'Delete them'),
      }).appendTo($buttons);
    } else {
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

      modal.hide();

      const postData = Garnish.getPostData($body);
      const postParams = Craft.expandPostArray(postData);
      postParams.sessionId = session.getSessionId();

      // Make this the next task for sure?
      const task: ConcurrentTask = {
        sessionId: session.getSessionId(),
        action: IndexingActions.FINISH,
        params: postParams,
      };

      this.enqueueTask(task, true);
    });
  }

  private _getMissingItemsHeading(
    missingType: string,
    translationParams: object,
    session: AssetIndexingSession
  ): string {
    let missingItemsHeading = Craft.t(
      'app',
      'Missing {items}',
      translationParams
    );

    if (missingType == 'folders' && session.getListEmptyFolders()) {
      missingItemsHeading = Craft.t(
        'app',
        'Missing or empty {items}',
        translationParams
      );
    }

    return missingItemsHeading;
  }

  private _getMissingItemsCopy(
    missingType: string,
    translationParams: object,
    session: AssetIndexingSession
  ): string {
    let missingItemsCopy = Craft.t(
      'app',
      'The following {items} could not be found. Should they be deleted from the index?',
      translationParams
    );

    if (missingType == 'folders' && session.getListEmptyFolders()) {
      missingItemsCopy = Craft.t(
        'app',
        'The following {items} could not be found or are empty. Should they be deleted from the index?',
        translationParams
      );
    }

    return missingItemsCopy;
  }

  public startIndexing(data: any, cb: () => void): void {
    Craft.sendActionRequest('POST', IndexingActions.START, {data})
      .then((response) => this.processSuccessResponse(response))
      .catch(({response}) => this.processFailureResponse(response))
      .finally(() => cb());
  }

  public performIndexingStep(): void {
    if (!this._currentIndexingSession) {
      this._updateCurrentIndexingSession();
    }

    if (!this._currentIndexingSession) {
      return;
    }

    const session = this.indexingSessions[this._currentIndexingSession];
    const concurrentSlots =
      this._maxConcurrentConnections - this._currentConnectionCount;

    // Queue up at least enough tasks to use up all the free connections of finish the session.
    for (
      let i = 0;
      i < Math.min(concurrentSlots, session.getEntriesRemaining());
      i++
    ) {
      const task: ConcurrentTask = {
        sessionId: session.getSessionId(),
        action: IndexingActions.PROCESS,
        params: {sessionId: this._currentIndexingSession},
      };

      this.enqueueTask(task);
    }

    if (session.getProcessIfRootEmpty()) {
      const task: ConcurrentTask = {
        sessionId: session.getSessionId(),
        action: IndexingActions.PROCESS,
        params: {sessionId: this._currentIndexingSession},
      };

      this.enqueueTask(task);
    }
  }

  /**
   * Stop and discard an indexing session.
   *
   * @param session
   */

  public stopIndexingSession(session: AssetIndexingSession): void {
    this.pruneWaitingTasks(session.getSessionId());

    const task: ConcurrentTask = {
      sessionId: session.getSessionId(),
      action: IndexingActions.STOP,
      params: {sessionId: session.getSessionId()},
    };

    this.enqueueTask(task, true);
  }

  /**
   * Pune the waiting task list by removing all tasks for a session id.
   *
   * @param sessionId
   */
  public pruneWaitingTasks(sessionId: number): void {
    const newTaskList: ConcurrentTask[] = [];
    let modified = false;

    this._prunedSessionIds.push(sessionId);

    for (const task of this._tasksWaiting) {
      if (task.sessionId !== sessionId) {
        newTaskList.push(task);
      } else {
        modified = true;
      }
    }

    if (modified) {
      this._tasksWaiting = newTaskList;
    }
  }

  protected enqueueTask(task: ConcurrentTask, prioritize = false): void {
    if (prioritize) {
      this._priorityTasks.push(task);
    } else {
      this._tasksWaiting.push(task);
    }

    this.runTasks();
  }

  protected runTasks(): void {
    if (
      this._tasksWaiting.length + this._priorityTasks.length === 0 ||
      this._currentConnectionCount >= this._maxConcurrentConnections
    ) {
      return;
    }

    while (
      this._tasksWaiting.length + this._priorityTasks.length !== 0 &&
      this._currentConnectionCount < this._maxConcurrentConnections
    ) {
      this._currentConnectionCount++;
      const task =
        this._priorityTasks.length > 0
          ? this._priorityTasks.shift()!
          : this._tasksWaiting.shift()!;

      Craft.sendActionRequest('POST', task.action, {data: task.params})
        .then((response) => this.processSuccessResponse(response))
        .catch(({response}) => this.processFailureResponse(response))
        .finally(() => {
          if (task.callback) {
            task.callback();
          }
        });
    }
  }

  private _updateCurrentIndexingSession(): void {
    for (const session of Object.values(this.indexingSessions)) {
      if (session.getSessionStatus() !== SessionStatus.ACTIONREQUIRED) {
        this._currentIndexingSession = session.getSessionId();
        return;
      }
    }
  }

  /**
   * Create a session from the data model.
   *
   * @param sessionData
   * @private
   */
  private createSessionFromModel(
    sessionData: AssetIndexingSessionModel
  ): AssetIndexingSession {
    return new AssetIndexingSession(sessionData, this);
  }
}

class AssetIndexingSession {
  private readonly indexingSessionData: AssetIndexingSessionModel;
  private readonly indexer: AssetIndexer;

  constructor(model: AssetIndexingSessionModel, indexer: AssetIndexer) {
    this.indexingSessionData = model;
    this.indexer = indexer;
  }

  /**
   * Get the session id
   */
  public getSessionId(): number {
    return this.indexingSessionData.id;
  }

  public getProcessIfRootEmpty(): boolean {
    return this.indexingSessionData.processIfRootEmpty;
  }

  public getListEmptyFolders(): boolean {
    return this.indexingSessionData.listEmptyFolders;
  }

  /**
   * Get the remaining entry count for this sessions.
   */
  public getEntriesRemaining(): number {
    return (
      this.indexingSessionData.totalEntries -
      this.indexingSessionData.processedEntries
    );
  }

  /**
   * Get the session status.
   */
  public getSessionStatus(): SessionStatus {
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
  public getIndexingSessionRowHtml(): JQuery {
    const $tr = $(
      '<tr class="indexingSession" data-session-id="' +
        this.getSessionId() +
        '">'
    );
    const $td = $('<td/>').appendTo($tr);
    const $ul = $('<ul/>').appendTo($td);
    for (const volume of Object.values(
      this.indexingSessionData.indexedVolumes
    )) {
      $('<li/>', {
        text: volume,
      }).appendTo($ul);
    }
    $tr.append('<td>' + this.indexingSessionData.dateCreated + '</td>');

    const $progressCell = $(
      '<td class="progress"><div class="progressContainer"></div></td>'
    ).css('position', 'relative');
    const progressBar = new Craft.ProgressBar(
      $progressCell.find('.progressContainer'),
      false
    );

    progressBar.setItemCount(this.indexingSessionData.totalEntries);
    progressBar.setProcessedItemCount(
      this.indexingSessionData.processedEntries
    );
    progressBar.updateProgressBar();
    progressBar.showProgressBar();
    $progressCell.data('progressBar', progressBar);
    $progressCell
      .find('.progressContainer')
      .append(
        `<div class="progressInfo">${this.indexingSessionData.processedEntries} / ${this.indexingSessionData.totalEntries}</div>`
      );
    $tr.append($progressCell);

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
    const $buttons = $('<div class="buttons"></div>');

    if (this.getSessionStatus() == SessionStatus.ACTIONREQUIRED) {
      const reviewMessage = Craft.t('app', 'Review');
      $buttons.append(
        $('<button />', {
          type: 'button',
          class: 'btn submit',
          title: reviewMessage,
          'aria-label': reviewMessage,
        })
          .text(reviewMessage)
          .on('click', (ev) => {
            const $container = $(ev.target).parent();

            if ($container.hasClass('disabled')) {
              return;
            }
            $container.addClass('disabled');

            this.indexer.getReviewData(this);
          })
      );
    }

    const discardMessage = Craft.t('app', 'Discard');
    $buttons.append(
      $('<button />', {
        type: 'button',
        class: 'btn submit',
        title: discardMessage,
        'aria-label': discardMessage,
      })
        .text(discardMessage)
        .on('click', (ev) => {
          if ($buttons.hasClass('disabled')) {
            return;
          }

          $buttons.addClass('disabled');

          this.indexer.stopIndexingSession(this);
        })
    );

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
      case SessionStatus.WAITING:
        return Craft.t('app', 'Waiting');
        break;
    }
  }

  /**
   * Return a list of missing entries for this session
   */
  public getMissingEntries(): StringHash {
    return this.indexingSessionData.missingEntries;
  }

  /**
   * Return a list of skipped entries for this session
   */
  public getSkippedEntries(): string[] {
    return this.indexingSessionData.skippedEntries;
  }
}

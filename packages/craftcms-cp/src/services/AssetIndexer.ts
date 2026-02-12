import {actionClient} from '../utilities/api/actionClient.js';
import type {AxiosResponse} from 'axios';
import type {DateObject} from '@src/types';

/**
 * API action endpoints for asset indexing operations.
 */
export const IndexingActions = {
  START: 'asset-indexes/start-indexing',
  STOP: 'asset-indexes/stop-indexing-session',
  PROCESS: 'asset-indexes/process-indexing-session',
  OVERVIEW: 'asset-indexes/indexing-session-overview',
  FINISH: 'asset-indexes/finish-indexing-session',
} as const;

/**
 * Missing entries categorized by type (files and folders).
 */
export interface MissingEntries {
  files?: Record<string, string>;
  folders?: Record<string, string>;
}

/**
 * Data model for an asset indexing session.
 */
export interface IndexingSession {
  /** Unique session identifier */
  readonly id: number;
  /** Map of volume IDs to volume names being indexed */
  readonly indexedVolumes: Record<number, string>;
  /** Total number of entries to process */
  readonly totalEntries: number;
  /** Number of entries processed so far */
  readonly processedEntries: number;
  /** Updated date */
  readonly dateUpdated: DateObject;
  /** Whether the session requires user action */
  readonly actionRequired: boolean;
  /** List of file paths that were skipped during indexing */
  readonly skippedEntries: string[];
  /** Files and folders that are missing from the filesystem */
  readonly missingEntries: MissingEntries;
  /** Whether to process if root folder is empty */
  readonly processIfRootEmpty: boolean;
  /** Whether to list empty folders as missing */
  readonly listEmptyFolders: boolean;
}

/**
 * API response from indexing operations.
 */
export interface IndexingResponse {
  message?: string;
  errors?: string[];
  session?: IndexingSession;
  /** Session ID that was stopped/removed */
  stop?: number;
  error?: string;
  /** Whether to skip the review dialog */
  skipDialog?: boolean;
}

/**
 * Parameters for starting a new indexing session.
 */
export interface StartIndexingParams {
  /** Volume IDs to index */
  volumes: (string | number)[];
  /** Whether to cache remote images locally */
  cacheImages?: boolean;
  /** Whether to list empty folders */
  listEmptyFolders?: boolean;
}

/**
 * Parameters for finishing an indexing session.
 */
export interface FinishIndexingParams {
  sessionId: number;
  /** Folder IDs to delete */
  deleteFolder?: Array<string | number>;
  /** Asset IDs to delete */
  deleteAsset?: Array<string | number>;
}

/**
 * Event types emitted by the AssetIndexer.
 */
export type IndexerEventType = 'change' | 'error' | 'complete';

/**
 * Event listener callback type.
 */
export type IndexerEventListener<T = unknown> = (data: T) => void;

/**
 * Event data for the `change` event, fired whenever session state changes.
 */
export interface ChangeEventData {
  sessions: IndexingSession[];
  currentSessionId: number | null;
  /** Set when a session has finished processing and requires user review. */
  reviewSessionId?: number;
}

/**
 * Event data for errors.
 */
export interface ErrorEventData {
  message: string;
  sessionId?: number;
}

/**
 * Internal task representation for the concurrent queue.
 */
interface QueuedTask {
  sessionId: number;
  action: string;
  params: Record<string, unknown>;
  priority: boolean;
}

/**
 * Asset Indexer Service
 *
 * Manages asset indexing sessions with support for concurrent connections,
 * task queuing, and event-based state updates. This is a pure data/logic
 * service with no rendering concerns.
 *
 * Public methods are fire-and-forget — call them to initiate operations,
 * then listen to `change`, `error`, and `complete` events to react to
 * state updates. The one exception is `startIndexing()`, which returns
 * a promise so callers can detect immediate failures.
 *
 * @example Basic usage
 * ```ts
 * const indexer = new AssetIndexer();
 *
 * indexer.on('change', ({ sessions, currentSessionId, reviewSessionId }) => {
 *   console.log('Sessions:', sessions);
 *   if (reviewSessionId) {
 *     console.log('Review needed for session', reviewSessionId);
 *   }
 * });
 *
 * indexer.on('error', ({ message }) => console.error(message));
 * indexer.on('complete', () => console.log('All done'));
 *
 * await indexer.startIndexing({ volumes: [1, 2, 3], cacheImages: true });
 * ```
 *
 * @example Resuming existing sessions
 * ```ts
 * const indexer = new AssetIndexer({
 *   existingSessions: sessionsFromServer,
 * });
 * ```
 *
 * @example Fire-and-forget operations
 * ```ts
 * indexer.stopSession(sessionId);        // no await needed
 * indexer.getSessionOverview(sessionId); // no await needed
 * indexer.finishSession({ sessionId });  // no await needed
 * // state updates arrive via 'change' event
 * ```
 */
export class AssetIndexer {
  /** Maximum number of concurrent API connections */
  readonly maxConcurrentConnections: number;

  /** Map of session IDs to session data */
  #sessions = new Map<number, IndexingSession>();

  /** Currently active session ID */
  #currentSessionId: number | null = null;

  /** Number of active API connections */
  #activeConnectionCount = 0;

  /** Queue of tasks waiting to be executed */
  #taskQueue: QueuedTask[] = [];

  /** Priority queue for urgent tasks (stop, finish) */
  #priorityQueue: QueuedTask[] = [];

  /** Session IDs that have been pruned (stopped/finished) */
  #prunedSessionIds = new Set<number>();

  /** Event listeners */
  #listeners = new Map<IndexerEventType, Set<IndexerEventListener>>();

  /**
   * Creates a new AssetIndexer instance.
   *
   * @param options.existingSessions - Existing sessions to resume
   * @param options.maxConcurrentConnections - Maximum concurrent API connections (default: 3)
   * @param options.autoResume - Whether to automatically resume processing (default: true)
   */
  constructor(
    options: {
      existingSessions?: IndexingSession[];
      maxConcurrentConnections?: number;
      autoResume?: boolean;
    } = {}
  ) {
    const {
      existingSessions = [],
      maxConcurrentConnections = 3,
      autoResume = true,
    } = options;

    this.maxConcurrentConnections = maxConcurrentConnections;

    for (const session of existingSessions) {
      this.#sessions.set(session.id, session);
    }

    if (autoResume) {
      this.#updateCurrentSession();

      if (this.#currentSessionId !== null) {
        this.#performIndexingStep();
      }
    }
  }

  /**
   * Gets all sessions as an array.
   */
  getSessions(): IndexingSession[] {
    return Array.from(this.#sessions.values());
  }

  /**
   * Gets the current session ID.
   */
  getCurrentSessionId(): number | null {
    return this.#currentSessionId;
  }

  /**
   * Whether any tasks are currently being processed.
   */
  isProcessing(): boolean {
    return this.#activeConnectionCount > 0;
  }

  /**
   * Registers an event listener. Returns an unsubscribe function.
   */
  on<T = unknown>(
    event: IndexerEventType,
    listener: IndexerEventListener<T>
  ): () => void {
    if (!this.#listeners.has(event)) {
      this.#listeners.set(event, new Set());
    }
    this.#listeners.get(event)!.add(listener as IndexerEventListener);

    return () => {
      this.#listeners.get(event)?.delete(listener as IndexerEventListener);
    };
  }

  /**
   * Starts a new indexing session. This is the only async public method —
   * it returns the response so callers can detect immediate failures.
   */
  async startIndexing(
    postData: StartIndexingParams
  ): Promise<AxiosResponse<IndexingResponse>> {
    const response = await actionClient.post<IndexingResponse>(
      IndexingActions.START,
      postData
    );

    const {data} = response;

    if (data.session) {
      this.#sessions.set(data.session.id, data.session);
      this.#currentSessionId = data.session.id;
      this.#emitChange();

      if (!data.stop) {
        this.#performIndexingStep();
      }
    }

    if (data.stop) {
      this.#removeSession(data.stop);
    }

    return response;
  }

  /**
   * Stops and discards an indexing session.
   */
  stopSession(sessionId: number): void {
    this.#pruneTasksForSession(sessionId);

    this.#enqueueTask({
      sessionId,
      action: IndexingActions.STOP,
      params: {sessionId},
      priority: true,
    });
  }

  /**
   * Fetches the review data (overview) for a session.
   * The updated session data will arrive via a `change` event.
   */
  getSessionOverview(sessionId: number): void {
    this.#enqueueTask({
      sessionId,
      action: IndexingActions.OVERVIEW,
      params: {sessionId},
      priority: true,
    });
  }

  /**
   * Finishes an indexing session, optionally deleting missing files/folders.
   */
  finishSession(params: FinishIndexingParams): void {
    this.#enqueueTask({
      sessionId: params.sessionId,
      action: IndexingActions.FINISH,
      params,
      priority: true,
    });
  }

  /**
   * Cleans up the indexer, removing all sessions and listeners.
   */
  destroy(): void {
    this.#sessions.clear();
    this.#taskQueue = [];
    this.#priorityQueue = [];
    this.#listeners.clear();
    this.#currentSessionId = null;
    this.#activeConnectionCount = 0;
  }

  // ============================================
  // Private methods
  // ============================================

  #emit<T>(event: IndexerEventType, data: T): void {
    this.#listeners.get(event)?.forEach((listener) => listener(data));
  }

  #emitChange(reviewSessionId?: number): void {
    this.#emit<ChangeEventData>('change', {
      sessions: this.getSessions(),
      currentSessionId: this.#currentSessionId,
      reviewSessionId,
    });
  }

  #removeSession(sessionId: number): void {
    this.#sessions.delete(sessionId);

    if (this.#currentSessionId === sessionId) {
      this.#currentSessionId = null;
    }

    this.#emitChange();
  }

  /**
   * Updates the current session to the next available one.
   */
  #updateCurrentSession(): void {
    for (const [id, session] of this.#sessions) {
      if (!session.actionRequired && !this.#prunedSessionIds.has(id)) {
        this.#currentSessionId = id;
        return;
      }
    }
    this.#currentSessionId = null;
  }

  /**
   * Performs the next indexing step by queueing process tasks.
   */
  #performIndexingStep(): void {
    if (!this.#currentSessionId) {
      this.#updateCurrentSession();
    }

    if (!this.#currentSessionId) {
      return;
    }

    const session = this.#sessions.get(this.#currentSessionId);
    if (!session) {
      return;
    }

    const entriesRemaining = session.totalEntries - session.processedEntries;
    const availableSlots =
      this.maxConcurrentConnections - this.#activeConnectionCount;

    const tasksToQueue = Math.min(availableSlots, entriesRemaining);
    for (let i = 0; i < tasksToQueue; i++) {
      this.#enqueueTask({
        sessionId: session.id,
        action: IndexingActions.PROCESS,
        params: {sessionId: this.#currentSessionId},
        priority: false,
      });
    }

    if (session.processIfRootEmpty) {
      this.#enqueueTask({
        sessionId: session.id,
        action: IndexingActions.PROCESS,
        params: {sessionId: this.#currentSessionId},
        priority: false,
      });
    }
  }

  #pruneTasksForSession(sessionId: number): void {
    this.#prunedSessionIds.add(sessionId);
    this.#taskQueue = this.#taskQueue.filter(
      (task) => task.sessionId !== sessionId
    );
  }

  #enqueueTask(task: QueuedTask): void {
    if (task.priority) {
      this.#priorityQueue.push(task);
    } else {
      this.#taskQueue.push(task);
    }

    this.#runTasks();
  }

  #runTasks(): void {
    if (
      this.#taskQueue.length + this.#priorityQueue.length === 0 ||
      this.#activeConnectionCount >= this.maxConcurrentConnections
    ) {
      return;
    }

    while (
      this.#taskQueue.length + this.#priorityQueue.length > 0 &&
      this.#activeConnectionCount < this.maxConcurrentConnections
    ) {
      this.#activeConnectionCount++;

      const task =
        this.#priorityQueue.length > 0
          ? this.#priorityQueue.shift()!
          : this.#taskQueue.shift()!;

      this.#executeTask(task);
    }
  }

  async #executeTask(task: QueuedTask): Promise<void> {
    try {
      const response = await actionClient.post<IndexingResponse>(
        task.action,
        task.params
      );

      this.#processResponse(response.data);
    } catch (error) {
      this.#processError(error as Error, task);
    } finally {
      this.#activeConnectionCount--;
      this.#runTasks();
    }
  }

  #processResponse(response: IndexingResponse): void {
    let reviewSessionId: number | undefined;

    if (response.session) {
      this.#sessions.set(response.session.id, response.session);
      this.#updateCurrentSession();

      if (response.session.actionRequired && !response.skipDialog) {
        if (!this.#prunedSessionIds.has(response.session.id)) {
          reviewSessionId = response.session.id;
        }
      } else if (!this.#prunedSessionIds.has(response.session.id)) {
        this.#performIndexingStep();
      }
    }

    this.#updateCurrentSession();

    if (response.stop) {
      this.#sessions.delete(response.stop);
      if (this.#currentSessionId === response.stop) {
        this.#currentSessionId = null;
      }
    }

    this.#emitChange(reviewSessionId);

    if (this.#sessions.size === 0) {
      this.#emit('complete', {});
    }
  }

  #processError(error: Error, task: QueuedTask): void {
    this.#updateCurrentSession();

    const message =
      (error as Error & {response?: {data?: {message?: string}}})?.response
        ?.data?.message ||
      error.message ||
      'An error occurred during indexing.';

    this.#emit<ErrorEventData>('error', {
      message,
      sessionId: task.sessionId,
    });

    this.#runTasks();
  }
}

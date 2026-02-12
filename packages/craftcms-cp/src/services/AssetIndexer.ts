import {actionClient} from '../utilities/api/actionClient.js';
import type {AxiosResponse} from 'axios';
import type {DateObject} from '@src/types';

/**
 * Session status indicating the current state of an indexing session.
 */
export enum SessionStatus {
  /** Session requires user review (missing files, skipped entries, etc.) */
  ACTION_REQUIRED = 'actionRequired',
  /** Session is currently being processed */
  ACTIVE = 'active',
  /** Session is queued and waiting to be processed */
  WAITING = 'waiting',
}

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
export type IndexerEventType =
  | 'sessionAdded'
  | 'sessionUpdated'
  | 'sessionRemoved'
  | 'reviewRequired'
  | 'progress'
  | 'error'
  | 'complete';

/**
 * Event listener callback type.
 */
export type IndexerEventListener<T = unknown> = (data: T) => void;

/**
 * Event data for session-related events.
 */
export interface SessionEventData {
  session: IndexingSession;
  status: SessionStatus;
}

/**
 * Event data for progress updates.
 */
export interface ProgressEventData {
  sessionId: number;
  processed: number;
  total: number;
  percent: number;
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
  method?: 'get' | 'post';
  params: Record<string, unknown>;
  priority: boolean;
  resolve: (response: IndexingResponse) => void;
  reject: (error: Error) => void;
}

/**
 * Asset Indexer Service
 *
 * Manages asset indexing sessions with support for concurrent connections,
 * task queuing, and event-based state updates. This is a pure data/logic
 * service with no rendering — consumers handle UI based on events and
 * data returned from methods.
 *
 * @example Basic usage
 * ```ts
 * const indexer = new AssetIndexer();
 *
 * // Listen for events
 * indexer.on('sessionUpdated', ({ session, status }) => {
 *   console.log(`Session ${session.id} is ${status}`);
 * });
 *
 * indexer.on('reviewRequired', ({ session }) => {
 *   // Show review modal with session data
 *   console.log('Missing files:', session.missingEntries.files);
 *   console.log('Skipped files:', session.skippedEntries);
 * });
 *
 * indexer.on('error', ({ message }) => {
 *   showErrorNotification(message);
 * });
 *
 * // Start indexing
 * await indexer.startIndexing({
 *   volumes: [1, 2, 3],
 *   cacheImages: true,
 * });
 * ```
 *
 * @example Resuming existing sessions
 * ```ts
 * const indexer = new AssetIndexer({
 *   existingSessions: sessionsFromServer,
 *   autoResume: true,
 * });
 * ```
 *
 * @example Custom concurrency
 * ```ts
 * const indexer = new AssetIndexer({
 *   maxConcurrentConnections: 5,
 * });
 * ```
 *
 * @example Handling review flow
 * ```ts
 * indexer.on('reviewRequired', ({ session }) => {
 *   // Mark review as started to prevent duplicates
 *   indexer.startReview(session.id);
 *
 *   // Show your review UI with session data
 *   showReviewModal({
 *     skippedEntries: session.skippedEntries,
 *     missingFiles: session.missingEntries.files,
 *     missingFolders: session.missingEntries.folders,
 *     listEmptyFolders: session.listEmptyFolders,
 *     onFinish: (deleteFolder, deleteAsset) => {
 *       indexer.finishSession({
 *         sessionId: session.id,
 *         deleteFolder,
 *         deleteAsset,
 *       });
 *       indexer.endReview();
 *     },
 *     onKeep: () => {
 *       indexer.stopSession(session.id);
 *       indexer.endReview();
 *     },
 *   });
 * });
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

  /** Whether a review is currently in progress */
  #isReviewing = false;

  /** Event listeners */
  #listeners = new Map<IndexerEventType, Set<IndexerEventListener>>();

  /**
   * Creates a new AssetIndexer instance.
   *
   * @param options - Configuration options
   * @param options.existingSessions - Existing sessions to load
   * @param options.maxConcurrentConnections - Maximum concurrent API connections (default: 3)
   * @param options.autoResume - Whether to automatically resume existing sessions (default: true)
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

    // Load existing sessions
    for (const session of existingSessions) {
      this.#sessions.set(session.id, session);
    }

    // Find initial current session
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
   * Gets a session by ID.
   */
  getSession(sessionId: number): IndexingSession | undefined {
    return this.#sessions.get(sessionId);
  }

  /**
   * Gets the currently active session.
   */
  getCurrentSession(): IndexingSession | null {
    if (this.#currentSessionId === null) {
      return null;
    }
    return this.#sessions.get(this.#currentSessionId) ?? null;
  }

  /**
   * Gets the current session ID.
   */
  getCurrentSessionId(): number | null {
    return this.#currentSessionId;
  }

  /**
   * Gets the status of a session.
   */
  getSessionStatus(sessionId: number): SessionStatus {
    const session = this.#sessions.get(sessionId);
    if (!session) {
      return SessionStatus.WAITING;
    }

    if (session.actionRequired) {
      return SessionStatus.ACTION_REQUIRED;
    }

    if (this.#currentSessionId === sessionId) {
      return SessionStatus.ACTIVE;
    }

    return SessionStatus.WAITING;
  }

  /**
   * Gets the progress percentage for a session (0-100).
   */
  getSessionProgress(sessionId: number): number {
    const session = this.#sessions.get(sessionId);
    if (!session || session.totalEntries === 0) {
      return 0;
    }
    return Math.round((session.processedEntries / session.totalEntries) * 100);
  }

  /**
   * Gets the progress info string for a session (e.g., "25 / 100").
   */
  getSessionProgressInfo(sessionId: number): string {
    const session = this.#sessions.get(sessionId);
    if (!session) {
      return '0 / 0';
    }
    return `${session.processedEntries} / ${session.totalEntries}`;
  }

  /**
   * Gets the number of remaining entries for a session.
   */
  getEntriesRemaining(sessionId: number): number {
    const session = this.#sessions.get(sessionId);
    if (!session) {
      return 0;
    }
    return session.totalEntries - session.processedEntries;
  }

  /**
   * Whether any tasks are currently being processed.
   */
  isProcessing(): boolean {
    return this.#activeConnectionCount > 0;
  }

  /**
   * Whether a review is currently in progress.
   */
  isCurrentlyReviewing(): boolean {
    return this.#isReviewing;
  }

  /**
   * Registers an event listener.
   *
   * @param event - Event type to listen for
   * @param listener - Callback function
   * @returns Unsubscribe function
   *
   * @example
   * ```ts
   * const unsubscribe = indexer.on('progress', (data) => {
   *   console.log(`${data.percent}% complete`);
   * });
   *
   * // Later, to stop listening:
   * unsubscribe();
   * ```
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
   * Removes an event listener.
   */
  off(event: IndexerEventType, listener: IndexerEventListener): void {
    this.#listeners.get(event)?.delete(listener);
  }

  /**
   * Starts a new indexing session.
   *
   * @param params - Indexing parameters
   * @returns Promise resolving to the API response
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
      this.#addSession(data.session);
      this.#currentSessionId = data.session.id;

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
   *
   * @param sessionId - Session to stop
   * @returns Promise resolving to the API response
   */
  async stopSession(sessionId: number): Promise<IndexingResponse> {
    this.#pruneTasksForSession(sessionId);

    return this.#enqueueTask({
      sessionId,
      action: IndexingActions.STOP,
      params: {sessionId},
      priority: true,
    });
  }

  /**
   * Fetches the review data (overview) for a session.
   * Use this to get updated missing/skipped entry data before showing a review modal.
   *
   * @param sessionId - Session to get overview for
   * @returns Promise resolving to the API response with updated session data
   */
  async getSessionOverview(sessionId: number): Promise<IndexingResponse> {
    return this.#enqueueTask({
      sessionId,
      action: IndexingActions.OVERVIEW,
      params: {sessionId},
      priority: true,
    });
  }

  /**
   * Finishes an indexing session, optionally deleting missing files/folders.
   *
   * @param params - Finish parameters including items to delete
   * @returns Promise resolving to the API response
   */
  async finishSession(params: FinishIndexingParams): Promise<IndexingResponse> {
    return this.#enqueueTask({
      sessionId: params.sessionId,
      action: IndexingActions.FINISH,
      params,
      priority: true,
    });
  }

  /**
   * Marks a session as being reviewed.
   * Call this when opening a review modal to prevent duplicate review events.
   */
  startReview(sessionId: number): void {
    this.#isReviewing = true;
    this.#pruneTasksForSession(sessionId);
  }

  /**
   * Marks the review as complete.
   * Call this when closing a review modal.
   */
  endReview(): void {
    this.#isReviewing = false;
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

  /**
   * Emits an event to all registered listeners.
   */
  #emit<T>(event: IndexerEventType, data: T): void {
    this.#listeners.get(event)?.forEach((listener) => listener(data));
  }

  /**
   * Adds a session to the store and emits events.
   */
  #addSession(session: IndexingSession): void {
    const isNew = !this.#sessions.has(session.id);
    this.#sessions.set(session.id, session);

    const eventData: SessionEventData = {
      session,
      status: this.getSessionStatus(session.id),
    };

    if (isNew) {
      this.#emit('sessionAdded', eventData);
    } else {
      this.#emit('sessionUpdated', eventData);
    }

    // Emit progress event
    this.#emit<ProgressEventData>('progress', {
      sessionId: session.id,
      processed: session.processedEntries,
      total: session.totalEntries,
      percent: this.getSessionProgress(session.id),
    });
  }

  /**
   * Removes a session from the store and emits events.
   */
  #removeSession(sessionId: number): void {
    const session = this.#sessions.get(sessionId);
    this.#sessions.delete(sessionId);

    if (this.#currentSessionId === sessionId) {
      this.#currentSessionId = null;
    }

    if (session) {
      this.#emit<SessionEventData>('sessionRemoved', {
        session,
        status: SessionStatus.WAITING,
      });
    }
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
   * Performs the next indexing step by queueing tasks.
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

    const entriesRemaining = this.getEntriesRemaining(this.#currentSessionId);
    const availableSlots =
      this.maxConcurrentConnections - this.#activeConnectionCount;

    // Queue up tasks to fill available connection slots
    const tasksToQueue = Math.min(availableSlots, entriesRemaining);
    for (let i = 0; i < tasksToQueue; i++) {
      this.#enqueueTask({
        sessionId: session.id,
        action: IndexingActions.PROCESS,
        params: {sessionId: this.#currentSessionId},
        priority: false,
      });
    }

    // Handle processIfRootEmpty case
    if (session.processIfRootEmpty) {
      this.#enqueueTask({
        sessionId: session.id,
        action: IndexingActions.PROCESS,
        params: {sessionId: this.#currentSessionId},
        priority: false,
      });
    }
  }

  /**
   * Removes all waiting tasks for a session.
   */
  #pruneTasksForSession(sessionId: number): void {
    this.#prunedSessionIds.add(sessionId);
    this.#taskQueue = this.#taskQueue.filter(
      (task) => task.sessionId !== sessionId
    );
  }

  /**
   * Enqueues a task and returns a promise for its completion.
   */
  #enqueueTask(
    taskInfo: Omit<QueuedTask, 'resolve' | 'reject'>
  ): Promise<IndexingResponse> {
    return new Promise((resolve, reject) => {
      const task: QueuedTask = {
        ...taskInfo,
        resolve,
        reject,
      };

      if (task.priority) {
        this.#priorityQueue.push(task);
      } else {
        this.#taskQueue.push(task);
      }

      this.#runTasks();
    });
  }

  /**
   * Processes queued tasks up to the connection limit.
   */
  #runTasks(): void {
    const totalTasks = this.#taskQueue.length + this.#priorityQueue.length;
    if (
      totalTasks === 0 ||
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

  /**
   * Executes a single task.
   */
  async #executeTask(task: QueuedTask): Promise<void> {
    try {
      const response = await actionClient.request<IndexingResponse>({
        method: task.method ?? 'post',
        url: task.action,
        data: task.params,
      });

      this.#processSuccessResponse(response.data, task);
      task.resolve(response.data);
    } catch (error) {
      this.#processFailureResponse(error as Error, task);
      task.reject(error as Error);
    } finally {
      this.#activeConnectionCount--;
      this.#runTasks();
    }
  }

  /**
   * Processes a successful API response.
   */
  #processSuccessResponse(response: IndexingResponse, task: QueuedTask): void {
    if (response.session) {
      this.#addSession(response.session);
      this.#updateCurrentSession();

      const session = response.session;
      const status = this.getSessionStatus(session.id);

      if (status === SessionStatus.ACTION_REQUIRED && !response.skipDialog) {
        if (!this.#prunedSessionIds.has(session.id)) {
          this.#emit<SessionEventData>('reviewRequired', {session, status});
        }
      } else if (!this.#prunedSessionIds.has(session.id)) {
        this.#performIndexingStep();
      }
    }

    this.#updateCurrentSession();

    if (response.stop) {
      this.#removeSession(response.stop);
    }

    // Check if all sessions are complete
    if (this.#sessions.size === 0) {
      this.#emit('complete', {});
    }
  }

  /**
   * Processes a failed API response.
   */
  #processFailureResponse(error: Error, task: QueuedTask): void {
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

    // Continue processing other tasks
    this.#runTasks();
  }
}

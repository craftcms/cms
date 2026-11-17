import {computed, getCurrentScope, onScopeDispose, ref, shallowRef} from 'vue';
import {
  AssetIndexer,
  type ChangeEventData,
  type ErrorEventData,
  type FinishIndexingParams,
  type IndexingSession,
  type StartIndexingParams,
} from '@craftcms/ui/services/AssetIndexer';

interface UseAssetIndexerOptions {
  existingSessions?: IndexingSession[];
  maxConcurrentConnections?: number;
  autoResume?: boolean;
}

// =============================================================================
// Global state — shared across all components that call useAssetIndexer()
// while the owning component (the one passing `existingSessions`) is mounted
// =============================================================================
const indexer = shallowRef<AssetIndexer | null>(null);
const sessions = ref<Map<number, IndexingSession>>(new Map());
const currentSessionId = ref<number | null>(null);
const reviewSession = ref<IndexingSession | null>(null);
const isReviewOpen = ref(false);
const isStarting = ref(false);
const lastError = ref<string | null>(null);
const isComplete = ref(false);

function resetState() {
  sessions.value = new Map();
  currentSessionId.value = null;
  reviewSession.value = null;
  isReviewOpen.value = false;
  isStarting.value = false;
  lastError.value = null;
  isComplete.value = false;
}

/** Replace any previous indexer with one seeded from fresh server data. */
function initialize({
  existingSessions = [],
  maxConcurrentConnections,
  autoResume = true,
}: UseAssetIndexerOptions): AssetIndexer {
  indexer.value?.destroy();
  resetState();

  const instance = new AssetIndexer({
    existingSessions,
    maxConcurrentConnections,
    autoResume,
  });
  indexer.value = instance;

  subscribe();
  sync();

  return instance;
}

/** Stop the indexer when its owner goes away, unless a newer owner already replaced it. */
function teardown(instance: AssetIndexer) {
  if (indexer.value !== instance) {
    return;
  }

  instance.destroy();
  indexer.value = null;
  resetState();
}

/** Pull the current truth out of the service into our refs. */
function sync() {
  if (!indexer.value) {
    return;
  }

  sessions.value = new Map(
    indexer.value.getSessions().map((s: IndexingSession) => [s.id, s])
  );
  currentSessionId.value = indexer.value.getCurrentSessionId();
}

/** Wire up event listeners on the indexer to keep global refs in sync. */
function subscribe() {
  if (!indexer.value) {
    return;
  }

  indexer.value.on<ChangeEventData>('change', ({reviewSessionId: reviewId}) => {
    sync();

    // Auto-close review if the session was removed (finished or stopped)
    if (
      isReviewOpen.value &&
      reviewSession.value &&
      !sessions.value.has(reviewSession.value.id)
    ) {
      closeReview();
    }

    // Open review if requested
    if (reviewId && !isReviewOpen.value) {
      const session = sessions.value.get(reviewId);
      if (session) {
        openReview(session);
      }
    }
  });

  indexer.value.on<ErrorEventData>('error', ({message}) => {
    lastError.value = message;
  });

  indexer.value.on('complete', () => {
    isComplete.value = true;
  });
}

// =============================================================================
// Global computed helpers
// =============================================================================
const sessionsArray = computed(() => Array.from(sessions.value.values()));
const hasSessions = computed(() => sessions.value.size > 0);
const isProcessing = computed(() => indexer.value?.isProcessing() ?? false);

const currentSession = computed(() => {
  if (currentSessionId.value === null) {
    return null;
  }
  return sessions.value.get(currentSessionId.value) ?? null;
});

const progressPercent = computed(() => {
  const session = currentSession.value;
  if (!session || session.totalEntries === 0) {
    return 0;
  }
  return Math.round((session.processedEntries / session.totalEntries) * 100);
});

const progressInfo = computed(() => {
  const session = currentSession.value;
  if (!session) {
    return null;
  }
  return `${session.processedEntries} / ${session.totalEntries}`;
});

// =============================================================================
// Global actions
// =============================================================================

/** Start a new indexing run (the only async action). */
async function startIndexing(params: StartIndexingParams) {
  if (!indexer.value || params.volumes.length === 0) {
    return;
  }

  isStarting.value = true;
  isComplete.value = false;
  lastError.value = null;

  try {
    const response = await indexer.value.startIndexing(params);
    sync();
    return response;
  } finally {
    isStarting.value = false;
  }
}

/** Stop / discard a session. State updates arrive via the change event. */
function stopSession(sessionId: number) {
  indexer.value?.stopSession(sessionId);
}

/** Fetch the overview for a session. Opens review when data arrives via change event. */
function reviewSessionOverview(sessionId: number) {
  indexer.value?.getSessionOverview(sessionId);
}

/**
 * Finish a session, optionally deleting selected items. Resolves once the
 * server has finished (or failed to finish) the session.
 */
function finishSession(params: FinishIndexingParams): Promise<void> {
  const instance = indexer.value;
  if (!instance) {
    return Promise.resolve();
  }

  return new Promise((resolve) => {
    const done = () => {
      offChange();
      offError();
      resolve();
    };
    const offChange = instance.on<ChangeEventData>(
      'change',
      ({sessions: current}) => {
        if (!current.some((session) => session.id === params.sessionId)) {
          done();
        }
      }
    );
    const offError = instance.on<ErrorEventData>('error', ({sessionId}) => {
      if (sessionId === params.sessionId) {
        done();
      }
    });

    instance.finishSession(params);
  });
}

/** Keep all files and stop the session (from the review modal). */
function keepFiles(sessionId: number) {
  stopSession(sessionId);
}

// =============================================================================
// Review helpers (UI-only state, not in the service)
// =============================================================================

function openReview(session: IndexingSession) {
  if (isReviewOpen.value) {
    return;
  }

  reviewSession.value = session;
  isReviewOpen.value = true;
}

function closeReview() {
  isReviewOpen.value = false;
  reviewSession.value = null;
}

/**
 * Composable that wraps the AssetIndexer service, exposing its state
 * as reactive Vue refs and its operations as plain functions.
 *
 * State is **global** — a call that provides `existingSessions` (the owner)
 * creates a fresh `AssetIndexer` instance seeded with those sessions and
 * destroys it when the owner's effect scope is disposed; calls without
 * options from descendant components share the same refs and actions.
 *
 * `startIndexing` returns the API response and `finishSession` resolves when
 * the session is finished. All other actions are fire-and-forget — state
 * updates arrive via the `change` event from the service and are synced into
 * reactive refs automatically.
 *
 * @example Initialize with server data (typically in a page-level component)
 * ```vue
 * <script setup lang="ts">
 * const { isProcessing, progressPercent, startIndexing } = useAssetIndexer({
 *   existingSessions: props.existingSessions,
 * });
 *
 * function handleSubmit() {
 *   startIndexing({ volumes: [1, 2], cacheImages: true });
 * }
 * </script>
 *
 * <template>
 *   <button @click="handleSubmit" :disabled="isProcessing">Index</button>
 *   <span v-if="isProcessing">{{ progressPercent }}%</span>
 * </template>
 * ```
 *
 * @example Access the same state from a child component (no options needed)
 * ```vue
 * <script setup lang="ts">
 * const { sessionsArray, hasSessions, stopSession } = useAssetIndexer();
 * </script>
 *
 * <template>
 *   <AdminTable v-if="hasSessions" :table="table" />
 * </template>
 * ```
 *
 * @example Handling the review flow
 * ```vue
 * <script setup lang="ts">
 * const {
 *   reviewSession,
 *   isReviewOpen,
 *   finishSession,
 *   keepFiles,
 *   closeReview,
 * } = useAssetIndexer();
 * </script>
 *
 * <template>
 *   <ReviewModal
 *     v-if="reviewSession"
 *     :open="isReviewOpen"
 *     :session="reviewSession"
 *     @close="closeReview"
 *     @finish="finishSession"
 *     @keep="keepFiles"
 *   />
 * </template>
 * ```
 *
 * @example Reacting to errors and completion
 * ```vue
 * <script setup lang="ts">
 * const { lastError, isComplete } = useAssetIndexer();
 *
 * watch(lastError, (msg) => { if (msg) flash('error', msg) });
 * watch(isComplete, (done) => { if (done) flash('success', t('Indexing complete')) });
 * </script>
 * ```
 */
export function useAssetIndexer(options: UseAssetIndexerOptions = {}) {
  if (options.existingSessions) {
    const instance = initialize(options);

    if (getCurrentScope()) {
      onScopeDispose(() => teardown(instance));
    }
  }

  return {
    // Reactive state
    sessions,
    sessionsArray,
    currentSessionId,
    currentSession,
    hasSessions,
    isProcessing,
    isStarting,
    isComplete,
    lastError,
    progressPercent,
    progressInfo,

    // Review state
    reviewSession,
    isReviewOpen,

    // Actions
    startIndexing,
    stopSession,
    reviewSessionOverview,
    finishSession,
    keepFiles,
    openReview,
    closeReview,
  };
}

import {computed, ref, shallowRef} from 'vue';
import {
  AssetIndexer,
  type ChangeEventData,
  type ErrorEventData,
  type FinishIndexingParams,
  type IndexingSession,
  type StartIndexingParams,
} from '@craftcms/cp/src/services/AssetIndexer.js';

interface UseAssetIndexerOptions {
  existingSessions?: IndexingSession[];
  maxConcurrentConnections?: number;
  autoResume?: boolean;
}

// =============================================================================
// Global state — shared across all components that call useAssetIndexer()
// =============================================================================
const indexer = shallowRef<AssetIndexer | null>(null);
const sessions = ref<Map<number, IndexingSession>>(new Map());
const currentSessionId = ref<number | null>(null);
const reviewSession = ref<IndexingSession | null>(null);
const isReviewOpen = ref(false);
const isStarting = ref(false);
const lastError = ref<string | null>(null);
const isComplete = ref(false);
let initialized = false;

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

/** Finish a session, optionally deleting selected items. */
function finishSession(params: FinishIndexingParams) {
  indexer.value?.finishSession(params);
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
 * State is **global** — the first call that provides `existingSessions`
 * creates the underlying `AssetIndexer` instance; subsequent calls from
 * any component share the same refs and actions.
 *
 * Only `startIndexing` is async (returns the API response). All other
 * actions are fire-and-forget — state updates arrive via the `change`
 * event from the service and are synced into reactive refs automatically.
 *
 * @example Initialise with server data (typically in a page-level component)
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
  if (!initialized) {
    const {
      existingSessions = [],
      maxConcurrentConnections,
      autoResume = true,
    } = options;

    indexer.value = new AssetIndexer({
      existingSessions,
      maxConcurrentConnections,
      autoResume,
    });

    subscribe();
    sync();
    initialized = true;
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

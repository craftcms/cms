import {computed, ref, shallowRef} from 'vue';
import {
  AssetIndexer,
  type ErrorEventData,
  type FinishIndexingParams,
  type IndexingSession,
  type SessionEventData,
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
const isLoadingReview = ref(false);
const isReviewOpen = ref(false);
const isFinishing = ref(false);
const isStarting = ref(false);
const isStopping = ref(false);
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

  indexer.value.on<SessionEventData>('sessionAdded', ({session}) => {
    sessions.value.set(session.id, session);
    currentSessionId.value = indexer.value!.getCurrentSessionId();
  });

  indexer.value.on<SessionEventData>('sessionUpdated', ({session}) => {
    sessions.value.set(session.id, session);
    currentSessionId.value = indexer.value!.getCurrentSessionId();
  });

  indexer.value.on<SessionEventData>('sessionRemoved', ({session}) => {
    sessions.value.delete(session.id);
    currentSessionId.value = indexer.value!.getCurrentSessionId();
  });

  indexer.value.on<SessionEventData>('reviewRequired', ({session}) => {
    openReview(session);
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
  if (currentSessionId.value === null || !indexer.value) {
    return 0;
  }
  return indexer.value.getSessionProgress(currentSessionId.value);
});

const progressInfo = computed(() => {
  if (currentSessionId.value === null || !indexer.value) {
    return null;
  }
  return indexer.value.getSessionProgressInfo(currentSessionId.value);
});

// =============================================================================
// Global actions
// =============================================================================

/** Start a new indexing run. */
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

/** Stop / discard a session. */
async function stopSession(sessionId: number) {
  if (!indexer.value) {
    return;
  }

  isStopping.value = true;

  try {
    const response = await indexer.value.stopSession(sessionId);
    sync();
    return response;
  } finally {
    isStopping.value = false;
  }
}

/** Fetch the overview (missing/skipped files) for a session, then open review. */
async function reviewSessionOverview(sessionId: number) {
  if (!indexer.value) {
    return;
  }

  isLoadingReview.value = true;
  await indexer.value.getSessionOverview(sessionId);
  sync();
  const session = sessions.value.get(sessionId);
  if (session) {
    openReview(session);
    isLoadingReview.value = false;
  }
}

/** Finish a session, optionally deleting selected items. */
async function finishSession(params: FinishIndexingParams) {
  if (!indexer.value) {
    return;
  }
  isFinishing.value = true;

  try {
    const response = await indexer.value.finishSession(params);
    sync();
    return response;
  } finally {
    isFinishing.value = false;
    closeReview();
  }
}

/** Keep all files and stop the session (from the review modal). */
async function keepFiles(sessionId: number) {
  await stopSession(sessionId);
  closeReview();
}

// =============================================================================
// Review helpers
// =============================================================================

function openReview(session: IndexingSession) {
  if (!indexer.value || indexer.value.isCurrentlyReviewing()) {
    return;
  }

  indexer.value.startReview(session.id);
  reviewSession.value = session;
  isReviewOpen.value = true;
}

function closeReview() {
  isReviewOpen.value = false;
  indexer.value?.endReview();
  reviewSession.value = null;
}

/**
 * Composable that wraps the AssetIndexer service, exposing its state
 * as reactive Vue refs and its operations as plain async functions.
 *
 * State is **global** — the first call that provides `existingSessions`
 * creates the underlying `AssetIndexer` instance; subsequent calls from
 * any component share the same refs and actions.
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
 *   isFinishing,
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
 *     :is-loading="isFinishing"
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
    isStopping,
    isComplete,
    lastError,
    progressPercent,
    progressInfo,

    // Review state
    reviewSession,
    isReviewOpen,
    isFinishing,
    isLoadingReview,

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

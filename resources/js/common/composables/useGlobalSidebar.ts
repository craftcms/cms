import { computed, effectScope, nextTick, reactive, ref, watch, type Ref } from "vue";
import { useMediaQuery } from "@vueuse/core";
import { useLocalStorage } from "@/common/composables/useStorage";

/**
 * State for the CP's global sidebar: docked and always visible on large
 * screens, floating and hidden behind a toggle button below that.
 *
 * The state lives at module scope rather than inside the composable, because
 * more than one component needs the *same* sidebar — the shell sizes its grid
 * column from it while the sidebar itself renders the toggle. Built per call,
 * each caller would get its own copy and toggling would move one of them while
 * the other kept rendering the old state.
 */
const sidebar = reactive<{
  mode: "docked" | "floating";
  visibility: "hidden" | "visible";
}>({
  mode: "floating",
  visibility: "hidden",
});

/**
 * The button that opens the floating sidebar, so focus can be returned to it
 * on close. Registered by whichever component renders it — a module-scoped
 * `useTemplateRef` would resolve against no template at all.
 */
const toggleButton = ref<HTMLElement | null>(null);

const isLargeScreen = useMediaQuery("(min-width: 1024px)");

/**
 * Wires up the breakpoint and the stored collapse preference, once, however
 * many components read the sidebar.
 *
 * Deferred to the first `useGlobalSidebar()` call rather than run at module
 * scope, because `useLocalStorage` prefixes its key with `Craft.systemUid`,
 * and the CP config doesn't set that until the app boots — later than this
 * module is evaluated. The scope is detached so the watchers outlive whichever
 * component happened to ask first.
 */
let scope: ReturnType<typeof effectScope> | null = null;

function initialize(): void {
  if (scope) {
    return;
  }

  scope = effectScope(true);
  scope.run(() => {
    // Persisted in localStorage rather than a cookie: no request needs to
    // carry it, since nothing on the server reads it. Craft 5 used a cookie
    // because PHP rendered the sidebar and had to render it already collapsed;
    // this one is rendered by Vue, and with Inertia SSR off the preference is
    // read before the first paint either way.
    const collapsedPreference = useLocalStorage("sidebar.collapsed", false);

    watch(
      isLargeScreen,
      (value) => {
        if (value) {
          sidebar.mode = "docked";
          sidebar.visibility = collapsedPreference.value ? "hidden" : "visible";
        } else {
          sidebar.mode = "floating";
          sidebar.visibility = "hidden";
        }
      },
      { immediate: true },
    );

    // Only remember what the user chose for the docked sidebar. A floating one
    // is hidden because the window is narrow, not because anyone asked for it,
    // and storing that would expand the rail on the next wide-screen visit.
    watch(
      () => sidebar.visibility,
      (visibility) => {
        if (sidebar.mode !== "docked") {
          return;
        }

        collapsedPreference.value = visibility === "hidden";
      },
    );
  });
}

function toggle() {
  sidebar.visibility = sidebar.visibility === "visible" ? "hidden" : "visible";
}

function close() {
  sidebar.visibility = "hidden";
}

/**
 * Hand focus back when the sidebar goes away under it.
 *
 * The control that closes the sidebar is usually *inside* it, so it unmounts
 * on the same change — focusing it synchronously would land on a detached
 * node and drop focus to `<body>`. Waiting a tick lets whichever button is
 * showing now register itself first.
 *
 * Only when focus was inside the sidebar: a viewport resize can hide it while
 * the user is typing somewhere else entirely, and stealing focus there would
 * be worse than doing nothing.
 */
watch(
  () => sidebar.visibility,
  async (visibility) => {
    if (visibility !== "hidden") {
      return;
    }

    const active = document.activeElement;
    const inSidebar = active?.closest?.(".cp-sidebar") != null;

    if (!inSidebar) {
      return;
    }

    await nextTick();
    toggleButton.value?.focus();
  },
);

/**
 * True when the sidebar is docked but hidden. A docked sidebar stays in the
 * layout as a rail of icons rather than going away, so `hidden` there means
 * "collapsed", not "gone" — only a floating sidebar, which overlays the
 * content, actually leaves.
 */
const collapsed = computed(() => sidebar.mode === "docked" && sidebar.visibility === "hidden");

const icon = computed(() =>
  sidebar.visibility === "visible" ? "arrow-left-to-line" : "arrow-right-from-line",
);

const width = computed(() => {
  if (sidebar.mode === "docked") {
    return sidebar.visibility === "visible"
      ? "var(--global-sidebar-width)"
      : "var(--global-sidebar-collapsed-width)";
  }

  return "auto";
});

export function useGlobalSidebar(): {
  isLargeScreen: Ref<boolean>;
  sidebar: typeof sidebar;
  collapsed: Ref<boolean>;
  toggleButton: Ref<HTMLElement | null>;
  toggle: () => void;
  close: () => void;
  icon: Ref<string>;
  width: Ref<string>;
} {
  initialize();

  return {
    isLargeScreen,
    sidebar,
    collapsed,
    toggleButton,
    toggle,
    close,
    icon,
    width,
  };
}

import { computed, nextTick, reactive, ref, watch, type Ref } from "vue";
import { useMediaQuery } from "@vueuse/core";

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

// At module scope, so the breakpoint is watched once however many components
// read the sidebar.
watch(
  isLargeScreen,
  (value) => {
    if (value) {
      sidebar.mode = "docked";
      sidebar.visibility = "visible";
    } else {
      sidebar.mode = "floating";
      sidebar.visibility = "hidden";
    }
  },
  { immediate: true },
);

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

const icon = computed(() => (sidebar.visibility === "visible" ? "arrow-left-to-line" : "bars"));

const width = computed(() => {
  if (sidebar.mode === "docked") {
    return sidebar.visibility === "visible" ? "var(--global-sidebar-width)" : "0";
  }

  return "auto";
});

export function useGlobalSidebar(): {
  isLargeScreen: Ref<boolean>;
  sidebar: typeof sidebar;
  toggleButton: Ref<HTMLElement | null>;
  toggle: () => void;
  close: () => void;
  icon: Ref<string>;
  width: Ref<string>;
} {
  return { isLargeScreen, sidebar, toggleButton, toggle, close, icon, width };
}

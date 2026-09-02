import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';

/**
 * The composable is global state, so each test imports it fresh — otherwise the
 * first test's sidebar leaks into the rest.
 *
 * `useLocalStorage` prefixes its key with `Craft.systemUid`, which is why the
 * state is built on first call rather than at module scope. The stub has to be
 * in place before the import for the same reason.
 */
async function freshSidebar() {
  vi.resetModules();
  localStorage.clear();

  (globalThis as {Craft?: {systemUid: string}}).Craft = {systemUid: 'test'};

  const {useGlobalSidebar} = await import('./useGlobalSidebar');

  return useGlobalSidebar;
}

describe('useGlobalSidebar', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('hands every caller the same sidebar', async () => {
    const useGlobalSidebar = await freshSidebar();

    const one = useGlobalSidebar();
    const two = useGlobalSidebar();

    // Same object, not a copy — the shell sizes its column from this while the
    // sidebar itself renders the toggle.
    expect(one.sidebar).toBe(two.sidebar);
    expect(one.toggleButton).toBe(two.toggleButton);
  });

  it('shows one caller a toggle made by another', async () => {
    const useGlobalSidebar = await freshSidebar();

    const shell = useGlobalSidebar();
    const sidebar = useGlobalSidebar();
    const before = shell.sidebar.visibility;

    sidebar.toggle();

    expect(shell.sidebar.visibility).not.toBe(before);
    expect(shell.icon.value).toBe(
      shell.sidebar.visibility === 'visible'
        ? 'arrow-left-to-line'
        : 'arrow-right-from-line'
    );
  });

  it('only remembers the collapse preference while docked', async () => {
    const useGlobalSidebar = await freshSidebar();
    const {sidebar} = useGlobalSidebar();
    const key = 'Craft-test.sidebar.collapsed';

    // `useStorage` seeds the key with its default on creation, so what matters
    // is that a floating sidebar never *moves* it: floating is hidden because
    // the window is narrow, not because anyone asked, and storing that would
    // expand the rail on the next wide-screen visit.
    const seeded = localStorage.getItem(key);

    sidebar.mode = 'floating';
    sidebar.visibility = 'visible';
    await Promise.resolve();
    sidebar.visibility = 'hidden';
    await Promise.resolve();
    expect(localStorage.getItem(key)).toBe(seeded);

    sidebar.mode = 'docked';
    sidebar.visibility = 'visible';
    await Promise.resolve();
    expect(localStorage.getItem(key)).toBe('false');

    sidebar.visibility = 'hidden';
    await Promise.resolve();
    expect(localStorage.getItem(key)).toBe('true');
  });

  it('reports collapsed only for a docked sidebar', async () => {
    const useGlobalSidebar = await freshSidebar();
    const {sidebar, collapsed, width} = useGlobalSidebar();

    // Docked and hidden is a rail of icons, not gone.
    sidebar.mode = 'docked';
    sidebar.visibility = 'hidden';
    expect(collapsed.value).toBe(true);
    expect(width.value).toBe('var(--global-sidebar-collapsed-width)');

    // Floating and hidden has actually left the layout.
    sidebar.mode = 'floating';
    expect(collapsed.value).toBe(false);
    expect(width.value).toBe('auto');
  });
});

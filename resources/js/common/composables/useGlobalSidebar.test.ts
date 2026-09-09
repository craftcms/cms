import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {nextTick} from 'vue';

const extraElements: HTMLElement[] = [];

beforeEach(() => {
  vi.resetModules();
});

afterEach(() => {
  vi.unstubAllGlobals();
  extraElements.splice(0).forEach((el) => el.remove());
});

function appendElement<T extends HTMLElement>(el: T): T {
  document.body.append(el);
  extraElements.push(el);
  return el;
}

it('returns focus to the registered toggle when the sidebar hides while focus was inside it', async () => {
  vi.stubGlobal('Craft', {systemUid: 'test'});
  const {useGlobalSidebar} = await import('./useGlobalSidebar');
  const {sidebar, toggleButton} = useGlobalSidebar();

  const externalToggle = appendElement(document.createElement('button'));
  toggleButton.value = externalToggle;

  const sidebarEl = appendElement(document.createElement('div'));
  sidebarEl.className = 'cp-sidebar';
  const innerControl = document.createElement('button');
  sidebarEl.append(innerControl);

  sidebar.visibility = 'visible';
  await nextTick();
  innerControl.focus();

  sidebar.visibility = 'hidden';
  await vi.waitFor(() => expect(document.activeElement).toBe(externalToggle));
});

it('leaves focus alone when the sidebar hides while focus was already elsewhere', async () => {
  vi.stubGlobal('Craft', {systemUid: 'test'});
  const {useGlobalSidebar} = await import('./useGlobalSidebar');
  const {sidebar, toggleButton} = useGlobalSidebar();

  const externalToggle = appendElement(document.createElement('button'));
  toggleButton.value = externalToggle;

  const unrelatedField = appendElement(document.createElement('input'));
  unrelatedField.focus();

  sidebar.visibility = 'visible';
  await nextTick();
  sidebar.visibility = 'hidden';
  await vi.waitFor(() => expect(document.activeElement).toBe(unrelatedField));
});

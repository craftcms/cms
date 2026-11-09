import jquery from 'jquery';
import {afterEach, expect, it, vi} from 'vite-plus/test';

const legacyWindow = window as any;
const ready = jquery.fn.ready;

async function loadCpCompat(craft: Record<string, unknown>) {
  legacyWindow.jQuery = legacyWindow.$ = jquery;
  legacyWindow.Craft = craft;
  vi.resetModules();
  await import('../../../packages/craftcms-legacy/cpcompat/src/cp-compat.js');
}

function readyShim() {
  let pending: Array<() => void> = [];
  let released = false;

  return {
    whenReady(callback: () => void) {
      if (released) {
        callback();
      } else {
        pending.push(callback);
      }
    },
    flushReady() {
      released = true;
      pending.forEach((callback) => callback());
      pending = [];
    },
  };
}

function nextTask() {
  return new Promise((resolve) => setTimeout(resolve));
}

afterEach(() => {
  jquery.fn.ready = ready;
  delete legacyWindow.Craft;
  delete legacyWindow.jQuery;
  delete legacyWindow.$;
});

it('holds jQuery ready callbacks on bridged screens until the screen mounts', async () => {
  const craft = readyShim();
  await loadCpCompat(craft);
  const ran: string[] = [];

  jquery(() => ran.push('function'));
  jquery(document).ready(() => ran.push('ready'));
  await nextTask();

  expect(ran).toEqual([]);

  craft.flushReady();
  await nextTask();

  expect(ran).toEqual(['function', 'ready']);
});

it('leaves jQuery ready callbacks alone on other pages', async () => {
  await loadCpCompat({});
  const ran: string[] = [];

  jquery(() => ran.push('function'));
  await nextTask();

  expect(ran).toEqual(['function']);
});

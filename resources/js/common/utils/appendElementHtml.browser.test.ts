import {appendElementHtml} from '@craftcms/ui/utilities/dom';
import {afterEach, expect, it} from 'vite-plus/test';

declare global {
  interface Window {
    scriptRuns?: string[];
  }
}

afterEach(() => {
  document.body.innerHTML = '';
  delete window.scriptRuns;
});

it('runs scripts nested in wrapper elements, like jQuery .html() did', async () => {
  window.scriptRuns = [];

  await appendElementHtml(
    '<script>scriptRuns.push("top")</script><div><p><script>scriptRuns.push("nested")</script></p></div>',
    document.body
  );

  expect(window.scriptRuns).toEqual(['top', 'nested']);
});

it('waits for nested external scripts before running the scripts after them', async () => {
  window.scriptRuns = [];

  await appendElementHtml(
    `<div><script src="data:text/javascript,scriptRuns.push('library')"></script><script>scriptRuns.push("init")</script></div>`,
    document.body
  );

  expect(window.scriptRuns).toEqual(['library', 'init']);
});

it('rejects when a nested external script fails to load', async () => {
  await expect(
    appendElementHtml(
      '<div id="wrapper"><script src="/missing-nested-script.js"></script></div>',
      document.body,
      true
    )
  ).rejects.toThrow('missing-nested-script.js');
  expect(document.getElementById('wrapper')).toBeNull();
});

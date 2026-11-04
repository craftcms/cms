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

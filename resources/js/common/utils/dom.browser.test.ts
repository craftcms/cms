import '@craftcms/ui/components/tabs/tabs';
import '@craftcms/ui/components/tab/tab';
import '@craftcms/ui/components/disclosure/disclosure';
import {afterEach, expect, it} from 'vite-plus/test';
import {revealElement} from './dom';

afterEach(() => {
  document.body.innerHTML = '';
});

it('selects the tab of each panel an element is in, leaving focus where it is', async () => {
  document.body.innerHTML = `
    <button id="chat">Apply</button>
    <craft-tabs label="Page">
      <craft-tab slot="tab" controls="page-1">Content</craft-tab>
      <craft-tab slot="tab" controls="page-2">Details</craft-tab>
    </craft-tabs>
    <section id="page-1"></section>
    <section id="page-2">
      <craft-tabs label="Entry">
        <craft-tab slot="tab" controls="entry-1">Main</craft-tab>
        <craft-tab slot="tab" controls="entry-2">Extra</craft-tab>
      </craft-tabs>
      <section id="entry-1"></section>
      <section id="entry-2"><input id="field"></section>
    </section>`;
  await customElements.whenDefined('craft-tabs');
  await Promise.all(
    [...document.querySelectorAll('craft-tabs')].map(
      (tabs) =>
        (tabs as HTMLElement & {updateComplete: Promise<unknown>})
          .updateComplete
    )
  );
  const chat = document.getElementById('chat')!;
  chat.focus();
  const field = document.getElementById('field')!;

  const hidden = () =>
    ['page-2', 'entry-2'].filter((id) =>
      document.getElementById(id)!.classList.contains('hidden')
    );

  expect(hidden()).toEqual(['page-2', 'entry-2']);

  await revealElement(field);

  expect(hidden()).toEqual([]);
  expect(document.activeElement).toBe(chat);
});

it('opens the hidden region a disclosure button shows, such as a details panel, that an element is in', async () => {
  document.body.innerHTML = `
    <craft-disclosure state="collapsed">
      <button type="button" aria-controls="info">Info</button>
    </craft-disclosure>
    <section id="info" hidden><input id="field"></section>`;
  await customElements.whenDefined('craft-disclosure');
  const panel = document.getElementById('info')!;
  document
    .querySelector('craft-disclosure')!
    .addEventListener('craft-show', () => (panel.hidden = false));

  await revealElement(document.getElementById('field')!);

  expect(panel.hidden).toBe(false);
});

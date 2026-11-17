import '../../../../../css/cp.css';
import '@craftcms/ui/components/chip/chip';
import '@craftcms/ui/components/thumbnail/thumbnail';
import {afterEach, expect, it} from 'vite-plus/test';
import {resizeThumbs} from './useElementIndexThumbSize';

type Thumbnail = HTMLElement & {
  width: number | null;
  height: number | null;
  updateComplete: Promise<unknown>;
};

const image =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="200"><rect width="300" height="200" fill="#305ce7"/></svg>'
  );

afterEach(() => {
  document.body.innerHTML = '';
});

async function render(): Promise<{root: HTMLElement; thumbnail: Thumbnail}> {
  document.body.innerHTML = `
    <div>
      <craft-chip thumb-size="lg" show-thumb>
        <craft-thumbnail slot="thumbnail" src="${image}" width="120" height="80" loading="eager"></craft-thumbnail>
        Landscape
      </craft-chip>
      <craft-chip show-thumb>
        <craft-thumbnail slot="thumbnail" src="${image}" width="30" height="20" loading="eager"></craft-thumbnail>
        Not the index’s
      </craft-chip>
    </div>`;
  const chip = document.querySelector<HTMLElement & {size: string | null}>(
    'craft-chip'
  )!;
  chip.size = 'large';
  const thumbnail = document.querySelector<Thumbnail>('craft-thumbnail')!;
  await thumbnail.updateComplete;

  return {root: document.querySelector('div')!, thumbnail};
}

function imageWidth(thumbnail: Thumbnail): number {
  return thumbnail.shadowRoot!.querySelector('img')!.getBoundingClientRect()
    .width;
}

it('grows the index’s chips and their thumbnails to a larger size', async () => {
  const {root, thumbnail} = await render();
  expect(imageWidth(thumbnail)).toBe(120);

  await resizeThumbs(root, '2xl');
  await thumbnail.updateComplete;

  const chip = root.querySelector<HTMLElement & {size: string | null}>(
    'craft-chip'
  )!;
  expect(chip.getAttribute('thumb-size')).toBe('2xl');
  expect(chip.size).toBeNull();
  expect([thumbnail.width, thumbnail.height]).toEqual([240, 160]);
  expect(imageWidth(thumbnail)).toBe(240);
});

it('shrinks them to a smaller size', async () => {
  const {root, thumbnail} = await render();

  await resizeThumbs(root, 'md');
  await thumbnail.updateComplete;

  expect([thumbnail.width, thumbnail.height]).toEqual([60, 40]);
  expect(imageWidth(thumbnail)).toBe(60);
});

it('leaves chips without a thumb size alone', async () => {
  const {root} = await render();

  await resizeThumbs(root, '2xl');

  const other = root.querySelectorAll<HTMLElement>('craft-chip')[1]!;
  const otherThumbnail = other.querySelector<Thumbnail>('craft-thumbnail')!;
  expect(other.hasAttribute('thumb-size')).toBe(false);
  expect([otherThumbnail.width, otherThumbnail.height]).toEqual([30, 20]);
});

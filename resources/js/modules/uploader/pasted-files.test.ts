import {expect, it} from 'vitest';
import {namePastedFiles} from './pasted-files';

const now = new Date(2026, 9, 30, 9, 5, 7);

it('gives pasted image data unique names and keeps named files', () => {
  const named = new File(['a'], 'report.pdf', {type: 'application/pdf'});
  const [image, jpeg, kept] = namePastedFiles(
    [
      new File(['b'], 'image.png', {type: 'image/png'}),
      new File(['c'], '', {type: 'image/jpeg'}),
      named,
    ],
    now
  );

  expect(image!.name).toBe('pasted-image-20261030-090507-1.png');
  expect(image!.type).toBe('image/png');
  expect(jpeg!.name).toBe('pasted-image-20261030-090507-2.jpg');
  expect(kept).toBe(named);
  expect(namePastedFiles([new File([''], 'image.png')], now)[0]!.name).toBe(
    'pasted-image-20261030-090507.png'
  );
});

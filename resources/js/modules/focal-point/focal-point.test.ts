import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {
  bootFocalPoint,
  destroyFocalPoint,
  FocalPoint,
  renderFocalPoint,
} from './focal-point';

vi.mock('@/modules/elements/live-preview/previews', () => ({
  refreshPreviews: () => {},
}));

beforeEach(() => {
  vi.spyOn(HTMLElement.prototype, 'clientWidth', 'get').mockReturnValue(200);
  vi.spyOn(HTMLElement.prototype, 'clientHeight', 'get').mockReturnValue(100);
  vi.spyOn(actionClient, 'post').mockResolvedValue({data: {}} as never);
});

afterEach(() => {
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

function setUp(): {focalPoint: FocalPoint; marker: HTMLElement} {
  document.body.innerHTML =
    '<div id="target"><img data-focal="0.25;0.5" data-uid="abc"></div>';
  const focalPoint = new FocalPoint(
    document.getElementById('target')!,
    null,
    true
  );

  return {
    focalPoint,
    marker: document.querySelector<HTMLElement>('.preview-focal-point')!,
  };
}

function drag(marker: HTMLElement, dx: number, dy: number): void {
  marker.dispatchEvent(
    new PointerEvent('pointerdown', {clientX: 0, clientY: 0, bubbles: true})
  );
  window.dispatchEvent(
    new PointerEvent('pointermove', {clientX: dx, clientY: dy})
  );
  window.dispatchEvent(new PointerEvent('pointerup'));
}

it('shows the saved focal point and moves it when dragged', () => {
  const {marker} = setUp();

  expect(marker.style.display).toBe('');
  expect(marker.style.left).toBe('50px');

  drag(marker, 20, 10);

  expect(marker.style.left).toBe('70px');
  expect(marker.style.top).toBe('60px');
});

it('stops following the pointer once destroyed', () => {
  const {focalPoint, marker} = setUp();
  focalPoint.destruct();

  drag(marker, 20, 10);

  expect(marker.style.left).toBe('50px');
});

it('repositions a booted preview marker when its image is resized', () => {
  document.body.innerHTML =
    '<div data-focal-point="view"><img data-focal="0.25;0.5" data-uid="abc"></div>';
  bootFocalPoint(document.body);
  const marker = document.querySelector<HTMLElement>('.preview-focal-point')!;
  expect(marker.style.left).toBe('50px');

  vi.spyOn(HTMLElement.prototype, 'clientWidth', 'get').mockReturnValue(400);
  renderFocalPoint();

  expect(marker.style.left).toBe('100px');
  destroyFocalPoint();
});

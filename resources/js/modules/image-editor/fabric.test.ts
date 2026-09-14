import {expect, it, vi} from 'vite-plus/test';
import {Rect} from 'fabric';
import {animate} from './fabric';

const settle = () => new Promise((resolve) => setTimeout(resolve, 60));

it("fires fabric's own callbacks once per property", async () => {
  // The trap `animate()` exists for. If fabric goes back to firing once, this
  // fails -- and the helper can go with it.
  const rect = new Rect({left: 0, top: 0, width: 10, height: 10});
  const onComplete = vi.fn();

  rect.animate(
    {left: 100, top: 50, scaleX: 2, scaleY: 2},
    {duration: 10, onComplete}
  );

  await vi.waitFor(() => expect(onComplete).toHaveBeenCalled());
  await settle();

  expect(onComplete).toHaveBeenCalledTimes(4);
});

it('completes a multi-property animation once, after every property lands', async () => {
  // Called once per property, the editor's completion handlers put the focal
  // point marker back on the canvas four times over -- and `add()` doesn't
  // check, so the copies piled up with every trip out of the crop view.
  const rect = new Rect({left: 0, top: 0, width: 10, height: 10});
  const onComplete = vi.fn(() => ({
    left: rect.left,
    top: rect.top,
    scaleX: rect.scaleX,
    scaleY: rect.scaleY,
  }));

  animate(
    rect,
    {left: 100, top: 50, scaleX: 2, scaleY: 2},
    {duration: 10, onComplete}
  );

  await vi.waitFor(() => expect(onComplete).toHaveBeenCalled());
  await settle();

  expect(onComplete).toHaveBeenCalledTimes(1);
  // Not the first property to finish: every one of them is at its end value.
  expect(onComplete.mock.results[0]?.value).toEqual({
    left: 100,
    top: 50,
    scaleX: 2,
    scaleY: 2,
  });
});

it('completes straight away when there is nothing to animate', () => {
  const onComplete = vi.fn();

  animate(new Rect({}), {}, {duration: 10, onComplete});

  expect(onComplete).toHaveBeenCalledTimes(1);
});

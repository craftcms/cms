import {cdp} from 'vite-plus/test/browser';

/**
 * Moves the pointer to the viewport's far corner. A test that hovers leaves
 * the pointer where the next test renders, which would start that test
 * hovered: a preview showing, or a hover fill behind a focus ring.
 */
export async function movePointerAway(): Promise<void> {
  await cdp().send('Input.dispatchMouseEvent', {
    type: 'mouseMoved',
    x: window.innerWidth - 1,
    y: window.innerHeight - 1,
  });
}

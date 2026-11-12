import {afterEach, expect, it, vi} from 'vite-plus/test';
import {ControllerElement} from './ControllerElement';
import {defineElement} from './defineElement';

const created = vi.fn<(root: HTMLElement) => void>();

class TestControllerElement extends ControllerElement<{destroy(): void}> {
  protected readonly rootSelector = ':scope > .root';

  protected create(root: HTMLElement): {destroy(): void} {
    created(root);
    return {destroy() {}};
  }
}

defineElement('test-controller-element', TestControllerElement);

function root(): HTMLElement {
  const element = document.createElement('div');
  element.className = 'root';
  return element;
}

afterEach(() => {
  created.mockReset();
  vi.restoreAllMocks();
  document.body.replaceChildren();
});

it('boots once its root is added after it connects', async () => {
  const element = document.createElement('test-controller-element');
  document.body.append(element);
  element.append(document.createElement('span'));
  await Promise.resolve();
  expect(created).not.toHaveBeenCalled();

  const controllerRoot = root();
  element.append(controllerRoot);

  await vi.waitFor(() => expect(created).toHaveBeenCalledOnce());
  expect(created).toHaveBeenCalledWith(controllerRoot);
});

it('waits for a root that never arrives without scheduling frames', async () => {
  const requestFrame = vi.spyOn(window, 'requestAnimationFrame');

  document.body.append(document.createElement('test-controller-element'));
  await new Promise((resolve) => setTimeout(resolve, 50));

  expect(requestFrame).not.toHaveBeenCalled();
  expect(created).not.toHaveBeenCalled();
});

it('keeps waiting for its root after it is moved', async () => {
  const element = document.createElement('test-controller-element');
  const sibling = document.createElement('div');
  document.body.append(element, sibling);

  sibling.after(element);
  element.append(root());

  await vi.waitFor(() => expect(created).toHaveBeenCalledOnce());
});

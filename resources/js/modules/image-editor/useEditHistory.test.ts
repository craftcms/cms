import {describe, expect, it} from 'vite-plus/test';
import {useEditHistory} from './useEditHistory';

const history = (limit?: number) =>
  useEditHistory<number>({equals: (a, b) => a === b, limit});

describe('useEditHistory', () => {
  it('undoes and redoes steps in order', () => {
    const h = history();

    h.record(0, 1);
    h.record(1, 2);

    expect(h.undo()).toBe(1);
    expect(h.undo()).toBe(0);
    expect(h.undo()).toBe(null);

    expect(h.redo()).toBe(1);
    expect(h.redo()).toBe(2);
    expect(h.redo()).toBe(null);
  });

  it('tracks whether there is anything to undo or redo', () => {
    const h = history();

    expect(h.canUndo.value).toBe(false);
    expect(h.canRedo.value).toBe(false);

    h.record(0, 1);
    expect(h.canUndo.value).toBe(true);

    h.undo();
    expect(h.canUndo.value).toBe(false);
    expect(h.canRedo.value).toBe(true);
  });

  it('drops the redo stack when a new edit is made after undoing', () => {
    const h = history();

    h.record(0, 1);
    h.record(1, 2);
    h.undo();
    h.record(1, 5);

    expect(h.canRedo.value).toBe(false);
    expect(h.undo()).toBe(1);
  });

  it('ignores a step that changed nothing', () => {
    const h = history();

    h.record(3, 3);

    expect(h.canUndo.value).toBe(false);
  });

  it('merges a keyed run into one step that undoes to where it started', () => {
    // A keyboard pick-up session: every nudge records, but it's one edit.
    const h = history();

    h.record(0, 1, 'nudge');
    h.record(1, 2, 'nudge');
    h.record(2, 3, 'nudge');

    expect(h.undo()).toBe(0);
    expect(h.canUndo.value).toBe(false);
    expect(h.redo()).toBe(3);
  });

  it('drops a keyed run that ends back where it started', () => {
    const h = history();

    h.record(0, 1, 'nudge');
    h.record(1, 0, 'nudge');

    expect(h.canUndo.value).toBe(false);
  });

  it('starts a new step after a run is sealed', () => {
    const h = history();

    h.record(0, 1, 'nudge');
    h.seal();
    h.record(1, 2, 'nudge');

    expect(h.undo()).toBe(1);
    expect(h.undo()).toBe(0);
  });

  it('does not merge into a step that has been undone and redone', () => {
    // A redone step is finished, not a run still in progress.
    const h = history();

    h.record(0, 1, 'nudge');
    h.undo();
    h.redo();
    h.record(1, 2, 'nudge');

    expect(h.undo()).toBe(1);
  });

  it('keeps only the most recent steps once past the limit', () => {
    const h = history(2);

    h.record(0, 1);
    h.record(1, 2);
    h.record(2, 3);

    expect(h.undo()).toBe(2);
    expect(h.undo()).toBe(1);
    expect(h.undo()).toBe(null);
  });

  it('forgets everything when cleared', () => {
    const h = history();

    h.record(0, 1);
    h.undo();
    h.clear();

    expect(h.canUndo.value).toBe(false);
    expect(h.canRedo.value).toBe(false);
  });
});

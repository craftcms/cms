import {describe, expect, it} from 'vitest';
import {isInteractiveClick, isInteractiveElement} from './dom';

describe('isInteractiveElement', () => {
  it('is true for focusable elements', () => {
    const link = document.createElement('a');
    link.setAttribute('href', '/x');
    const custom = document.createElement('craft-thing');
    custom.tabIndex = 0;

    expect(isInteractiveElement(link)).toBe(true);
    expect(isInteractiveElement(document.createElement('button'))).toBe(true);
    expect(isInteractiveElement(document.createElement('input'))).toBe(true);
    expect(isInteractiveElement(custom)).toBe(true);
  });

  it('is false for passive content and non-elements', () => {
    expect(isInteractiveElement(document.createElement('span'))).toBe(false);
    expect(isInteractiveElement(document.createElement('td'))).toBe(false);
    // A custom-element host with no tabindex is not focusable on its own.
    expect(isInteractiveElement(document.createElement('craft-chip'))).toBe(
      false
    );
    expect(isInteractiveElement(null)).toBe(false);
  });
});

describe('isInteractiveClick', () => {
  const boundary = document.createElement('div');
  boundary.tabIndex = 0; // the listener element is itself focusable

  // composedPath() is ordered deepest-first; the boundary terminates the walk.
  function event(...path: Element[]) {
    const click = new Event('click');
    Object.defineProperties(click, {
      currentTarget: {value: boundary},
      composedPath: {value: () => [...path, boundary]},
    });

    return click;
  }

  it('is false for a click on passive, non-focusable content', () => {
    const cell = document.createElement('td');
    const text = document.createElement('span');
    cell.append(text);
    expect(isInteractiveClick(event(text, cell))).toBe(false);
  });

  it('is false for a click on the boundary itself (its own tabindex is ignored)', () => {
    expect(isInteractiveClick(event())).toBe(false);
  });

  it('is true when a focusable control is anywhere on the path', () => {
    const link = document.createElement('a');
    link.setAttribute('href', '/edit/1');
    expect(isInteractiveClick(event(document.createElement('button')))).toBe(
      true
    );
    expect(isInteractiveClick(event(link))).toBe(true);
  });

  it('sees a focusable control inside a web component via the composed path', () => {
    // The host is not focusable; the real control lives in its shadow root and
    // is only visible through composedPath.
    const host = document.createElement('craft-checkbox'); // tabIndex -1
    const inner = document.createElement('input'); // tabIndex 0
    expect(isInteractiveClick(event(inner, host))).toBe(true);
  });

  it('defaults the boundary to the event currentTarget', () => {
    // No explicit boundary passed: the walk still stops at currentTarget.
    const text = document.createElement('span');
    expect(isInteractiveClick(event(text))).toBe(false);
  });

  it('accepts an explicit boundary', () => {
    const outer = document.createElement('div');
    const inner = document.createElement('button');
    const evt = new Event('click');
    Object.defineProperties(evt, {
      currentTarget: {value: null},
      composedPath: {value: () => [inner, outer]},
    });

    expect(isInteractiveClick(evt, outer)).toBe(true);
  });
});

import {describe, expect, it} from 'vite-plus/test';

import {getDist, within, isString, isTextNode} from '../src/utils/misc';
import {getInputPostVal, getPostData, findInputs} from '../src/utils/forms';
import {hasAttr, nearestSibling, closestRegistered} from '../src/utils/dom';

describe('misc utils', () => {
  it('getDist computes Euclidean distance', () => {
    expect(getDist(0, 0, 3, 4)).toBe(5);
  });
  it('within clamps', () => {
    expect(within(5, 0, 10)).toBe(5);
    expect(within(-1, 0, 10)).toBe(0);
    expect(within(11, 0, 10)).toBe(10);
  });
  it('isString', () => {
    expect(isString('x')).toBe(true);
    expect(isString(1)).toBe(false);
  });
  it('isTextNode', () => {
    expect(isTextNode(document.createTextNode('x'))).toBe(true);
    expect(isTextNode(document.createElement('div'))).toBe(false);
  });
});

describe('dom utils', () => {
  it('hasAttr reflects attribute presence', () => {
    const el = document.createElement('a');
    expect(hasAttr(el, 'href')).toBe(false);
    el.setAttribute('href', '/x');
    expect(hasAttr(el, 'href')).toBe(true);
  });

  it('nearestSibling skips non-matching siblings in each direction', () => {
    const ul = document.createElement('ul');
    ul.innerHTML =
      '<li class="g" id="a"></li><li class="sep"></li><li class="g" id="b"></li><span></span><li class="g" id="c"></li>';
    const b = ul.querySelector<HTMLElement>('#b')!;
    expect(nearestSibling(b, 'li.g', 'previous')?.id).toBe('a');
    expect(nearestSibling(b, 'li.g', 'next')?.id).toBe('c');
    expect(
      nearestSibling(ul.querySelector('#a')!, 'li.g', 'previous')
    ).toBeNull();
    expect(nearestSibling(ul.querySelector('#c')!, 'li.g', 'next')).toBeNull();
  });

  it('closestRegistered returns the nearest registered ancestor (self excluded)', () => {
    const registry = new WeakMap<Element, string>();
    const outer = document.createElement('div');
    const inner = document.createElement('div');
    const leaf = document.createElement('span');
    outer.appendChild(inner);
    inner.appendChild(leaf);
    registry.set(outer, 'outer');
    registry.set(inner, 'inner');
    expect(closestRegistered(leaf, registry)).toBe('inner');
    registry.set(leaf, 'leaf');
    expect(closestRegistered(leaf, registry)).toBe('inner');
    expect(closestRegistered(document.createElement('p'), registry)).toBeNull();
  });
});

describe('forms utils', () => {
  it('getInputPostVal returns null for an unchecked checkbox', () => {
    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.value = 'yes';
    expect(getInputPostVal(cb)).toBeNull();
    cb.checked = true;
    expect(getInputPostVal(cb)).toBe('yes');
  });

  it('findInputs collects inputs within a container', () => {
    const container = document.createElement('div');
    container.innerHTML =
      '<input name="a"><textarea name="b"></textarea><select name="c"></select><button name="d"></button>';
    expect(findInputs(container)).toHaveLength(4);
  });

  it('getPostData serializes name[] arrays with indexing', () => {
    const container = document.createElement('div');
    const i1 = document.createElement('input');
    i1.name = 'tags[]';
    i1.value = 'x';
    const i2 = document.createElement('input');
    i2.name = 'tags[]';
    i2.value = 'y';
    container.appendChild(i1);
    container.appendChild(i2);
    expect(getPostData(container)).toEqual({'tags[0]': 'x', 'tags[1]': 'y'});
  });

  it('getPostData skips disabled and unnamed inputs', () => {
    const container = document.createElement('div');
    const named = document.createElement('input');
    named.name = 'a';
    named.value = '1';
    const disabled = document.createElement('input');
    disabled.name = 'b';
    disabled.value = '2';
    disabled.disabled = true;
    container.appendChild(named);
    container.appendChild(disabled);
    expect(getPostData(container)).toEqual({a: '1'});
  });
});

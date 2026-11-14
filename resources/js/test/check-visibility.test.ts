import {afterEach, describe, expect, it} from 'vite-plus/test';
import {checkVisibility} from './check-visibility';

function render(html: string): void {
  document.body.innerHTML = html;
}

function el(selector: string, root: ParentNode = document): Element {
  return root.querySelector(selector)!;
}

afterEach(() => {
  document.body.innerHTML = '';
  document.head.innerHTML = '';
});

describe('checkVisibility', () => {
  it('is true for a rendered element', () => {
    render('<div><input class="target"></div>');
    expect(checkVisibility(el('.target'))).toBe(true);
  });

  it('is false for an element with the hidden attribute, or inside one', () => {
    render(
      '<input class="self" hidden><div hidden><input class="child"></div>'
    );
    expect(checkVisibility(el('.self'))).toBe(false);
    expect(checkVisibility(el('.child'))).toBe(false);
  });

  it('lets CSS override the hidden attribute, as the browser does', () => {
    document.head.innerHTML =
      '<style>.shown[hidden] { display: block; }</style>';
    render(
      '<p class="shown" hidden>Shown</p><p class="inline" hidden style="display: flex">Inline</p>'
    );
    expect(checkVisibility(el('.shown'))).toBe(true);
    expect(checkVisibility(el('.inline'))).toBe(true);
  });

  it('treats hidden="until-found" as hiding the content, not the element', () => {
    render(
      '<div class="found" hidden="until-found"><input class="inner"></div>'
    );
    expect(checkVisibility(el('.found'))).toBe(true);
    expect(checkVisibility(el('.inner'))).toBe(false);
  });

  it('is false for an element with no box: display: contents, or not in the document', () => {
    render(
      '<div class="contents" style="display: contents"><input class="child"></div>'
    );
    expect(checkVisibility(el('.contents'))).toBe(false);
    expect(checkVisibility(el('.child'))).toBe(true);
    expect(checkVisibility(document.createElement('input'))).toBe(false);
  });

  it('applies the hidden attribute inside a shadow tree', () => {
    render('<div class="host"></div>');
    const shadow = el('.host').attachShadow({mode: 'open'});
    shadow.innerHTML =
      '<input class="hidden" hidden><input class="overridden" hidden style="display: block"><div hidden="until-found"><input class="found"></div>';
    expect(checkVisibility(el('.hidden', shadow))).toBe(false);
    expect(checkVisibility(el('.overridden', shadow))).toBe(true);
    expect(checkVisibility(el('.found', shadow))).toBe(false);
  });

  it('is false inside an element hidden by a stylesheet class or inline style', () => {
    document.head.innerHTML = '<style>.collapsed { display: none; }</style>';
    render(
      '<div class="collapsed"><input class="by-class"></div><div style="display: none"><input class="inline"></div>'
    );
    expect(checkVisibility(el('.by-class'))).toBe(false);
    expect(checkVisibility(el('.inline'))).toBe(false);
  });

  it('is false for content in a closed details element, but not its summary', () => {
    render(
      '<details><summary class="summary">More</summary><input class="content"></details><details open><input class="open"></details>'
    );
    expect(checkVisibility(el('.summary'))).toBe(true);
    expect(checkVisibility(el('.content'))).toBe(false);
    expect(checkVisibility(el('.open'))).toBe(true);
  });

  it('is false inside content-visibility: hidden, but not for the element itself', () => {
    render(
      '<div class="outer" style="content-visibility: hidden"><input class="inner"></div>'
    );
    expect(checkVisibility(el('.inner'))).toBe(false);
    expect(checkVisibility(el('.outer'))).toBe(true);
  });

  it('checks visibility and opacity only when asked to', () => {
    render(
      '<input class="invisible" style="visibility: hidden"><div style="opacity: 0"><input class="transparent"></div>'
    );
    expect(checkVisibility(el('.invisible'))).toBe(true);
    expect(checkVisibility(el('.invisible'), {visibilityProperty: true})).toBe(
      false
    );
    expect(checkVisibility(el('.invisible'), {checkVisibilityCSS: true})).toBe(
      false
    );
    expect(checkVisibility(el('.transparent'))).toBe(true);
    expect(checkVisibility(el('.transparent'), {opacityProperty: true})).toBe(
      false
    );
  });

  it('follows the flattened tree, through shadow hosts and slots', () => {
    render('<div class="host"><input class="slotted"></div>');
    const host = el('.host');
    const shadow = host.attachShadow({mode: 'open'});
    shadow.innerHTML =
      '<div class="wrapper" style="display: none"><slot></slot></div><input class="inside">';

    expect(checkVisibility(el('.slotted'))).toBe(false);
    expect(checkVisibility(el('.inside', shadow))).toBe(true);

    (host as HTMLElement).hidden = true;
    expect(checkVisibility(el('.inside', shadow))).toBe(false);
  });

  it('matches named slots, and treats a child no slot takes as not rendered', () => {
    render(
      '<div class="host"><input class="named" slot="input"><input class="unslotted" slot="nowhere"></div>'
    );
    el('.host').attachShadow({mode: 'open'}).innerHTML =
      '<slot name="input"></slot><slot class="default"></slot>';

    expect(checkVisibility(el('.named'))).toBe(true);
    expect(checkVisibility(el('.unslotted'))).toBe(false);
  });
});

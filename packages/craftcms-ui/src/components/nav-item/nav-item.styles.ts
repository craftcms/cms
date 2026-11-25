import {css} from 'lit';
import {trimmedTextBoxStyles} from '@src/styles/text-box.styles';
import {badgeIndicatorCutoutStyles} from '../badge-indicator/badge-indicator.styles.js';

export default css`
  :host {
    --_padding-inline: var(--c-spacing-md);
    --_prefix-size: calc(24rem / 16);
    --_padding-block: calc(
      (var(--_rail-size) - var(--_prefix-size) - var(--_rail-border) * 2) / 2
    );
    --_prefix-gap: var(--c-spacing-sm);
    --_subnav-rule: 2px;
    /* Rows keep a transparent border to reserve room for the focus state. */
    --_rail-border: 1px;
    --_rail-size: var(--c-nav-item-size, var(--c-size-touch-target));
    /* Keeps the icon where a collapsed row centers it. */
    --_prefixed-padding-inline: calc(
      (var(--_rail-size) - var(--_prefix-size)) / 2 - var(--_rail-border)
    );

    border-radius: var(--c-radius-md);
  }

  .nav-item {
    display: grid;
    gap: var(--c-spacing-md);
    /* An item with no icon needs no prefix track. */
    grid-template-columns: 1fr auto;
    align-items: center;
    text-decoration: none;
    color: var(--c-nav-item-color, inherit);
    padding-inline: var(--_padding-inline);
    padding-block: var(--_padding-block);
    border: 1px solid transparent;
    border-radius: var(--c-radius-md);
    position: relative;
    box-sizing: border-box;
    min-block-size: var(--_rail-size);

    /* Expanded, the label is focusable, so the ring goes on the row. Collapsed,
       the row itself is focusable and needs craft-button's ring, not the
       browser's. */
    &:focus-visible,
    &:has(.nav-item__action-item:focus-visible) {
      outline: var(--c-focus-outline-width) solid var(--c-color-focus-outline);
      outline-offset: var(--c-focus-outline-offset);
    }
  }

  .nav-item__action-item {
    text-decoration: none;
    color: inherit;
    ${trimmedTextBoxStyles}

    &::after {
      content: '';
      position: absolute;
      inset: 0;
    }

    &:focus-visible {
      outline: none;
    }
  }

  /* Zero specificity, so the rules that shape the row still win. */
  :where(button.nav-item, button.nav-item__action-item) {
    appearance: none;
    background: none;
    border: 0;
    color: inherit;
    font: inherit;
    padding: 0;
    text-align: start;
    cursor: pointer;
  }

  craft-badge-indicator {
    position: absolute;
    inset-inline-end: 2px;
    inset-block-end: 2px;
  }

  .nav-item__prefix--indicated {
    --c-badge-indicator-cutout-x: calc(50% + 6px);
    --c-badge-indicator-cutout-y: calc(50% + 6px);
  }

  .nav-item__prefix--indicated:dir(rtl) {
    --c-badge-indicator-cutout-x: calc(50% - 6px);
  }

  .nav-item__prefix--indicated .nav-icon,
  .nav-item__prefix--indicated .nav-item__initial,
  .nav-item__prefix--indicated ::slotted([slot='icon']) {
    ${badgeIndicatorCutoutStyles}
  }

  /* Above the label's full-row click target, so clicks on a control don't
     follow the link. */
  .nav-item__prefix craft-button,
  .nav-item__suffix craft-button,
  ::slotted([slot='actions']) {
    position: relative;
    z-index: 1;
    font-size: 1em;
  }

  .nav-item--prefixed {
    padding-inline: var(--_prefixed-padding-inline);
    grid-template-columns: var(--_prefix-size) 1fr auto;

    /* The icon sits closer to the label than the row's gap allows. */
    > .nav-item__action-item {
      margin-inline-start: calc(var(--_prefix-gap) - var(--c-spacing-md));
    }
  }

  .nav-item--flush {
    margin-inline-start: calc(var(--_padding-inline) * -1);
  }

  /*
   * Shared with the flyout label so the two headings stay in sync. Set on the
   * row, not the host, so the nested subnav doesn't inherit it.
   */
  :host([group]) .nav-item,
  .flyout__label {
    font-size: var(--c-text-sm);
    font-weight: bold;
  }

  /*
   * Breaks add whole spacing steps above: md for a top-level heading, sm for
   * a nested one (its type already sets it off), and md after an open subnav
   * (see craft-nav-list).
   */
  :host([group]) {
    border-radius: 0;
    margin-block-start: var(--c-spacing-md);
  }

  :host([group][nested]) {
    margin-block-start: var(--c-spacing-sm);
  }

  :host([group]:first-child) {
    margin-block-start: 0;
  }

  :host([after-open-subnav]:not([group], [icon-only])) {
    margin-block-start: var(--c-spacing-md);
  }

  :host([group]) .nav-item {
    border-radius: 0;
    padding-block: var(--_padding-block) var(--c-spacing-xs);
    min-block-size: 0;
  }

  /* Before the selected states, so they keep their own color. */
  .nav-item:not(.nav-item--static):hover,
  .nav-item--flyout-open {
    color: inherit;
  }

  :host([active]) .nav-item {
    background-color: var(--c-color-accent-fill-quiet);
    border: 1px solid var(--c-color-accent-border-normal);
  }

  /* Selected parent: on the trail to the current page. Stub values. */
  :host([active]:not([current])) .nav-item {
    background-color: transparent;
    border-color: transparent;

    /* In line with the row's hover background. */
    &:before {
      content: '';
      position: absolute;
      inset-inline-start: calc(var(--_rail-border) * -1);
      inset-block-start: 12%;
      width: calc(3rem / 16);
      height: 76%;
      border-radius: calc(2rem / 16);
      background-color: var(--c-color-accent-fill-loud);
    }
  }

  /* Selected child: the current page. Stub values. */
  :host([current]) .nav-item {
    background-color: var(--c-color-accent-fill-quiet);
    border-color: var(--c-color-accent-border-normal);
    color: var(--c-color-accent-on-quiet);
  }

  :host([current]) ::slotted(craft-badge-indicator) {
    --c-color-neutral-fill-normal: var(--c-color-accent-fill-normal);
    --c-color-neutral-on-normal: var(--c-color-accent-on-normal);
  }

  /* The current row shows its selected fill instead. An open flyout keeps its
     row looking hovered while the pointer is in the flyout. */
  :host(:not([current], [appearance='tabs']))
    .nav-item:not(.nav-item--static):hover:not(:has(craft-button:hover)),
  :host(:not([current], [appearance='tabs'])) .nav-item--flyout-open {
    background-color: color-mix(
      in oklab,
      var(--c-color-mix-hover),
      transparent
    );
  }

  /* Pressed, like a tinted button. A press on a control belongs to it. */
  :host(:not([current], [appearance='tabs']))
    .nav-item:not(.nav-item--static):active:not(:has(craft-button:active)) {
    background-color: color-mix(in srgb, currentColor 12%, transparent);
  }

  /* No href: a plain label, not interactive. */
  .nav-item--static {
    cursor: default;
  }

  .nav-item__prefix {
    position: relative;
    display: grid;
    justify-content: center;
    align-items: center;
    aspect-ratio: 1;
    width: 100%;
    color: var(--c-nav-item-icon-color, inherit);
  }

  :host([active]) .nav-item__prefix,
  .nav-item:not(.nav-item--static):hover .nav-item__prefix,
  .nav-item--flyout-open .nav-item__prefix {
    color: inherit;
  }

  .nav-item__suffix {
    justify-self: end;
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
  }

  .active-indicator {
    display: inline-block;
    aspect-ratio: 1;
    width: calc(4rem / 16);
    border-radius: var(--c-radius-full);
    background-color: currentColor;

    :host([active]) & {
      width: calc(6rem / 16);
    }
  }

  /* Lines a child's label up with its parent's. */
  :host(:not([group])) .subnav {
    --_subnav-indent: calc(
      var(--_prefixed-padding-inline) + var(--_prefix-size) +
        var(--_prefix-gap) - var(--_subnav-rule) - var(--c-spacing-sm) -
        var(--_padding-inline)
    );
    position: relative;
    margin-block-start: var(--c-nav-list-gap, var(--c-spacing-xs));
    margin-inline-start: var(--_subnav-indent);
    padding-inline: var(--c-spacing-sm);
    border-inline-start: var(--_subnav-rule) solid transparent;
  }

  /* A hairline centered on the selected parent's indicator. */
  :host(:not([group]):not([icon-only])) .subnav::before {
    content: '';
    position: absolute;
    inset-block: 0;
    inset-inline-start: calc(
      var(--_rail-border) - var(--_subnav-indent) - var(--_subnav-rule)
    );
    width: 1px;
    background-color: var(--c-color-neutral-border-quiet);
  }

  /* A rail has nowhere to indent a subnav, so it moves into a popover, led by
     the label in place of a tooltip. */
  .flyout {
    display: grid;
    gap: var(--c-spacing-xs);
    padding: var(--c-spacing-md);
  }

  /*
   * Scrolls like .cp-inner-scroller. The list scrolls rather than the pane so
   * the fade doesn't mask the pane's border and shadow. The fade's lengths are
   * registered in variables.css.
   */
  @supports (animation-timeline: scroll()) {
    @keyframes nav-item-flyout-fade-start {
      from {
        --c-scroll-fade-start: 0px;
      }
      to {
        --c-scroll-fade-start: var(--c-spacing-xl);
      }
    }

    @keyframes nav-item-flyout-fade-end {
      from {
        --c-scroll-fade-end: var(--c-spacing-xl);
      }
      to {
        --c-scroll-fade-end: 0px;
      }
    }

    @media (forced-colors: none) and (prefers-contrast: no-preference) {
      craft-popover::part(popup) {
        overflow: hidden;
      }

      .flyout {
        box-sizing: border-box;
        max-block-size: calc(
          var(
              --popover-max-block-size,
              var(--_popover-available-block-size, 40vh)
            ) -
            2px
        );
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-width: thin;
        scrollbar-color: transparent transparent;
        scroll-padding-block: var(--c-spacing-xl);
        mask-image: linear-gradient(
          to bottom,
          transparent 0,
          #000 var(--c-scroll-fade-start),
          #000 calc(100% - var(--c-scroll-fade-end)),
          transparent 100%
        );
        animation-name: nav-item-flyout-fade-start, nav-item-flyout-fade-end;
        animation-timing-function: linear;
        animation-fill-mode: both;
        animation-timeline: scroll(self block);
        animation-range:
          0 var(--c-spacing-xl),
          calc(100% - var(--c-spacing-xl)) 100%;
      }

      /* Matches .cp-inner-scroller's thumb. */
      .flyout:is(:hover, :focus-within) {
        scrollbar-color: color-mix(
            in oklab,
            var(--color-slate-600),
            var(--color-blue-500) 20%
          )
          transparent;
      }
    }
  }

  /* Chevrons follow the row's color, which a plain craft-button would
     override. */
  .nav-item__suffix :is(.subnav-toggle, .flyout-toggle) {
    color: inherit;
  }

  /* Matches the toggle chevron it stands in for. */
  .flyout-indicator {
    font-size: var(--c-size-icon-sm, calc(12rem / 16));
  }

  .flyout-indicator:dir(rtl) {
    scale: -1 1;
  }

  .flyout__label {
    padding-inline: var(--c-spacing-sm);
  }

  /* The row's icon size, though the toggle is a small button. */
  .subnav-toggle craft-icon {
    font-size: var(--c-size-icon-sm, calc(12rem / 16));
  }

  /*
   * Collapsed to a rail: the row is only its icon, so the subnav loses its
   * indent, the disclosure sits over the icon, and headings become rules.
   */
  .nav-item--icon {
    box-sizing: border-box;
    width: var(--_rail-size);
    display: block;
    text-decoration: none;
    border: var(--_rail-border) solid transparent;
    aspect-ratio: 1;
    padding: 0;

    .nav-item__suffix {
      display: grid;
      justify-content: center;
      align-items: center;
    }
  }

  :host([icon-only]) {
  }

  :host([icon-only]) li {
    position: relative;
  }

  /* A rail shows no child rows, so the branch carries the current page's fill. */
  :host([icon-only][active]) .nav-item {
    background-color: var(--c-color-accent-fill-quiet);
    border-color: var(--c-color-accent-border-normal);
    color: var(--c-color-accent-on-quiet);

    &::before {
      content: none;
    }
  }

  :host([icon-only]) .subnav {
    margin: 0;
    border-inline-start: none;
    padding-inline: 0;
  }

  /*
   * With no indent, a rule marks the stand-ins as the icon's children. It
   * matches the active indicator so the two line up. Not on a group, whose
   * children already sit inside the branch's rule.
   */
  :host([icon-only]:not([group])) .subnav {
    position: relative;

    &::before {
      content: '';
      position: absolute;
      inset-block: 0;
      inset-inline-start: 0;
      width: calc(3rem / 16);
      border-radius: calc(2rem / 16);
      background-color: color-mix(in srgb, currentcolor, transparent 90%);
      transform: translateX(-150%);
    }
  }

  /*
   * Hidden over the icon until tabbed to; pointers have hover and the icon.
   * Opacity, since visibility or display would drop it from the tab order.
   */
  .rail-toggle {
    position: absolute;
    inset-block-start: 0;
    inset-inline-start: 0;
    z-index: 1;
    display: grid;
    place-items: center;
    /* The row's whole box, so the chevron and focus ring land where the row's
       would. */
    width: var(--_rail-size);
    height: var(--_rail-size);
    /* The row's ring offset, which the plain button would otherwise inset. */
    --_row-focus-outline-offset: var(--c-focus-outline-offset);

    /* Filled, so a keyboard user sees more than a bare chevron. */
    craft-button {
      --c-focus-outline-offset: var(--_row-focus-outline-offset);
      width: 100%;
      height: 100%;
      background-color: var(--c-color-neutral-fill-quiet);
    }

    &:not(:focus-within) {
      opacity: 0;
      pointer-events: none;
    }

    /* Matches the nav item's icon, which it replaces. */
    --_rail-icon-size: var(
      --c-icon-size,
      var(--c-size-icon-sm, calc(12rem / 16))
    );

    craft-icon {
      font-size: var(--_rail-icon-size);
    }
  }

  :host([icon-only]) li:has(.rail-toggle:focus-within) .nav-item__prefix {
    opacity: 0;
  }

  /* On a rail a heading becomes a rule, which owns the space above it. */
  :host([group][icon-only]) {
    margin-block-start: 0;
    padding-block-start: var(--c-spacing-sm);
  }

  .rail-separator {
    position: relative;
    border: 0;
    width: 60%;
    margin-block: 0;
    margin-inline: auto;
    inset-block-start: calc(var(--c-spacing-sm) * -1);
    border-block-start: 1px solid
      color-mix(transparent, var(--c-color-neutral-border-quiet));
  }

  /*
   * A filled chip, so a letter reads as a mark rather than a truncated word.
   * Mixed from currentcolor since the nav's background comes from its container.
   */
  .nav-item__initial {
    display: grid;
    place-items: center;
    width: calc(14rem / 16);
    aspect-ratio: 1;
    border-radius: var(--c-radius-full);
    background-color: color-mix(in srgb, currentcolor, transparent 88%);
    font-size: var(--c-text-xs);
    font-weight: bold;
    line-height: 1;
  }

  :host([appearance='tabs']),
  :host([appearance='tabs']) .nav-item {
    border-radius: 0;
  }

  /* No gap, so the empty suffix column doesn't widen the bar past the label. */
  :host([appearance='tabs']) .nav-item {
    gap: 0;
    padding-inline: 0;
    border-color: transparent;
    background-color: transparent;
  }

  :host([appearance='tabs'][current]) .nav-item {
    color: inherit;
    font-weight: var(--font-weight-semibold);
  }

  :host([appearance='tabs'][active]:not([current])) .nav-item::before {
    display: none;
  }

  :host([appearance='tabs']) .nav-item::after {
    content: '';
    position: absolute;
    inset-block-end: -1px;
    inset-inline: 0;
    block-size: calc(2rem / 16);
    background-color: transparent;
  }

  :host([appearance='tabs'][current]) .nav-item::after {
    background-color: var(
      --c-nav-item-border-active,
      var(--c-color-accent-fill-loud)
    );
  }

  @media (forced-colors: active) {
    :host([appearance='tabs'][current]) .nav-item::after {
      background-color: Highlight;
    }
  }
`;

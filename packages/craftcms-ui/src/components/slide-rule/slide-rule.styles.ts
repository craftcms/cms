import {css} from 'lit';

// Ported from the legacy packages/craftcms-legacy/cp/src/css/_image_editor.scss
// `.slide-rule` block into the component's shadow root. The legacy hard-coded
// colours (`#63a6e1`, `--white`, `--gray-900`) are gone: everything here comes
// from the `--c-slide-rule-*` tokens, so the rule follows the CP's scheme and
// its themes rather than assuming a dark image editor around it.
//
// Nothing paints the page background any more either -- see the mask on
// `.graduations` -- so it can sit on anything.
export default css`
  :host {
    display: block;
  }

  .slide-rule {
    /*
      Seeded from the public tokens so the rest of the sheet reads off one
      name apiece. The graduation width is the load-bearing one: the strip's
      positioning maths is in units of it, and the JS measures what was
      actually rendered rather than assuming the default.
    */
    --_graduation-width: var(--c-slide-rule-graduation-width, 8px);
    --_graduation-height: var(--c-slide-rule-graduation-height, 10px);
    --_graduation-color: var(--c-slide-rule-graduation-color, currentColor);
    --_accent-color: var(--c-slide-rule-accent-color, currentColor);

    /* Every fifth graduation, half again as tall and twice as thick as the
       rest. Derived rather than their own knobs, so they can't drift out of
       proportion with the graduation they are a louder version of. */
    --_graduation-main-height: calc(var(--_graduation-height) * 1.5);
    --_graduation-thickness: 2px;
    --_graduation-main-thickness: calc(var(--_graduation-thickness) * 2);

    /* A label sits this far below the tallest mark, and takes about this much
       room once it is there -- the floor on the graduations uses both. */
    --_label-gap: var(--c-spacing-xs);
    --_label-space: calc(12rem / 16);

    /*
      The cursor stacked above the ruler it points at. It used to be absolutely
      positioned at 50% with a -4px margin pulling it back by half its width --
      a magic number that landed it a pixel off the centre the strip is
      positioned against, which taps then measured their delta from. Letting
      the layout centre it is exact, and stacking the two means they can no
      longer overlap by accident.
    */
    display: flex;
    flex-direction: column;
    /* Sets the cursor off the marks it points at. Taken out of the space that
       used to sit above the cursor rather than added to the total, so the
       control stays the height the token asks for. That slack is now spent:
       widening this again grows the control. */
    gap: var(--c-spacing-sm);
    outline: none;
    height: var(--c-slide-rule-height, 40px);

    /* Digits that don't change width as the strip slides past. */
    font-family: var(--c-font-mono);
  }

  .slide-rule:focus-visible .cursor::after {
    outline: var(--c-focus-outline-width) var(--c-focus-outline-style)
      var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
    border-radius: var(--c-radius-sm);
  }

  .cursor {
    /* The cross axis of a column, so this is the horizontal centring that
       replaced the margin. justify-self is the grid spelling and does
       nothing here. */
    align-self: center;
    /* Not for placement -- the focus ring below anchors to it. */
    position: relative;
    width: 0;
    height: 0;
    border-inline-start: calc(5 / 16 * 1rem) solid transparent;
    border-inline-end: calc(5 / 16 * 1rem) solid transparent;
    border-block-start: calc(5 / 16 * 1rem) solid var(--_accent-color);
  }

  .cursor::after {
    content: '';
    width: calc(20 / 16 * 1rem);
    height: calc(50 / 16 * 1rem);
    position: absolute;
    inset-block-start: -15px;
    inset-inline-start: 50%;
    transform: translateX(-50%);
  }

  .graduations {
    /*
      Takes the height the cursor row leaves behind. A height has to come from
      somewhere: the graduations' only content is an absolutely positioned
      label, so there is nothing here to size to. It also has to end up
      definite, because the strip sizes to it and the graduations are the
      pointer targets -- a zero-height strip is one a drag can never hit.

      The floor keeps a taller tick from pushing its label out of the clip.
    */
    flex: 1;
    min-block-size: calc(
      var(--_graduation-main-height) + var(--_label-gap) + var(--_label-space)
    );
    position: relative;
    /*
      This element is the window the strip slides behind, and the positioning
      maths centres the strip on its width — so it has to be the width of the
      rule, not of the strip inside it. Without the clip it sizes to its
      content, and the strip is centred on a box wider than the one the cursor
      sits in the middle of: about 20 degrees out for the default range.

      The legacy markup got this from a wrapper around the component --
      .straightening, with a max-width and its own clip -- so the component
      owns it now rather than depending on where it is placed.
    */
    overflow: hidden;

    /*
      The graduations fade out towards the edges. This masks their own alpha
      rather than painting a gradient of the page background over them, so the
      rule holds up on any background -- the overlay it replaces was a hard
      --gray-900 and smeared a dark band across anything lighter.

      Masking the window rather than the graduations themselves keeps it free
      as the strip slides underneath: the fade belongs to the visible box, so
      dragging doesn't have to recompute anything per graduation.
    */
    mask-image: linear-gradient(
      to right,
      transparent 0%,
      #000 15%,
      #000 85%,
      transparent 100%
    );
  }

  /*
    The span between zero and the current value, in the accent fill a selected
    row or menu item uses -- so a selection reads the same here as it does
    anywhere else in the CP.

    A band rather than lit-up graduations: the value is continuous, so it
    usually falls between two marks and there is nothing there to light up.

    Hidden at zero, where the band has no width and would be nothing but the
    two borders standing either side of the cursor.

    Behind the strip, which is why both are positioned and this one comes
    first.
  */
  .indicator {
    position: absolute;
    /* The width the JS sets is the span from zero to the value, borders and
       all -- a content box would make the band two pixels wider than the
       value it stands for. */
    box-sizing: border-box;
    inset-block: 0;
    inset-inline-start: 50%;
    inline-size: 0;
    background: var(--c-color-accent-fill-quiet);
    border-inline: 1px solid var(--c-color-accent-border-quiet);
    pointer-events: none;
    transition:
      200ms inline-size linear,
      200ms translate linear;
  }

  .slide-rule.dragging .indicator {
    transition: none;
  }

  .graduations ul {
    position: relative;
    /*
      One column per graduation, sized by the track. The width is declared once
      here rather than on every item, and a fixed track has no reason to shrink
      -- which is what the flex: none on each item was for.

      Whatever lays this out has to put the graduations flush against each
      other. As inline-blocks the newlines between them in the template each
      rendered as a space, making the strip wider than the graduation apiece
      the positioning maths works in, which put zero nowhere near the cursor.
    */
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: var(--_graduation-width);

    /*
      Shrink-to-fit around those tracks, which is what the float here used to
      buy. As a plain block the strip would size to the window it slides
      behind instead, and the JS measures this element to learn how wide a
      graduation actually came out.
    */
    width: max-content;

    height: 100%;
    margin: 0;
    padding: 0;
    list-style: none;

    /*
      Translated rather than offset: sliding the strip is then a compositor
      job with no layout behind it, which is what a drag is doing every frame.
    */
    transition: 200ms transform linear;

    /* On the strip, not the marks: a press anywhere in it drags, and the marks
       are only a couple of pixels wide to aim at. */
    cursor: pointer;
  }

  .slide-rule.dragging .graduations ul,
  .slide-rule.dragging .indicator {
    transition: none;
  }

  /* Until the first placement has been rendered -- see #markPlaced(). */
  .slide-rule:not(.placed) .graduations ul,
  .slide-rule:not(.placed) .indicator {
    transition: none;
  }

  .graduations ul li {
    /*
      The graduation is the mark, rather than a box holding a pseudo-element
      that draws one. The grid track supplies the spacing the box used to, so
      the item is free to be exactly the tick it represents.
    */
    justify-self: start;
    /*
      A graduation is a point on the ruler, and the maths puts that point at
      the start of the track -- so the mark has to straddle it rather than
      begin at it, or every mark sits half its own thickness to the right of
      the value it stands for, and the cursor points between them.

      Centring on the point rather than in the track also means marks of
      different thicknesses line up on it for free.
    */
    translate: -50%;
    inline-size: var(--_graduation-thickness);
    block-size: var(--_graduation-height);
    background: var(--_graduation-color);

    font-size: 8px;
    /* The label is positioned against this. */
    position: relative;
  }

  .graduations ul li.main-graduation {
    inline-size: var(--_graduation-main-thickness);
    block-size: var(--_graduation-main-height);
  }

  .graduations ul li:hover {
    background: var(--_accent-color);
  }

  .graduations ul li .label {
    width: 20px;
    position: absolute;
    /* Clear of the tallest mark, so a taller graduation pushes the labels
       down with it rather than crowding them. */
    inset-block-start: calc(var(--_graduation-main-height) + var(--_label-gap));
    /* Centred on the mark, whatever it is -- the -9px this replaces was half
       of a 20px label against a box that is no longer 10px wide. */
    inset-inline-start: 50%;
    transform: translateX(-50%);
    display: none;
    text-align: center;
    /* Narrower than some labels are long, and a leading minus is a break
       opportunity -- so they stay on one line explicitly. */
    white-space: nowrap;
  }

  .graduations ul li.main-graduation .label {
    display: block;
    cursor: default;
  }
`;

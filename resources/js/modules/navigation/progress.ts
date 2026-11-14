import {router} from '@inertiajs/vue3';
import type {Visit} from '@inertiajs/core';
import './progress.css';

/** How long a visit runs before the bar shows, as Inertia's own bar waits. */
const DELAY_MS = 250;

/**
 * A progress bar for Inertia visits, in place of Inertia's own.
 *
 * Inertia's bar reads `offsetWidth` each time it moves, forcing a layout of
 * whatever is on the page at that moment, and it moves most while a new page
 * is being built — the finishing step alone cost ~90 ms of a navigation to the
 * entries index (4× CPU). This one animates with the Web Animations API, which
 * never needs the layout, so it adds nothing while the page renders.
 *
 * The bar stays in the document, empty at `scaleX(0)` while no visit runs, so
 * showing and hiding it are animations alone and never touch the DOM. It's
 * decorative: navigations are announced separately.
 */
export function installNavigationProgress(): void {
  let visit: Visit | null = null;
  let timer: ReturnType<typeof setTimeout> | undefined;
  let animations: Animation[] = [];

  const bar = document.createElement('div');
  bar.className = 'cp-navigation-progress';
  bar.setAttribute('aria-hidden', 'true');
  const fill = document.createElement('div');
  fill.className = 'cp-navigation-progress__fill';
  bar.append(fill);
  document.body.append(bar);

  function hide(): void {
    animations.forEach((animation) => animation.cancel());
    animations = [];
  }

  function show(): void {
    hide();
    // Eases toward 90% and waits there, as a trickle would, until the visit
    // finishes.
    animations = [
      fill.animate([{transform: 'scaleX(0)'}, {transform: 'scaleX(0.9)'}], {
        duration: 8000,
        easing: 'cubic-bezier(0.1, 0.7, 0.2, 1)',
        fill: 'forwards',
      }),
    ];
  }

  function complete(): void {
    if (!animations.length) {
      return;
    }

    // Without a starting keyframe, each animation starts from where the ones
    // before it left the bar, so it finishes from wherever the trickle got to
    // without reading it back.
    const fillUp = fill.animate([{transform: 'scaleX(1)'}], {
      duration: 200,
      easing: 'ease-out',
      fill: 'forwards',
    });
    const fadeOut = bar.animate([{opacity: 0}], {
      duration: 200,
      delay: 200,
      fill: 'forwards',
    });

    animations.push(fillUp, fadeOut);
    fadeOut.finished.then(hide, () => {});
  }

  router.on('start', (event) => {
    clearTimeout(timer);
    visit = event.detail.visit;

    if (visit.showProgress === false) {
      hide();

      return;
    }

    timer = setTimeout(show, DELAY_MS);
  });

  router.on('finish', (event) => {
    // An interrupted visit finishes after the one replacing it has started,
    // whose bar carries on.
    if (event.detail.visit !== visit) {
      return;
    }

    clearTimeout(timer);
    visit = null;

    if (event.detail.visit.completed) {
      complete();
    } else {
      hide();
    }
  });
}

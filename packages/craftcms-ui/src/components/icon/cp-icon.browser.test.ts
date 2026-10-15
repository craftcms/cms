import {beforeEach, expect, it} from 'vite-plus/test';
import '../../styles/cp.css';

beforeEach(() => {
  document.body.innerHTML = '';
});

// Server-rendered `.cp-icon`s are inline SVGs, which fill black unless told
// otherwise, so they'd vanish on a dark surface and ignore link colors.
it('fills icons with the surrounding text color', () => {
  document.body.innerHTML = `
    <a href="#" style="color: rgb(0, 0, 255)">
      <span class="cp-icon"><svg viewBox="0 0 1 1"><path d="M0 0h1v1H0z"/></svg></span>
    </a>`;

  expect(getComputedStyle(document.querySelector('path')!).fill).toBe(
    'rgb(0, 0, 255)'
  );
});

it('leaves shapes with their own fill alone', () => {
  document.body.innerHTML = `
    <span class="cp-icon" style="color: rgb(0, 0, 255)">
      <svg viewBox="0 0 1 1"><path fill="rgb(255, 0, 0)" d="M0 0h1v1H0z"/></svg>
    </span>`;

  expect(getComputedStyle(document.querySelector('path')!).fill).toBe(
    'rgb(255, 0, 0)'
  );
});

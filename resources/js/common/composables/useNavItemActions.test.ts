import {effectScope} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import {useNavItemAction, useNavItemActions} from './useNavItemActions';

// The registry is global, so every lend has to be taken back or it would leak
// into whichever test runs next.
const scopes: Array<ReturnType<typeof effectScope>> = [];

afterEach(() => {
  scopes.splice(0).forEach((scope) => scope.stop());
});

function lend(url: () => string, label = 'Customize sources') {
  const scope = effectScope();
  scope.run(() =>
    useNavItemAction(url, {label, icon: 'gear', onClick: () => {}})
  );
  scopes.push(scope);

  return scope;
}

const labels = (href: string) =>
  useNavItemActions()(href).map((action) => action.label);

it('finds the action from the branch the page sits inside', () => {
  // The asset route carries the volume, so the page's URL is deeper than the
  // branch's own href — matching them exactly would never find it.
  lend(() => '/admin/assets/uploads?sort=title');

  expect(labels('/admin/assets')).toEqual(['Customize sources']);
});

it('ignores a branch the page is not inside', () => {
  lend(() => '/admin/assets/uploads');

  expect(labels('/admin/content/entries')).toEqual([]);
});

it('matches whole path segments, not a shared prefix', () => {
  lend(() => '/admin/assetsettings');

  expect(labels('/admin/assets')).toEqual([]);
});

it('takes the action back once the page is gone', () => {
  const scope = lend(() => '/admin/assets');

  scope.stop();

  expect(labels('/admin/assets')).toEqual([]);
});

it('follows a page whose url changes without it remounting', () => {
  // An Inertia visit between entry pages patches the index rather than
  // rebuilding it, so the registration has to read the url live.
  let url = '/admin/content/entries';
  lend(() => url);

  url = '/admin/content/blog';

  expect(labels('/admin/content/entries')).toEqual([]);
  expect(labels('/admin/content/blog')).toEqual(['Customize sources']);
});

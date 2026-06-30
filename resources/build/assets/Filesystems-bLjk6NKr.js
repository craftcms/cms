import {l as e} from './nav-item-CyC1px5v-DZDpUldI.js';
import './cp-npqTfNqh.js';
import {
  E as t,
  R as n,
  T as r,
  X as i,
  _ as a,
  ct as o,
  gt as s,
  ht as c,
  k as l,
  tt as u,
  w as d,
  y as f,
} from './_plugin-vue_export-helper-g2tzphu6.js';
import {i as p, o as m, t as h} from './AdminTable-32qWVDq-.js';
import {t as g} from './Empty-D0VMu5kQ.js';
import {t as _} from './Pane-CJi1MPPH.js';
import {s as v} from './InlineFlash-DnK6Yp2V.js';
import {n as y, t as b} from './wayfinder-V597ZF_3.js';
import {t as x} from './DeleteButton-BLk4eDvl.js';
import {t as S} from './createCraftColumnHelper-ssekFPQZ.js';
import {t as C} from './AppLayout-BXGMFlSp.js';
var w = (e) => ({url: w.url(e), method: `get`});
((w.definition = {methods: [`get`, `head`], url: `/admin/actions/fs/edit`}),
  (w.url = (e) => w.definition.url + y(e)),
  (w.get = (e) => ({url: w.url(e), method: `get`})),
  (w.head = (e) => ({url: w.url(e), method: `head`})));
var T = (e, t) => ({url: T.url(e, t), method: `get`});
((T.definition = {
  methods: [`get`, `head`],
  url: `/admin/settings/filesystems/{handle}`,
}),
  (T.url = (e, t) => {
    ((typeof e == `string` || typeof e == `number`) && (e = {handle: e}),
      Array.isArray(e) && (e = {handle: e[0]}),
      (e = b(e)));
    let n = {handle: e.handle};
    return (
      T.definition.url
        .replace(`{handle}`, n.handle.toString())
        .replace(/\/+$/, ``) + y(t)
    );
  }),
  (T.get = (e, t) => ({url: T.url(e, t), method: `get`})),
  (T.head = (e, t) => ({url: T.url(e, t), method: `head`})));
var E = (e, t) => ({url: E.url(e, t), method: `get`});
((E.definition = {
  methods: [`get`, `head`],
  url: `/admin/settings/filesystems/{handle}/edit`,
}),
  (E.url = (e, t) => {
    ((typeof e == `string` || typeof e == `number`) && (e = {handle: e}),
      Array.isArray(e) && (e = {handle: e[0]}),
      (e = b(e)));
    let n = {handle: e.handle};
    return (
      E.definition.url
        .replace(`{handle}`, n.handle.toString())
        .replace(/\/+$/, ``) + y(t)
    );
  }),
  (E.get = (e, t) => ({url: E.url(e, t), method: `get`})),
  (E.head = (e, t) => ({url: E.url(e, t), method: `head`})));
var D = {
    '/admin/actions/fs/edit': w,
    '/admin/settings/filesystems/{handle}': T,
    '/admin/settings/filesystems/{handle}/edit': E,
  },
  O = (e) => ({url: O.url(e), method: `post`});
((O.definition = {methods: [`post`], url: `/admin/actions/fs/save`}),
  (O.url = (e) => O.definition.url + y(e)),
  (O.post = (e) => ({url: O.url(e), method: `post`})));
var k = (e, t) => ({url: k.url(e, t), method: `post`});
((k.definition = {
  methods: [`post`],
  url: `/admin/settings/filesystems/{handle}`,
}),
  (k.url = (e, t) => {
    ((typeof e == `string` || typeof e == `number`) && (e = {handle: e}),
      Array.isArray(e) && (e = {handle: e[0]}),
      (e = b(e)));
    let n = {handle: e.handle};
    return (
      k.definition.url
        .replace(`{handle}`, n.handle.toString())
        .replace(/\/+$/, ``) + y(t)
    );
  }),
  (k.post = (e, t) => ({url: k.url(e, t), method: `post`})));
var A = (e) => ({url: A.url(e), method: `get`});
((A.definition = {
  methods: [`get`, `head`],
  url: `/admin/settings/filesystems`,
}),
  (A.url = (e) => A.definition.url + y(e)),
  (A.get = (e) => ({url: A.url(e), method: `get`})),
  (A.head = (e) => ({url: A.url(e), method: `head`})));
var j = (e) => ({url: j.url(e), method: `get`});
((j.definition = {
  methods: [`get`, `head`],
  url: `/admin/settings/filesystems/new`,
}),
  (j.url = (e) => j.definition.url + y(e)),
  (j.get = (e) => ({url: j.url(e), method: `get`})),
  (j.head = (e) => ({url: j.url(e), method: `head`})));
var M = (e, t) => ({url: M.url(e, t), method: `delete`});
((M.definition = {
  methods: [`delete`],
  url: `/admin/settings/filesystems/{handle}`,
}),
  (M.url = (e, t) => {
    ((typeof e == `string` || typeof e == `number`) && (e = {handle: e}),
      Array.isArray(e) && (e = {handle: e[0]}),
      (e = b(e)));
    let n = {handle: e.handle};
    return (
      M.definition.url
        .replace(`{handle}`, n.handle.toString())
        .replace(/\/+$/, ``) + y(t)
    );
  }),
  (M.delete = (e, t) => ({url: M.url(e, t), method: `delete`})));
var N = t({
  __name: `Filesystems`,
  props: {filesystems: {}, readOnly: {type: Boolean}},
  setup(t) {
    let y = t;
    function b(t) {
      confirm(e(`Are you sure you want to delete “{name}”`, {name: t.name})) &&
        s.delete(M(t.handle));
    }
    let w = S(),
      T = a(() => ({name: !0, handle: !0, type: !0, actions: !y.readOnly})),
      E = o([
        w.link(`name`, {
          header: e(`Name`),
          props: ({row: e}) => ({
            href: D[`/admin/settings/filesystems/{handle}/edit`]({
              handle: e.original.handle,
            }).url,
            inertia: !1,
          }),
        }),
        w.handle(`handle`),
        w.accessor(`type`, {
          header: e(`Type`),
          cell: ({row: e, getValue: t}) =>
            e.original.missing ? l(`span`, {class: `c-color-error`}, t()) : t(),
        }),
        w.actions(({row: e}) => [l(x, {onClick: () => b(e.original)})]),
      ]),
      O = p({
        get data() {
          return y.filesystems;
        },
        get columns() {
          return E.value;
        },
        state: {
          get columnVisibility() {
            return T.value;
          },
        },
        enableSorting: !1,
        getCoreRowModel: m(),
      });
    return (t, a) => (
      n(),
      f(C, null, {
        actions: i(() => [
          r(
            v,
            {
              variant: `accent`,
              appearance: `button`,
              href: c(j)().url,
              inertia: !1,
            },
            {default: i(() => [d(u(c(e)(`New filesystem`)), 1)]), _: 1},
            8,
            [`href`]
          ),
        ]),
        default: i(() => [
          r(
            _,
            {padding: 0, appearance: `raised`},
            {
              default: i(() => [
                r(
                  h,
                  {table: c(O), reorderable: !1},
                  {
                    'empty-row': i(() => [
                      r(
                        g,
                        {
                          label: c(e)(`No filesystems exist yet.`),
                          icon: `light/folder-open`,
                        },
                        {
                          default: i(() => [
                            r(
                              v,
                              {
                                appearance: `button`,
                                href: c(j)().url,
                                inertia: !1,
                              },
                              {
                                default: i(() => [
                                  d(u(c(e)(`New filesystem`)), 1),
                                ]),
                                _: 1,
                              },
                              8,
                              [`href`]
                            ),
                          ]),
                          _: 1,
                        },
                        8,
                        [`label`]
                      ),
                    ]),
                    _: 1,
                  },
                  8,
                  [`table`]
                ),
              ]),
              _: 1,
            }
          ),
        ]),
        _: 1,
      })
    );
  },
});
export {N as default};

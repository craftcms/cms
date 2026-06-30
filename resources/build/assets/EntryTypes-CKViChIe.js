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
import {i as p, o as m, r as h, t as g} from './AdminTable-32qWVDq-.js';
import {t as _} from './Empty-D0VMu5kQ.js';
import {t as v} from './Pane-CJi1MPPH.js';
import {s as y} from './InlineFlash-DnK6Yp2V.js';
import {t as b} from './DeleteButton-BLk4eDvl.js';
import {t as x} from './createCraftColumnHelper-ssekFPQZ.js';
import {t as S} from './DynamicHtmlRenderer-tRw5R4gl.js';
import {t as C} from './AppLayout-BXGMFlSp.js';
import {i as w, n as T, r as E} from './EntryTypesController-BmQTZRaT.js';
import {n as D, r as O, t as k} from './useServerSort-DgkJYi_Z.js';
var A = t({
  __name: `EntryTypes`,
  props: {
    title: {},
    pagination: {},
    sort: {},
    searchTerm: {},
    data: {},
    readOnly: {type: Boolean},
  },
  setup(t) {
    let A = t;
    function j(t) {
      confirm(
        e(
          `Are you sure you want to delete “{name}” and all entries of that type?`,
          {name: t.title}
        )
      ) && s.delete(E(t.id));
    }
    let M = o(A.searchTerm ?? ``),
      N = a(() => A.data),
      P = x(),
      F = a(() => ({name: !0, handle: !0, usages: !0, actions: !A.readOnly})),
      I = a(() => [
        P.display({
          id: `name`,
          header: e(`Entry Type`),
          cell: ({row: e}) => l(S, {html: e.original.chip}),
        }),
        P.accessor(`handle`, {
          header: e(`Handle`),
          meta: {cellClass: `justify-center`},
          cell: ({getValue: e}) => l(`craft-copy-attribute`, {value: e()}, e()),
        }),
        P.accessor(`usages`, {
          header: e(`Usages`),
          cell: ({getValue: e}) => l(S, {html: e()}),
        }),
        P.actions(({row: e}) => [l(b, {onClick: () => j(e.original)})]),
      ]),
      {paginationState: L, paginationConfig: R} = O({
        initialState: A.pagination,
        onChange: ({query: e}) => {
          s.visit(w({query: e}), {
            only: [`data`, `pagination`],
            preserveScroll: !0,
          });
        },
      }),
      {sortingState: z, sortingConfig: B} = k({
        initialState: A.sort,
        onChange: ({query: e}) => {
          s.visit(w({query: e}), {only: [`data`, `sort`], preserveScroll: !0});
        },
      }),
      V = p({
        get data() {
          return N.value;
        },
        get columns() {
          return I.value;
        },
        state: {
          get pagination() {
            return L.value;
          },
          get sorting() {
            return z.value;
          },
          get columnVisibility() {
            return F.value;
          },
        },
        getCoreRowModel: m(),
        ...R,
        ...B,
      });
    return (a, o) => (
      n(),
      f(
        C,
        {title: t.title},
        {
          actions: i(() => [
            r(
              y,
              {
                appearance: `button`,
                href: c(T)[`/admin/settings/entry-types/new`]().url,
                variant: `accent`,
                inertia: !1,
                icon: `plus`,
              },
              {default: i(() => [d(u(c(e)(`New entry type`)), 1)]), _: 1},
              8,
              [`href`]
            ),
          ]),
          default: i(() => [
            r(
              v,
              {padding: 0, appearance: `raised`},
              {
                default: i(() => [
                  r(
                    g,
                    {
                      spacing: c(h).Relaxed,
                      table: c(V),
                      reorderable: !1,
                      from: t.pagination.from,
                      to: t.pagination.to,
                      total: t.pagination.total,
                      'enable-adjust-page-size': !0,
                    },
                    {
                      'empty-row': i(() => [
                        r(
                          _,
                          {
                            icon: `light/files`,
                            label: c(e)(`No entry types exist yet.`),
                          },
                          null,
                          8,
                          [`label`]
                        ),
                      ]),
                      'search-form': i(() => [
                        r(
                          D,
                          {
                            action: c(w)(),
                            modelValue: M.value,
                            'onUpdate:modelValue': (o[0] ||= (e) =>
                              (M.value = e)),
                          },
                          null,
                          8,
                          [`action`, `modelValue`]
                        ),
                      ]),
                      _: 1,
                    },
                    8,
                    [`spacing`, `table`, `from`, `to`, `total`]
                  ),
                ]),
                _: 1,
              }
            ),
          ]),
          _: 1,
        },
        8,
        [`title`]
      )
    );
  },
});
export {A as default};

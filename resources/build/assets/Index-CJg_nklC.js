import {l as e} from './nav-item-CyC1px5v-DZDpUldI.js';
import {d as t, r as n} from './cp-npqTfNqh.js';
import {
  B as r,
  E as i,
  G as a,
  M as o,
  Q as s,
  R as c,
  T as l,
  V as u,
  X as d,
  Z as f,
  _ as p,
  b as m,
  ct as h,
  et as g,
  f as _,
  ht as v,
  m as y,
  o as b,
  s as x,
  tt as S,
  v as C,
  w,
  x as T,
  y as E,
} from './_plugin-vue_export-helper-g2tzphu6.js';
import {i as ee, o as D, t as O} from './AdminTable-32qWVDq-.js';
import {t as k} from './Select-uUcGssK_.js';
import {i as A, n as j, r as M, t as N} from './wayfinder-V597ZF_3.js';
import {a as P} from './dist-BSfJDNP3.js';
import {t as F} from './createCraftColumnHelper-ssekFPQZ.js';
import {t as I} from './CheckboxGroup-tx-Aecav.js';
import {t as L} from './CraftInput-B2G9EaE3.js';
import {t as R} from './IndexLayout-dNU8P7d2.js';
var z = (e, t) => ({url: z.url(e, t), method: `get`});
((z.definition = {
  methods: [`get`, `head`],
  url: `/admin/content/{page}/{sectionHandle?}`,
}),
  (z.url = (e, t) => {
    (Array.isArray(e) && (e = {page: e[0], sectionHandle: e[1]}),
      (e = N(e)),
      M(e, [`sectionHandle`]));
    let n = {page: e.page, sectionHandle: e.sectionHandle};
    return (
      z.definition.url
        .replace(`{page}`, n.page.toString())
        .replace(`{sectionHandle?}`, n.sectionHandle?.toString() ?? ``)
        .replace(/\/+$/, ``) + j(t)
    );
  }),
  (z.get = (e, t) => ({url: z.url(e, t), method: `get`})),
  (z.head = (e, t) => ({url: z.url(e, t), method: `head`})),
  Object.assign(z, z));
var B = i({
    __name: `ElementSources`,
    props: {sources: {}, activeSource: {default: null}},
    setup(e) {
      let {site: t} = A();
      return (n, i) => (
        c(),
        T(
          y,
          null,
          [
            C(`craft-nav-list`, null, [
              (c(!0),
              T(
                y,
                null,
                r(
                  e.sources,
                  (n, r) => (
                    c(),
                    T(
                      y,
                      {key: n.key},
                      [
                        n.type === `heading`
                          ? (c(), T(y, {key: 0}, [w(S(n.heading), 1)], 64))
                          : (c(),
                            E(
                              v(b),
                              {
                                key: 1,
                                as: `craft-nav-item`,
                                href: v(z)(
                                  {page: `entries`},
                                  {query: {source: n.key, site: v(t)?.handle}}
                                ),
                                'preserve-state': ``,
                                active: n.key === e.activeSource,
                              },
                              {default: d(() => [w(S(n.label), 1)]), _: 2},
                              1032,
                              [`href`, `active`]
                            )),
                      ],
                      64
                    )
                  )
                ),
                128
              )),
            ]),
            (i[0] ||= C(`ul`, null, null, -1)),
          ],
          64
        )
      );
    },
  }),
  V = [`.modelValue`, `has-feedback-for`],
  H = [`.choiceValue`],
  te = {slot: `feedback`},
  U = {key: 0, class: `error-list`},
  W = i({
    name: `CraftSelectRich`,
    __name: `CraftSelectRich`,
    props: o({error: {}, options: {}}, {modelValue: {}, modelModifiers: {}}),
    emits: [`update:modelValue`],
    setup(e) {
      let t = a(e, `modelValue`);
      return (n, i) => (
        c(),
        T(
          `craft-select-rich`,
          {
            '.modelValue': t.value,
            onModelValueChanged: (i[0] ||= (e) =>
              (t.value = e.target?.modelValue)),
            'has-feedback-for': e.error ? `error` : ``,
          },
          [
            (c(!0),
            T(
              y,
              null,
              r(
                e.options,
                (e) => (
                  c(),
                  T(
                    `craft-option`,
                    {key: e.value, '.choiceValue': String(e.value)},
                    [
                      u(n.$slots, `option`, {option: e}, () => [
                        w(S(e.label), 1),
                      ]),
                    ],
                    40,
                    H
                  )
                )
              ),
              128
            )),
            C(`div`, te, [
              e.error
                ? (c(), T(`ul`, U, [C(`li`, null, S(e.error), 1)]))
                : m(``, !0),
            ]),
          ],
          40,
          V
        )
      );
    },
  }),
  G = [`variant`],
  K = i({
    __name: `ElementStatus`,
    props: {label: {}, value: {}, mode: {default: `inline`}, color: {}},
    setup(e) {
      let t = e,
        r = p(() => {
          if (t.color || t.color?.value) return `custom`;
          switch (t.value) {
            case `live`:
            case `on`:
            case `success`:
            case `enabled`:
              return `success`;
            case `off`:
            case `suspended`:
            case `expired`:
            case `danger`:
            case `red`:
              return `danger`;
            case `pending`:
            case `warning`:
              return `warning`;
            case `info`:
              return `info`;
            case `disabled`:
              return `empty`;
            default:
              return `custom`;
          }
        }),
        i = p(() =>
          t.value
            ? {}
            : {
                '--background': `transparent linear-gradient(60deg, #184cef, #e5422b) border-box`,
              }
        ),
        a = p(() => t.label ?? n(t.value));
      return (t, n) => (
        c(),
        T(
          `div`,
          {
            class: s({
              'inline-flex border': !0,
              'gap-2 border-transparent': e.mode === `inline`,
              [`bg-${r.value}-fill-quiet border-${r.value}-border-quiet`]:
                e.mode === `badge`,
              'gap-1 px-1.5 py-0.5 rounded-full text-xs': e.mode === `badge`,
            }),
          },
          [
            C(
              `craft-indicator`,
              {variant: r.value, style: g(i.value)},
              null,
              12,
              G
            ),
            w(` ` + S(a.value), 1),
          ],
          2
        )
      );
    },
  });
function q(e) {
  return `Craft-${Craft.systemUid}.${e}`;
}
function J(e, t, n = localStorage, r) {
  return P(q(e), t, n, r);
}
function Y(e, t, n) {
  return J(e, t, localStorage, n);
}
var X = {'aria-labelledby': `source-heading`},
  Z = {id: `source-heading`, class: `sr-only`},
  Q = {class: `flex gap-2 items-center`},
  ne = {
    type: `button`,
    slot: `suffix`,
    icon: ``,
    size: `small`,
    appearance: `plain`,
  },
  re = [`label`],
  ie = [`appearance`, `icon`, `aria-label`, `active`, `value`],
  ae = [`appearance`],
  oe = {slot: `content`},
  $ = {class: `p-2`},
  se = {class: `flex items-end gap-2`},
  ce = [`appearance`, `active`],
  le = [`appearance`, `active`],
  ue = {class: `p-2`},
  de = [`loading`],
  fe = i({
    __name: `Index`,
    props: {
      elementType: {},
      elementDisplayName: {},
      elementPluralDisplayName: {},
      context: {default: `index`},
      canHaveDrafts: {type: Boolean, default: !1},
      criteria: {default: Craft.defaultIndexCriteria},
      page: {default: null},
      sources: {},
      source: {},
      contentHtml: {},
      search: {},
      status: {},
      viewMode: {},
      statusOptions: {},
      sectionHandle: {},
      viewState: {},
      elements: {},
      tableColumns: {},
      viewModes: {},
      baseSortOptions: {},
      pagination: {},
      sort: {},
    },
    setup(n) {
      let i = n,
        a = {
          inlineEditing: !1,
          mode: `table`,
          tableColumns: [`title`, `dateCreated`],
          nestedInputNamespace: null,
          showHeaderColumn: !0,
          order: `dateCreated`,
          sort: `desc`,
          static: !1,
          ...i.viewState,
        },
        o = Y(`elementindex.${i.elementType}.${i.context}`, a),
        s = x({
          search: i.search ?? ``,
          status: i.status,
          viewMode: o.value.mode ?? i.viewMode ?? `table`,
        });
      function u() {
        s.submit(z({page: i.page, sectionHandle: i.sectionHandle}));
      }
      let m = F(),
        g = p(() => [
          m.html(`title`, {header: e(`Entry`)}),
          ...Object.entries(i.tableColumns)
            .filter(([e]) => o.value.tableColumns?.includes(e))
            .map(([e, t]) => m.html(e, {header: t.label})),
        ]),
        b = p(() => [
          {label: e(`Entry`), value: `title`, disabled: !0},
          ...Object.entries(i.tableColumns).map(([e, t]) => ({
            label: t.label,
            value: e,
          })),
        ]),
        w = h({}),
        A = ee({
          get data() {
            return i.elements;
          },
          get columns() {
            return g.value;
          },
          state: {
            get columnOrder() {
              return [`title`, `dateCreated`];
            },
            get columnVisibility() {
              return w.value;
            },
          },
          getCoreRowModel: D(),
        });
      return (i, a) => (
        c(),
        E(R, null, {
          'interior-nav': d(() => [
            C(`nav`, X, [
              C(`h2`, Z, S(v(e)(`Sources`)), 1),
              l(
                B,
                {sources: n.sources, 'active-source': n.source?.key},
                null,
                8,
                [`sources`, `active-source`]
              ),
            ]),
            (a[7] ||= C(`div`, {id: `source-actions`}, null, -1)),
          ]),
          default: d(() => [
            l(
              O,
              {table: v(A)},
              {
                'table-header': d(() => [
                  C(
                    `form`,
                    {onSubmit: u, class: `w-full`},
                    [
                      C(`div`, Q, [
                        C(`div`, null, [
                          l(
                            W,
                            {
                              modelValue: v(s).status,
                              'onUpdate:modelValue': (a[0] ||= (e) =>
                                (v(s).status = e)),
                              options: n.statusOptions,
                              label: v(e)(`Status`),
                              'label-sr-only': ``,
                            },
                            {
                              option: d(({option: e}) => [
                                l(
                                  K,
                                  {label: e.label, value: e.value},
                                  null,
                                  8,
                                  [`label`, `value`]
                                ),
                              ]),
                              _: 1,
                            },
                            8,
                            [`modelValue`, `options`, `label`]
                          ),
                        ]),
                        l(
                          L,
                          {
                            class: `flex-1`,
                            name: `search`,
                            label: v(e)(`Search term`),
                            modelValue: v(s).search,
                            'onUpdate:modelValue': (a[1] ||= (e) =>
                              (v(s).search = e)),
                            'label-sr-only': ``,
                          },
                          {
                            default: d(() => [
                              C(`craft-button`, ne, [
                                C(
                                  `craft-icon`,
                                  {
                                    name: `filter`,
                                    label: v(e)(`Filter results`),
                                  },
                                  null,
                                  8,
                                  re
                                ),
                              ]),
                            ]),
                            _: 1,
                          },
                          8,
                          [`label`, `modelValue`]
                        ),
                        f(
                          C(
                            `craft-button-group`,
                            {
                              'onUpdate:modelValue': (a[2] ||= (e) =>
                                (v(s).viewMode = e)),
                              name: `viewMode`,
                              onChange: (a[3] ||= (e) =>
                                (v(s).viewMode = e.detail.value)),
                            },
                            [
                              (c(!0),
                              T(
                                y,
                                null,
                                r(
                                  n.viewModes,
                                  (e) => (
                                    c(),
                                    T(
                                      `craft-button`,
                                      {
                                        key: e.type,
                                        type: `button`,
                                        appearance: v(t).Fill,
                                        icon: e.icon,
                                        'aria-label': e.title,
                                        active: v(s).viewMode === e.type,
                                        value: e.type,
                                      },
                                      null,
                                      8,
                                      ie
                                    )
                                  )
                                ),
                                128
                              )),
                            ],
                            544
                          ),
                          [[_, v(s).viewMode]]
                        ),
                        C(`craft-action-menu`, null, [
                          C(
                            `craft-button`,
                            {
                              type: `button`,
                              slot: `invoker`,
                              icon: `sliders`,
                              appearance: v(t).Fill,
                            },
                            S(v(e)(`View`)),
                            9,
                            ae
                          ),
                          C(`div`, oe, [
                            C(`div`, $, [
                              C(`div`, se, [
                                l(
                                  k,
                                  {
                                    label: v(e)(`Sort by`),
                                    name: `viewState[order]`,
                                    modelValue: v(o).order,
                                    'onUpdate:modelValue': (a[4] ||= (e) =>
                                      (v(o).order = e)),
                                    options: b.value,
                                  },
                                  null,
                                  8,
                                  [`label`, `modelValue`, `options`]
                                ),
                                f(
                                  C(
                                    `craft-button-group`,
                                    {
                                      name: `viewState[sort]`,
                                      'onUpdate:modelValue': (a[5] ||= (e) =>
                                        (v(o).sort = e)),
                                    },
                                    [
                                      C(
                                        `craft-button`,
                                        {
                                          type: `button`,
                                          icon: `asc`,
                                          value: `asc`,
                                          'aria-label': `t('Sort ascending')`,
                                          appearance: v(t).Fill,
                                          active: v(o).sort === `asc`,
                                        },
                                        null,
                                        8,
                                        ce
                                      ),
                                      C(
                                        `craft-button`,
                                        {
                                          type: `button`,
                                          icon: `desc`,
                                          'aria-label': `t('Sort descending')`,
                                          value: `desc`,
                                          appearance: v(t).Fill,
                                          active: v(o).sort === `desc`,
                                        },
                                        null,
                                        8,
                                        le
                                      ),
                                    ],
                                    512
                                  ),
                                  [[_, v(o).sort]]
                                ),
                              ]),
                            ]),
                            C(`div`, ue, [
                              l(
                                I,
                                {
                                  label: v(e)(`Table Columns`),
                                  name: `viewState[tableColumns]`,
                                  modelValue: v(o).tableColumns,
                                  'onUpdate:modelValue': (a[6] ||= (e) =>
                                    (v(o).tableColumns = e)),
                                  options: b.value,
                                  'allow-select-all': ``,
                                },
                                null,
                                8,
                                [`label`, `modelValue`, `options`]
                              ),
                            ]),
                            (a[8] ||= C(`div`, {class: `p-2`}, null, -1)),
                          ]),
                        ]),
                        C(`div`, null, [
                          C(
                            `craft-button`,
                            {type: `submit`, loading: v(s).processing},
                            S(v(e)(`Update`)),
                            9,
                            de
                          ),
                        ]),
                      ]),
                    ],
                    32
                  ),
                ]),
                _: 1,
              },
              8,
              [`table`]
            ),
          ]),
          _: 1,
        })
      );
    },
  });
export {fe as default};

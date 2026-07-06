import {
  E as e,
  G as t,
  M as n,
  R as r,
  V as i,
  _ as a,
  b as o,
  ct as s,
  k as c,
  tt as l,
  v as u,
  x as d,
} from './_plugin-vue_export-helper-g2tzphu6.js';
import {a as f, i as p, o as m} from './AdminTable-32qWVDq-.js';
import {i as h} from './wayfinder-V597ZF_3.js';
import {t as g} from './InputCombobox-BGX5z3XZ.js';
var _ = [`.checked`, `has-feedback-for`],
  v = {slot: `feedback`},
  y = {key: 0, class: `error-list`},
  b = e({
    name: `CraftSwitch`,
    __name: `CraftSwitch`,
    props: n({error: {}}, {modelValue: {type: Boolean}, modelModifiers: {}}),
    emits: [`update:modelValue`],
    setup(e) {
      let n = t(e, `modelValue`);
      return (t, a) => (
        r(),
        d(
          `craft-switch`,
          {
            '.checked': n.value,
            onModelValueChanged: (a[0] ||= (e) =>
              (n.value = e.target?.checked)),
            'has-feedback-for': e.error ? `error` : ``,
          },
          [
            i(t.$slots, `default`),
            u(`div`, v, [
              e.error
                ? (r(), d(`ul`, y, [u(`li`, null, l(e.error), 1)]))
                : o(``, !0),
            ]),
          ],
          40,
          _
        )
      );
    },
  });
function x(e) {
  return typeof e == `function` ? e() : e;
}
function S(e) {
  let t = e.key ?? `id`,
    {readOnly: n} = h();
  function r(e) {
    return !Array.isArray(e);
  }
  let i = a(() => {
    let n = e.data();
    return r(n) ? Object.entries(n).map(([e, n]) => ({...n, [t]: e})) : n;
  });
  function o(n, a, o) {
    let s = r(e.data()),
      c = i.value.map((e, t) => (t === n.index ? {...e, [a]: o} : e));
    if (s) {
      let n = {};
      for (let e of c) {
        let {[t]: r, ...i} = e;
        n[r] = i;
      }
      e.onChange(n);
    } else e.onChange(c);
  }
  function l(e, t) {
    let r = e;
    return (
      typeof e == `boolean` && (r = e),
      typeof e == `function` && (r = e(t)),
      n ? !0 : !!r
    );
  }
  function u(n, r) {
    return ({row: i, column: a, getValue: s}) =>
      c(`textarea`, {
        rows: 1,
        type: n,
        value: s(),
        class: `cp-table-input cp-table-input--text ${r?.class ?? ``}`,
        autocomplete: `off`,
        autocorrect: `off`,
        autocapitalize: `off`,
        spellcheck: !1,
        placeholder: r?.placeholder,
        disabled: l(r?.disabled, i),
        name: r?.name
          ? r.name(i, a.id)
          : e.name
            ? `${e.name}[${i.original[t]}][${a.id}]`
            : void 0,
        'aria-labelledby': `header-${a.id}`,
        onInput: (e) => {
          typeof r?.onInput == `function` && r.onInput(e);
        },
        onChange: (e) => {
          let t = e.target.value;
          (typeof r?.onChange == `function` &&
            r.onChange(t, {row: i, column: a}),
            o(i, a.id, t));
        },
      });
  }
  function d(e) {
    return ({row: t, column: n}) =>
      c(b, {
        modelValue: t.original[n.id],
        'label-sr-only': !0,
        size: e?.switchSize ?? `small`,
        label: e?.label,
        class: `cp-table-input cp-table-input--switch ${e?.class ?? ``}`,
        'aria-labelledby': e?.ariaLabelledBy ?? `header-${n.id}`,
        disabled: l(e?.disabled, t),
        'onUpdate:modelValue': (r) => {
          (typeof e?.onUpdate == `function` && e.onUpdate(r),
            o(t, n.id, r ?? !1));
        },
      });
  }
  function _(e) {
    return ({row: t, column: n}) =>
      c(`input`, {
        type: `checkbox`,
        checked: t.original[n.id],
        class: `cp-table-input cp-table-input--switch ${e?.class ?? ``}`,
        'aria-labelledby': e?.ariaLabelledBy ?? `header-${n.id}`,
        disabled: l(e?.disabled, t),
        onChange: (r) => {
          let i = r.target.checked;
          (typeof e?.onChange == `function` &&
            e.onChange(i, {row: t, column: n}),
            o(t, n.id, i ?? !1));
        },
      });
  }
  function v(e) {
    return ({row: t, column: n}) => {
      let r =
        typeof e?.options == `function` ? e.options(t) : x(e?.options ?? []);
      return c(g, {
        modelValue: t.original[n.id],
        options: r,
        class: `cp-table-input cp-table-input--autocomplete ${e?.class ?? ``}`,
        placeholder: e?.placeholder,
        label: e?.label ?? n.id,
        ...(e?.requireOptionMatch !== void 0 && {
          requireOptionMatch: e.requireOptionMatch,
        }),
        ...(e?.transformModelValue !== void 0 && {
          transformModelValue: e.transformModelValue,
        }),
        disabled: l(e?.disabled, t),
        'onUpdate:modelValue': (r) => {
          let i = String(r);
          (typeof e?.onChange == `function` &&
            e.onChange(i, {row: t, column: n}),
            o(t, n.id, i));
        },
      });
    };
  }
  let y = f();
  function S(e) {
    let t = {};
    return (
      e?.header !== void 0 && (t.header = e.header),
      e?.size !== void 0 && (t.size = e.size),
      e?.meta !== void 0 && (t.meta = e.meta),
      t
    );
  }
  let C = {
      accessor: y.accessor,
      display: y.display,
      group: y.group,
      text(e, t = {}) {
        let {
            inputType: n,
            class: r,
            placeholder: i,
            name: a,
            onInput: o,
            onChange: s,
            ...c
          } = t,
          l = S(c);
        return (
          (l.cell = u(n ?? `text`, {
            class: r,
            placeholder: i,
            disabled: c.disabled,
            name: a,
            onInput: o,
            onChange: s,
          })),
          y.accessor(e, l)
        );
      },
      lightswitch(e, t = {}) {
        let {label: n, ariaLabelledBy: r, switchSize: i, onUpdate: a, ...o} = t,
          s = S(o);
        return (
          (s.cell = d({
            disabled: o.disabled,
            label: n,
            ariaLabelledBy: r,
            switchSize: i,
            onUpdate: a,
          })),
          y.accessor(e, s)
        );
      },
      checkbox(e, t = {}) {
        let {ariaLabelledBy: n, onChange: r, ...i} = t,
          a = S(i);
        return (
          (a.cell = _({disabled: i.disabled, ariaLabelledBy: n, onChange: r})),
          y.accessor(e, a)
        );
      },
      autocomplete(e, t = {}) {
        let {
            options: n,
            requireOptionMatch: r,
            transformModelValue: i,
            onChange: a,
            ...o
          } = t,
          s = S(o);
        return (
          (s.cell = v({
            disabled: o.disabled,
            options: n,
            requireOptionMatch: r,
            transformModelValue: i,
            onChange: a,
            class: t.class ?? ``,
            placeholder: t.placeholder ?? ``,
          })),
          y.accessor(e, s)
        );
      },
    },
    w = s(e.columns({columnHelper: C})),
    T = {
      get data() {
        return i.value;
      },
      get columns() {
        return w.value;
      },
      enableSorting: !1,
      getCoreRowModel: m(),
      defaultColumn: {size: `auto`},
    };
  return (
    e.columnVisibility &&
      (T.state = {
        get columnVisibility() {
          return e.columnVisibility();
        },
      }),
    {table: p(T)}
  );
}
export {b as n, S as t};

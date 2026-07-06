import {
  A as e,
  D as t,
  I as n,
  J as r,
  P as i,
  Y as a,
  _ as o,
  ct as s,
  ft as c,
  ht as l,
  j as u,
  lt as d,
  nt as f,
  pt as p,
  rt as m,
  st as h,
  ut as g,
} from './_plugin-vue_export-helper-g2tzphu6.js';
var _ = new WeakMap(),
  v = (...n) => {
    let r = n[0],
      i = t()?.proxy ?? m();
    if (i == null && !e()) throw Error(`injectLocal must be called in setup`);
    return i && _.has(i) && r in _.get(i) ? _.get(i)[r] : u(...n);
  },
  y = typeof window < `u` && typeof document < `u`;
typeof WorkerGlobalScope < `u` && globalThis instanceof WorkerGlobalScope;
var b = Object.prototype.toString,
  x = (e) => b.call(e) === `[object Object]`,
  S = () => {};
function C(...e) {
  if (e.length !== 1) return c(...e);
  let t = e[0];
  return typeof t == `function` ? h(f(() => ({get: t, set: S}))) : s(t);
}
function w(e, t) {
  function n(...n) {
    return new Promise((r, i) => {
      Promise.resolve(
        e(() => t.apply(this, n), {fn: t, thisArg: this, args: n})
      )
        .then(r)
        .catch(i);
    });
  }
  return n;
}
var T = (e) => e();
function E(e, t = {}) {
  let n,
    r,
    i = S,
    a = (e) => {
      (clearTimeout(e), i(), (i = S));
    },
    o;
  return (s) => {
    let c = p(e),
      l = p(t.maxWait);
    return (
      n && a(n),
      c <= 0 || (l !== void 0 && l <= 0)
        ? ((r &&= (a(r), void 0)), Promise.resolve(s()))
        : new Promise((e, u) => {
            ((i = t.rejectOnCancel ? u : e),
              (o = s),
              l &&
                !r &&
                (r = setTimeout(() => {
                  (n && a(n), (r = void 0), e(o()));
                }, l)),
              (n = setTimeout(() => {
                (r && a(r), (r = void 0), e(s()));
              }, c)));
          })
    );
  };
}
function D(e = T, t = {}) {
  let {initialState: n = `active`} = t,
    r = C(n === `active`);
  function i() {
    r.value = !1;
  }
  function a() {
    r.value = !0;
  }
  return {
    isActive: d(r),
    pause: i,
    resume: a,
    eventFilter: (...t) => {
      r.value && e(...t);
    },
  };
}
function O(e) {
  return e.endsWith(`rem`) ? Number.parseFloat(e) * 16 : Number.parseFloat(e);
}
function k(e) {
  return Array.isArray(e) ? e : [e];
}
function A(e) {
  return e || t();
}
function j(e, t = 200, n = {}) {
  return w(E(t, n), e);
}
function M(e, t, n = {}) {
  let {eventFilter: i = T, ...a} = n;
  return r(e, w(i, t), a);
}
function N(e, t, n = {}) {
  let {eventFilter: r, initialState: i = `active`, ...a} = n,
    {
      eventFilter: o,
      pause: s,
      resume: c,
      isActive: l,
    } = D(r, {initialState: i});
  return {
    stop: M(e, t, {...a, eventFilter: o}),
    pause: s,
    resume: c,
    isActive: l,
  };
}
function P(e, t = !0, r) {
  A(r) ? n(e, r) : t ? e() : i(e);
}
function F(e, t, n) {
  return r(e, t, {...n, immediate: !0});
}
var I = y ? window : void 0;
(y && window.document, y && window.navigator, y && window.location);
function L(e) {
  let t = p(e);
  return t?.$el ?? t;
}
function R(...e) {
  let t = (e, t, n, r) => (
      e.addEventListener(t, n, r),
      () => e.removeEventListener(t, n, r)
    ),
    n = o(() => {
      let t = k(p(e[0])).filter((e) => e != null);
      return t.every((e) => typeof e != `string`) ? t : void 0;
    });
  return F(
    () => [
      n.value?.map((e) => L(e)) ?? [I].filter((e) => e != null),
      k(p(n.value ? e[1] : e[0])),
      k(l(n.value ? e[2] : e[1])),
      p(n.value ? e[3] : e[2]),
    ],
    ([e, n, r, i], a, o) => {
      if (!e?.length || !n?.length || !r?.length) return;
      let s = x(i) ? {...i} : i,
        c = e.flatMap((e) => n.flatMap((n) => r.map((r) => t(e, n, r, s))));
      o(() => {
        c.forEach((e) => e());
      });
    },
    {flush: `post`}
  );
}
function z() {
  let e = g(!1),
    r = t();
  return (
    r &&
      n(() => {
        e.value = !0;
      }, r),
    e
  );
}
function B(e) {
  let t = z();
  return o(() => (t.value, !!e()));
}
function V(e) {
  return typeof e == `function`
    ? e
    : typeof e == `string`
      ? (t) => t.key === e
      : Array.isArray(e)
        ? (t) => e.includes(t.key)
        : () => !0;
}
function H(...e) {
  let t,
    n,
    r = {};
  e.length === 3
    ? ((t = e[0]), (n = e[1]), (r = e[2]))
    : e.length === 2
      ? typeof e[1] == `object`
        ? ((t = !0), (n = e[0]), (r = e[1]))
        : ((t = e[0]), (n = e[1]))
      : ((t = !0), (n = e[0]));
  let {
      target: i = I,
      eventName: a = `keydown`,
      passive: o = !1,
      dedupe: s = !1,
    } = r,
    c = V(t);
  return R(
    i,
    a,
    (e) => {
      (e.repeat && p(s)) || (c(e) && n(e));
    },
    o
  );
}
var U = Symbol(`vueuse-ssr-width`);
function W() {
  let t = e() ? v(U, null) : null;
  return typeof t == `number` ? t : void 0;
}
function G(e, t = {}) {
  let {window: n = I, ssrWidth: r = W()} = t,
    i = B(() => n && `matchMedia` in n && typeof n.matchMedia == `function`),
    s = g(typeof r == `number`),
    c = g(),
    l = g(!1);
  return (
    a(() => {
      if (s.value) {
        ((s.value = !i.value),
          (l.value = p(e)
            .split(`,`)
            .some((e) => {
              let t = e.includes(`not all`),
                n = e.match(/\(\s*min-width:\s*(-?\d+(?:\.\d*)?[a-z]+\s*)\)/),
                i = e.match(/\(\s*max-width:\s*(-?\d+(?:\.\d*)?[a-z]+\s*)\)/),
                a = !!(n || i);
              return (
                n && a && (a = r >= O(n[1])),
                i && a && (a = r <= O(i[1])),
                t ? !a : a
              );
            })));
        return;
      }
      i.value && ((c.value = n.matchMedia(p(e))), (l.value = c.value.matches));
    }),
    R(
      c,
      `change`,
      (e) => {
        l.value = e.matches;
      },
      {passive: !0}
    ),
    o(() => l.value)
  );
}
var K =
    typeof globalThis < `u`
      ? globalThis
      : typeof window < `u`
        ? window
        : typeof global < `u`
          ? global
          : typeof self < `u`
            ? self
            : {},
  q = `__vueuse_ssr_handlers__`,
  J = Y();
function Y() {
  return (q in K || (K[q] = K[q] || {}), K[q]);
}
function X(e, t) {
  return J[e] || t;
}
function Z(e) {
  return e == null
    ? `any`
    : e instanceof Set
      ? `set`
      : e instanceof Map
        ? `map`
        : e instanceof Date
          ? `date`
          : typeof e == `boolean`
            ? `boolean`
            : typeof e == `string`
              ? `string`
              : typeof e == `object`
                ? `object`
                : Number.isNaN(e)
                  ? `any`
                  : `number`;
}
var Q = {
    boolean: {read: (e) => e === `true`, write: (e) => String(e)},
    object: {read: (e) => JSON.parse(e), write: (e) => JSON.stringify(e)},
    number: {read: (e) => Number.parseFloat(e), write: (e) => String(e)},
    any: {read: (e) => e, write: (e) => String(e)},
    string: {read: (e) => e, write: (e) => String(e)},
    map: {
      read: (e) => new Map(JSON.parse(e)),
      write: (e) => JSON.stringify(Array.from(e.entries())),
    },
    set: {
      read: (e) => new Set(JSON.parse(e)),
      write: (e) => JSON.stringify(Array.from(e)),
    },
    date: {read: (e) => new Date(e), write: (e) => e.toISOString()},
  },
  $ = `vueuse-storage`;
function ee(e, t, n, a = {}) {
  let {
      flush: c = `pre`,
      deep: l = !0,
      listenToStorageChanges: u = !0,
      writeDefaults: d = !0,
      mergeDefaults: f = !1,
      shallow: m,
      window: h = I,
      eventFilter: _,
      onError: v = (e) => {
        console.error(e);
      },
      initOnMounted: y,
    } = a,
    b = (m ? g : s)(typeof t == `function` ? t() : t),
    x = o(() => p(e));
  if (!n)
    try {
      n = X(`getDefaultStorage`, () => I?.localStorage)();
    } catch (e) {
      v(e);
    }
  if (!n) return b;
  let S = p(t),
    C = Z(S),
    w = a.serializer ?? Q[C],
    {pause: T, resume: E} = N(b, (e) => k(e), {
      flush: c,
      deep: l,
      eventFilter: _,
    });
  r(x, () => j(), {flush: c});
  let D = !1;
  (h &&
    u &&
    (n instanceof Storage
      ? R(
          h,
          `storage`,
          (e) => {
            (y && !D) || j(e);
          },
          {passive: !0}
        )
      : R(h, $, (e) => {
          (y && !D) || M(e);
        })),
    y
      ? P(() => {
          ((D = !0), j());
        })
      : j());
  function O(e, t) {
    if (h) {
      let r = {key: x.value, oldValue: e, newValue: t, storageArea: n};
      h.dispatchEvent(
        n instanceof Storage
          ? new StorageEvent(`storage`, r)
          : new CustomEvent($, {detail: r})
      );
    }
  }
  function k(e) {
    try {
      let t = n.getItem(x.value);
      if (e == null) (O(t, null), n.removeItem(x.value));
      else {
        let r = w.write(e);
        t !== r && (n.setItem(x.value, r), O(t, r));
      }
    } catch (e) {
      v(e);
    }
  }
  function A(e) {
    let t = e ? e.newValue : n.getItem(x.value);
    if (t == null) return (d && S != null && n.setItem(x.value, w.write(S)), S);
    if (!e && f) {
      let e = w.read(t);
      return typeof f == `function`
        ? f(e, S)
        : C === `object` && !Array.isArray(e)
          ? {...S, ...e}
          : e;
    } else if (typeof t != `string`) return t;
    else return w.read(t);
  }
  function j(e) {
    if (!(e && e.storageArea !== n)) {
      if (e && e.key == null) {
        b.value = S;
        return;
      }
      if (!(e && e.key !== x.value)) {
        T();
        try {
          let t = w.write(b.value);
          (e === void 0 || e?.newValue !== t) && (b.value = A(e));
        } catch (e) {
          v(e);
        } finally {
          e ? i(E) : E();
        }
      }
    }
  }
  function M(e) {
    j(e.detail);
  }
  return b;
}
function te(e, t) {
  let n = s(e),
    r = o(() => (Array.isArray(n.value) ? n.value : Object.keys(n.value))),
    i = s(r.value.indexOf(t ?? r.value[0])),
    a = o(() => f(i.value)),
    c = o(() => i.value === 0),
    l = o(() => i.value === r.value.length - 1),
    u = o(() => r.value[i.value + 1]),
    d = o(() => r.value[i.value - 1]);
  function f(e) {
    return Array.isArray(n.value) ? n.value[e] : n.value[r.value[e]];
  }
  function p(e) {
    if (r.value.includes(e)) return f(r.value.indexOf(e));
  }
  function m(e) {
    r.value.includes(e) && (i.value = r.value.indexOf(e));
  }
  function h() {
    l.value || i.value++;
  }
  function g() {
    c.value || i.value--;
  }
  function _(e) {
    S(e) && m(e);
  }
  function v(e) {
    return r.value.indexOf(e) === i.value + 1;
  }
  function y(e) {
    return r.value.indexOf(e) === i.value - 1;
  }
  function b(e) {
    return r.value.indexOf(e) === i.value;
  }
  function x(e) {
    return i.value < r.value.indexOf(e);
  }
  function S(e) {
    return i.value > r.value.indexOf(e);
  }
  return {
    steps: n,
    stepNames: r,
    index: i,
    current: a,
    next: u,
    previous: d,
    isFirst: c,
    isLast: l,
    at: f,
    get: p,
    goTo: m,
    goToNext: h,
    goToPrevious: g,
    goBackTo: _,
    isNext: v,
    isPrevious: y,
    isCurrent: b,
    isBefore: x,
    isAfter: S,
  };
}
export {ee as a, te as i, R as n, j as o, G as r, H as t};

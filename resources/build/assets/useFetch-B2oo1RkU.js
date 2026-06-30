import {h as e, l as t} from './cp-npqTfNqh.js';
import {
  J as n,
  _ as r,
  ct as i,
  ht as a,
} from './_plugin-vue_export-helper-g2tzphu6.js';
import {a as o} from './wayfinder-V597ZF_3.js';
function s(t, o = {}) {
  let {
      immediate: s = !0,
      refetch: c = !0,
      params: l,
      enabled: u = !0,
      debounce: d = 0,
      transform: f = (e) => e,
      onSuccess: p,
      onError: m,
      initialData: h = null,
      method: g = `get`,
      axiosInstance: _ = e,
      ...v
    } = o,
    y = i(h),
    b = i(`idle`),
    x = i(null),
    S = r(() => b.value === `loading`),
    C = r(() => b.value === `success`),
    w = r(() => b.value === `error`),
    T = r(() => a(t)),
    E = r(() => a(u)),
    D = r(() => a(l)),
    O = r(() => a(g.toLowerCase())),
    k = null,
    A = null,
    j = async (t = {}) => {
      if (!(!T.value || !E.value)) {
        (k && k.cancel(`Request superseded by new request`),
          (k = e.CancelToken.source()),
          (b.value = `loading`),
          (x.value = null));
        try {
          let e = await _({
              method: O.value,
              url: T.value,
              params: D.value,
              cancelToken: k.token,
              data: O.value === `get` ? void 0 : t,
              ...v,
            }),
            n = f(e.data);
          ((b.value = `success`), (y.value = n), p?.(n, e));
        } catch (t) {
          e.isCancel(t)
            ? (b.value = `aborted`)
            : e.isAxiosError(t)
              ? (console.error(`Axios error:`, t.response?.data),
                (b.value = `error`),
                (x.value = t.response?.data || t.message || `Unknown error`),
                m?.(t))
              : t instanceof Error
                ? (console.error(`Unknown error:`, t.message),
                  (b.value = `error`),
                  (x.value = t.message || `Unknown error`))
                : (console.error(`Unknown error:`, t),
                  (b.value = `error`),
                  (x.value = `Unknown error`));
        }
      }
    },
    M = () => {
      (A && clearTimeout(A),
        d > 0
          ? (A = setTimeout(() => {
              j();
            }, d))
          : j());
    };
  return (
    c
      ? n(
          [T, D, E],
          () => {
            E.value
              ? M()
              : (A && clearTimeout(A), k && k.cancel(`Request disabled`));
          },
          {immediate: s, deep: !0}
        )
      : s && E.value && M(),
    {
      data: y,
      error: x,
      state: b,
      isLoading: S,
      isSuccess: C,
      isError: w,
      execute: j,
      refetch: () => j(),
      abort: () => {
        (A && clearTimeout(A), k && k.cancel(`Request cancelled by user`));
      },
    }
  );
}
function c(e, t = {}) {
  return s(e, {immediate: !1, ...t, method: `post`});
}
function l(e, t = {}) {
  let n = t.method ?? `POST`,
    {getActionUrl: i} = o();
  return s(
    r(() => i(a(e))),
    {immediate: !1, ...t, method: n}
  );
}
function u(e, n = {}) {
  let {getApiUrl: i} = o();
  return s(
    r(() => i(a(e))),
    {...n, axiosInstance: t}
  );
}
export {c as i, u as n, s as r, l as t};

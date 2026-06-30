import {
  E as e,
  Q as t,
  R as n,
  T as r,
  V as i,
  X as a,
  _ as o,
  b as s,
  m as c,
  t as l,
  u,
  v as d,
  x as f,
  y as p,
} from './_plugin-vue_export-helper-g2tzphu6.js';
import {t as m} from './dist-BSfJDNP3.js';
var h = {key: 0, class: `cp-modal`},
  g = l(
    e({
      __name: `Modal`,
      props: {
        isActive: {type: Boolean, default: !1},
        overlay: {type: Boolean, default: !0},
        width: {default: `md`},
      },
      emits: [`close`],
      setup(e, {emit: l}) {
        let g = l,
          _ = e;
        m(`Escape`, () => {
          g(`close`);
        });
        let v = o(() => `w-${_.width}`);
        return (o, l) => (
          n(),
          f(
            c,
            null,
            [
              r(
                u,
                {name: `body`},
                {
                  default: a(() => [
                    e.isActive
                      ? (n(),
                        f(`div`, h, [
                          d(
                            `div`,
                            {class: t({content: !0, [v.value]: !0})},
                            [i(o.$slots, `default`, {}, void 0, !0)],
                            2
                          ),
                        ]))
                      : s(``, !0),
                  ]),
                  _: 3,
                }
              ),
              e.overlay
                ? (n(),
                  p(
                    u,
                    {key: 0, name: `fade`},
                    {
                      default: a(() => [
                        e.isActive
                          ? (n(),
                            f(`div`, {
                              key: 0,
                              class: `cp-overlay`,
                              onClick: (l[0] ||= (e) => g(`close`)),
                            }))
                          : s(``, !0),
                      ]),
                      _: 1,
                    }
                  ))
                : s(``, !0),
            ],
            64
          )
        );
      },
    }),
    [[`__scopeId`, `data-v-03a670fc`]]
  );
export {g as t};

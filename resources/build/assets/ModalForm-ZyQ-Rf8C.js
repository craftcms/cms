import {l as e} from './nav-item-CyC1px5v-DZDpUldI.js';
import './cp-npqTfNqh.js';
import {
  B as t,
  E as n,
  R as r,
  S as i,
  T as a,
  V as o,
  X as s,
  p as c,
  tt as l,
  v as u,
  y as d,
} from './_plugin-vue_export-helper-g2tzphu6.js';
import {t as f} from './Pane-CJi1MPPH.js';
import {t as p} from './Modal-D6UmxwmM.js';
var m = [`loading`],
  h = n({
    __name: `ModalForm`,
    props: {
      isActive: {type: Boolean},
      overlay: {type: Boolean, default: !0},
      width: {},
      loading: {type: Boolean, default: !1},
      title: {default: void 0},
      resetLabel: {default: e(`Cancel`)},
      submitLabel: {default: e(`Save`)},
    },
    emits: [`close`, `submit`],
    setup(e, {emit: n}) {
      let h = n;
      function g() {
        h(`submit`);
      }
      return (n, _) => (
        r(),
        d(
          p,
          {
            'is-active': e.isActive,
            overlay: e.overlay,
            onClose: (_[1] ||= (e) => h(`close`)),
            width: e.width,
          },
          {
            default: s(() => [
              u(
                `form`,
                {onSubmit: c(g, [`prevent`])},
                [
                  a(
                    f,
                    {title: e.title},
                    i(
                      {
                        'secondary-action': s(() => [
                          u(
                            `craft-button`,
                            {
                              type: `reset`,
                              onClick: (_[0] ||= (e) => h(`close`)),
                              appearance: `plain`,
                            },
                            l(e.resetLabel),
                            1
                          ),
                        ]),
                        'primary-action': s(() => [
                          u(
                            `craft-button`,
                            {
                              type: `submit`,
                              variant: `accent`,
                              loading: e.loading,
                            },
                            l(e.submitLabel),
                            9,
                            m
                          ),
                        ]),
                        default: s(() => [o(n.$slots, `default`)]),
                        _: 2,
                      },
                      [
                        t(n.$slots, (e, t) => ({
                          name: t,
                          fn: s(() => [o(n.$slots, t)]),
                        })),
                      ]
                    ),
                    1032,
                    [`title`]
                  ),
                ],
                32
              ),
            ]),
            _: 3,
          },
          8,
          [`is-active`, `overlay`, `width`]
        )
      );
    },
  });
export {h as t};

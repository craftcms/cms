---
paths:
  - 'packages/craftcms-ui/**'
---

# Craftcms Ui

## Build the UI package before consumers
Build `@craftcms/ui` with `vp run build:ui` before building or checking the main Vite application after UI package changes. Run its tests with `vp run test:ui`.

## Don't comment CSS unless it's a hack
Leave CSS uncommented — `.css`/`.scss` files, Lit `css` templates and Vue `<style>` blocks. Only comment a genuine hack, or a workaround for platform or library behaviour a reader couldn't infer, and keep it to one line about the code, not the history behind it.

## Don't comment tests unless the behaviour is odd
Leave tests uncommented; the test name and assertions say what is being checked. Only comment when the behaviour under test is decidedly odd and a reader couldn't infer why the assertion holds, and keep it to one line about the code, not the bug or history that prompted the test.

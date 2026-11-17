#!/bin/bash
# Moves the system pointer to a point on the screen, as a real mouse move, so
# the page gets :hover and hover-triggered tooltips without the extension
# drawing its own cursor. screencapture -x leaves the system pointer out.
#
# usage: hover.sh <screen-x> <screen-y>
#   Coordinates are screen points: the page's own coordinates plus the page's
#   screen origin, which origin.sh prints. Move it off the target again, e.g.
#   to a corner of the screen, to end the hover.
set -euo pipefail

x=$1 y=$2

osascript -l JavaScript - "$x" "$y" <<'JXA'
ObjC.import('CoreGraphics');
function run(argv) {
  // Arrive from a few points away, so the target gets a fresh pointerenter.
  const target = {x: Number(argv[0]), y: Number(argv[1])};
  for (const p of [{x: target.x - 6, y: target.y - 6}, target]) {
    const event = $.CGEventCreateMouseEvent(null, $.kCGEventMouseMoved, p, $.kCGMouseButtonLeft);
    $.CGEventPost($.kCGHIDEventTap, event);
    delay(0.15);
  }
}
JXA

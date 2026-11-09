#!/bin/bash
# Captures a region of the screen showing the Chrome tab titled
# SCREENSHOT-TARGET, at the display's pixel density (2x on Retina). The tab is
# made its window's active tab, and the window brought to the front.
#
# usage: capture.sh <out.png> <screen-x> <screen-y> <width> <height>
#   Coordinates are screen points: the page's own coordinates plus the page's
#   screen origin, which origin.sh prints.
set -euo pipefail

out=$1 x=$2 y=$3 w=$4 h=$5
locked="The screen appears to be locked. Ask the person to unlock the Mac, then capture again."

if ioreg -n Root -d1 | grep -q '"CGSSessionScreenIsLocked"=Yes'; then
  echo "$locked" >&2
  exit 1
fi

"$(dirname "$0")/activate.sh"

if ! screencapture -x -R"$x,$y,$w,$h" "$out"; then
  echo "$locked If it isn't, the display may have changed: check the region with a full screencapture -x." >&2
  exit 1
fi
sips -g pixelWidth -g pixelHeight "$out" | tail -2 | tr '\n' ' '
echo "$out"

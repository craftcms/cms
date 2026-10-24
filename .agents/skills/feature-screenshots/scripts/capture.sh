#!/bin/bash
# Captures a region of the Chrome window whose active tab is titled
# SCREENSHOT-TARGET, at the display's pixel density (2x on Retina).
#
# usage: capture.sh <out.png> <screen-x> <screen-y> <width> <height>
#   Coordinates are screen points: the page's own coordinates plus the page's
#   screen origin, which the page reports as
#   [screenX, screenY + outerHeight - innerHeight].
set -euo pipefail

out=$1 x=$2 y=$3 w=$4 h=$5

osascript \
  -e 'tell application "Google Chrome"' \
  -e 'repeat with w in windows' \
  -e 'if title of active tab of w is "SCREENSHOT-TARGET" then set index of w to 1' \
  -e 'end repeat' \
  -e 'activate' \
  -e 'end tell'
sleep 0.8
screencapture -x -R"$x,$y,$w,$h" "$out"
sips -g pixelWidth -g pixelHeight "$out" | tail -2 | tr '\n' ' '
echo "$out"

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

osascript <<'APPLESCRIPT'
tell application "Google Chrome"
  repeat with w in windows
    set i to 0
    repeat with t in tabs of w
      set i to i + 1
      if title of t is "SCREENSHOT-TARGET" then
        set active tab index of w to i
        set index of w to 1
      end if
    end repeat
  end repeat
  activate
end tell
APPLESCRIPT
sleep 0.8
screencapture -x -R"$x,$y,$w,$h" "$out"
sips -g pixelWidth -g pixelHeight "$out" | tail -2 | tr '\n' ' '
echo "$out"

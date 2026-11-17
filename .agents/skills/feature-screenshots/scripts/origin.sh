#!/bin/bash
# Prints the screen origin of the page in the Chrome window showing the tab
# titled SCREENSHOT-TARGET, as "x y" in screen points, for capture.sh.
#
# usage: origin.sh <innerHeight>
#   innerHeight: the page's window.innerHeight, read in the state you'll
#   capture. The page fills the window's bottom, so its top is the window's
#   bottom edge less that height. The page's own screenY can't be trusted for
#   this: in fullscreen or on another display it reports the wrong position.
set -euo pipefail

inner=$1

osascript - "$inner" <<'APPLESCRIPT'
on run argv
  set inner to (item 1 of argv) as integer
  tell application "Google Chrome"
    repeat with w in windows
      repeat with t in tabs of w
        if title of t is "SCREENSHOT-TARGET" then
          set b to bounds of w
          return ((item 1 of b) as text) & " " & (((item 4 of b) - inner) as text)
        end if
      end repeat
    end repeat
  end tell
  error "No tab is titled SCREENSHOT-TARGET."
end run
APPLESCRIPT

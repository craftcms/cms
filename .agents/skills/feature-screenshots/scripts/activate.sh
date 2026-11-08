#!/bin/bash
# Makes the Chrome tab titled SCREENSHOT-TARGET its window's active tab, and
# brings that window to the front as window 1. Run it before reading the
# page's innerHeight for origin.sh, since switching tabs can change it.
#
# usage: activate.sh
set -euo pipefail

osascript <<'APPLESCRIPT'
tell application "Google Chrome"
  repeat with w in windows
    set i to 0
    repeat with t in tabs of w
      set i to i + 1
      if title of t is "SCREENSHOT-TARGET" then
        set active tab index of w to i
        set index of w to 1
        activate
        return
      end if
    end repeat
  end repeat
  error "No tab is titled SCREENSHOT-TARGET."
end tell
APPLESCRIPT
sleep 0.8

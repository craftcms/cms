#!/bin/bash
# Recovers transparency from two captures of the same region, one with the
# page behind the feature painted pure black and one pure white. Each pixel's
# opacity is 1 - (white - black), so shadows come out partially transparent.
#
# usage: matte.sh <black.png> <white.png> <out.png> [crop]
#   crop: ffmpeg crop as W:H:X:Y in output pixels. Use the same crop for every
#   shot in a set so they all come out the same size. Without one, the result
#   is trimmed to its visible pixels plus an 8px margin.
#
# Prints the bounds of the visible pixels as `visible: W:H:X:Y`, in the
# capture's pixels, so a set's shared crop can cover every shot's. Then prints
# the highest alpha along each edge (top, bottom, left, right). Any value above
# 0 means something was cut off: capture a larger region.
set -euo pipefail

black=$1 white=$2 out=$3 crop=${4:-}
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT

ffmpeg -hide_banner -loglevel error -y -i "$white" -i "$black" -filter_complex \
  "[0]format=gbrp[w];[1]format=gbrp,split[b1][b2];[w][b1]blend=all_expr='clip(255-(A-B),0,255)',extractplanes=g,split=2[a1][a3];[a3]format=gbrp[a3c];[b2][a3c]blend=all_expr='if(gt(B,0),clip(A*255/B,0,255),0)'[c];[c][a1]alphamerge,format=rgba[out]" \
  -map "[out]" -frames:v 1 "$tmp/matte.png"

# The bounds of every pixel with any opacity, even a shadow's faintest tail.
read -r x1 x2 y1 y2 < <(ffmpeg -hide_banner -i "$tmp/matte.png" -vf "alphaextract,bbox=min_val=0" -f null - 2>&1 |
  grep -o "x1:.*y2:[ 0-9]*" | tail -1 | sed 's/[xy][12]://g')
echo "visible: $((x2 - x1 + 1)):$((y2 - y1 + 1)):$x1:$y1"

if [ -z "$crop" ]; then
  crop="$((x2 - x1 + 17)):$((y2 - y1 + 17)):$((x1 - 8)):$((y1 - 8))"
fi

ffmpeg -hide_banner -loglevel error -y -i "$tmp/matte.png" -vf "crop=$crop" -pix_fmt rgba "$out"
ffmpeg -hide_banner -loglevel error -y -i "$out" -vf alphaextract "$tmp/edges.png"
for edge in "crop=iw:2:0:0" "crop=iw:2:0:ih-2" "crop=2:ih:0:0" "crop=2:ih:iw-2:0"; do
  ffmpeg -hide_banner -i "$tmp/edges.png" -vf "$edge,signalstats,metadata=print:key=lavfi.signalstats.YMAX" -f null - 2>&1 |
    grep -o "YMAX=[0-9]*" | tr '\n' ' '
done
echo
sips -g pixelWidth -g pixelHeight "$out" | tail -2 | tr '\n' ' '
echo "$out"

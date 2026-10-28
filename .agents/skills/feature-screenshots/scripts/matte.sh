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
# Prints the highest alpha along each edge (top, bottom, left, right). Any
# value above 0 means something was cut off: capture a larger region.
set -euo pipefail

black=$1 white=$2 out=$3 crop=${4:-}
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT

ffmpeg -hide_banner -loglevel error -y -i "$white" -i "$black" -filter_complex \
  "[0]format=gbrp[w];[1]format=gbrp,split[b1][b2];[w][b1]blend=all_expr='clip(255-(A-B),0,255)',extractplanes=g,split=2[a1][a3];[a3]format=gbrp[a3c];[b2][a3c]blend=all_expr='if(gt(B,0),clip(A*255/B,0,255),0)'[c];[c][a1]alphamerge,format=rgba[out]" \
  -map "[out]" -frames:v 1 "$tmp/matte.png"

if [ -z "$crop" ]; then
  ffmpeg -hide_banner -loglevel error -y -i "$tmp/matte.png" -vf alphaextract "$tmp/alpha.png"
  detected=$(ffmpeg -hide_banner -loop 1 -i "$tmp/alpha.png" -frames:v 4 \
    -vf "format=gray,cropdetect=limit=0:round=2:reset=0:skip=0" -f null - 2>&1 |
    grep -o "crop=[0-9:]*" | tail -1 | cut -d= -f2)
  IFS=: read -r cw ch cx cy <<< "$detected"
  crop="$((cw + 16)):$((ch + 16)):$((cx - 8)):$((cy - 8))"
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

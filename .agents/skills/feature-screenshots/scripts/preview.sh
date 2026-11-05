#!/bin/bash
# Composites a transparent PNG over a bright orange, scaled down, so fringing,
# a clipped shadow, or anything left behind the feature is easy to spot.
#
# usage: preview.sh <in.png> <out.png>
set -euo pipefail

size=$(sips -g pixelWidth -g pixelHeight "$1" | awk '/pixel(Width|Height)/ {print $2}' | paste -sd x -)
ffmpeg -hide_banner -loglevel error -y -f lavfi -i "color=c=#e8a33d:s=$size" -i "$1" \
  -filter_complex "[0][1]overlay,scale=700:-2" -frames:v 1 "$2"
echo "$2"

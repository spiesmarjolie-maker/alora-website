# Build the hero film from the two Flow clips (storage/film/clip1.mp4 + clip2.mp4).
#   powershell -File tools/build-film.ps1     (run from alora/)
# - Flow baked black cinema bars into clip 1; a soft black ramp at the top and bottom hides their hard edge
#   (and the faint one at the start of clip 2), then fades away as the light fills the frame.
# - Every frame is a keyframe (-g 1) so scroll can scrub forwards and backwards without stutter.
$ErrorActionPreference = 'Stop'
$src = Join-Path $PSScriptRoot '..\storage\film'
$out = Join-Path $PSScriptRoot '..\public\media\film'
New-Item -ItemType Directory -Force $out | Out-Null

# Soft edge mask: solid black over the bars (88 px top and bottom) plus a little, then a smooth ramp to clear by 300 px in.
$mask = "color=black:s=1280x720:d=16,format=rgba,geq=r=0:g=0:b=0:a='255*pow(clip(1-(min(Y\,H-1-Y)-100)/200\,0\,1)\,1.6)'"
$fc = "[0:v][1:v]concat=n=2:v=1[v];$mask,fade=t=out:st=8.8:d=1.8:alpha=1[m];[v][m]overlay=shortest=1,format=yuv420p,split=2[a][b];" +
      "[a]null[desk];[b]scale=960:540[phone]"
ffmpeg -v error -y -i "$src\clip1.mp4" -i "$src\clip2.mp4" -filter_complex $fc `
  -map '[desk]' -an -c:v libx264 -preset slow -crf 24 -g 1 -movflags +faststart "$out\alora-film-1280.mp4" `
  -map '[phone]' -an -c:v libx264 -preset slow -crf 25 -g 1 -movflags +faststart "$out\alora-film-960.mp4"
# Poster: the opening frame, so nothing jumps when the video takes over.
ffmpeg -v error -y -ss 0 -i "$out\alora-film-1280.mp4" -frames:v 1 -c:v libwebp -quality 82 "$out\alora-film-poster.webp"
Get-ChildItem $out | Select-Object Name, @{n = 'MB'; e = { [math]::Round($_.Length / 1MB, 2) } }

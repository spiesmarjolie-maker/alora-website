# Build the hero film from the two Flow clips, in 1080p.
#   powershell -File tools/build-film.ps1     (run from alora/)
# Sources: storage/film/clip1-1080.mp4 + clip2-1080.mp4 (Flow's 720p downloads, AI-upscaled to 1920x1080 with
# realesr-animevideov3 x2 + lanczos; see tools/upscale-film.ps1). Falls back to the 720p clips if those are missing.
# - Flow baked black cinema bars into clip 1; a soft black ramp at the top and bottom hides their hard edge
#   (and the faint one at the start of clip 2), then fades away as the light fills the frame.
# - Every frame is a keyframe (-g 1) so scroll can scrub forwards and backwards without stutter.
# Outputs: alora-film-1080.mp4 (desktop), alora-film-720.mp4 (phones), alora-film-poster.webp.
$ErrorActionPreference = 'Stop'
$src = Join-Path $PSScriptRoot '..\storage\film'
$out = Join-Path $PSScriptRoot '..\public\media\film'
New-Item -ItemType Directory -Force $out | Out-Null
$c1 = "$src\clip1-1080.mp4"; $c2 = "$src\clip2-1080.mp4"
if (-not (Test-Path $c1)) { $c1 = "$src\clip1.mp4"; $c2 = "$src\clip2.mp4" }

# Work at 1920x1080. The mask was tuned at 720p (100 px solid, 200 px ramp), so it scales by 1.5.
$mask = "color=black:s=1920x1080:d=16,format=rgba,geq=r=0:g=0:b=0:a='255*pow(clip(1-(min(Y\,H-1-Y)-150)/300\,0\,1)\,1.6)'"
$fc = "[0:v]scale=1920:1080:flags=lanczos[a0];[1:v]scale=1920:1080:flags=lanczos[a1];[a0][a1]concat=n=2:v=1[v];" +
      "$mask,fade=t=out:st=8.8:d=1.8:alpha=1[m];[v][m]overlay=shortest=1,format=yuv420p,split=2[a][b];" +
      "[a]null[desk];[b]scale=1280:720:flags=lanczos[phone]"
ffmpeg -v error -y -i $c1 -i $c2 -filter_complex $fc `
  -map '[desk]' -an -c:v libx264 -preset slow -crf 23 -g 1 -movflags +faststart "$out\alora-film-1080.mp4" `
  -map '[phone]' -an -c:v libx264 -preset slow -crf 24 -g 1 -movflags +faststart "$out\alora-film-720.mp4"
# Poster: the opening frame, so nothing jumps when the video takes over.
ffmpeg -v error -y -ss 0 -i "$out\alora-film-1080.mp4" -frames:v 1 -c:v libwebp -quality 82 "$out\alora-film-poster.webp"
Get-ChildItem $out | Select-Object Name, @{n = 'MB'; e = { [math]::Round($_.Length / 1MB, 2) } }

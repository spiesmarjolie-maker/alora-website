# Upscale Flow's 720p clips to 1080p (the free Flow plan only downloads 720p; the site always uses 1080p).
#   powershell -File tools/upscale-film.ps1     (run from alora/; about 25 min on this PC's Radeon GPU)
# Frames -> realesr-animevideov3 x2 (2560x1440, fast video model) -> lanczos down to 1920x1080 -> clipN-1080.mp4
$ErrorActionPreference = 'Stop'
$src = Join-Path $PSScriptRoot '..\storage\film'
$esr = "$env:LOCALAPPDATA\alora-tools\realesrgan\realesrgan-ncnn-vulkan.exe"
$work = Join-Path $env:TEMP 'alora-film-up'
foreach ($n in 1, 2) {
  $in = "$work\in$n"; $up = "$work\out$n"
  New-Item -ItemType Directory -Force $in, $up | Out-Null
  ffmpeg -v error -y -i "$src\clip$n.mp4" "$in\%04d.png"
  & $esr -i $in -o $up -n realesr-animevideov3 -s 2 -f png | Out-Null
  ffmpeg -v error -y -framerate 24 -i "$up\%04d.png" -vf "scale=1920:1080:flags=lanczos,format=yuv420p" -c:v libx264 -preset slow -crf 14 "$src\clip$n-1080.mp4"
}
Remove-Item -Recurse -Force $work

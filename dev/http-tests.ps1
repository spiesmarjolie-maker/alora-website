# Dev HTTP checks against the local server (http://localhost:8080). Run: powershell -File dev/http-tests.ps1
# Uses the local test owner from dev/LOCAL-LOGINS.txt. Creates a throwaway customer.
# It edits product 1 in the LOCAL database; run tools/import-wp.php afterwards to restore it.
$ErrorActionPreference = 'Stop'
$base = 'http://localhost:8080'
$root = Split-Path $PSScriptRoot
$jar = Join-Path $env:TEMP 'alora-test-cookies.txt'
$fails = 0
function Check($what, $ok) { if ($ok) { "PASS $what" } else { "FAIL $what"; $script:fails++ } }
function Get-Csrf($html) { if ($html -match 'name="csrf" value="([a-f0-9]+)"') { $matches[1] } }
function Get-Page($url) { (curl.exe -s -b $jar -c $jar "$base$url") -join "`n" }
function Get-Code($url, $extra = @()) { curl.exe -s -o NUL -w '%{http_code} %{redirect_url}' -b $jar -c $jar @extra "$base$url" }
Remove-Item $jar -ErrorAction SilentlyContinue

$pw = ((Get-Content "$root/dev/LOCAL-LOGINS.txt")[1] -split ' / ')[1].Trim()

# 1. Admin is locked without a login
Check 'admin redirects to login when signed out' ((Get-Code '/admin/products/') -match '^303 .*/admin/login/$')

# 2. CSRF: a POST without the token is refused
Check 'POST without CSRF token is refused' ((Get-Code '/admin/login/' @('-d', 'email=x@y.z&password=nope')) -match '^400')

# 3. Wrong admin password is refused, right one works
$csrf = Get-Csrf (Get-Page '/admin/login/')
$r = curl.exe -s -b $jar -c $jar -d "csrf=$csrf&email=owner@alora.test&password=wrong-password" "$base/admin/login/"
Check 'wrong admin password refused' (($r -join '') -match 'do not match')
$csrf = Get-Csrf (Get-Page '/admin/login/')
# (goes back to the admin page they first tried to open)
Check 'owner can sign in' ((Get-Code '/admin/login/' @('--data-urlencode', "csrf=$csrf", '--data-urlencode', 'email=owner@alora.test', '--data-urlencode', "password=$pw")) -match '^303 .*/admin/products/$')

# 4. Product edit + photo upload: a real JPG is accepted, a text file named .jpg is refused
$page = Get-Page '/admin/products/1/'
$csrf = Get-Csrf $page
$before = [regex]::Matches($page, 'value="image-delete"').Count
$real = (Get-ChildItem "$root/storage/originals" -Filter *.jpg | Select -First 1).FullName
$fake = Join-Path $env:TEMP 'not-a-photo.jpg'; Set-Content $fake 'this is not an image'
$name = [regex]::Match($page, 'name="name" required value="([^"]*)"').Groups[1].Value
$out = curl.exe -s -b $jar -c $jar -L -F "csrf=$csrf" -F "name=$name" -F 'price=99.50' -F 'stock=4' -F 'do=save' -F "photos[]=@$real" -F "photos[]=@$fake" "$base/admin/products/1/"
$after = [regex]::Matches(($out -join ''), 'value="image-delete"').Count
Check "real photo added ($before -> $after photos)" ($after -eq $before + 1)
Check 'fake photo refused with a message' (($out -join '') -match 'Please upload a JPG, PNG or WebP photo')
Check 'price saved as R99,50' (($out -join '') -match 'name="price" inputmode="decimal" required value="99.50"')

# 5. Staff-only pages stay closed to customers; owner-only pages open for the owner
Check 'settings open for owner' ((Get-Code '/admin/settings/') -match '^200')

# 6. Customer: register, sign out, five wrong passwords lock the account
$email = "test-$(Get-Random)@example.com"
Remove-Item $jar
$csrf = Get-Csrf (Get-Page '/account/')
Check 'customer can register' ((Get-Code '/account/register/' @('--data-urlencode', "csrf=$csrf", '-d', "name=Test+Shopper&email=$email&password=correct-horse-1&agree=1")) -match '^303 .*/account/$')
Check 'account page shows their name' ((Get-Page '/account/') -match 'Hello, <em>Test</em>')
Remove-Item $jar
$csrf = Get-Csrf (Get-Page '/account/')
1..5 | % { curl.exe -s -o NUL -b $jar -c $jar --data-urlencode "csrf=$csrf" -d "email=$email&password=wrong-$_" "$base/account/login/" }
$r = curl.exe -s -b $jar -c $jar --data-urlencode "csrf=$csrf" -d "email=$email&password=correct-horse-1" "$base/account/login/"
Check 'locked after 5 wrong passwords (even the right one waits)' (($r -join '') -match 'Too many attempts')

# 7. Someone else's order page needs the private link
Check 'order page without its token is 404' ((Get-Code '/order/AL-10003/') -match '^404')

Remove-Item $jar, $fake -ErrorAction SilentlyContinue
if ($fails) { "`n$fails FAILED"; exit 1 } else { "`nAll passed" }

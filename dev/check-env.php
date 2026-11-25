<?php
// Quick local environment check: php -c dev/php.ini dev/check-env.php
echo 'PHP ', PHP_VERSION, PHP_EOL;
$g = gd_info();
echo 'GD webp: ', var_export($g['WebP Support'], true), ', jpeg: ', var_export($g['JPEG Support'], true), PHP_EOL;
echo 'SQLite: ', SQLite3::version()['versionString'], PHP_EOL;
$c = curl_init('https://joliefemme.co.za/wp-json/');
curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true]);
curl_exec($c);
echo 'HTTPS: ', curl_getinfo($c, CURLINFO_HTTP_CODE), ' ', curl_error($c), PHP_EOL;

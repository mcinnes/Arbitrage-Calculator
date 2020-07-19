<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://www.sportsbet.com.au/apigw/sportsbook-sports/Sportsbook/Sports/Competitions/4165?displayType=default&includeTopMarkets=true&eventFilter=matches",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "",
  CURLOPT_HTTPHEADER => array(
    "accept: application/json",
    "accept-language: en-US,en;q=0.5",
    "apptoken: cxp-desktop-web",
    "cache-control: no-cache",
    "channel: cxp",
    "connection: keep-alive",
    "content-type: application/json",
    "pragma: no-cache",
    "referer: https://www.sportsbet.com.au/betting/australian-rules/afl",
    "user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:75.0) Gecko/20100101 Firefox/75.0"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

// if ($err) {
//   echo "cURL Error #:" . $err;
// } else {
//   echo $response;
// }

$response = json_decode($response, true);

foreach ($response['events'] as $game) {
  foreach ($game['marketList'] as $list) {
    if ($list['name'] == 'Head to Head') {
      echo $game['name'] . "\n";
      echo $list['name'];
      foreach ($list['selections'] as $selection) {
        echo $selection['name'] . ' @ ' . $selection['price']['winPrice'] . "\n";
      }
      echo "\n\n";
    }
  }
}

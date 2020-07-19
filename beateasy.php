<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://beteasy.com.au/api/sports/sports?pager%5Bsize%5D=20&pager%5Bpage%5D=0&filters%5BEventTypes%5D=101&filters%5BMasterCategoryID%5D=16&filters%5BMasterCategoryClassID%5D=11&filters%5BCategoryID%5D=5471&filters%5BCategoryClassID%5D=10&ignoreSummaries=0",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "",
  CURLOPT_COOKIE => "visid_incap_1771737=fB6HVgVkQTiOyswAcWP9xe3bEl8AAAAAQUIPAAAAAACfsM1BiDWwplCtnc8Bv9OS; nlbi_1771737=MXNoCWSYtxWz08p5DZ4XBwAAAAB1vAjTUSbu7D2N6gBPZGcN; incap_ses_321_1771737=X96oEanFLyqr2LnW%2F2t0BO3bEl8AAAAAQvWvedxmvnyxjODpdHNl1g%3D%3D",
  CURLOPT_HTTPHEADER => array(
    "accept: application/json, text/plain, */*",
    "accept-language: en-US,en;q=0.5",
    "cache-control: no-cache",
    "connection: keep-alive",
    "pragma: no-cache",
    "referer: https://beteasy.com.au/sports-betting/australian-rules/afl/afl-matches",
    "te: Trailers",
    "user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:75.0) Gecko/20100101 Firefox/75.0"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

$response = json_decode($response,true);

foreach ($response['result']['sports']['events'] as $game) {
  foreach ($game['BettingType'] as $list) {
      if ($list['EventName'] == 'Head to Head') {
          echo $game['MasterEventName'] . "\n";
          foreach ($list['Outcomes'] as $selection) {
            echo $selection['OutcomeName'] . ' @ ' . $selection['BetTypes'][0]['Price'] . "\n";
          }
          echo "\n\n";
      }
  }
}

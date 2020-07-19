<?php
require 'vendor/autoload.php';
use Medoo\Medoo;

class Aribitrage
{

  protected $cash;
  protected $database;
  protected $odds;

  public function __construct()
  {
    $this->database = new Medoo([
        'database_type' => 'mysql',
        'database_name' => 'betting_odds',
        'server' => 'calishot.cb7kuhequj2i.ap-southeast-2.rds.amazonaws.com',
        'username' => 'caliuser',
        'password' => 'znrr70xx'
    ]);


  }

  protected function CalculateArbitrage()
  {
    //Leave half the cash remaining
    $total_cash = $this->GetCurrentCash() / 2;

    foreach ($this->odds['data'] as $game) {
      $team1 = [];
      $team2 = [];

      foreach ($game['sites'] as $site) {
        $team1[] = $site['odds']['h2h'][0];
        $team2[] = $site['odds']['h2h'][1];
      }

      // echo implode(', ', $game['teams']) . "\n";

      foreach ($team1 as $key1 => $t1) {
        foreach ($team2 as $key2 => $t2) {
          if ($key1 != $key2) {
            $a1 = ((1 / $t1) * 100);
            $a2 = ((1 / $t2) * 100);

            $arbitrage = number_format(($a1+$a2),2);
            if ($arbitrage < 100) {

              $hash = md5(implode(', ', $game['teams']).$game['sites'][$key1]['site_key'].$game['sites'][$key2]['site_key'].$game['commence_time']);
              $profit = abs((($total_cash * ($arbitrage/100)) - $total_cash));



              if (is_null($this->CheckHash($hash))) {
                $game = $this->AddToGames([
                  'hash' => $hash,
                  'team1' => $game['teams'][0],
                  'team1_odds' => number_format($t1, 2),
                  'team1_amount' => number_format(abs(($total_cash * ($a1/100)) / ($arbitrage/100)),2),
                  'team2' => $game['teams'][1],
                  'team2_odds' => number_format($t2, 2),
                  'team2_amount' => number_format(abs(($total_cash * ($a2/100)) / ($arbitrage/100)),2),
                  'total_profit' => $profit
                ]);
                $this->AddToLedger([
                  'game_id' => $game,
                  'cost' => $total_cash,
                  'profit' =>  $profit,
                  'placed_at' => date('Y-m-d H:i:s'),
                  'paid_at' => $this->FormatDate($game['commence_time'])
                ]);
              }
              // echo $game['sites'][$key1]['site_key'] . ' vs ' . $game['sites'][$key2]['site_key']  . ' @ ' . number_format($t1, 2) . ' vs ' . number_format($t2, 2) . ' = ' . $arbitrage ."\n";
              // echo 'Profit = ' . abs(((BASE_BET * ($arbitrage/100)) - BASE_BET)) ."\n";
              // echo $game['sites'][$key1]['site_key'] . ' = ' . number_format(abs((BASE_BET * ($a1/100)) / ($arbitrage/100)),2) . "\n";
              // echo $game['sites'][$key2]['site_key'] . ' = ' . number_format(abs((BASE_BET * ($a2/100)) / ($arbitrage/100)),2) . "\n\n";
            }
          }
        }

      }
    }
  }

  protected function GetCurrentCash()
  {

  }

  protected function AddToLedger($data)
  {
    $this->database->insert('ledger', $data);
  }

  protected function AddToGames($data)
  {
    $this->database->insert('games', $data);
  }

  protected function CheckHash($hash)
  {
    return $this->database->get("games", "id", [
      "hash" => $hash
    ]);
  }

  protected function GetDataFromAPI()
  {

  }

  protected function FormatDate($time)
  {
    $date = new DateTime();
    $date->setTimestamp($time);
    $date->add(new DateInterval('PT' . 160 . 'M'));

    return $date->format('Y-m-d H:i:s');
  }

// const BASE_BET = 500;

// $odds = json_decode(file_get_contents('odds.json'), true);

}

// Arbitrage % = ((1 / 3.) x 100) + ((1 / decimal odds for outcome B) x 100)

// Profit = (Investment / Arbitrage %) – Investment

// Individual bets = (Investment x Individual Arbitrage %) / Total Arbitrage %


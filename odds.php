<?php
require 'vendor/autoload.php';
use Medoo\Medoo;
use Dotenv\Dotenv;

class Aribitrage
{

  protected $cash;
  protected $database;
  protected $odds;
  protected $dotenv;

  public function __construct()
  {
    //Load environment variables
    $this->dotenv = Dotenv::createImmutable(__DIR__);
    $this->dotenv->load();

    //Setup database connection
    $this->database = new Medoo([
        'database_type' => 'mysql',
        'database_name' => $_ENV['DATABASE_NAME'],
        'server' => $_ENV['DATABASE_HOST'],
        'username' => $_ENV['DATABASE_USER'],
        'password' => $_ENV['DATABASE_PASSWORD']
    ]);

    //Download latest data file
    $this->GetDataFromAPI($_ENV['THE_ODDS_API_KEY']);
    $this->CalculateArbitrage();
  }

  protected function CalculateArbitrage()
  {
    //Loop over each game
    foreach ($this->odds['data'] as $game) {
      $team1 = [];
      $team2 = [];

      //Loop over each site for that game
      foreach ($game['sites'] as $site) {
        $team1[] = $site['odds']['h2h'][0];
        $team2[] = $site['odds']['h2h'][1];
      }

      //Loop over each home team
      foreach ($team1 as $key1 => $t1) {
        //Loop over each away team
        foreach ($team2 as $key2 => $t2) {
          //Make sure we aren't comparing the same odds eg. 1.01 vs 1.01
          if ($key1 != $key2) {

            //Arbitrage % = ((1 / decimal odds for outcome A) x 100) + ((1 / decimal odds for outcome B) x 100)
            //Percentage for Team 1
            $a1 = ((1 / $t1) * 100);
            //Percentage for Team 2
            $a2 = ((1 / $t2) * 100);

            $arbitrage = number_format(($a1+$a2),2);

            //Sm of a1 + a2 less than 100%, therefore an arbitrage bet
            if ($arbitrage < 100) {
              //Create a simple hash of the game + some other things, thi will stop us doubling up on the same game
              $hash = md5(implode(', ', $game['teams']).$game['sites'][$key1]['site_key'].$game['sites'][$key2]['site_key'].$game['commence_time']);

              //Check how much cash we have left. Take the same percentage that the arbitrage is * 10.
              //I'm sure theres an actual formula we could use to make better decisions about this, but I just don't want to run out of money
              $total_cash = $this->GetCurrentCash() / ((100-floatval($arbitrage) *10));

              //Profit = (Investment / Arbitrage %) – Investment
              $profit = abs((($total_cash * ($arbitrage/100)) - $total_cash));

              //Check that the game hash doesn't exist
              if (is_null($this->CheckHash($hash))) {
                //Add the game to the database
                $game1 = $this->AddToGames([
                  'hash' => $hash,
                  'team1' => $game['teams'][0],
                  'team1_odds' => number_format($t1, 2),
                  'team1_amount' => number_format(abs(($total_cash * ($a1/100)) / ($arbitrage/100)),2), //Individual bets = (Investment x Individual Arbitrage %) / Total Arbitrage %
                  'team2' => $game['teams'][1],
                  'team2_odds' => number_format($t2, 2),
                  'team2_amount' => number_format(abs(($total_cash * ($a2/100)) / ($arbitrage/100)),2),
                  'total_profit' => $profit
                ]);

                //Add the transaction to the ledger
                $this->AddToLedger([
                  'game_id' => $game,
                  'cost' => $total_cash,
                  'profit' =>  $profit,
                  'placed_at' => date('Y-m-d H:i:s'),
                  'paid_at' => $this->FormatDate($game['commence_time']) //Add 160 minutes, I don't know how long it takes to get paid
                ]);
              }
            }
          }
        }

      }
    }
  }

  protected function GetCurrentCash()
  {
    //Reset the cash everytime this is called, it may have changed since last time
    $this->cash = 0;

    $date = new DateTime();

    //Select everything from the ledger
    $ledger = $this->database->select("ledger", [
      "profit",
      "cost",
      "paid_at"
    ]);

    foreach ($ledger as $transaction) {
      //If paid at is less than now, we've got the inital payment + profit
     if ($transaction['paid_at'] < $date->format('Y-m-d H:m:i')) {
      $this->cash += (floatval($transaction['cost'])+ floatval($transaction['profit']));
     } else {
      //We haven't been paid, remove the inital payment from our cash on hand
      $this->cash -= floatval($transaction['cost']);
     }
    }
  }

  protected function AddToLedger($data)
  {
    return $this->database->insert('ledger', $data);
  }

  protected function AddToGames($data1)
  {
    return $this->database->insert('games', $data1);
  }

  protected function CheckHash($hash)
  {
    //Will return the id in the database if it exists, if not it returns null
    return $this->database->get("games", "id", [
      "hash" => $hash
    ]);
  }

  protected function GetDataFromAPI($key)
  {
    //Download the JSON from the site, parse into an array
    $this->odds = json_decode(file_get_contents('https://api.the-odds-api.com/v3/odds/?apiKey='.$key.'&sport=aussierules_afl&region=au'), true);
  }

  protected function FormatDate($time)
  {
    $date = new DateTime();
    $date->setTimestamp($time);
    $date->add(new DateInterval('PT' . 160 . 'M'));

    return $date->format('Y-m-d H:i:s');
  }
}

$a = new Aribitrage();


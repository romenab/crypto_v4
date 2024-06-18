<?php

namespace CryptoTrade;

use CryptoTrade\App\Database\Sqlite;
use CryptoTrade\App\Display;
use CryptoTrade\App\Tasks;
use CryptoTrade\App\User;
use CryptoTrade\App\Wallet;
use Dotenv\Dotenv;

require_once 'vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$api = $_ENV['MY_API'];

$user = new User();
$user->login();

$database = new Sqlite($user);
$transactions = $database->loadTransactions();
$owned = $database->loadOwned();
$money = $database->loadMoney();

$tasks = new Tasks($api);
$wallet = new Wallet($api, $transactions, $owned, $money);
$show = new Display($tasks, $wallet);

while (true) {
    $show->getMenu();
    $userAction = (int)readline("Enter your action: ");
    $show->chooseAction($userAction, $database);
}
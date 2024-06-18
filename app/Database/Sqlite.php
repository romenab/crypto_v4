<?php

namespace CryptoTrade\App\Database;

use CryptoTrade\App\User;
use CryptoTrade\App\Wallet;
use Medoo\Medoo;

class Sqlite
{
    protected Medoo $db;
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->db = new Medoo([
            'database_type' => 'sqlite',
            'database_name' => 'app/storage/database.sqlite',
        ]);

        $this->create();
    }

    private function create(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS wallet (
            user TEXT,
            money INTEGER,
            owned TEXT
        )");
        $this->db->exec("CREATE TABLE IF NOT EXISTS transactions (
            user TEXT,
            trade TEXT,
            cryptoName TEXT,
            spent REAL,
            received REAL,
            price TEXT
        )");
    }

    public function insert(Wallet $wallet): void
    {
        $username = $this->user->getUser();
        $money = $wallet->getMoney();
        $owned = json_encode($wallet->getOwned());
        $this->db->update("wallet", ["money" => $money, "owned" => $owned], ["user" => $username]);

        foreach ($wallet->getTransaction() as $transaction) {
            $transaction['user'] = $username;
            $isTransaction = $this->db->get("transactions", "*", [
                "AND" => [
                    "user" => $username,
                    "trade" => $transaction['trade'],
                    "cryptoName" => $transaction['cryptoName'],
                    "spent" => $transaction['spent'],
                    "received" => $transaction['received'],
                    "price" => $transaction['price']
                ]
            ]);

            if (!$isTransaction) {
                $this->db->insert("transactions", $transaction);
            }
        }
    }
    public function loadTransactions(): array
    {
        $username = $this->user->getUser();
        return $this->db->select("transactions", ["trade", "cryptoName", "spent", "received", "price"],
        ["user" => $username]);
    }

    public function loadOwned(): array
    {
        $username = $this->user->getUser();
        $owned = $this->db->get("wallet", "owned", ["user" => $username]);
        return $owned ? json_decode($owned, true) : [];
    }

    public function loadMoney(): float
    {
        $username = $this->user->getUser();
        $money = $this->db->get("wallet", "money", ["user" => $username]);
        if ($money === null) {
            $this->db->insert("wallet", ["user" => $username, "money" => 1000, "owned" => json_encode([])]);
            return 1000;
        }
        return (float)$money;
    }
}
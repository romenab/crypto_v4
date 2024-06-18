<?php

namespace CryptoTrade\App;

use CryptoTrade\App\Api\CoinMC;
use CryptoTrade\App\Api\CryptoApi;

class Wallet
{
    private CryptoApi $cryptoApi;

    private float $money;
    private array $transactions;
    private array $owned;
    private float $profit;
    private array $avgPrice;

    public function __construct(
        string $api,
        array  $transactions,
        array  $owned,
        float  $money,
        float  $profit = 0,
        array  $avgPrice = []
    )
    {
        $this->cryptoApi = new CoinMC($api);
        $this->transactions = $transactions;
        $this->owned = $owned;
        $this->money = $money;
        $this->profit = $profit;
        $this->avgPrice = $avgPrice;
    }

    public function transaction(
        string $trade,
        string $cryptoName,
        float  $spent,
        float  $received,
        float  $price
    ): array
    {
        return $this->transactions[] = [
            "trade" => $trade,
            "cryptoName" => $cryptoName,
            "spent" => $spent,
            "received" => $received,
            "price" => $price
        ];
    }

    public function owned(string $name, float $amount): void
    {
        if (isset($this->owned[$name])) {
            $this->owned[$name] += $amount;
        } else {
            $this->owned[$name] = $amount;
        }
    }

    public function profit(string $name, float $amount, float $price): float
    {
        if (!isset($this->avgPrice[$name])) {
            $this->avgPrice[$name] = [
                'totalAmount' => 0,
                'avgPrice' => 0,
            ];
        }

        $totalAmount = $this->avgPrice[$name]['totalAmount'] + $amount;
        $totalSpent = $this->avgPrice[$name]['totalAmount'] * $this->avgPrice[$name]['avgPrice'] + $amount * $price;
        $avgPrice = $totalSpent / $totalAmount;

        $this->avgPrice[$name] = [
            'totalAmount' => $totalAmount,
            'avgPrice' => $avgPrice,
        ];

        $cryptoList = $this->cryptoApi->getResponse();
        foreach ($cryptoList as $item) {
            if ($name === $item->getName()) {
                $currentPrice = $item->getPrice();
                $this->profit = (($currentPrice - $avgPrice) / $avgPrice) * 100;
            }
        }
        return $this->profit;
    }

    public function purchase(): void
    {

        while (true) {
            $userCrypto = ucfirst(readline("Crypto you want to purchase: "));
            if ($userCrypto == "") {
                continue;
            }
            $userAmount = (int)readline("Amount $: ");
            if ($userAmount < 1) {
                continue;
            }
            break;
        }
        if ($userAmount > $this->money) {
            echo "You don't have enough money to purchase." . PHP_EOL;
            return;
        }
        $cryptoList = $this->cryptoApi->getResponse();
        foreach ($cryptoList as $item) {
            if ($userCrypto === $item->getName()) {
                $price = $item->getPrice();
                $totalCrypto = $userAmount / $price;
                $purchase = strtolower(readline("Are you sure you want to purchase {$item->getName()} for $$userAmount (y/n)? "));
                if ($purchase === "n" || $purchase === "no") {
                    return;
                }
                $this->money -= $userAmount;
                echo "You purchased $totalCrypto {$item->getName()} for $$userAmount." . PHP_EOL;

                $this->owned($item->getName(), $totalCrypto);
                $this->profit($item->getName(), $totalCrypto, $price);
                $this->transaction("Purchased", $item->getName(), $userAmount, $totalCrypto, $price);
                return;
            }
        }
        echo "Didn't find a match!" . PHP_EOL;
    }

    public function sell()
    {
        $cryptoList = $this->cryptoApi->getResponse();
        if (empty($this->owned)) {
            return;
        }
        $userSell = ucfirst(readline("What crypto you want to sell: "));
        if ($userSell == "") {
            return;
        }
        if (isset($this->owned[$userSell])) {
            $sell = strtolower(readline("Are you sure you want to sell $userSell (y/n)? "));
            if ($sell === "n" || $sell === "no") {
                return;
            }
            foreach ($cryptoList as $crypto) {
                if ($userSell === $crypto->getName()) {
                    $price = $crypto->getPrice();
                    $totalDollars = $price * $this->owned[$userSell];
                    $this->money += $totalDollars;
                    $this->transaction("Sold", $userSell, $this->owned[$userSell], $totalDollars, $price);
                    echo "You sold $userSell and received $$totalDollars." . PHP_EOL;
                    unset($this->owned[$userSell]);
                    unset($this->avgPrice[$userSell]);
                    return;
                }
            }
        }
        echo "You don't own $userSell." . PHP_EOL;
    }

    public function getProfit(): float
    {
        return $this->profit;
    }

    public function getTransaction(): array
    {
        return $this->transactions;
    }

    public function getOwned(): array
    {
        return $this->owned;
    }

    public function getMoney(): int
    {
        return $this->money;
    }
}
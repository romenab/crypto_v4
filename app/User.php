<?php

namespace CryptoTrade\App;

use Medoo\Medoo;

class User
{
    protected string $user;
    protected Medoo $db;

    public function __construct()
    {
        $this->db = new Medoo([
            'database_type' => 'sqlite',
            'database_name' => 'app/storage/database.sqlite',
        ]);
    }

    public function login(): void
    {
        $attempts = 3;

        while ($attempts > 0) {
            $username = readline("Username: ");
            $password = readline("Password: ");

            $user = $this->db->get("user", ["password"], ["username" => $username]);
            if ($user && md5($password) === $user['password']) {
                echo "Login Successful!" . PHP_EOL;
                $this->user = $username;
                return;
            }
            echo "Login was not successful." . PHP_EOL;
            $attempts--;
        }
        exit("Maximum login attempts reached.");
    }

    public function getUser(): string
    {
        return $this->user;
    }
}
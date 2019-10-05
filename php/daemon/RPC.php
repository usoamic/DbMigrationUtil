<?php
require_once ('jsonRPCClient.php');

class RPC {
    private $client;

    public function __construct($user = RPC_USER, $password = RPC_PASSWORD, $host = RPC_HOST, $port = RPC_PORT)
    {
        $this->client = new jsonRPCClient("http://".$user.":".$password."@".$host.":".$port."/");
    }

    public function getInfo() {
        return $this->client->getinfo();
    }

    public function getNewAddress() {
        return $this->client->getnewaddress();
    }

    public function getCurrentBlock() {
        return $this->client->getblockcount();
    }

    public function getTransaction($txhash) {
        return $this->client->gettransaction($txhash);
    }

    public function getBalance($email) {
        return $this->client->getbalance($email);
    }

    public function getListTransactionsByEmail($email) {
        return $this->client->listtransactions($email);
    }

    public function getListTransactions() {
        return $this->client->listtransactions();
    }
}
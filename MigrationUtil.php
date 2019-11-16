<?php

class MigrationUtil
{
    private $encryptionUtil,
        $encryptionLegacyUtil,
        $oldDbUtil,
        $newDbUtil,
        $rpc,
        $gAuth;

    public function __construct()
    {
        $this->encryptionUtil = new EncryptionUtil();
        $this->encryptionLegacyUtil = new EncryptionLegacyUtil();
        $this->gAuth = new GoogleAuthenticator();
        $this->oldDbUtil = new DBClass(
            OLD_DB_USER,
            OLD_DB_PASSWORD,
            OLD_DB_HOST,
            $this->encryptionLegacyUtil
        );

        $this->newDbUtil = new DBClass(
            NEW_DB_USER,
            NEW_DB_PASSWORD,
            NEW_DB_HOST,
            $this->encryptionUtil
        );
//null//$this->encryptionUtil
        $this->rpc = new RPC();
    }

    public function clear()
    {
        $tables = array(
            USERS_TABLE,
            TRANSACTIONS_TABLE
        );
        foreach ($tables as $table) {
            $this->newDbUtil->clearTable($table);
        }
    }

    public function run()
    {
        $legacyUsers = $this->oldDbUtil->getRows(USERS_TABLE);
        $isExistList = array();
        $allBalances = 0;
        $numberOfAdded = 0;

        foreach ($legacyUsers as $oldUser) {
            $calculatedBalance = 0;
            $email = $oldUser["email"];
            $stakeBalance = $this->getStakeAmount($oldUser);
            if(!$this->rpc->move(STAKING_ACCOUNT, $email, $stakeBalance*1e8)) {
                throw new Exception("Move coins");
            }
            $balance = $this->getBalance($email);

            $secretKey = $oldUser["secretKey"];


            if($secretKey == NULL || $secretKey == "NULL") {
                $secretKey = $this->gAuth->generateSecret();
            }

            $newUser = array(
                "email" => $email,
                "password" => $oldUser["password"],
                "salt" => $oldUser["salt"],
                "confirm_code" => $oldUser["confirmcode"],
                "tfa_status" => (($oldUser["2fa"] == "Disabled") ? "n" : "y"),
                "secret_key" => $secretKey,
                "received" => (((float)$balance)) * 1e8,
                "reset_code" => "n",
                "withdrawn" => "0"
            );

            if (!$this->newDbUtil->isExist(USERS_TABLE, "email", $email)) {
                $this->newDbUtil->insert(USERS_TABLE, $newUser);
                $numberOfAdded++;
            } else {
                array_push($isExistList, $email);
            }
            $txList = ($this->getTransactions($email));

            foreach ($txList as $tx) {
                $time = $tx["time"];
                $type = $this->getTxType($tx["category"]);
                $fromAddress = "N/A";
                $amount = $tx["amount"] * 1e8;
                $txId = NULL;
                $toAddress = NULL;
                $account = $tx['account'];

                if ($type == TX_MOVED) {
                    $otherAccount = $tx['otheraccount'];
                    if (compare($otherAccount, "interestsstorage")) {
                        $type = TX_MINED;
                    }
                    $isNegative = ($amount < 0);
                    $fromAddress = $isNegative ? $email : $otherAccount;
                    $toAddress = $isNegative ? $otherAccount : $email;
                    $uniqId = sha1($amount.$type.$email.$account.$time.$toAddress.$fromAddress);
                } else {
                    $txId = $tx['txid'];
                    $uniqId = sha1($amount.$txId.$type.$email.$account.$time.$toAddress.$fromAddress);
                    $toAddress = $tx["address"];
                    if ($type == TX_RECEIVED) {
                        $txData = $this->rpc->getTransaction($txId);
                        $fromAddress = (isset($txData['vout'][0]['scriptPubKey']['addresses'][0])) ? $txData['vout'][0]['scriptPubKey']['addresses'][0] : NULL;
                        if($fromAddress == NULL) {
                            $fromAddress = "N/A";
                        }
                    }
                }

                $calculatedBalance += $amount;
                $item = array(
                    "amount" => $amount,
                    "time" => $time,
                    "email" => $email,
                    "type" => $type,
                    "txid" => $txId,
                    "uniqid" => $uniqId,
                    "blockhash" => (isset($tx['blockhash']) ? $tx['blockhash'] : NULL),
                    "ticker" => TICKER,
                    "from_address" => $fromAddress,
                    "to_address" => $toAddress,
                    "status" => TX_CONFIRMED
                );
                $this->newDbUtil->insert(TRANSACTIONS_TABLE, $item);
            }
            $allBalances += $balance;
            if(($balance != 0 || $calculatedBalance != 0) && $balance >= 1) {
                print_r(array("email" => $email, "balance" => $balance, "calc_balance" => $calculatedBalance / 1e8, "stakeBalance" => $stakeBalance));
            }
        }
        print_r("Added: $numberOfAdded\n");
        print_r("Balances: $allBalances\n");
        print_r("BAD: isExist = ".count($isExistList)."\n");
        print_r($isExistList);
    }

    private function getTxType($category)
    {
        $type = TX_UNKNOWN;
        switch ($category) {
            case "send":
                $type = TX_SENT;
                break;
            case "move":
                $type = TX_MOVED;
                break;
            case "receive":
                $type = TX_RECEIVED;
                break;
        }
        return $type;
    }

    private function getBalance($email)
    {
        return $this->rpc->getBalance($email);
    }

    private function calculateStakeAmount($sql_stAmount, $sql_stTime)
    {
        $cTime = time();
        $seTime = $cTime - $sql_stTime;
        $newAmount = (((float)$sql_stAmount) / 100 * 5 / 365 / 24 / 60 / 60 * ($seTime));
        return $newAmount;
    }

    private function getStakeAmount($oldUser)
    {
        $sql_stAmount = $oldUser["stakeAmount"];
        $sql_stTime = $oldUser["stakeTime"];
        if ((strcasecmp($sql_stAmount, "NULL") != 0) && (strcasecmp($sql_stTime, "NULL") != 0) && is_numeric($sql_stAmount)) {
            return ($sql_stAmount + $this->calculateStakeAmount($sql_stAmount, $sql_stTime));
        }
        return 0;
    }

    private function getTransactions($email)
    {
        return $this->rpc->getListTransactionsByEmail($email);
    }
}
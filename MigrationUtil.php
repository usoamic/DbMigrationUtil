<?php

class MigrationUtil
{
    private $encryptionUtil,
            $encryptionLegacyUtil,
            $oldDbUtil,
            $newDbUtil,
            $rpc;

    public function __construct()
    {
        $this->encryptionUtil = new EncryptionUtil();
        $this->encryptionLegacyUtil = new EncryptionLegacyUtil();
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

        $this->rpc = new RPC();
    }

    public function run() {
        $legacyUsers = $this->oldDbUtil->getRows(USERS_TABLE);

        foreach ($legacyUsers as $oldUser) {
            $calculatedBalance = 0;
            $email = $oldUser["email"];
            $balance = $this->getBalance($email);
            $stakeBalance = $this->getStakeAmount($oldUser);

            $newUser = array(
                  "email" => $email,
                  "password" => $oldUser["password"],
                  "salt" => $oldUser["salt"],
                  "confirm_code" => $oldUser["confirmcode"],
                  "tfa_status" => (($oldUser["2fa"] == "Disabled") ? "n" : "y"),
                  "secret_key" => $oldUser["secretKey"],
                  "received" => (((float) $balance) + ((float) $stakeBalance))*1e8,
                  "reset_code" => "n",
                  "received_by_yobit_codes" => "0",
                  "withdrawn" => "0"
            );
            $this->newDbUtil->insert(USERS_TABLE, $newUser);

            $txList = ($this->getTransactions($email));
            foreach ($txList as $tx) {
                $txId = $tx['txid'];
                $txData = $this->rpc->getTransaction($txId);
                $fromAddress = $txData['vout'][0]['scriptPubKey']['addresses'][0];
                if(!isset($fromAddress)) {
                    $fromAddress = "N/A";
                }

                $amount = $tx["amount"]*1e8;
                $calculatedBalance += $amount;

                $item = array(
                    "amount" => $amount,
                    "time" => $tx["time"],
                    "timereceived" => $tx["timereceived"],
                    "email" => $email,
                    "type" => TX_RECEIVED,
                    "txid" => $tx["txid"],
                    "blockhash" => "",
                    "ticker" => TICKER,
                    "from_address" => $fromAddress,
                    "to_address" => $tx["address"],
                    "status" => TX_CONFIRMED
                );
                $this->newDbUtil->insert(TRANSACTIONS_TABLE, $item);
            }
            print_r(array("email" => $email, "balance" => $balance, "calc_balance" => $calculatedBalance));
        }
    }

    private function getBalance($email) {
        return $this->rpc->getBalance($email);
    }

    private function calculateStakeAmount($sql_stAmount, $sql_stTime) {
        $cTime = time();
        $seTime = $cTime - $sql_stTime;
        $newAmount = (((float)$sql_stAmount)/100*5/365/24/60/60*($seTime));
        return $newAmount;
    }

    private function getStakeAmount($oldUser) {
        $sql_stAmount = $oldUser["stakeAmount"];
        $sql_stTime = $oldUser["stakeTime"];
        if((strcasecmp($sql_stAmount, "NULL") != 0) && (strcasecmp($sql_stTime, "NULL") != 0) && is_numeric($sql_stAmount)) {
            return ($sql_stAmount + $this->calculateStakeAmount($sql_stAmount, $sql_stTime));
        }
        return 0;
    }

    private function getTransactions($email) {
        return $this->rpc->getListTransactionsByEmail($email);
    }
}
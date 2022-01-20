<?php namespace Models\Brokers;

use stdClass;

class CreditCardBroker extends Broker
{
    public function getAll($userId): array {
        $sql = "SELECT id, decrypt(name) as name, decrypt(number) as number, expirationmonth, expirationyear, decrypt(cvc) as cvc FROM credit_card WHERE userid = ? ORDER BY number";
        return $this->select($sql, [$userId]);
    }

    public function getById(int $cardId): stdClass {
        $sql = "SELECT decrypt(name) as name, decrypt(number) as number, expirationmonth, expirationyear, decrypt(cvc) as cvc FROM credit_card WHERE id = ?";
        return $this->selectSingle($sql, [$cardId]);
    }

    public function insertCrediCard(int $userId, stdClass $cardInfo): int {
        $sql= "INSERT INTO credit_card(userid, name, number, expirationmonth, expirationyear, cvc) VALUES (?, encrypt(?), encrypt(?), ?, ?, encrypt(?))";
        $this->query($sql, [
            $userId,
            $cardInfo->name,
            $cardInfo->number,
            $cardInfo->expirationMonth,
            $cardInfo->expirationYear,
            $cardInfo->cvc,
        ]);
        return $this->getDatabase()->getLastInsertedId();
    }

    public function updateCreditCard(stdClass $data, $cardId) {
        $sql= "UPDATE credit_card SET name = encrypt(?), number = encrypt(?), expirationmonth = ?, expirationyear = ?, cvc = encrypt(?) WHERE id = ?";
        $this->query($sql, [
            $data->name,
            $data->number,
            $data->expirationMonth,
            $data->expirationYear,
            $data->cvc,
            $cardId
        ]);
    }

    public function deleteCreditCard($cardId) {
        $sql= "DELETE FROM credit_card WHERE id = ?";
        $this->query($sql, [
            $cardId
        ]);
    }
}
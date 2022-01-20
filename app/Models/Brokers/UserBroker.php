<?php namespace Models\Brokers;

use stdClass;
use Zephyrus\Security\Cryptography;

class UserBroker extends Broker
{
    public function authenticate(string $email, string $password): ?stdClass {
        $sql = "SELECT id, decrypt(u.firstname) as firstName, decrypt(u.lastName) as lastName, decrypt(u.email) as email, a.password FROM \"user\" u JOIN authentication a on u.id = a.userid WHERE decrypt(u.email) = ?";
        $user = $this->selectSingle($sql, [$email]);
        if(is_null($user)) {
            return null;
        }
        if(!Cryptography::verifyHashedPassword($password, $user->password)) {
            return null;
        }
        return $user;
    }

    public function createUser($userInfo): int {
        $sql = "INSERT INTO \"user\" VALUES (DEFAULT, encrypt(?), encrypt(?), encrypt(?))";
        $this->query($sql, [
            $userInfo->firstName,
            $userInfo->lastName,
            $userInfo->email,
        ]);

        return $this->getDatabase()->getLastInsertedId();
    }

    public function addUserPassword(int $userId, string $password) {
        $sql = "INSERT INTO authentication (userId, password) VALUES (?, ?)";
        $this->query($sql, [
            $userId,
            $password
        ]);
    }

    public function findByEmail($userEmail): ?stdClass {
        $sql = "SELECT id, decrypt(firstName) as firstName, decrypt(lastName) as lastName, decrypt(email) as email  from \"user\" where decrypt(email) = ?";
        return $this->selectSingle($sql, [$userEmail]);
    }
}
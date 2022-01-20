<?php namespace Models\Brokers;

use stdClass;

class PasswordBroker extends Broker
{
    public function getAll(int $userId): array {
        $sql = "SELECT w.id, decrypt(w.url) as url, w.isfavorite, decrypt(w.username) as username, decrypt(p.password) as password FROM \"user\" u 
                JOIN user_website uw ON u.id = uw.userid
                JOIN website w ON w.id = uw.websiteid 
                JOIN password p ON p.id = w.id
                WHERE u.id = ? ORDER BY p.datetime";
        return $this->select($sql, [$userId]);
    }

    public function getAllFavorite(int $userId): array {
        $sql = "SELECT w.id, decrypt(w.url) as url, w.isfavorite, decrypt(w.username) as username, decrypt(p.password) as password FROM \"user\" u 
                JOIN user_website uw ON u.id = uw.userid
                JOIN website w ON w.id = uw.websiteid 
                JOIN password p ON p.id = w.id
                WHERE u.id = ? AND w.isfavorite = true ORDER BY p.datetime";
        return $this->select($sql, [$userId]);
    }

    public function getById(int $websiteId): stdClass {
        $sql = "SELECT decrypt(w.url) as url, decrypt(w.username) as username, decrypt(p.password) as password FROM website w JOIN password p on w.id = p.id WHERE w.id = ?";
        return $this->selectSingle($sql, [$websiteId]);
    }

    public function getByUrl(string $url, int $userId): ?stdClass {
        $sql = "SELECT decrypt(w.url) as url, decrypt(w.username) as username, decrypt(p.password) as password, w.usernamefieldid, w.passwordfieldid
                FROM  user_website uw 
                JOIN website w ON uw.websiteid = w.id
                JOIN password p on w.id = p.id
                WHERE decrypt(w.url) = ? AND uw.userid = ?";
        return $this->selectSingle($sql, [$url, $userId]);
    }

    public function insertPassword(string $password, int $websiteId): int {
        $sql= "INSERT INTO password(id, password) VALUES (?, encrypt(?))";
        $this->query($sql, [
            $websiteId,
            $password
        ]);
        return $this->getDatabase()->getLastInsertedId();
    }

    public function insertWebsite(string $url, string $username, bool $isFavorite): int {
        $sql= "INSERT INTO website(url, username, isfavorite) VALUES (encrypt(?), encrypt(?), ?)";
        $this->query($sql, [
            $url,
            $username,
            $isFavorite
        ]);
        return $this->getDatabase()->getLastInsertedId();
    }

    public function insertUserWebsite(int $userId, int $websiteId) {
        $sql= "INSERT INTO user_website(userid, websiteid) VALUES (?, ?)";
        $this->query($sql, [
            $userId,
            $websiteId
        ]);
    }

    public function updatePassword(string $password, int $websiteId) {
        $sql= "UPDATE password SET password = encrypt(?) WHERE id = ?";
        $this->query($sql, [
            $password,
            $websiteId
        ]);
    }

    public function updateWebsite(string $url, string $username, $websiteId) {
        $sql= "UPDATE website SET url = encrypt(?), username = encrypt(?) WHERE id = ?";
        $this->query($sql, [
            $url,
            $username,
            $websiteId
        ]);
    }

    public function addFavoriteWebsite($websiteId) {
        $sql= "UPDATE website SET isfavorite = true WHERE id = ?";
        $this->query($sql, [
            $websiteId
        ]);
    }

    public function removeFavoriteWebsite($websiteId) {
        $sql= "UPDATE website SET isfavorite = false WHERE id = ?";
        $this->query($sql, [
            $websiteId
        ]);
    }


    public function deleteWebsite($websiteId) {
        $sql= "DELETE FROM website WHERE id = ?";
        $this->query($sql, [
            $websiteId
        ]);
    }

    public function sharePassword($userId, $passwordId) {
        $sql= "INSERT INTO user_website VALUES (?, ?)";
        $this->query($sql, [
            $userId,
            $passwordId
        ]);
    }
}
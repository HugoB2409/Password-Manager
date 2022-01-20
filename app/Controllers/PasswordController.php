<?php namespace Controllers;

use Models\Brokers\PasswordBroker;
use Models\Brokers\UserBroker;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Rule;
use Zephyrus\Application\Session;
use Zephyrus\Security\Cryptography;

class PasswordController extends Controller
{
    public function initializeRoutes()
    {
        $this->get("/newPassword", "addPassword");
        $this->get("/password/{id}", "editPassword");
        $this->get("/sharePassword/{id}", "sharePassword");
        $this->post("/getWebsite", "getWebsite");

        $this->post("/authenticate", "authenticateExtension");
        $this->post("/addFavorite/{id}", "addFavorite");
        $this->post("/removeFavorite/{id}", "removeFavorite");
        $this->post("/newPassword", "newPassword");
        $this->post("/updatePassword/{id}", "updatePassword");
        $this->post("/deletePassword/{id}", "deletePassword");
        $this->post("/sharePassword/{id}", "sendPassword");
    }

    public function authenticateExtension()
    {
        $user = (new UserBroker())->authenticate($this->request->getParameter("username"), $this->request->getParameter("password"));
        if(is_null($user)) {
            return $this->json("");
        }
        $key = Cryptography::encrypt($user->id, "SDbjjhb23jksadKJAHS");
        return $this->json($key);
    }

    public function getWebsite()
    {
        $id = Cryptography::decrypt($this->request->getParameter("key"), "SDbjjhb23jksadKJAHS");
        $website = (new PasswordBroker())->getByUrl($this->request->getParameter("url"), (int)$id);
        return $this->json($website);
    }

    public function addPassword()
    {
        return $this->render("addPassword", [
            'title' => 'Nouveau mot de passe'
        ]);
    }

    public function editPassword($id)
    {
        $data = (new PasswordBroker())->getById($id);
        return $this->render("editPassword", [
            'title' => 'Modifier mot de passe',
            'websiteId' => $id,
            'data' => $data
        ]);
    }

    public function sharePassword($passwordId)
    {
        return $this->render("sharePassword", [
            'title' => 'Partager mot de passe',
            'passwordId' => $passwordId
        ]);
    }

    public function newPassword()
    {
        $form = $this->buildForm();
        $form->validate('url', Rule::notEmpty("Le site web ne doit pas etre vide."));
        $form->validate('username', Rule::notEmpty("Le nom d'utilisateur ne doit pas etre vide."));

        if(!$form->verify()) {
            Flash::error($form->getErrorMessages());
            return $this->redirect("/newPassword");
        }

        $data = $form->buildObject();
        if(!isset($data->isFavorite)) {
            $isFavorite = false;
        } else {
            $isFavorite = true;
        }

        if(isset($data->isGenerated)) {
            $data->password = Cryptography::randomString(12);
        }

        $broker = new PasswordBroker();
        $userId = Session::getInstance() ->read("userId");
        $websiteId = $broker->insertWebsite($data->url, $data->username, $isFavorite);
        $broker->insertPassword($data->password, $websiteId);
        $broker->insertUserWebsite($userId, $websiteId);

        Flash::success("Mot de passe ajouter.");
        return $this->redirect("/");
    }

    public function updatePassword($websiteId)
    {
        $form = $this->buildForm();
        $form->validate('url', Rule::notEmpty("Le site web ne doit pas etre vide."));
        $form->validate('username', Rule::notEmpty("Le nom d'utilisateur ne doit pas etre vide."));
        $form->validate('password', Rule::notEmpty("Le mot de passe ne doit pas etre vide."));

        if(!$form->verify()) {
            Flash::error($form->getErrorMessages());
            return $this->redirect("/editPassword/" + $websiteId);
        }

        $data = $form->buildObject();

        $broker = new PasswordBroker();
        $broker->updateWebsite($data->url, $data->username, $websiteId);
        $broker->updatePassword($data->password, $websiteId);

        Flash::success("Mot de passe modifier.");
        return $this->redirect("/");
    }

    public function addFavorite($websiteId)
    {
        (new PasswordBroker())->addFavoriteWebsite($websiteId);
        Flash::success("Ajouter au favoris");
        return $this->redirect("/");
    }

    public function removeFavorite($websiteId)
    {
        (new PasswordBroker())->removeFavoriteWebsite($websiteId);
        Flash::success("Supprimer des favoris");
        return $this->redirect("/");
    }

    public function deletePassword($websiteId)
    {
        (new PasswordBroker())->deleteWebsite($websiteId);
        Flash::success("Information supprimer");
        return $this->redirect("/");
    }

    public function sendPassword($passwordId)
    {
        $form = $this->buildForm();
        $data = $form->buildObject();

        print_r($data);
        $user = (new UserBroker())->findByEmail($data->email);
        if($user == null) {
            Flash::error("Aucun utilisateur trouver.");
            return $this->redirect("/sharePassword/" . $passwordId);
        }

        (new PasswordBroker())->sharePassword($user->id, $passwordId);
        Flash::success("Mot de passe partager");
        return $this->redirect("/");
    }
}
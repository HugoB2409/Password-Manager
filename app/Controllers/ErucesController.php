<?php namespace Controllers;

use Models\Brokers\CreditCardBroker;
use Models\Brokers\PasswordBroker;
use Models\Brokers\UserBroker;
use Models\TableObject;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Rule;
use Zephyrus\Application\Session;
use Zephyrus\Security\Cryptography;

class ErucesController extends Controller
{
    public function initializeRoutes() {
        $this->get("/", "homePage");
        $this->get("/login", "loginPage");
        $this->get("/signup", "signupPage");
        $this->get("/logout", "logout");

        $this->post("/login", "connect");
        $this->post("/signup", "createUser");
    }

    public function homePage()
    {
        $userId = Session::getInstance() ->read("userId");
        $favorites = (new PasswordBroker())->getAllFavorite($userId);
        $website = (new PasswordBroker())->getAll($userId);
        $creditCard = (new CreditCardBroker())->getAll($userId);
        return $this->render("homePage", [
            'title' => '',
            'favoritesTable' => new TableObject(["Site Web", "Nom d'utilisateur", "Mot de passe (Cliquer pour copier)", "Actions"], $favorites),
            'passwordsTable' => new TableObject(["Site Web", "Nom d'utilisateur", "Mot de passe (Cliquer pour copier)", "Actions"], $website),
            'creditCardsTable' => new TableObject(["Nom", "Numéro", "Expiration", "CVC", "Actions"], $creditCard),
        ]);
    }

    public function loginPage()
    {
        return $this->render("login", [
            'title' => 'Connection'
        ]);
    }

    public function signupPage()
    {
        return $this->render("signup", [
            'title' => 'Inscription'
        ]);
    }

    public function connect()
    {
        $form = $this->buildForm();
        $form->validate('email', Rule::notEmpty("Le email ne doit pas etre vide."));
        //$form->validate('email', Rule::email("Le email n'est pas valide."));
        $form->validate('password', Rule::notEmpty("Le mot de passe ne doit pas etre vide."));

        if(!$form->verify()) {
            Flash::error($form->getErrorMessages());
            return $this->redirect("/login");
        }

        $credentials = $form->buildObject();
        $user = (new UserBroker())->authenticate($credentials->email, $credentials->password);
        if(is_null($user)) {
            Flash::error("Information d'authentification invalide.");
            return $this->redirect("/login");
        }

        Session::getInstance()->set("userId", $user->id);
        return $this->redirect("/");
    }

    public function logout() {
        Session::getInstance()->destroy();
        Flash::error("Vous etes deconnecter.");
        return $this->redirect("/login");
    }

    public function createUser()
    {
        $form = $this->buildForm();
        $form->validate('email', Rule::notEmpty("Le email ne doit pas etre vide."));
        //$form->validate('email', Rule::email("Le email n'est pas valide."));
        $form->validate('username', Rule::notEmpty("Le surnom ne doit pas etre vide."));
        $form->validate('firstName', Rule::notEmpty("Le prénom ne doit pas etre vide."));
        $form->validate('lastName', Rule::notEmpty("Le nom de famille ne doit pas etre vide."));
        $form->validate('password', Rule::notEmpty("Le mot de passe ne doit pas etre vide."));

        if(!$form->verify()) {
            Flash::error($form->getErrorMessages());
            return $this->redirect("/signup");
        }

        $broker = new UserBroker();
        $data = $form->buildObject();
        $userId = $broker->createUser($data);
        $broker->addUserPassword($userId, Cryptography::hashPassword($data->password));
        Session::getInstance()->set("userId", $userId);
        return $this->redirect("/");
    }
}
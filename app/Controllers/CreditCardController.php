<?php namespace Controllers;

use Models\Brokers\CreditCardBroker;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Rule;
use Zephyrus\Application\Session;

class CreditCardController extends Controller
{
    public function initializeRoutes()
    {
        $this->get("/newCreditCard", "addCreditCard");
        $this->get("/creditCard/{id}", "editCreditCard");

        $this->post("/newCreditCard", "newCreditCard");
        $this->post("/updateCreditCard/{id}", "updateCreditCard");
        $this->post("/deleteCreditCard/{id}", "deleteCreditCard");
    }

    public function addCreditCard()
    {
        return $this->render("addCreditCard", [
            'title' => 'Nouvelle carte de credit'
        ]);
    }

    public function editCreditCard($id)
    {
        $data = (new CreditCardBroker())->getById($id);
        return $this->render("editCreditCard", [
            'title' => 'Modifier carte de credit',
            'data' => $data,
            'cardId' => $id
        ]);
    }
    public function newCreditCard()
    {
        $form = $this->buildForm();
        $form->validate('name', Rule::notEmpty("Le nom sur la carte ne doit pas etre vide."));
        $form->validate('number', Rule::notEmpty("Le numero de carte ne doit pas etre vide."));
        $form->validate('expirationMonth', Rule::notEmpty("Le mois d'expiration ne doit pas etre vide."));
        $form->validate('expirationYear', Rule::notEmpty("L'annee d'expiration ne doit pas etre vide."));
        $form->validate('cvc', Rule::notEmpty("Le code cvc ne doit pas etre vide."));

        if(!$form->verify()) {
            Flash::error($form->getErrorMessages());
            return $this->redirect("/newCreditCard");
        }

        $userId = Session::getInstance()->read("userId");
        (new CreditCardBroker())->insertCrediCard($userId, $form->buildObject());

        Flash::success("Carte de credit ajouter.");
        return $this->redirect("/");
    }

    public function updateCreditCard($id)
    {
        $form = $this->buildForm();
        $form->validate('name', Rule::notEmpty("Le nom sur la carte ne doit pas etre vide."));
        $form->validate('number', Rule::notEmpty("Le numero de carte ne doit pas etre vide."));
        $form->validate('expirationMonth', Rule::notEmpty("Le mois d'expiration ne doit pas etre vide."));
        $form->validate('expirationYear', Rule::notEmpty("L'annee d'expiration ne doit pas etre vide."));
        $form->validate('cvc', Rule::notEmpty("Le code CVC ne doit pas etre vide."));

        if(!$form->verify()) {
            Flash::error($form->getErrorMessages());
            return $this->redirect("/editCreditCard/" + $id);
        }

        $data = $form->buildObject();

        (new CreditCardBroker())->updateCreditCard($data, $id);

        Flash::success("Carte de credit modifier.");
        return $this->redirect("/");
    }

    public function deleteCreditCard($cardId)
    {
        (new CreditCardBroker())->deleteCreditCard($cardId);
        Flash::success("Carte supprimer.");
        return $this->redirect("/");
    }
}
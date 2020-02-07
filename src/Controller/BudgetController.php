<?php

namespace App\Controller;

use App\Entity\Budget;
use App\Entity\BusinessTraffic;
use App\Entity\Category;
use App\Entity\Currency;
use App\Entity\Schedule;
use App\Entity\User;
use DateTime;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BudgetController extends AbstractController
{

  /**
   * @Route("/budgets", name="app_budgets")
   */
  public function viewBudgets(){

    $budgets = $this->getDoctrine()->getRepository(Budget::class);
    $budgets = $budgets->findAll();

    return $this->render('budget/budgets.html.twig', [
      'title' => 'Budsafes',
      'pageName' => 'Budgets',
      'budgets' => $budgets
    ]);

  }

  private function newBudgetCard($budget){
    return $this->render('budget/newBudgetCard.html.twig', [
      'budget' => $budget
    ]);
  }

  /**
   * @Route("budget/new/", name="app_budget_new", methods={"POST"})
   * @param ValidatorInterface $validator
   * @return JsonResponse
   * @throws \Exception
   */
  public function newBudget(ValidatorInterface $validator){
    $entityManager = $this->getDoctrine()->getManager();
    $request = Request::createFromGlobals();
    $post = $request->request;

    $budgetName = $post->get('budgetName');
    $user = $this->getDoctrine()->getRepository(User::class)->find($post->get('user'));
    $created = date('Y/m/d H:i');
    $active = $post->get('active');

    if($active == 1){
      $budgetCurrentActive = $this->getDoctrine()->getRepository(Budget::class)->findBy(['active' => 1]);
      if(count($budgetCurrentActive) > 0){
        $budgetCurrentActive[0]->setActive(0);
        $entityManager->persist($budgetCurrentActive[0]);
      }
    }

    $budget = new Budget();
    $budget->setName($budgetName);
    $budget->setUser($user);
    $budget->setCreated(new DateTime($created));
    $budget->setActive($active);

    $errors = $validator->validate($budget);
    if(count($errors) > 0)
      return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

    try{
      $entityManager->persist($budget);
      $entityManager->flush();
    }catch (DBALException $exception){
      return $this->json(['result' => false, 'message' => 'Errore, sembra che qualcosa sia andato storto durante il salvataggio del Budget']);
    }

    return $this->json(['result' => true, 'message' => 'Nuovo Budget aggiunto con succeso', 'HTMLData' => $this->newBudgetCard($budget)]);
  }

    /**
   * @Route("/budget/active/", name="app_budget_active", methods={"POST"})
   * @return JsonResponse
   */
  public function activeBudget(){
    $entityManager = $this->getDoctrine()->getManager();
    $request = Request::createFromGlobals();
    $post = $request->request;
    $budget = $this->getDoctrine()->getRepository(Budget::class);
    $budgetToActive = $budget->find($post->get('budget'));

    if(empty($budget->findBy(['active' => 1]))) {
      $budgetToActive->setActive(1);
    }else {

      $budgetCurrentActive = $budget->findBy(['active' => 1])[0];
      if ($budgetToActive === null)
        return $this->json(['result' => false, 'message' => 'Errore, nessun Budget trovato con id: ' . $post->get('budget')]);

      $budgetToActive->setActive(1);
      $budgetCurrentActive->setActive(0);
    }

    try {
      $entityManager->flush();
    }catch (DBALException $exception){
      return $this->json(['result' => false, 'message' => 'Errore, sembra che qualcosa sia andato storto durante l\'attivazione di questo budget']);
    }

    return $this->json(['result' => true, 'message' => 'Il Budget con id '.$post->get('budget').' è stato attivato corretamente']);
  }

  /**
   * @Route("/rename/budget/", name="app_budget_rename", methods={"POST"})
   * @param ValidatorInterface $validator
   * @return JsonResponse
   */
  public function renameBudget(ValidatorInterface $validator){
    $entityManager = $this->getDoctrine()->getManager();
    $request = Request::createFromGlobals();
    $post = $request->request;
    $id = $post->get('id');
    $newBudgetName = $post->get('budgetName');
    $budget = $this->getDoctrine()->getRepository(Budget::class)->find($id);

    if($budget === null)
      return $this->json(['result' => false, 'message' => 'Errore, nessun Budget trovato con id: '.$post->get('id')]);

    $budget->setName($newBudgetName);
    $errors = $validator->validate($budget);

    if(count($errors) > 0)
      return $this->json(['result' => false, 'message' => 'Errore, i dati inseriti non sembrano corretti']);

    try{
      $entityManager->persist($budget);
      $entityManager->flush();
    }catch (DBALException $exception){
      return $this->json(['result' => false, 'message' => 'Errore, si sono verificati degli imprevisti durante la rinomina del budget si prega di riprovare']);
    }

    return $this->json(['result' => true, 'message' => 'Rinomina del budget effettuata con successo']);
  }


  /**
   * @Route("/budget/delete", name="app_budget_delete", methods={"POST"})
   * @return JsonResponse
   */
  public function deleteBudget(){
    $entityManager = $this->getDoctrine()->getManager();
    $request = Request::createFromGlobals();
    $post = $request->request;
    $budgetRepository = $this->getDoctrine()->getRepository(Budget::class);
    //get budget current active
    $budgetActiveID = $budgetRepository->findBy(['active' => 1])[0]->getId();
    $id = $post->get('id');
    if($budgetActiveID == $id)
      return $this->json(['result' => false, 'message' => 'Attenzione, non è possibile eliminare il Budget al momento attivo!']);

    $budget = $this->getDoctrine()->getRepository(Budget::class)->find($id);
    //delete all data that required that budget
    $deleteAllBusinessTrafficDataOfBudget = $this->getDoctrine()->getRepository(BusinessTraffic::class)->deleteAllBusinessTrafficDataOfBudget($id);
    $deleteAllCategoryDataOfBudget = $this->getDoctrine()->getRepository(Category::class)->deleteAllCategoryDataOfBudget($id);

    if($deleteAllBusinessTrafficDataOfBudget === false  && $deleteAllCategoryDataOfBudget === false)
      return $this->json(['result' => false, 'message' => 'Errore, non è stato possibile eliminare i dati collegati a questo budget']);

    $entityManager->remove($budget);
    try {
      $entityManager->flush();
    }catch (DBALException $exception){
      return $this->json(['result' => false, 'message' => 'Errore, questo budget non risulta essere registrato']);
    }
    return $this->json(['result' => true, 'message' => 'Eliminazione del budget effettuata con successo']);
  }

    /**
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function newCurrency(ValidatorInterface $validator){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;

      $currency = new Currency();

      $name = $post->get('name');
      $symbol = $post->get('symbol');
      $value = $post->get('value');

      $currency->setName($name);
      $currency->setSymbol($symbol);
      $currency->setValue($value);

      //validate input
      $errors = $validator->validate($currency);
      if(count($errors) > 0)
        return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

      $entityManager->persist($currency);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo']);
    }

    /**
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function editCurrency(ValidatorInterface $validator){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;
      $id = $post->get('id');
      $currency = $this->getDoctrine()->getRepository(Currency::class)->find($id);

      $name = $post->get('name');
      $symbol = $post->get('symbol');
      $value = $post->get('value');

      $currency->setName($name);
      $currency->setSymbol($symbol);
      $currency->setValue($value);

      //validate input
      $errors = $validator->validate($currency);
      if(count($errors) > 0)
        return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

      $entityManager->persist($currency);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo']);
    }

    /**
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function deleteCurrency(ValidatorInterface $validator){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;

      $currency = $this->getDoctrine()->getRepository(Currency::class);

      $id = $post->get('id');
      $currency->find($id);
      $entityManager->remove($currency);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo']);
    }

    /**
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function newSchedule(ValidatorInterface $validator){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;

      $categoryRepository = $this->getDoctrine()->getRepository(Category::class);
      $currencyRepository = $this->getDoctrine()->getRepository(Currency::class);

      $schedule = new Schedule();

      $category = $categoryRepository->find($post->get('idCategory'));
      $currency = $currencyRepository->find($post->get('idCurrency'));
      $amount = $post->get('amount');
      $description = $post->get('description');
      $firstInvoice = $post->get('firstInvoice');
      $cycle = $post->get('cycle');

      $schedule->setCategory($category);
      $schedule->setCurrency($currency);
      $schedule->setAmount($amount);
      $schedule->setDescription($description);
      $schedule->setFirstInvoice($firstInvoice);
      $schedule->setCycle($cycle);


      //validate input
      $errors = $validator->validate($schedule);
      if(count($errors) > 0)
        return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

      $entityManager->persist($schedule);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo']);
    }

    /**
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function addBusinessTraffic(ValidatorInterface $validator){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;

      $categoryRepository = $this->getDoctrine()->getRepository(Category::class);
      $currencyRepository = $this->getDoctrine()->getRepository(Currency::class);

      $businessTraffic = new BusinessTraffic();

      $category = $categoryRepository->find($post->get('idCategory'));
      $currency = $currencyRepository->find($post->get('idCurrency'));
      $amount = $post->get('amount');
      $description = $post->get('description');
      $added = $post->get('added');

      $businessTraffic->setCategory($category);
      $businessTraffic->setCurrency($currency);
      $businessTraffic->setAmount($amount);
      $businessTraffic->setDescription($description);
      $businessTraffic->setAdded($added);

      //validate input
      $errors = $validator->validate($businessTraffic);
      if(count($errors) > 0)
        return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

      $entityManager->persist($businessTraffic);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo']);
    }

    /**
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function editBusinessTraffic(ValidatorInterface $validator){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;

      $categoryRepository = $this->getDoctrine()->getRepository(Category::class);
      $currencyRepository = $this->getDoctrine()->getRepository(Currency::class);

      $id = $post->get('id');
      $businessTraffic = $this->getDoctrine()->getRepository(BusinessTraffic::class)->find($id);

      $category = $categoryRepository->find($post->get('idCategory'));
      $currency = $currencyRepository->find($post->get('idCurrency'));
      $amount = $post->get('amount');
      $description = $post->get('description');
      $added = $post->get('added');

      $businessTraffic->setCategory($category);
      $businessTraffic->setCurrency($currency);
      $businessTraffic->setAmount($amount);
      $businessTraffic->setDescription($description);
      $businessTraffic->setAdded($added);

      //validate input
      $errors = $validator->validate($businessTraffic);
      if(count($errors) > 0)
        return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

      $entityManager->persist($businessTraffic);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo']);
    }

    /**
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function deleteBusinessTraffic(ValidatorInterface $validator){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;

      $id = $post->get('id');
      $businessTraffic = $this->getDoctrine()->getRepository(BusinessTraffic::class)->find($id);
      $entityManager->remove($businessTraffic);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo']);
    }

    /**
     * @param ValidatorInterface $validator
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     */
    public function newUser(ValidatorInterface $validator, UserPasswordEncoderInterface $encoder){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;

      $user = new User();

      $name = $post->get('name');
      $surname = $post->get('surname');
      $email = $post->get('email');
      $password = $post->get('password');
      $passwordEncoded = $encoder->encodePassword($user, $password);
      $imageProfile = $post->get('imageProfile');
      $work = $post->get('work');
      $birthday = $post->get('birthday');
      $created = $post->get('created');
      $gender = $post->get('gender');

      $user->setName($name);
      $user->setSurname($surname);
      $user->setEmail($email);
      $user->setPassword($passwordEncoded);
      $user->setImageProfile($imageProfile);
      $user->setWork($work);
      $user->setBirthday($birthday);
      $user->setCreated($created);
      $user->setGender($gender);
      $user->setRoles(['ROLE_USER']);

      //validate input
      $errors = $validator->validate($user);
      if(count($errors) > 0)
        return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

      $entityManager->persist($user);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo']);
    }

    /**
     * @param ValidatorInterface $validator
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     */
    public function editUser(ValidatorInterface $validator, UserPasswordEncoderInterface $encoder){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;

      $id = $post->get('id');
      $user = $this->getDoctrine()->getRepository(User::class)->find($id);

      $name = $post->get('name');
      $surname = $post->get('surname');
      $email = $post->get('email');
      $password = $post->get('password');
      $passwordEncoded = $encoder->encodePassword($user, $password);
      $imageProfile = $post->get('imageProfile');
      $work = $post->get('work');
      $birthday = $post->get('birthday');
      $created = $post->get('created');
      $gender = $post->get('gender');

      $user->setName($name);
      $user->setSurname($surname);
      $user->setEmail($email);
      $user->setPassword($passwordEncoded);
      $user->setImageProfile($imageProfile);
      $user->setWork($work);
      $user->setBirthday($birthday);
      $user->setCreated($created);
      $user->setGender($gender);
      $user->setRoles(['ROLE_USER']);

      //validate input
      $errors = $validator->validate($user);
      if(count($errors) > 0)
        return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

      $entityManager->persist($user);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo']);
    }

    /**
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function deleteUser(ValidatorInterface $validator){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;

      $id = $post->get('id');
      $user = $this->getDoctrine()->getRepository(User::class)->find($id);

      $entityManager->remove($user);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo']);
    }

    /**
     * @return JsonResponse
     */
    public function percentualSpentCategoryByBudget(){
      $request = Request::createFromGlobals();
      $post = $request->request;
      $idBudget = $post->get('id');
      $budget = $this->getDoctrine()->getRepository(Budget::class);
      $categories = $budget->find($idBudget)->getCategories();

      $resultArray = [];
      $totAmountCategorySpent = null;
      foreach ($categories as $category){
        $amountCategoryScheduled = $category->getSchedules()[0]->getAmount();
        $businessTrafficsOfCategory = $category->getBusinessTraffic();
        foreach ($businessTrafficsOfCategory as $businessTrafficOfCategory){
          $totAmountCategorySpent = $totAmountCategorySpent + $businessTrafficOfCategory->getAmount();
        }

        $percentSpentCategory = ($totAmountCategorySpent / $amountCategoryScheduled) * 100;
        $resultArray[] = array('id_category' => $category['id'], 'name_category' => $category['name'],
          'totAmountCategorySpent' => $totAmountCategorySpent, 'percentualSpentCategory' => $percentSpentCategory);
      }

      if(count($resultArray) > 0)
        return $this->json(['result' => true, 'message' => 'Ci sono '.count($resultArray).' risultati', 'data' => $resultArray]);
      else
        return $this->json(['result' => false, 'message' => 'Non ci sono risultati', 'data' => $resultArray]);
    }

    /**
     * @return JsonResponse
     */
    public function getTotalFromPeriodByType(){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;
      $businessTraffic = $this->getDoctrine()->getRepository(BusinessTraffic::class);
      $idBudget = $post->get('idBudget');
      $dateFrom = $post->get('dateFrom');
      $dateTo = $post->get('dateTo');
      $type = $post->get('type');

      if(empty($dateTo))
        $dateTo = $dateFrom;

      $businessTrafficDataIncomes = $businessTraffic->findBusinessTrafficDataByType($type, $idBudget);

      $totalIncome = null;
      foreach ($businessTrafficDataIncomes as $businessTrafficDataIncome){
        try {
          $businessTrafficDataIncomeDateAdded = new DateTime($businessTrafficDataIncome['added']);
          $businessTrafficDataIncomeDateAdded = $businessTrafficDataIncome->format('Y-m-d');
          if($businessTrafficDataIncomeDateAdded >= $dateFrom && $businessTrafficDataIncomeDateAdded <= $dateTo){
            $totalIncome = $totalIncome + $businessTrafficDataIncome['amount'];
          }
        } catch (\Exception $e) {
          return $this->json(['result' => false, 'message' => 'Conversione errata della DateTime', 'data' => $totalIncome]);
        }
      }

      return $this->json(['result' => true, 'message' => 'Ricerca effettuata con successo', 'data' => $totalIncome]);
    }

    /**
     * @return JsonResponse
     */
    public function getBusinessTrafficOfPeriod(){
      $request = Request::createFromGlobals();
      $post = $request->request;
      $businessTraffic = $this->getDoctrine()->getRepository(BusinessTraffic::class);
      $dateFrom = $post->get('dateFrom');
      $dateTo = $post->get('dateTo');

      if(empty($dateTo))
        $dateTo = $dateFrom;

      $businessTrafficDataFromPeriod = $businessTraffic->findBusinessTrafficDataFromPeriod($dateFrom, $dateTo);
      if(count($businessTrafficDataFromPeriod) > 0)
        return $this->json(['result' => true, 'message' => 'Ricerca effettuata con successo', 'data' => $businessTrafficDataFromPeriod]);
      else
        return $this->json(['result' => false, 'message' => 'Non sono stati trovati dati per quel periodo', 'data' => $businessTrafficDataFromPeriod]);
    }
}

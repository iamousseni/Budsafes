<?php

namespace App\Controller;

use App\Entity\Budget;
use App\Entity\BusinessTraffic;
use App\Entity\Category;
use App\Entity\Currency;
use DateTime;
use mysql_xdevapi\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BusinessTrackingController extends AbstractController
{

    /**
     * @Route("/", name="app_budget")
     */
    public function index()
    {
      $budgetEntity = $this->getDoctrine()->getRepository(Budget::class);
      $budget = $budgetEntity->findBy(['active' => '1'])[0];
      $categories = $budget->getCategories();
      $currencyEntity = $this->getDoctrine()->getRepository(Currency::class);
      $currencies = $currencyEntity->findAll();

      return $this->render('business_traffic/businessTracking.html.twig', [
        'title' => 'Budsafes',
        'pageName' => 'Business Tracking',
        'budget' => $budget,
        'businessTrafficsData' => $this->groupedBusinessTrafficByMonths(),
        'categories' => $categories,
        'currencies' => $currencies,
      ]);
    }

    /**
     * @Route("/percentual/spent/", name="app_business_taffic_percentual_spent", methods="POST")
     * @return JsonResponse
     */
    public function viewPercentualSpentCategory(){
        $request = Request::createFromGlobals();
        $post = $request->request;
        $idBudget = $post->get('id');
        $param = $post->get('param');
        $month = $post->get('month');
        $percentualSpentCategoryByBudget = $this->percentualSpentCategoryByBudget($idBudget, $month, $param);

        if($percentualSpentCategoryByBudget === false)
            return $this->json(['result' => false, 'message' => 'Attenzione, sembra che non hai ancora nessun dato registrato nella Business Traffic']);

        $HTMLData = $this->render('business_traffic/percentualsSpentCategories.html.twig', [
            'percentualsSpentCategories' => $percentualSpentCategoryByBudget,
            'totalCashRemainToSpent' => $this->formatPrice($this->totalCashRemainToSpent($idBudget, $month)),
            'businessTrafficsData' => $this->groupedBusinessTrafficByMonths(),
        ]);

        return $this->json(['result' => true, 'HTMLData' => $HTMLData]);
    }

    private function formatPrice($price){
        if(floatval($price) != intval($price))
            return number_format($price, 2, ',', '.');

        return number_format($price, 0, ',', '.');
    }

    private function percentualSpentCategoryByBudget($budegt_id, $month,  $param = null){
        $categoryRepository = $this->getDoctrine()->getRepository(Category::class);
        $categories = array();

        if($param == '0')
            $categories = $categoryRepository->findIncomesBusinessTrafficByBudget($budegt_id, $month);
        elseif ($param == 1)
            $categories = $categoryRepository->findOutcomesBusinessTrafficByBudget($budegt_id, $month);
        elseif ($param == 2)
            $categories = $categoryRepository->findSavesBusinessTrafficByBudget($budegt_id, $month);
        elseif($param === null || empty($param))
            $categories = $categoryRepository->findAllCategoriesBusinessTrafficByBudget($budegt_id, $month);

        $oldCategoryType = null;
        $totAmountCategorySpent = null;
        $resultArray = [];
        $categoriesSpent = [];
        if(!empty($categories))
            $lastIndex = count($categories) - 1;

            foreach ($categories as $index => $category) {
                if ($index === 0)
                    $oldCategoryType = $category->getType();

                $totAmountCategorySpent = 0;

                $businessTrafficsOfCategory = $category->getBusinessTraffic();
                foreach ($businessTrafficsOfCategory as $businessTrafficOfCategory) {
                    $totAmountCategorySpent = $totAmountCategorySpent + $businessTrafficOfCategory->getAmount();
                }

                $categoryScheduled = $category->getSchedules()[0];
                if (!empty($categoryScheduled)) {
                    $amountCategoryScheduled = $categoryScheduled->getAmount();
                    $percentSpentCategory = round(($totAmountCategorySpent / $amountCategoryScheduled) * 100);
                    if($oldCategoryType == 0 || $oldCategoryType == 2)
                        $cashRemainToSpent = $totAmountCategorySpent - $amountCategoryScheduled;
                    else
                        $cashRemainToSpent = $amountCategoryScheduled - $totAmountCategorySpent;
                } else {
                    $cashRemainToSpent = $totAmountCategorySpent;
                    $amountCategoryScheduled = null;
                    $percentSpentCategory = null;
                }

                if ($oldCategoryType != $category->getType() && ($param === null || empty($param))) {
                    $resultArray[$oldCategoryType] = array('categoriesSpent' => $categoriesSpent, 'totalSpent' => $this->formatPrice($this->totalCashRemainToSpent($budegt_id, $month, $oldCategoryType)), 'type' => $oldCategoryType);
                    $categoriesSpent = [];
                    $oldCategoryType = $category->getType();
                }

                $categoriesSpent[] = array('id' => $category->getId(), 'name' => $category->getName(), 'type' => $category->getType(),
                    'totAmountCategoryScheduled' => $this->formatPrice($amountCategoryScheduled),
                    'totAmountCategorySpent' => $this->formatPrice($totAmountCategorySpent), 'percentualSpentCategory' => $percentSpentCategory, 'cashRemainToSpent' => $this->formatPrice($cashRemainToSpent));
            }

            if($param !== null || !empty($param)){
                $resultArray[$oldCategoryType] = array('categoriesSpent' => $categoriesSpent, 'totalSpent' => $this->formatPrice($this->totalCashRemainToSpent($budegt_id, $month, $oldCategoryType)), 'type' => $oldCategoryType);
            }


        if(empty($resultArray))
            return false;

        return $resultArray;
    }

    private function totalCashRemainToSpent($budget_id, $month, $param = null){
        $categoryRepository = $this->getDoctrine()->getRepository(Category::class);
        if($param == '0')
            return $categoryRepository->getSumAmountIncomesBusinessTrafficByBudget($budget_id, $month)[0]['total'];
        elseif ($param == 1)
            return $categoryRepository->getSumAmountOutcomesBusinessTrafficByBudget($budget_id, $month)[0]['total'];
        elseif ($param == 2)
            return $categoryRepository->getSumAmountSavesBusinessTrafficByBudget($budget_id, $month)[0]['total'];

        $spentIncomes = $categoryRepository->getSumAmountIncomesBusinessTrafficByBudget($budget_id, $month)[0]['total'];
        $spentOutcomes = $categoryRepository->getSumAmountOutcomesBusinessTrafficByBudget($budget_id, $month)[0]['total'];
        $spentSaves = $categoryRepository->getSumAmountSavesBusinessTrafficByBudget($budget_id, $month)[0]['total'];

        if($spentIncomes === false && ($spentOutcomes === false || $spentSaves === false))
            return false;

        return $spentIncomes - ($spentOutcomes + $spentSaves);
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

    private function groupedBusinessTrafficByMonths($budget_id=null){
      $data = array();
      $budgetEntity = $this->getDoctrine()->getRepository(Budget::class);
      if($budget_id === null)
        $budget_id = $budgetEntity->findBy(['active' => '1'])[0]->getId();

      $businessTraffic = $this->getDoctrine()->getRepository(BusinessTraffic::class);
      $businessTrafficsData = $businessTraffic->orderdBusinessTrafficByMonth($budget_id);
      foreach ($businessTrafficsData as $businessTrafficData){
        $dateObj   = DateTime::createFromFormat('m', $businessTrafficData['month']);
        $monthName = $dateObj->format('Y/m/d');
        unset($businessTrafficData['month']);
        $data[$monthName][] = $businessTrafficData[0];
      }

      return $data;
    }

    /**
     * @Route("/new/business/tracking", name="app_budsafes_new_row_business_tracking", methods={"POST"})
     */
    public function newRow(){
      $request = Request::createFromGlobals();
      $post = $request->request;
      $budget = $post->get('budget');
      $numLastRow = $post->get('numLastRow');
      $categoryID = $post->get('categoryID');
      $description = $post->get('description');
      $amount = $post->get('amount');
      $currencyID = $post->get('currencyID');
      $dateAdded = $post->get('dateAdded');
      $businessTrafficID = $post->get('businessTrafficID');
      $budgetEntity = $this->getDoctrine()->getRepository(Budget::class);
      $categories = $budgetEntity->find($budget)->getCategories();
      $currencyEntity = $this->getDoctrine()->getRepository(Currency::class);
      $currencies = $currencyEntity->findAll();


      return $this->render('business_traffic/newRow.html.twig', [
        'numLastRow' => $numLastRow,
        'categories' => $categories,
        'currencies' => $currencies,
        'categoryID' => $categoryID,
        'description' => $description,
        'amount' => $amount,
        'currencyID' => $currencyID,
        'dateAdded' => $dateAdded,
        'businessTrafficID' => $businessTrafficID
      ]);
    }

    private function getActiveBusinessTrafficData(){
      $budgetEntity = $this->getDoctrine()->getRepository(Budget::class);
      $budget = $budgetEntity->findBy(['active' => '1'])[0];
      $businessTraffic = $this->getDoctrine()->getRepository(BusinessTraffic::class)->findBy(['budget' => $budget->getId()]);

      return $this->render('business_traffic/businessTrafficData.html.twig', [
        'businessTraffics' => $businessTraffic
      ]);
    }

    private function generateBusinessTrafficTableByMonth($month){
      if($month == 0){
        $businessTraffic = $this->groupedBusinessTrafficByMonths();
        //taglio e faccio vedere solo 2 mesi
        $businessTraffic = array_slice($businessTraffic, 0, 2);
      }else {
        $date = DateTime::createFromFormat('m', $month);
        $date = $date->format('Y/m/d');
        $businessTraffic = $this->groupedBusinessTrafficByMonths();

        if(empty($businessTraffic))
          return false;

        $businessTraffic = array( $date => $businessTraffic[$date] );
      }

      return $this->render('business_traffic/businessTrafficMonthTable.html.twig', [
        'businessTrafficsData' =>  $businessTraffic
      ]);
    }

  /**
   * @Route("/budget/business/tracking/month", name="app_budsafes_generate_business_tracking_data_month", methods={"POST"})
   * @return JsonResponse
   */
    public function returnBusinessTrafficTable(){
      $request = Request::createFromGlobals();
      $post = $request->request;
      
      $month = $post->get('month');
      $businessTrafficTable = $this->generateBusinessTrafficTableByMonth($month);
      if($businessTrafficTable === false)
        return $this->json(['result' => false, 'message' => 'Ops, sembra che per questo mese non ci siano transazioni']);

      return $this->json(['result' => true, 'message' => 'Transazioni ritornate con successo', 'HTMLData' => $businessTrafficTable]);
    }

    private function generateActiveBusinessTrafficDataByMonth($month){
      $date   = DateTime::createFromFormat('m', $month);
      $date = $date->format('Y/m/d');
      $businessTraffic = $this->groupedBusinessTrafficByMonths();

      if(empty($businessTraffic[$date]))
        return false;
      return $this->render('business_traffic/businessTrafficData.html.twig', [
        'businessTraffics' => $businessTraffic[$date]
      ]);
    }

  /**
   * @Route("/budget/business/tracking/save/", name="app_budsafes_new_business_tracking", methods={"POST"})
   * @param ValidatorInterface $validator
   * @return JsonResponse
   * @throws \Exception
   */
    public function addBusinessTraffic(ValidatorInterface $validator){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;

      $budgetRepository = $this->getDoctrine()->getRepository(Budget::class);
      $categoryRepository = $this->getDoctrine()->getRepository(Category::class);
      $currencyRepository = $this->getDoctrine()->getRepository(Currency::class);

      $businessTraffic = new BusinessTraffic();

      $category = $categoryRepository->find($post->get('idCategory'));
      $currency = $currencyRepository->find($post->get('idCurrency'));
      $amount = $post->get('amount');
      $description = $post->get('description');
      $budget = $budgetRepository->find($post->get('budget'));
      $added = $post->get('added');
      $month = $post->get('month');

      if(empty($category) || empty($currency) || empty($amount)
        || empty($budget) || empty($added))
          return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

      $businessTraffic->setCategory($category);
      $businessTraffic->setCurrency($currency);
      $businessTraffic->setAmount($amount);
      $businessTraffic->setDescription($description);
      $businessTraffic->setBudget($budget);
      $businessTraffic->setAdded(new \DateTime($added));


      //validate input
      $errors = $validator->validate($businessTraffic);
      if(count($errors) > 0)
        return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

      $entityManager->persist($businessTraffic);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo', 'HTMLData' => $this->generateActiveBusinessTrafficDataByMonth($month)]);
    }

    /**
     * @Route("/budget/business/traffic/edit/", name="app_budsafes_edit_business_traffic", methods={"POST"})
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
      $month = $post->get('month');

      $businessTraffic->setCategory($category);
      $businessTraffic->setCurrency($currency);
      $businessTraffic->setAmount($amount);
      $businessTraffic->setDescription($description);
      $businessTraffic->setAdded(new \DateTime($added));

      //validate input
      $errors = $validator->validate($businessTraffic);
      if(count($errors) > 0)
        return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

      $entityManager->persist($businessTraffic);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Modifica effettuata con successo', 'HTMLData' => $this->generateActiveBusinessTrafficDataByMonth($month)]);
    }

    /**
     * @Route("/budget/business/traffic/delete/", name="app_budsafes_delete_business_traffic", methods={"POST"})
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

      return $this->json(['result' => true, 'message' => 'Eliminazione effettuata con successo']);
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
}

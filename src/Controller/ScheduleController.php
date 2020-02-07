<?php

namespace App\Controller;

use App\Entity\Budget;
use App\Entity\Category;
use App\Entity\Currency;
use App\Entity\Schedule;
use DateTime;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ScheduleController extends AbstractController
{
    /**
     * @Route("/budget/schedule/{budgetID}", name="app_budget_schedule")
     */
    public function index($budgetID)
    {
      $budget = $this->getDoctrine()->getRepository(Budget::class)->find($budgetID);
      $scheduleIncomes = $this->getDoctrine()->getRepository(Schedule::class)->findAllScheduleIncomesOfBudget($budgetID);
      $scheduleOutcomes = $this->getDoctrine()->getRepository(Schedule::class)->findAllScheduleOutcomesOfBudget($budgetID);
      $scheduleSaves = $this->getDoctrine()->getRepository(Schedule::class)->findAllScheduleSavesOfBudget($budgetID);
        return $this->render('schedule/viewSchedules.html.twig', [
          'title' => 'Schedule',
          'pageName' => 'Schedule',
          'budgetID' => $budgetID,
          'budget' => $budget,
          'scheduleIncomes' => $scheduleIncomes,
          'scheduleOutcomes' => $scheduleOutcomes,
          'scheduleSaves' => $scheduleSaves,
        ]);
    }

    /**
     * @Route("/budget/schedule/{budget_id}/preview/", name="app_budget_schedule_preview")
     */
    public function viewSchedulePreview($budget_id){
        $budget = $this->getDoctrine()->getRepository(Budget::class)->find($budget_id);
        return $this->render('schedule/preview.html.twig', [
            'title' => 'Preview',
            'pageName' => 'Preview',
            'budget' => $budget,
            'budgetID' =>  $budget_id
        ]);
    }

    private function formatPrice($price){
        if(floatval($price) != intval($price))
            return number_format($price, 2, ',', '.');

        return number_format($price, 0, ',', '.');
    }

    private function cashRemain($budget_id){
        $totalsIncome = $this->totalFinanceCategory($budget_id, 0);
        $totalsOutcome = $this->totalFinanceCategory($budget_id, 1);
        $totalsSaves = $this->totalFinanceCategory($budget_id, 2);
        $cashRemainMonth = $totalsIncome['month'] - ($totalsOutcome['month'] + $totalsSaves['month']);
        $cashRemainYear = $totalsIncome['year'] - ($totalsOutcome['year'] + $totalsSaves['year']);

        return array('month' => $this->formatPrice($cashRemainMonth), 'year' => $this->formatPrice($cashRemainYear));
    }

    private function totalFinanceCategory($budget_id, $category_type){
        $totalAmount = array('month' => 0, 'year' => 0);
        try {
            $schedule = $this->getDoctrine()->getRepository(Schedule::class);
            if ($category_type == 0) {
                $scheduleCategories = $schedule->findIncomesScheduleByBudget($budget_id);
            } elseif ($category_type == 1) {
                $scheduleCategories = $schedule->findOutcomesScheduleByBudget($budget_id);
            } elseif ($category_type == 2) {
                $scheduleCategories = $schedule->findSavesScheduleByBudget($budget_id);
            }

            if(empty($scheduleCategories))
                return false;

            foreach ($scheduleCategories as $categoryScheduled){
                $categoryScheduledAmount = $categoryScheduled->getAmount();
                if($categoryScheduled->getCycle() == 1) {
                    $totalAmount['month'] = $totalAmount['month'] + $categoryScheduledAmount;
                    $totalAmount['year'] = $totalAmount['year'] + ($categoryScheduledAmount * 12);
                }elseif($categoryScheduled->getCycle() == 0) {
                    $totalAmount['month'] = $totalAmount['month'] + ($categoryScheduledAmount / 12);
                    $totalAmount['year'] = $totalAmount['year'] + $categoryScheduledAmount;
                }
            }

        }catch (DBALException $exception){
            return $this->json(['result' => false, 'message' => 'Errore, sembra che non ci siano dati per il budget e la categoria selezionata', 'exception' => $exception->getMessage()]);
        }

        return $totalAmount;
    }

    /**
     * @Route("/budget/schedule/preview/data/", name="app_budegt_schedule_preview_data", methods={"POST"})
     * @return JsonResponse
     */
    public function getChartScheduleDataByCategoryType(){
        $labels = array();
        $data = array();
        $totalAmount = array('month' => 0, 'year' => 0);
        $label = null;
        $request = Request::createFromGlobals();
        $post = $request->request;

        $budget_id = $post->get('budgetID');
        $category_type = $post->get('categoryType');

        try {
            $schedule = $this->getDoctrine()->getRepository(Schedule::class);
            if ($category_type == 0) {
                $scheduleCategories = $schedule->findIncomesScheduleByBudget($budget_id);
                $label = 'Entrate';
            } elseif ($category_type == 1) {
                $scheduleCategories = $schedule->findOutcomesScheduleByBudget($budget_id);
                $label = 'Uscite';
            } elseif ($category_type == 2) {
                $scheduleCategories = $schedule->findSavesScheduleByBudget($budget_id);
                $label = 'Risparmi';
            }

            if(empty($scheduleCategories))
                return $this->json(['result' => false, 'message' => 'Errore, sembra che non ci siano dati per il budget e la categoria selezionata']);

            foreach ($scheduleCategories as $categoryScheduled){
                $labels[] = $categoryScheduled->getCategory()->getName();
                $categoryScheduledAmount = $categoryScheduled->getAmount();
                if($categoryScheduled->getCycle() == 1) {
                    $data[] = $categoryScheduledAmount;
                    $totalAmount['month'] = $totalAmount['month'] + $categoryScheduledAmount;
                    $totalAmount['year'] = $totalAmount['year'] + ($categoryScheduledAmount * 12);
                }elseif($categoryScheduled->getCycle() == 0) {
                    $data[] = $categoryScheduledAmount / 12;
                    $totalAmount['month'] = $totalAmount['month'] + ($categoryScheduledAmount / 12);
                    $totalAmount['year'] = $totalAmount['year'] + $categoryScheduledAmount;
                }
            }

        }catch (DBALException $exception){
            return $this->json(['result' => false, 'message' => 'Errore, sembra che non ci siano dati per il budget e la categoria selezionata', 'exception' => $exception->getMessage()]);
        }

        $totalAmount['month'] = $this->formatPrice($totalAmount['month']);
        $totalAmount['year'] = $this->formatPrice($totalAmount['year']);

        return $this->json(['result' => true, 'label' => $label, 'labels' => $labels, 'data' => $data, 'totalAmount' => $totalAmount, 'cashRemain' => $this->cashRemain($budget_id)]);
    }

  /**
   * @Route("/new/schedule/", name="app_budsafes_new_row_schedule", methods={"POST"})
   */
    public function newRow(){
      $request = Request::createFromGlobals();
      $post = $request->request;
      $budget = $post->get('budget');
      $numLastRow = $post->get('numLastRow');
      $categoryID = $post->get('categoryID');
      $description = $post->get('description');
      $amount = $post->get('amount');
      $firstInvoice = $post->get('firstInvoice');
      $cycle = $post->get('cycle');
      $type = $post->get('type');
      if($type == 0)
        $categories = $this->getDoctrine()->getRepository(Category::class)->findIncomesCategoriesByBudget($budget);
      elseif($type == 1)
        $categories = $this->getDoctrine()->getRepository(Category::class)->findOutcomesCategoriesByBudget($budget);
      elseif ($type == 2)
        $categories = $this->getDoctrine()->getRepository(Category::class)->findSavesCategoriesByBudget($budget);

      $currencyID = $post->get('currencyID');
      $scheduleID = $post->get('scheduleID');
      $currencyEntity = $this->getDoctrine()->getRepository(Currency::class);
      $currencies = $currencyEntity->findAll();


      return $this->render('schedule/newRow.html.twig', [
        'numLastRow' => $numLastRow,
        'categories' => $categories,
        'currencies' => $currencies,
        'categoryID' => $categoryID,
        'description' => $description,
        'amount' => $amount,
        'currencyID' => $currencyID,
        'cycle' => $cycle,
        'firstInvoice' => $firstInvoice,
        'scheduleID' => $scheduleID,
      ]);
    }

  /**
   * @Route("/budget/schedule/save/", name="app_budsafes_new_schedule", methods={"POST"})
   * @param ValidatorInterface $validator
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Exception
   */
    public function newSchedule(ValidatorInterface $validator){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;

      $categoryRepository = $this->getDoctrine()->getRepository(Category::class);
      $currencyRepository = $this->getDoctrine()->getRepository(Currency::class);
      $scheduleRepository = $this->getDoctrine()->getRepository(Schedule::class);

      if($scheduleRepository->isExistScheduleCategory($post->get('idCategory')) === true )
        return $this->json(['result' => false, 'message' => 'Errore, sembra che questa categoria sia già stata pianificata']);

      $schedule = new Schedule();

      $category = $categoryRepository->find($post->get('idCategory'));

      $type = $category->getType();
      $budget = $category->getBudget();
      $currency = $currencyRepository->find($post->get('idCurrency'));
      $amount = $post->get('amount');
      $description = $post->get('description');
      $firstInvoice = $post->get('firstInvoice');
      $cycle = $post->get('cycle');

      if(empty($amount) && !is_numeric($amount))
        return $this->json(['result' => false, 'message' => 'Errore, sembra che tu non abbia inserito un importo']);

      $schedule->setCategory($category);
      $schedule->setCurrency($currency);
      $schedule->setAmount($amount);
      $schedule->setDescription($description);
      $schedule->setFirstInvoice(new \DateTime($firstInvoice));
      $schedule->setCycle($cycle);


      //validate input
      $errors = $validator->validate($schedule);
      if(count($errors) > 0)
        return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

      try {
        $entityManager->persist($schedule);
        $entityManager->flush();
      }catch (DBALException $exception){
        return $this->json(['result' => false, 'message' => 'Errore sembra che qualcosa sia andato storto durante il salvataggio della schedule']);
      }

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo', 'HTMLData' => $this->getAllScheduleDataOfType($budget, $type)]);
    }

    private function getAllScheduleDataOfType($budget_id, $type){
      switch ($type){
        case 0:
          $schedule = $this->getDoctrine()->getRepository(Schedule::class)->findAllScheduleIncomesOfBudget($budget_id);
          break;
        case 1:
          $schedule = $this->getDoctrine()->getRepository(Schedule::class)->findAllScheduleOutcomesOfBudget($budget_id);
          break;
        case 2:
          $schedule = $this->getDoctrine()->getRepository(Schedule::class)->findAllScheduleSavesOfBudget($budget_id);
          break;
      }

      return $this->render('schedule/scheduleData.html.twig', [
        'schedules' => $schedule,
      ]);
    }

    /**
     * @Route("/budget/schedule/edit/", name="app_budsafes_schedule_edit", methods={"POST"})
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    public function editSchedule(ValidatorInterface $validator){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;
      $id = $post->get('id');

      $categoryRepository = $this->getDoctrine()->getRepository(Category::class);
      $currencyRepository = $this->getDoctrine()->getRepository(Currency::class);

      $schedule = $this->getDoctrine()->getRepository(Schedule::class)->find($id);
      $category = $categoryRepository->find($post->get('idCategory'));
      $type = $category->getType();
      $budget = $category->getBudget();
      $currency = $currencyRepository->find($post->get('idCurrency'));
      $amount = $post->get('amount');
      $description = $post->get('description');
      $firstInvoice = $post->get('firstInvoice');
      $cycle = $post->get('cycle');

      $schedule->setCategory($category);
      $schedule->setCurrency($currency);
      $schedule->setAmount($amount);
      $schedule->setDescription($description);
      $schedule->setFirstInvoice(new DateTime($firstInvoice));
      $schedule->setCycle($cycle);

      //validate input
      $errors = $validator->validate($schedule);
      if(count($errors) > 0)
        return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

      $entityManager->persist($schedule);
      $entityManager->flush();

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo', 'HTMLData' => $this->getAllScheduleDataOfType($budget, $type)]);
    }

    /**
     * @Route("/budget/schedule/delete/", name="app_budsafes_schedule_delete", methods={"POST"})
     * @return JsonResponse
     */
    public function deleteSchedule(){
      $entityManager = $this->getDoctrine()->getManager();
      $request = Request::createFromGlobals();
      $post = $request->request;
      $id = $post->get('id');

      $schedule = $this->getDoctrine()->getRepository(Schedule::class)->find($id);

      try {
        $entityManager->remove($schedule);
        $entityManager->flush();
      }catch (DBALException $exception){
        return $this->json(['result' => false, 'message' => 'Errore, sembra che ci siano problemi ad eliminare questa Schedule track, motivo: '.$exception->getMessage()]);
      }

      return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo']);
    }
}

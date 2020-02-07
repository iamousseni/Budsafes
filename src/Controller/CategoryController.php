<?php

namespace App\Controller;

use App\Entity\Budget;
use App\Entity\Category;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryController extends AbstractController
{

    /**
     * @Route("/budget/categorie/{budgetName}/{budget_id}", name="app_budget_category")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function category($budget_id){
      $category = $this->getDoctrine()->getRepository(Category::class);
      $budget = $this->getDoctrine()->getRepository(Budget::class)->find($budget_id);
      return $this->render('category/viewBudgetCategory.html.twig', [
        'title' => 'Budsafes',
        'pageName' => 'Categorie',
        'incomes' => $category->findIncomesCategoriesByBudget($budget_id),
        'outcomes' => $category->findOutcomesCategoriesByBudget($budget_id),
        'saves' => $category->findSavesCategoriesByBudget($budget_id),
        'budget' => $budget,
      ]);
    }
    /**
     * @Route("/new/category", name="app_budsafes_new_row_category", methods={"POST"})
     */
    public function newRow()
    {
        $request = Request::createFromGlobals();
        $post = $request->request;
        $numLastRow = $post->get('numLastRow');
        $type = $post->get('type');
        $categoryName = $post->get('name');
        $categoryID = $post->get('categoryID');

        return $this->render('category/newRow.html.twig', [
            'numLastRow' => $numLastRow,
            'type' => $type,
            'categoryName' => $categoryName,
            'categoryID' => $categoryID,
        ]);
    }

  /**
   * @Route("/budget/categorie/", name="app_budget_all_category_of_type", methods={"POST"})
   */
  public function getAllCategoriesByBudget(){
    $category = $this->getDoctrine()->getRepository(Category::class);
    $request = Request::createFromGlobals();
    $post = $request->request;
    $budget_id = $post->get('budget');
    $type = $post->get('type');
    if(is_numeric($type)){
      switch ($type){
        case 0:
          $categoryData = $category->findIncomesCategoriesByBudget($budget_id);
          break;
        case 1:
          $categoryData = $category->findOutcomesCategoriesByBudget($budget_id);
          break;
        case 2:
          $categoryData = $category->findSavesCategoriesByBudget($budget_id);
          break;
      }

      return $this->render('category/categoryData.html.twig', [
        'categories' => $categoryData,
        'budgetID' => $budget_id,
      ]);
    }

    return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);
  }

  /**
   * @Route("/budget/categorie/save/", name="app_budsafes_new_category", methods={"POST"})
   * @param ValidatorInterface $validator
   * @return JsonResponse
   */
  public function newCategory(ValidatorInterface $validator){
    $entityManager = $this->getDoctrine()->getManager();
    $request = Request::createFromGlobals();
    $post = $request->request;

    $budgetRepository = $this->getDoctrine()->getRepository(Budget::class);
    $category = new Category();

    $budget = $budgetRepository->find($post->get('budget'));
    $type = $post->get('type');
    $name = $post->get('name');
    $status = $post->get('status');

    if(is_numeric($type) && !empty($name) && is_numeric($status)){
      $category->setType($type);
      $category->setName($name);
      $category->setStatus($status);
      $category->setBudget($budget);
    }else{
      return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);
    }

    //validate input
    $errors = $validator->validate($category);
    if(count($errors) > 0)
      return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

    $entityManager->persist($category);
    try {
      $entityManager->flush();
    }catch (NotNullConstraintViolationException $exception){
      return $this->json(['result' => false, 'message' => 'Errore sembra che il budget selezionato non esista']);
    }

    return $this->json(['result' => true, 'message' => 'Salvataggio effettuato con successo', 'HTMLData' => $this->getAllCategoriesByBudget()]);
  }

  /**
   * @Route("/budget/categorie/edit/", name="app_budsafes_edit_category", methods={"POST"})
   * @param ValidatorInterface $validator
   * @return JsonResponse
   */
  public function editCategory(ValidatorInterface $validator){
    $entityManager = $this->getDoctrine()->getManager();
    $request = Request::createFromGlobals();
    $post = $request->request;
    $id = $post->get('id');
    $budgetRepository = $this->getDoctrine()->getRepository(Budget::class);
    $category = $this->getDoctrine()->getRepository(Category::class)->find($id);

    $budget = $budgetRepository->find($post->get('budget'));
    $type = $post->get('type');
    $name = $post->get('name');
    $status = $post->get('status');

    if(is_numeric($type) && !empty($name) && is_numeric($status)){
      $category->setType($type);
      $category->setName($name);
      $category->setStatus($status);
      $category->setBudget($budget);
    }else{
      return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);
    }

    //validate input
    $errors = $validator->validate($category);
    if(count($errors) > 0)
      return $this->json(['result' => false, 'message' => 'Errore controlla i dati inseriti']);

    $entityManager->persist($category);
    $entityManager->flush();

    return $this->json(['result' => true, 'message' => 'Modifica effettuata con successo', 'HTMLData' => $this->getAllCategoriesByBudget()]);
  }

  /**
   * @Route("/budget/categorie/delete/", name="app_budsafes_delete_category", methods={"POST"})
   * @param ValidatorInterface $validator
   * @return JsonResponse
   */
  public function deleteCategory(ValidatorInterface $validator){
    $entityManager = $this->getDoctrine()->getManager();
    $request = Request::createFromGlobals();
    $post = $request->request;
    $id = $post->get('id');
    $category = $entityManager->getRepository(Category::class)->find($id);

    $entityManager->remove($category);
    $entityManager->flush();

    return $this->json(['result' => true, 'message' => 'Eliminazione effettuata con successo']);
  }

  /**
   * @return JsonResponse
   */
  public function getAllCategoriesOfBudget(){
    $entityManager = $this->getDoctrine()->getManager();
    $request = Request::createFromGlobals();
    $post = $request->request;
    $budget =  $this->getDoctrine()->getRepository(Budget::class);
    $idBudget = $post->get('id');
    $categories = $budget->find($idBudget)->getCategories();

    if(count($categories) === 0)
      return $this->json(['result' => false, 'message' => 'Non ci sono categorie collegate', 'data' => $categories]);
    else
      return $this->json(['result' => true, 'message' => 'Ci sono '.count($categories).' categorie collegate', 'data' => $categories]);
  }
}

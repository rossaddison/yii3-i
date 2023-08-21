<?php

declare(strict_types=1); 

namespace App\Invoice\Task;

use App\Invoice\Entity\Task;
use App\Invoice\Entity\InvItem;

use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;


use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvItemAmount\InvItemAmountService as iiaS;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as itrR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Invoice\Payment\PaymentRepository as pymR;
use App\Invoice\Project\ProjectRepository as prjctR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Task\TaskRepository as tR;
use App\Invoice\TaxRate\TaxRateRepository as trR;

use App\Invoice\Task\TaskService;
use App\Service\WebControllerService;
use App\User\UserService;
use App\Invoice\InvItem\InvItemService;

use App\Invoice\InvItem\InvItemForm;
use App\Invoice\Task\TaskForm;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Json\Json;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\ViewRenderer;
use Yiisoft\Validator\ValidatorInterface;

final class TaskController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private TaskService $taskService;
    private TranslatorInterface $translator;     
    private DataResponseFactoryInterface $factory;
    private InvItemService $invitemService;
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        TaskService $taskService,
        TranslatorInterface $translator,
        DataResponseFactoryInterface $responseFactory,
        InvItemService $invitemService
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/task')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->taskService = $taskService;
        $this->translator = $translator;
        $this->factory = $responseFactory;
        $this->invitemService = $invitemService;
    }
    
    /**
     * @param Request $request
     * @param SessionInterface $session
     * @param tR $tR
     * @param DateHelper $dateHelper
     * @param prjctR $prjctR
     * @param sR $sR
     */
    public function index(Request $request, SessionInterface $session, tR $tR, DateHelper $dateHelper, prjctR $prjctR, sR $sR) : \Yiisoft\DataResponse\DataResponse
    {            
        $pageNum = (int)$request->getAttribute('page','1');
        $paginator = (new OffsetPaginator($this->tasks($tR)))
        ->withPageSize((int)$sR->get_setting('default_list_limit'))
        ->withCurrentPage($pageNum);      
        $canEdit = $this->rbac($session);
        $flash = $this->flash($session, '', '');
        $parameters = [
            'paginator' => $paginator,
            's'=>$sR,
            'canEdit' => $canEdit,
            'datehelper'=>$dateHelper,
            'alerts'=>$this->viewRenderer->renderPartialAsString('/invoice/layout/alert',[
                    'flash'=>$this->flash($session,'', ''),
                    'errors' => [],
            ]),
            'prjct'=>$prjctR,
            'tasks' => $this->tasks($tR),
            'flash'=> $flash
        ];    
        return $this->viewRenderer->render('index', $parameters);  
    }
    
   /**
    * 
    * @param ViewRenderer $head
    * @param SessionInterface $session
    * @param Request $request
    * @param ValidatorInterface $validator
    * @param sR $sR
    * @param prjctR $projectRepository
    * @param trR $trR
    * @return Response
    */
    public function add(ViewRenderer $head,SessionInterface $session, Request $request, 
                        ValidatorInterface $validator,
                        sR $sR,                        
                        prjctR $projectRepository,
                        trR $trR
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['task/add'],
            'body' => $request->getParsedBody(),
            'errors' => [],
            'numberhelper'=>new NumberHelper($sR),
            'datehelper'=>new DateHelper($sR),
            'statuses'=>$this->getStatuses($sR),
            's'=>$sR,
            'head'=>$head,            
            'projects'=>$projectRepository->findAllPreloaded(),
            'tax_rates'=>$trR->findAllPreloaded(),
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new TaskForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->taskService->saveTask(new Task(), $form, $sR);
                $this->flash($session, 'info', $sR->trans('record_successfully_created'));
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param tR $tR
     * @param sR $sR
     * @param prjctR $projectRepository
     * @param trR $tax_rateRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, SessionInterface $session, Request $request, CurrentRoute $currentRoute,
                        ValidatorInterface $validator,
                        tR $tR, 
                        sR $sR,                        
                        prjctR $projectRepository,
                        trR $tax_rateRepository
    ): Response {
            $task = $this->task($currentRoute, $tR);
            if ($task) {
            $parameters = [
                'title' => 'Edit',
                'action' => ['task/edit', ['id' => $task->getId()]],
                'body' => $this->body($task),
                'errors'=>[],
                'numberhelper'=>new NumberHelper($sR),
                'datehelper'=>new DateHelper($sR),
                'statuses'=>$this->getStatuses($sR),
                's'=>$sR,
                'head'=>$head,            
                'projects'=>$projectRepository->findAllPreloaded(),
                'tax_rates'=>$tax_rateRepository->findAllPreloaded(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                $form = new TaskForm();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->taskService->saveTask($task, $form, $sR);
                    $this->flash($session, 'info', $sR->trans('record_successfully_updated'));
                    return $this->webService->getRedirectResponse('task/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }    
        return $this->webService->getRedirectResponse('task/index');
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param sR $sR
     * @param tR $tR
     * @return Response
     */
    public function delete(SessionInterface $session, CurrentRoute $currentRoute, sR $sR, tR $tR 
    ): Response {
            $task = $this->task($currentRoute, $tR);
            /** @var Task $task */
            $this->taskService->deleteTask($task); 
            $this->flash($session, 'info', $sR->trans('record_successfully_deleted'));
            return $this->webService->getRedirectResponse('task/index'); 	
    }
    
    /**
     * @return string[][]
     *
     * @psalm-return array{1: array{label: string, class: 'draft'}, 2: array{label: string, class: 'viewed'}, 3: array{label: string, class: 'sent'}, 4: array{label: string, class: 'paid'}}
     */
    public function getStatuses(sR $s): array
    {
        return [
            1 => [
                'label' => $s->trans('not_started'),
                'class' => 'draft'
            ],
            2 => [
                'label' => $s->trans('in_progress'),
                'class' => 'viewed'
            ],
            3 => [
                'label' => $s->trans('complete'),
                'class' => 'sent'
            ],
            4 => [
                'label' => $s->trans('invoiced'),
                'class' => 'paid'
            ]
        ];
    }
    
    //views/invoice/task/modal-task-lookups-inv.php => modal_task_lookups_inv.js $(document).on('click', '.select-items-confirm-inv', function () 
    
    /**
     * @param ValidatorInterface $validator
     * @param Request $request
     * @param SessionInterface $session
     * @param ACIR $aciR
     * @param tR $taskR
     * @param sR $sR
     * @param trR $trR
     * @param iiaR $iiaR
     * @param iiR $iiR
     * @param itrR $itrR
     * @param iaR $iaR
     * @param iR $iR
     * @param pymR $pymR
     */
    public function selection_inv(ValidatorInterface $validator, Request $request, SessionInterface $session,
                                  ACIR $aciR, tR $taskR, sR $sR, trR $trR, iiaR $iiaR, iiR $iiR, itrR $itrR, iaR $iaR, iR $iR, pymR $pymR)
                                  : \Yiisoft\DataResponse\DataResponse {        
        $select_items = $request->getQueryParams();
        /** @var array $task_ids */
        $task_ids = ($select_items['task_ids'] ? $select_items['task_ids'] : []);
        $inv_id = (string)$select_items['inv_id'];
        // Use Spiral||Cycle\Database\Injection\Parameter to build 'IN' array of tasks.
        $tasks = $taskR->findinTasks($task_ids);
        $numberHelper = new NumberHelper($sR);
        // Format the task prices according to comma or point or other setting choice.
        $order = 1;
        /** @var Task $task */ 
        foreach ($tasks as $task) {           
            $task->setPrice((float)$numberHelper->format_amount($task->getPrice()));
            $this->save_task_lookup_item_inv($order, $task, $inv_id, $taskR, $trR, $iiaR, $sR, $validator);
            $order++;          
        }
        $numberHelper->calculate_inv((string)$session->get('inv_id'), $aciR, $iiR, $iiaR, $itrR, $iaR, $iR, $pymR);
        return $this->factory->createResponse(Json::encode($tasks));        
    }
    
/**
 * 
 * @param int $order
 * @param Task $task
 * @param string $inv_id
 * @param tR $taskR
 * @param trR $trR
 * @param iiaR $iiaR
 * @param sR $sR
 * @param ValidatorInterface $validator
 * @return void
 */    
private function save_task_lookup_item_inv(int $order, Task $task, string $inv_id, tR $taskR, trR $trR, iiaR $iiaR, sR $sR, ValidatorInterface $validator) : void {
           $form = new InvItemForm();
           $ajax_content = [
                'name'=> $task->getName(),        
                'inv_id'=> $inv_id,            
                'tax_rate_id'=> $task->getTax_rate_id(),
                'task_id'=> $task->getId(),
                'product_id'=>null,
                'date_added'=>new \DateTimeImmutable('now'),
                'description'=> $task->getDescription(),
                // A default quantity of 1 is used to initialize the item
                'quantity'=>floatval(1),
                'price'=> $task->getPrice(),
                // The user will determine how much discount to give on this item later
                'discount_amount'=>floatval(0),
                'order'=> $order
           ];
           if ($form->load($ajax_content) && $validator->validate($form)->isValid()) {
                $this->invitemService->addInvItem_task(new InvItem(), $form, $inv_id, $taskR, $trR, new iiaS($iiaR), $iiaR, $sR);                 
           }
    }
    
    public function view(CurrentRoute $currentRoute, tR $tR,
        sR $sR,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $task = $this->task($currentRoute, $tR);
        if ($task) {
            $parameters = [
                'title' => $sR->trans('view'),
                'action' => ['task/view', ['id' => $task->getId()]],
                'errors' => [],
                'body' => $this->body($task),
                's'=>$sR,             
                'task'=>$tR->repoTaskquery($task->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('task/index'); 	
    }
        
    /**
     * @return Response|true
     */
    private function rbac(SessionInterface $session): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('task/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param tR $tR
     * @return Task|null
     */
    private function task(CurrentRoute $currentRoute, tR $tR): Task|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $task = $tR->repoTaskquery($id);
            return $task;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function tasks(tR $tR): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $tasks = $tR->findAllPreloaded();        
        return $tasks;
    }
    
    /**
     * 
     * @param Task $task
     * @return array
     */
    private function body(Task $task): array {
        $body = [                
          'id'=>$task->getId(),
          'project_id'=>$task->getProject_id(),
          'name'=>$task->getName(),
          'description'=>$task->getDescription(),
          'price'=>$task->getPrice(),
          'finish_date'=>$task->getFinish_date(),
          'status'=>$task->getStatus(),
          'tax_rate_id'=>$task->getTax_rate_id()
        ];
        return $body;
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash(SessionInterface $session, string $level, string $message): Flash{
        $flash = new Flash($session);
        $flash->set($level, $message); 
        return $flash;
    }
}
<?php

declare(strict_types=1); 

namespace App\Invoice\InvRecurring;

// Entities
use App\Invoice\Entity\InvRecurring;

// Forms

use App\Invoice\Inv\InvService as IS;
use App\Invoice\InvRecurring\InvRecurringService;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;

use App\User\UserService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\InvCustom\InvCustomService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Log\Logger;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

final class InvRecurringController
{
    private DataResponseFactoryInterface $factory;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private InvRecurringService $invrecurringService; 
    private InvAmountService $invAmountService;    
    private InvCustomService $invCustomService;
    private InvItemService $invItemService;
    private InvTaxRateService $invTaxRateService;
    private Session $session;
    private SR $s;
    private IS $iS;
    private TranslatorInterface $translator;
    private Logger $_logger;
    private MailerInterface $mailer;
        
    public function __construct(
        DataResponseFactoryInterface $factory,    
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,        
        InvCustomService $invcustomService,    
        InvAmountService $invamountService,
        InvItemService $invitemService,
        InvRecurringService $invrecurringService,
        InvTaxRateService $invtaxrateService,
        Session $session,
        SR $s,
        IS $iS,
        TranslatorInterface $translator,
        MailerInterface $mailer,
    )    
    {
        $this->factory = $factory;
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/invrecurring')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->invCustomService = $invcustomService;
        $this->invAmountService = $invamountService;
        $this->invItemService = $invitemService;
        $this->invrecurringService = $invrecurringService;
        $this->invTaxRateService = $invtaxrateService;
        $this->session = $session;
        $this->s = $s;
        $this->iS = $iS;
        $this->translator = $translator;        
        $this->_logger = new Logger();
        $this->mailer = $mailer;
    }
    
    /**
     * 
     * @return string
     */
    private function alert() : string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash'=>$this->flash($this->session, '', ''),
            'errors' => [],
        ]);
    }
    
    /**
     * 
     * @param InvRecurring $invrecurring
     * @return array
     */
    private function body(InvRecurring $invrecurring): array {
        $body = [                
          'id'=>$invrecurring->getId(),
          'inv_id'=>$invrecurring->getInv_id(),
          'start'=>$invrecurring->getStart(),
          'end'=>$invrecurring->getEnd(),
          'frequency'=>$invrecurring->getFrequency(),
          'next'=>$invrecurring->getNext()
        ];
        return $body;
    }
    
    //inv.js create_recurring_confirm function calls this function
    
    /**
     * 
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function create_recurring_confirm(Request $request, ValidatorInterface $validator) : Response {
        $body = $request->getQueryParams() ?? [];
        $form = new InvRecurringForm();
        $invrecurring = new InvRecurring(); 
        $body_array = [
            'inv_id'=>$body['inv_id'],
            'start'=>$body['recur_start_date'] ?? null,
            'end'=>$body['recur_end_date'] ?? null,
            'frequency'=>$body['recur_frequency'],
            // The next invoice date is the new recur start date
            'next'=>$body['recur_start_date'] ?? null
        ];
        if ($form->load($body_array) && $validator->validate($form)->isValid()) {    
                $this->invrecurringService->saveInvRecurring($invrecurring,$form);
                 $parameters = ['success'=>1];
           //return response to inv.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));          
        } else {
            $parameters = [
               'success'=>0,
            ];
            //return response to quote.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));          
        } 
    }
        
    /**
     * @param $invoice_recurring_id
     */
    public function set_next_recur_date(string $invoice_recurring_id, IRR $irR) : void
    {
        $invoice_recurring = $irR->repoInvRecurringquery($invoice_recurring_id);
        
        $datehelper = new DateHelper($this->s);

        $recur_next_date = $datehelper->increment_date(($invoice_recurring->getNext())->format($datehelper->style()), $invoice_recurring->getFrequency());       
        
        $invoice_recurring->setNext($recur_next_date);
        $irR->save($invoice_recurring);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param IRR $iR
     * @return Response
     */
    public function stop(CurrentRoute $currentRoute, IRR $iR): Response {
        $ivr = $iR->repoInvRecurringquery($this->invrecurring($currentRoute, $iR)->getId());
        $ivr->setEnd(date('Y-m-d'));
        $ivr->setNext('0000-00-00');
        $iR->save($ivr);
        return $this->webService->getRedirectResponse('invrecurring/index');
    }
    
    // Used in inv.js get_recur_start_date to pass the frequency determined start date back to the modal 
    
    /**
     * 
     * @param Request $request
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function get_recur_start_date(Request $request): \Yiisoft\DataResponse\DataResponse{
        $body = $request->getQueryParams() ?? [];
        $invoice_date = $body['invoice_date'];
        // DateTimeImmutable::__construct(): Failed to parse time string (22-04-202222-04-2022) at position 10 (2): Double date specification
        $sub_str = substr($invoice_date,0,10);
        $immutable_invoice_date = new \DateTimeImmutable($sub_str);
        // see InvRecurringRepository recur_frequencies eg. '8M' => 'calendar_month_8',
        $recur_frequency = $body['recur_frequency'];
        $dateHelper = new DateHelper($this->s);
        $parameters = [
                    'success'=>1,
                    // Calculate the recur_start_date in DateTime format.
                    'recur_start_date'=>$dateHelper->increment_user_date($immutable_invoice_date, $recur_frequency)
        ];
        return $this->factory->createResponse(Json::encode($parameters));       
    }
    
    //TODO
    public function delete_recurring(): void {
        
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param IRR $invrecurringRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                        ValidatorInterface $validator,
                        IRR $invrecurringRepository    

    ): Response {
        $parameters = [
            'title' => 'Edit',
            'action' => ['invrecurring/edit', ['id' => $this->invrecurring($currentRoute, $invrecurringRepository)->getId()]],
            'errors' => [],
            'body' => $this->body($this->invrecurring($currentRoute, $invrecurringRepository)),
            'head'=>$head,
            's'=>$this->s,
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new InvRecurringForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->invrecurringService->saveInvRecurring($this->invrecurring($currentRoute,$invrecurringRepository), $form);
                return $this->webService->getRedirectResponse('invrecurring/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param Session $session
     * @param CurrentRoute $currentRoute
     * @param IRR $invrecurringRepository
     * @return Response
     */
    public function delete(Session $session, CurrentRoute $currentRoute,IRR $invrecurringRepository 
    ): Response {
        try {
            $this->invrecurringService->deleteInvRecurring($this->invrecurring($currentRoute,$invrecurringRepository));               
            $this->flash($session, 'info', 'Deleted.');
            return $this->webService->getRedirectResponse('invrecurring/index'); 
	} catch (\Exception $e) {
            $this->flash($session, 'danger', $e->getMessage());
            unset($e);
            return $this->webService->getRedirectResponse('invrecurring/index'); 
        }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param IRR $invrecurringRepository
     * @return InvRecurring|null
     */
    private function invrecurring(CurrentRoute $currentRoute,IRR $invrecurringRepository): InvRecurring|null
    {
        $id = $currentRoute->getArgument('id');       
        $invrecurring = $invrecurringRepository->repoInvRecurringquery($id);
        return $invrecurring;
    }
    
    /**
     * @return Response|\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return Response|\Yiisoft\Data\Reader\DataReaderInterface<int, InvRecurring>
     */
    private function invrecurrings(IRR $invrecurringRepository): \Yiisoft\Data\Reader\DataReaderInterface|Response 
    {
        $invrecurrings = $invrecurringRepository->findAllPreloaded();        
        if ($invrecurrings === null) {
            return $this->webService->getNotFoundResponse();
        }
        return $invrecurrings;
    }
           
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($this->session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('invrecurring/index');
        }
        return $canEdit;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param IRR $invrecurringRepository
     * @return Response
     */
    public function view(CurrentRoute $currentRoute,IRR $invrecurringRepository): Response {
        $parameters = [
            'title' => $this->s->trans('view'),
            'action' => ['invrecurring/view', ['id' => $this->invrecurring($currentRoute, $invrecurringRepository)->getId()]],
            'errors' => [],
            'body' => $this->body($this->invrecurring($currentRoute, $invrecurringRepository)),
            's'=>$this->s,             
            'invrecurring'=>$invrecurringRepository->repoInvRecurringquery($this->invrecurring($currentRoute, $invrecurringRepository)->getId()),
        ];
        return $this->viewRenderer->render('_view', $parameters);
    }
    
    /**
     * 
     * @param Session $session
     * @param CurrentRoute $currentRoute
     * @param IRR $irR
     * @return Response
     */
    public function index(Session $session, CurrentRoute $currentRoute, IRR $irR): Response
    {
        $pageNum = (int)$currentRoute->getArgument('page', '1');
        $paginator = (new OffsetPaginator($this->invrecurrings($irR)))
        ->withPageSize((int)$this->s->get_setting('default_list_limit'))
        ->withCurrentPage($pageNum);
        $numberhelper = new NumberHelper($this->s);
        $canEdit = $this->rbac();
        $flash = $this->flash($session, '','');
        $parameters = [        
                'paginator'=>$paginator,
                's'=>$this->s,
                'canEdit' => $canEdit,
                'recur_frequencies'=>$numberhelper->recur_frequencies(), 
                'invrecurrings'=>$this->invrecurrings($irR),
                'flash'=> $flash
        ];
        return $this->viewRenderer->render('index', $parameters);  
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator  

    ) : Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['invrecurring/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$this->s,
            'head'=>$head,
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new InvRecurringForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->invrecurringService->saveInvRecurring(new InvRecurring(),$form);
                return $this->webService->getRedirectResponse('invrecurring/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param Session $session
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash(Session $session, string $level, string $message): Flash{
        $flash = new Flash($session);
        $flash->set($level, $message); 
        return $flash;
    }    
}


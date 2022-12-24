<?php

declare(strict_types=1); 

namespace App\Invoice\InvItem;

use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvItemAmount;
use App\Invoice\Product\ProductRepository as PR; 
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository aS TRR;
use App\Invoice\Unit\UnitRepository as UR;
use App\Service\WebControllerService;
use App\User\UserService;
// Helpers
use App\Invoice\Helpers\NumberHelper;
// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Yii
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

final class InvItemController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private InvItemService $invitemService;    
    private DataResponseFactoryInterface $factory;
    private UrlGenerator $urlGenerator;
    private TranslatorInterface $translator;
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        InvItemService $invitemService,        
        DataResponseFactoryInterface $factory,
        UrlGenerator $urlGenerator,
        TranslatorInterface $translator,
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/invitem')
                                           ->withLayout('@views/layout/invoice.php');                                                
        $this->webService = $webService;
        $this->userService = $userService;
        $this->invitemService = $invitemService;
        $this->factory = $factory;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param SR $sR
     * @param PR $pR
     * @param UR $uR
     * @param TRR $trR
     * @param IIAR $iiar
     * @return Response
     */
    public function add_product(ViewRenderer $head, SessionInterface $session, Request $request, 
                        ValidatorInterface $validator,
                        SR $sR,
                        PR $pR,
                        UR $uR,                                                
                        TRR $trR,
                        IIAR $iiar,
    ) : Response
    {
        $inv_id = $session->get('inv_id');
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['invitem/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$sR,
            'head'=>$head,
            'inv_id'=>$inv_id,
            'tax_rates'=>$trR->findAllPreloaded(),
              // Only tasks that are complete are put on the invoice
            'products'=>$pR->findAllPreloaded(),
            'units'=>$uR->findAllPreloaded(),
            'numberhelper'=>new NumberHelper($sR)
        ];
        
        if ($request->getMethod() === Method::POST) {            
            $form = new InvItemForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                  $this->invitemService->addInvItem_product(new InvItem(), $form, $inv_id, $pR, $trR, new IIAS($iiar), $iiar, $sR, $uR);
                  $this->flash($session, 'info', $sR->trans('record_successfully_created'));
                  return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                         ['heading'=>'', 'message'=>$sR->trans('record_successfully_created'),'url'=>'inv/view','id'=>$inv_id]));  
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_item_form_product', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param SR $sR
     * @param TaskR $taskR
     * @param UR $uR
     * @param TRR $trR
     * @param IIAR $iiar
     * @return Response
     */
    public function add_task(ViewRenderer $head, SessionInterface $session, Request $request, 
                        ValidatorInterface $validator,
                        SR $sR,
                        TaskR $taskR,
                        UR $uR,                                                
                        TRR $trR,
                        IIAR $iiar,
    ) : Response
    {
        $inv_id = $session->get('inv_id');
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['invitem/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$sR,
            'head'=>$head,
            'inv_id'=>$inv_id,
            'tax_rates'=>$trR->findAllPreloaded(),
              // Only tasks that are complete are put on the invoice
            'tasks'=>$taskR->repoTaskStatusquery(3),
            'units'=>$uR->findAllPreloaded(),
            'numberhelper'=>new NumberHelper($sR)
        ];
        
        if ($request->getMethod() === Method::POST) {            
            $form = new InvItemForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                  $this->invitemService->addInvItem_task(new InvItem(), $form, $inv_id, $taskR, $trR, new IIAS($iiar), $iiar, $sR);
                  $this->flash($session, 'info', $sR->trans('record_successfully_created'));
                  return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                         ['heading'=>'','message'=>$sR->trans('record_successfully_created'),'url'=>'inv/view','id'=>$inv_id]));  
            }
            $parameters['errors'] = $form->getFormErrors();
        }        
        return $this->viewRenderer->renderPartial('_item_form_task', $parameters);
    }
    
    /**
     * @return (float|int|null|string)[]
     *
     * @psalm-return array{id: string, inv_id: string, tax_rate_id: string, product_id: null|string, task_id: null|string, name: null|string, description: null|string, quantity: float, price: float|null, discount_amount: float|null, order: int, product_unit: null|string, product_unit_id: null|string}
     */
    private function body(InvItem $invitem): array {
        $body = [
          'id'=>$invitem->getId(),
          'inv_id'=>$invitem->getInv_id(),
          'tax_rate_id'=>$invitem->getTax_rate_id(),
          'product_id'=>$invitem->getProduct_id(),
          'task_id'=>$invitem->getTask_id(),
          'name'=>$invitem->getName(),
          'description'=>$invitem->getDescription(),
          'quantity'=>$invitem->getQuantity(),
          'price'=>$invitem->getPrice(),
          'discount_amount'=>$invitem->getDiscount_amount(),
          'order'=>$invitem->getOrder(),
          'product_unit'=>$invitem->getProduct_unit(),
          'product_unit_id'=>$invitem->getProduct_unit_id()
        ];
        return $body;
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param IIR $iiR
     * @param SR $sR
     * @param TRR $trR
     * @param PR $pR
     * @param TaskR $taskR
     * @param UR $uR
     * @param IR $iR
     * @param IIAS $iias
     * @param IIAR $iiar
     * @return Response
     */
    public function edit_product(ViewRenderer $head, SessionInterface $session, CurrentRoute $currentRoute, Request $request, ValidatorInterface $validator,
                        IIR $iiR, SR $sR, TRR $trR, PR $pR, TaskR $taskR, UR $uR, IR $iR, IIAS $iias, IIAR $iiar): Response {
        $inv_id = $session->get('inv_id');
        $parameters = [
            'title' => 'Edit',
            'action' => ['invitem/edit_product', ['id' => $this->invitem($currentRoute, $iiR)->getId()]],
            'errors' => [],
            'body' => $this->body($this->invitem($currentRoute, $iiR)),
            'inv_id'=>$inv_id,
            'head'=>$head,
            's'=>$sR,
            'tax_rates'=>$trR->findAllPreloaded(),
            'products'=>$pR->findAllPreloaded(),
            'invs'=>$iR->findAllPreloaded(),                  
            'units'=>$uR->findAllPreloaded(),
            'numberhelper'=>new NumberHelper($sR)
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new InvItemForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $quantity = $form->getQuantity() ?? 0.00;
                $price = $form->getPrice() ?? 0.00;
                $discount = $form->getDiscount_amount() ?? 0.00;
                $tax_rate_id = $this->invitemService->saveInvItem_product($this->invitem($currentRoute, $iiR), $form, $inv_id, $pR, $sR, $uR) ?: 1;        
                $tax_rate_percentage = $this->taxrate_percentage($tax_rate_id, $trR);
                $this->saveInvItemAmount((int)$this->invitem($currentRoute, $iiR)->getId(), 
                                         $quantity, $price, $discount, $tax_rate_percentage, $iias, $iiar);
                return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                ['heading'=>'Successful','message'=>$sR->trans('record_successfully_updated'),'url'=>'inv/view','id'=>$inv_id])); 
            } else {   
                return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                ['heading'=>'Not successful','message'=>'nosussss','url'=>'inv/view','id'=>$inv_id])); 
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        } 
        return $this->viewRenderer->render('_item_edit_product', $parameters);
    }
    
    
    public function taxrate_percentage(int $id, TRR $trr): float|null
    {
        $taxrate = $trr->repoTaxRatequery((string)$id);
        $percentage = $taxrate->getTax_rate_percent();        
        return $percentage;
    }
    
    
    public function saveInvItemAmount(int $inv_item_id, float $quantity, float $price, float $discount, float $tax_rate_percentage, IIAS $iias, IIAR $iiar): void
    {       
       $iias_array['inv_item_id'] = $inv_item_id;       
       $sub_total = $quantity * $price;
       $tax_total = (($sub_total * ($tax_rate_percentage/100)));
       $discount_total = ($quantity*$discount);
       
       $iias_array['discount'] = $discount_total;
       $iias_array['subtotal'] = $sub_total;
       $iias_array['taxtotal'] = $tax_total;
       $iias_array['total'] = ($sub_total - $discount_total + $tax_total);       
       
       if ($iiar->repoCount((string)$inv_item_id) === 0) {
         $iias->saveInvItemAmountNoForm(new InvItemAmount(), $iias_array);} else {
         $iias->saveInvItemAmountNoForm($iiar->repoInvItemAmountquery((string)$inv_item_id), $iias_array);     
       }                      
    }   
    
    /**
     * 
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param IIR $iiR
     * @param SR $sR
     * @param TRR $trR
     * @param PR $pR
     * @param TaskR $taskR
     * @param UR $uR
     * @param IR $iR
     * @param IIAS $iias
     * @param IIAR $iiar
     * @return Response
     */
    public function edit_task(ViewRenderer $head, SessionInterface $session, CurrentRoute $currentRoute, Request $request, ValidatorInterface $validator,
                        IIR $iiR, SR $sR, TRR $trR, PR $pR, TaskR $taskR, UR $uR, IR $iR, IIAS $iias, IIAR $iiar): Response {
        $inv_id = $session->get('inv_id');
        $parameters = [
            'title' => 'Edit',
            'action' => ['invitem/edit_task', ['id' => $this->invitem($currentRoute, $iiR)->getId()]],
            'errors' => [],
            'body' => $this->body($this->invitem($currentRoute, $iiR)),
            'inv_id'=>$inv_id,
            'head'=>$head,
            's'=>$sR,
            'tax_rates'=>$trR->findAllPreloaded(),
            // Only tasks that are complete are put on the invoice
            'tasks'=>$taskR->repoTaskStatusquery(3),
            'invs'=>$iR->findAllPreloaded(),                  
            'units'=>$uR->findAllPreloaded(),
            'numberhelper'=>new NumberHelper($sR)
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new InvItemForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $tax_rate_id = $this->invitemService->saveInvItem_task($this->invitem($currentRoute, $iiR), $form, $inv_id, $taskR, $sR)  ?: 1;        
                $tax_rate_percentage = $this->taxrate_percentage($tax_rate_id, $trR);
                $quantity = $form->getQuantity() ?? 0.00;
                $price = $form->getPrice() ?? 0.00;
                $discount = $form->getDiscount_amount() ?? 0.00;
                $this->saveInvItemAmount((int)$this->invitem($currentRoute, $iiR)->getId(), 
                                         $quantity, $price, $discount, $tax_rate_percentage, $iias, $iiar);
                return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                ['heading'=>'Successful','message'=>$sR->trans('record_successfully_updated'),'url'=>'inv/view','id'=>$inv_id])); 
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        } 
        return $this->viewRenderer->render('_item_edit_task', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param IIR $iiR
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, IIR $iiR): Response {
            $iiR->repoInvItemCount((string)$this->invitem($currentRoute, $iiR)->getId()) === 1  ? (($this->invitemService->deleteInvItem($this->invitem($currentRoute, $iiR)))): '';
            return $this->viewRenderer->render('inv/index');
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
    
    /**
     * 
     * @param SessionInterface $session
     * @param IIR $iiR
     * @param SR $sR
     * @return Response
     */
    public function index(SessionInterface $session, IIR $iiR, SR $sR): Response
    {       
         $canEdit = $this->rbac($session);
         $flash = $this->flash($session, '','');
         $parameters = [      
          's'=>$sR,
          'inv_id'=>$session->get('inv_id'),
          'canEdit' => $canEdit,
          'invitems' => $this->invitems($iiR),
          'flash'=> $flash
         ];
        
        return $this->viewRenderer->render('index', $parameters);
    } 
    
    /**
     * @param CurrentRoute $currentRoute
     * @param IIR $iiR
     * @return InvItem
     */
    private function invitem(CurrentRoute $currentRoute, IIR $iiR): InvItem|null
    {
        $id = $currentRoute->getArgument('id'); 
        $invitem = $iiR->repoInvItemquery($id);
        return $invitem;
    }
    
    /**
     * @return \Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\DataReaderInterface<int, InvItem>
     */
    private function invitems(IIR $iiR): \Yiisoft\Data\Reader\DataReaderInterface 
    {
        $invitems = $iiR->findAllPreloaded();        
        return $invitems;
    }
    
    /**
     * 
     * @param Request $request
     * @param IIR $iiR
     * @return Response
     */
    public function multiple(Request $request, IIR $iiR): Response {
        //jQuery parameters from inv.js function delete-items-confirm-inv 'item_ids' and 'inv_id'
        $select_items = $request->getQueryParams() ?? [];
        $result = false;
        $item_ids = ($select_items['item_ids'] ? $select_items['item_ids'] : []);
        $items = $iiR->findinInvItems($item_ids);
        // If one item is deleted, the result is positive
        foreach ($items as $item){
            ($this->invitemService->deleteInvItem($item));
            $result = true;
        }
        return $this->factory->createResponse(Json::encode(($result ? ['success'=>1]:['success'=>0])));  
    }
    
    /**
     * @return Response|true
     */
    private function rbac(SessionInterface $session): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('invitem/index');
        }
        return $canEdit;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param IIR $iiR
     * @param SR $sR
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, IIR $iiR,
        SR $sR 
        ): Response {
        $parameters = [
            'title' => $sR->trans('view'),
            'action' => ['invitem/edit', ['id' => $this->invitem($currentRoute, $iiR)->getId()]],
            'errors' => [],
            'body' => $this->body($this->invitem($currentRoute, $iiR)),
            's'=>$sR,             
            'invitem'=>$iiR->repoInvItemquery((string)$this->invitem($currentRoute, $iiR)->getId()),
        ];
        return $this->viewRenderer->render('_view', $parameters);
    } 
}
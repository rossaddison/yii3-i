<?php
declare(strict_types=1); 

namespace App\Invoice\QuoteItem;

use App\Invoice\Entity\QuoteItem;
use App\Invoice\Entity\QuoteItemAmount;
use App\Invoice\Product\ProductRepository as PR; 
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteItem\QuoteItemService;
use App\Invoice\QuoteItem\QuoteItemForm;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as QIAS;
use App\Invoice\Setting\SettingRepository as SR;
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

final class QuoteItemController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private QuoteItemService $quoteitemService;    
    private DataResponseFactoryInterface $factory;
    private UrlGenerator $urlGenerator;
    private TranslatorInterface $translator;
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        QuoteItemService $quoteitemService,        
        DataResponseFactoryInterface $factory,
        UrlGenerator $urlGenerator,
        TranslatorInterface $translator,
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/quoteitem')
                                           ->withLayout('@views/layout/invoice.php');                                                
        $this->webService = $webService;
        $this->userService = $userService;
        $this->quoteitemService = $quoteitemService;
        $this->factory = $factory;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param QIR $qiR
     * @param SR $sR
     * @return Response
     */
    public function index(SessionInterface $session, QIR $qiR, SR $sR): Response
    {       
         $canEdit = $this->rbac($session);
         $flash = $this->flash($session, '','');
         $parameters = [      
          's'=>$sR,
          'quote_id'=>$session->get('quote_id'),
          'canEdit' => $canEdit,
          'quoteitems' => $this->quoteitems($qiR),
          'flash'=> $flash
         ];
        
        return $this->viewRenderer->render('index', $parameters);
    }
    
    // Quoteitem/add accessed from quote/view renderpartialasstring add_quote_item
    // Triggered by clicking on the save button on the item view appearing above the quote view
    
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
     * @param QIAR $qiar
     * @return Response
     */
    public function add(ViewRenderer $head,SessionInterface $session, Request $request,  
                        ValidatorInterface $validator,
                        SR $sR,
                        PR $pR,
                        UR $uR,                                                
                        TRR $trR,
                        QIAR $qiar,
    ) : Response
    {
        // This function is used 
        $quote_id = $session->get('quote_id');
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['quoteitem/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$sR,
            'head'=>$head,
            'quote_id'=>$quote_id,
            'tax_rates'=>$trR->findAllPreloaded(),
            'products'=>$pR->findAllPreloaded(),
            'units'=>$uR->findAllPreloaded(),
            'numberhelper'=>new NumberHelper($sR)
        ];
        
        if ($request->getMethod() === Method::POST) {            
           $form = new QuoteItemForm();
           if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
               $this->quoteitemService->addQuoteItem(new QuoteItem(), $form, $quote_id, $pR, $qiar, new QIAS($qiar),$uR, $trR);
                return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/quote_successful',
                       ['heading'=>'Successful','message'=>$sR->trans('record_successfully_created'),'url'=>'quote/view','id'=>$quote_id]));  
           }    
           $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_item_form', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param QIR $qiR
     * @param SR $sR
     * @param TRR $trR
     * @param PR $pR
     * @param UR $uR
     * @param QR $qR
     * @param QIAS $qias
     * @param QIAR $qiar
     * @return Response
     */
    public function edit(ViewRenderer $head, SessionInterface $session, CurrentRoute $currentRoute, Request $request, ValidatorInterface $validator,
                        QIR $qiR, SR $sR, TRR $trR, PR $pR, UR $uR, QR $qR, QIAS $qias, QIAR $qiar): Response {
        $quote_id = $session->get('quote_id');
        $parameters = [
            'title' => 'Edit',
            'action' => ['quoteitem/edit', ['id' => $this->quoteitem($currentRoute, $qiR)->getId()]],
            'errors' => [],
            'body' => $this->body($this->quoteitem($currentRoute, $qiR)),
            'quote_id'=>$quote_id,
            'head'=>$head,
            's'=>$sR,
            'tax_rates'=>$trR->findAllPreloaded(),
            'products'=>$pR->findAllPreloaded(),
            'quotes'=>$qR->findAllPreloaded(),            
            'units'=>$uR->findAllPreloaded(),
            'numberhelper'=>new NumberHelper($sR)
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new QuoteItemForm();            
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $quantity = $form->getQuantity() ?? 0.00;
                $price = $form->getPrice() ?? 0.00;
                $discount = $form->getDiscount_amount() ?? 0.00;
                $tax_rate_id = $this->quoteitemService->saveQuoteItem($this->quoteitem($currentRoute, $qiR), $form, $quote_id, $pR, $uR) ?: 1;
                $tax_rate_percentage = $this->taxrate_percentage($tax_rate_id, $trR);
                $this->saveQuoteItemAmount((int)$this->quoteitem($currentRoute, $qiR)->getId(), 
                                           $quantity, $price, $discount, $tax_rate_percentage, $qias, $qiar);    
                return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/quote_successful',
                ['heading'=>'Successful', 'message'=>$sR->trans('record_successfully_updated'),'url'=>'quote/view','id'=>$quote_id])); 
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        } 
        return $this->viewRenderer->render('_item_edit_form', $parameters);
    }
    
    public function taxrate_percentage(int $id, TRR $trr): float|null
    {
        $taxrate = $trr->repoTaxRatequery((string)$id);
        $percentage = $taxrate->getTax_rate_percent();        
        return $percentage;
    }
    
    public function saveQuoteItemAmount(int $quote_item_id, float $quantity, float $price, float $discount, float|null $tax_rate_percentage, QIAS $qias, QIAR $qiar): void
    {  
       $qias_array['quote_item_id'] = $quote_item_id;
       $sub_total = $quantity * $price;
       $tax_total = ($sub_total * ($tax_rate_percentage/100));
       $discount_total = $quantity*$discount;
       $qias_array['discount'] = $discount_total;
       $qias_array['subtotal'] = $sub_total;
       $qias_array['taxtotal'] = $tax_total;
       $qias_array['total'] = $sub_total - $discount_total + $tax_total;       
       if ($qiar->repoCount((string)$quote_item_id) === 0) {
         $qias->saveQuoteItemAmountNoForm(new QuoteItemAmount() , $qias_array);} else {
         $qias->saveQuoteItemAmountNoForm($qiar->repoQuoteItemAmountquery((string)$quote_item_id) , $qias_array);     
       }                      
    } 
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param QIR $qiR
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, QIR $qiR): Response {
        $qiR->repoQuoteItemCount($this->quoteitem($currentRoute, $qiR)->getId()) === 1  ? (($this->quoteitemService->deleteQuoteItem($this->quoteitem($currentRoute, $qiR)))): '';
        return $this->viewRenderer->render('quote/index');
    }
    
    /**
     * 
     * @param Request $request
     * @param QIR $qiR
     * @return Response
     */
    public function multiple(Request $request, QIR $qiR): Response {
        //jQuery parameters from quote.js function delete-items-confirm-quote 'item_ids' and 'quote_id'
        $select_items = $request->getQueryParams() ?? [];
        $result = false;
        $item_ids = ($select_items['item_ids'] ? $select_items['item_ids'] : []);
        $items = $qiR->findinQuoteItems($item_ids);
        // If one item is deleted, the result is positive
        foreach ($items as $item){
            ($this->quoteitemService->deleteQuoteItem($item));
            $result = true;
        }
        return $this->factory->createResponse(Json::encode(($result ? ['success'=>1]:['success'=>0])));  
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param QIR $qiR
     * @param SR $sR
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, QIR $qiR,
        SR $sR 
        ): Response {
        $parameters = [
            'title' => $sR->trans('view'),
            'action' => ['quoteitem/edit', ['id' => $this->quoteitem($currentRoute, $qiR)->getId()]],
            'errors' => [],
            'body' => $this->body($this->quoteitem($currentRoute, $qiR)),
            's'=>$sR,             
            'quoteitem'=>$qiR->repoQuoteItemquery($this->quoteitem($currentRoute, $qiR)->getId()),
        ];
        return $this->viewRenderer->render('_view', $parameters);
    }
    
    /**
     * @return Response|true
     */
    private function rbac(SessionInterface $session): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('quoteitem/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param QIR $qiR
     * @return QuoteItem|null
     */
    private function quoteitem(CurrentRoute $currentRoute, QIR $qiR): QuoteItem|null
    {
        $id = $currentRoute->getArgument('id'); 
        $quoteitem = $qiR->repoQuoteItemquery($id);
        return $quoteitem;
    }
    
    /**
     * @return Response|\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return Response|\Yiisoft\Data\Reader\DataReaderInterface<int, QuoteItem>
     */
    private function quoteitems(QIR $qiR): \Yiisoft\Data\Reader\DataReaderInterface|Response 
    {
        $quoteitems = $qiR->findAllPreloaded();        
        if ($quoteitems === null) {
            return $this->webService->getNotFoundResponse();
        }
        return $quoteitems;
    }
    
    /**
     * @return (float|int|null|string)[]
     *
     * @psalm-return array{id: string, quote_id: string, tax_rate_id: string, product_id: null|string, name: null|string, description: null|string, quantity: float|null, price: float|null, discount_amount: float|null, order: int, product_unit: null|string, product_unit_id: null|string}
     */
    private function body(QuoteItem $quoteitem): array {
        $body = [
          'id'=>$quoteitem->getId(),
          'quote_id'=>$quoteitem->getQuote_id(),
          'tax_rate_id'=>$quoteitem->getTax_rate_id(),
          'product_id'=>$quoteitem->getProduct_id(),
          'name'=>$quoteitem->getName(),
          'description'=>$quoteitem->getDescription(),
          'quantity'=>$quoteitem->getQuantity(),
          'price'=>$quoteitem->getPrice(),
          'discount_amount'=>$quoteitem->getDiscount_amount(),
          'order'=>$quoteitem->getOrder(),
          'product_unit'=>$quoteitem->getProduct_unit(),
          'product_unit_id'=>$quoteitem->getProduct_unit_id()
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
<?php
declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\Entity\Product;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Entity\InvItem;
use App\Invoice\Family\FamilyRepository as fR;
use App\Invoice\Helpers\NumberHelper;
// Product
use App\Invoice\Product\ProductService;
use App\Invoice\Product\ProductRepository as pR;
// Quote
use App\Invoice\QuoteItem\QuoteItemForm;
use App\Invoice\QuoteItem\QuoteItemService;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as qiaS;
// Inv
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItemAmount\InvItemAmountService as iiaS;
// Setting, TaxRate, Unit
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Invoice\Unit\UnitRepository as uR;
use App\Invoice\QuoteItem\QuoteItemRepository as qiR;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as qiaR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as qtrR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as itrR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as qaR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Quote\QuoteRepository as qR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Invoice\Payment\PaymentRepository as pymR;
use App\Service\WebControllerService;
use App\User\UserService;

//  Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Yiisoft
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

class ProductController
{
    private const FILTER_FAMILY = 'ff';
    private const FILTER_PRODUCT = 'fp';
    private const RESET_TRUE = 'rt';
    public  ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private ProductService $productService;
    private QuoteItemService $quoteitemService;
    private InvItemService $invitemService;
    private UserService $userService;   
    private DataResponseFactoryInterface $responseFactory;
    private SessionInterface $session;
    private TranslatorInterface $translator;
    private string $ffc = self::FILTER_FAMILY;
    private string $fpc = self::FILTER_PRODUCT;
    private string $rtc = self::RESET_TRUE;
    
    public function __construct(
            ViewRenderer $viewRenderer,
            WebControllerService $webService,
            ProductService $productService,
            QuoteItemService $quoteitemService,
            InvItemService $invitemService,
            UserService $userService,
            DataResponseFactoryInterface $responseFactory,
            SessionInterface $session,
            TranslatorInterface $translator
    )
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/product')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->productService = $productService;
        $this->quoteitemService = $quoteitemService;
        $this->invitemService = $invitemService;
        $this->userService = $userService;
        $this->responseFactory = $responseFactory;
        $this->session = $session;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param sR $sR
     * @param fR $fR
     * @param uR $uR
     * @param trR $trR
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, ValidatorInterface $validator, sR $sR, fR $fR, uR $uR, trR $trR): Response
    {
        $parameters = [
            'title' => $sR->trans('add'),
            'action' => ['product/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$sR,
            'head'=>$head,
            'families'=>$fR->findAllPreloaded(),
            'units'=>$uR->findAllPreloaded(),
            'tax_rates'=>$trR->findAllPreloaded()
        ];
        
        if ($request->getMethod() === Method::POST) {            
            $form = new ProductForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                  $this->productService->addProduct(new Product(),$form);
                  $this->flash('info', $sR->trans('record_successfully_created'));
                  return $this->webService->getRedirectResponse('product/index');   
            }  
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form_add', $parameters);                
    }
    
    /**
     * 
     * @return string
     */
    private function alert() : string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash'=>$this->flash('', ''),
            'errors' => [],
        ]);
    }
    
    /**
     * 
     * @param Product $product
     * @return array
     */
    private function body(Product $product): array {
        $body = [
                'id'=>$product->getProduct_id(),
                'product_sku'=>$product->getProduct_sku(),
                'product_name'=>$product->getProduct_name(),
                'product_description' => $product->getProduct_description(),
                'product_price' => $product->getProduct_price(),
                'purchase_price' => $product->getPurchase_price(),
                'provider_name' => $product->getProvider_name(),
                'tax_rate_id'=>$product->getTax_rate_id(),
                'unit_id'=>$product->getUnit_id(),
                'family_id'=>$product->getFamily_id(), 
                'product_tariff'=>$product->getProduct_tariff()
                ];
        return $body;
    }
    
    /**
     * 
     * @param pR $pR
     * @param CurrentRoute $currentRoute
     * @param sR $sR
     * @return Response
     */
    public function delete(pR $pR, CurrentRoute $currentRoute, sR $sR
    ): Response {
        try {
            $product = $this->product($currentRoute, $pR);
            if ($product) { 
                $this->productService->deleteProduct($product);  
                $this->flash('info', $sR->trans('record_successfully_deleted'));
            }
            return $this->webService->getRedirectResponse('product/index');
	} catch (\Exception $e) {
           unset($e);
           $this->flash('danger', 'Cannot delete. This product is on an invoice or quote.');
           return $this->webService->getRedirectResponse('product/index');   
        }
    }
    
    /**
     * 
     * @param string $level
     * @param string $message
     * @return Flash
     */    
    private function flash(string $level, string $message): Flash{
        $flash = new Flash($this->session);
        $flash->set($level, $message); 
        return $flash;
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param pR $pR
     * @param sR $sR
     * @param fR $fR
     * @param uR $uR
     * @param trR $trR
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, ValidatorInterface $validator,
                    pR $pR, sR $sR, fR $fR, uR $uR, trR $trR, 
    ): Response {
        $product = $this->product($currentRoute, $pR);
        if ($product) {
        $parameters = [
            'title' => $sR->trans('edit'),
            'action' => ['product/edit', ['id' => $product->getProduct_id()]],
            'errors' => [],
            'body' => $this->body($product),
            's'=>$sR,
            'head'=>$head,
            'families'=>$fR->findAllPreloaded(),
            'units'=>$uR->findAllPreloaded(),
            'tax_rates'=>$trR->findAllPreloaded()    
        ];
        $body = $request->getParsedBody();
        if ($request->getMethod() === Method::POST) {
            $form = new ProductForm();            
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->productService->editProduct($product, $form); 
                return $this->responseFactory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                ['heading'=>'','message'=>$sR->trans('record_successfully_updated'),'url'=>'product/view',
                    'id'=>$product->getProduct_id()]));  
            } else {
                $parameters['errors'] = $form->getFormErrors();
                $parameters['body'] = $body;
            }
        }
        return $this->viewRenderer->render('_form_edit', $parameters);
    } //if $product 
    return $this->webService->getRedirectResponse('product/index');   
}
    
    /**
     * @param pR $pR
     * @param sR $sR
     * @param CurrentRoute $currentRoute
     * @param Request $request
     */
    public function index(pR $pR, sR $sR, CurrentRoute $currentRoute, Request $request): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac(); 
        $query_params = $request->getQueryParams();
        /** @var string $query_params['sort'] */
        $page = (int)$currentRoute->getArgument('page', '1');
        $sort = Sort::only(['id','family_id','unit_id','tax_rate_id','product_name','product_sku'])
                    // (@see vendor\yiisoft\data\src\Reader\Sort
                    // - => 'desc'  so -id => default descending on id
                    // Show the latest quotes first => -id
                    ->withOrderString($query_params['sort'] ?? '-id');
        $products = $this->products_with_sort($pR, $sort); 
        $paginator = (new OffsetPaginator($products))
        ->withPageSize((int)$sR->get_setting('default_list_limit'))
        ->withCurrentPage($page)
        ->withNextPageToken((string) $page); 
        $parameters = [
            'alert' => $this->alert(),
            'paginator'=>$paginator,
            'canEdit' => $canEdit,
            'products' => $this->products($pR),
        ]; 
        return $this->viewRenderer->render('index', $parameters);
    }
    
    // queryparams coming from modal_product_lookups.js ---> line 165 filter_button_inv
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param fR $fR
     * @param sR $sR
     * @param pR $pR
     */
    public function lookup(ViewRenderer $head, Request $request, fR $fR, sR $sR, pR $pR): \Yiisoft\DataResponse\DataResponse {
        $queryparams = $request->getQueryParams();
        /** @var string $queryparams[$this->fpc] */
        /** @var string $queryparams[$this->ffc] */
        /** @var string $queryparams[$this->rtc] */
        /** @var string $fp */
        $fp = $queryparams[$this->fpc] ?? '';
        /** @var string $ff */
        $ff = $queryparams[$this->ffc] ?? '';
        /** @var string $rt */
        $rt = $queryparams[$this->rtc] ?? '';
        $parameters = [
            'numberhelper'=>new NumberHelper($sR),
            'families'=> $fR->findAllPreloaded(),
            'filter_product'=> $fp,            
            'filter_family'=> $ff,
            'reset_table'=> $rt,
            's'=> $sR,
            'head'=> $head,
            'products'=> $rt || ($ff=='' && $fp=='') ? $pR->findAllPreloaded() : $pR->repoProductwithfamilyquery($fp, $ff),
            'default_item_tax_rate'=> $sR->get_setting('default_item_tax_rate') !== '' ?: 0,
        ];
        return $this->viewRenderer->renderPartial('_partial_product_table_modal', $parameters);        
    }
    
    /**
     * @param ProductRepository $pR
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Product>
     */
    private function products_with_sort(ProductRepository $pR, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $products = $pR->findAllPreloaded()
                       ->withSort($sort);
        return $products;
    }
    
    /**
     * 
     * @param int $order
     * @param Product $product
     * @param string $quote_id
     * @param pR $pR
     * @param trR $trR
     * @param uR $unR
     * @param QIAR $qiaR
     * @param QIAS $qiaS
     * @param ValidatorInterface $validator
     * @return void
     */
    private function save_product_lookup_item_quote(int $order, Product $product, string $quote_id, pR $pR, trR $trR, uR $unR, QIAR $qiaR, QIAS $qiaS, ValidatorInterface $validator) : void {
           $form = new QuoteItemForm();
           $ajax_content = [
                'name'=>$product->getProduct_name(),        
                'quote_id'=>$quote_id,            
                'tax_rate_id'=>$product->getTax_rate_id(),
                'product_id'=>$product->getProduct_id(),
                'date_added'=>new \DateTimeImmutable(),
                'description'=>$product->getProduct_description(),
                // A default quantity of 1 is used to initialize the item
                'quantity'=>floatval(1),
                'price'=>$product->getProduct_price(),
                // The user will determine how much discount to give on this item later
                'discount_amount'=>floatval(0),
                'order'=>$order,
                // The default quantity is 1 so the singular name will be used.
                'product_unit'=>$unR->singular_or_plural_name($product->getUnit_id(),1),
                'product_unit_id'=>$product->getUnit_id(),
           ];
           if ($form->load($ajax_content) && $validator->validate($form)->isValid()) {
                 $this->quoteitemService->addQuoteItem(new QuoteItem(), $form, $quote_id, $pR, $qiaR, $qiaS, $unR, $trR);
           }      
    }
    
    /**
     * 
     * @param int $order
     * @param Product $product
     * @param string $inv_id
     * @param pR $pR
     * @param sR $sR
     * @param trR $trR
     * @param uR $unR
     * @param iiaR $iiaR
     * @param uR $uR
     * @param ValidatorInterface $validator
     * @return void
     */
    private function save_product_lookup_item_inv(int $order, Product $product, string $inv_id, pR $pR, sR $sR, trR $trR, uR $unR, iiaR $iiaR, uR $uR, ValidatorInterface $validator) : void {
           $form = new InvItemForm();
           $ajax_content = [
                'name'=> $product->getProduct_name(),        
                'inv_id'=>$inv_id,            
                'tax_rate_id'=>$product->getTax_rate_id(),
                'product_id'=>$product->getProduct_id(),
                'task_id'=>null,
                'date_added'=>new \DateTimeImmutable(),
                'description'=>$product->getProduct_description(),
                // A default quantity of 1 is used to initialize the item
                'quantity'=>floatval(1),
                'price'=>$product->getProduct_price(),
                // The user will determine how much discount to give on this item later
                'discount_amount'=>floatval(0),
                'order'=>$order,
                // The default quantity is 1 so the singular name will be used.
                'product_unit'=>$unR->singular_or_plural_name($product->getUnit_id(),1),
                'product_unit_id'=>$product->getUnit_id(),
           ];
           if ($form->load($ajax_content) && $validator->validate($form)->isValid()) {
                $this->invitemService->addInvItem_product(new InvItem(), $form, $inv_id, $pR, $trR, new iiaS($iiaR),$iiaR, $sR, $uR);                 
           }      
    }
    
    //views/invoice/product/modal-product-lookups-quote.php => modal_product_lookups.js $(document).on('click', '.select-items-confirm-quote', function () => selection_quote
    
    /**
     * @param ValidatorInterface $validator
     * @param Request $request
     * @param pR $pR
     * @param qaR $qaR
     * @param qiR $qiR
     * @param qR $qR
     * @param qtrR $qtrR
     * @param sR $sR
     * @param trR $trR
     * @param uR $uR
     * @param qiaR $qiaR
     * @param qiaS $qiaS
     */
    public function selection_quote(ValidatorInterface $validator, Request $request,
                                   pR $pR, qaR $qaR, qiR $qiR, qR $qR, qtrR $qtrR,
                                   sR $sR, trR $trR, uR $uR, qiaR $qiaR, qiaS $qiaS) : \Yiisoft\DataResponse\DataResponse {        
        $select_items = $request->getQueryParams();
        /** @var array $select_items['product_ids'] */
        $product_ids = ($select_items['product_ids'] ?: []);
        /** @var string $quote_id */
        $quote_id = $select_items['quote_id'];
        // Use Spiral||Cycle\Database\Injection\Parameter to build 'IN' array of products.
        $products = $pR->findinProducts($product_ids);
        $numberHelper = new NumberHelper($sR);
        // Format the product prices according to comma or point or other setting choice.
        $order = 1;
        /** @var Product $product */
        foreach ($products as $product) {
            $product->setProduct_price((float)$numberHelper->format_amount($product->getProduct_price()));
            $this->save_product_lookup_item_quote($order, $product, $quote_id, $pR, $trR, $uR, $qiaR, $qiaS, $validator);            
            $order++;          
        } 
        $numberHelper->calculate_quote((string)$this->session->get('quote_id'), $qiR, $qiaR, $qtrR, $qaR, $qR); 
        return $this->responseFactory->createResponse(Json::encode($products));
}
    
    //views/invoice/product/modal-product-lookups-inv.php => modal_product_lookups.js $(document).on('click', '.select-items-confirm-inv', function () 
    
    /**
     * @param ValidatorInterface $validator
     * @param Request $request
     * @param pR $pR
     * @param sR $sR
     * @param trR $trR
     * @param uR $uR
     * @param iiaR $iiaR
     * @param iiR $iiR
     * @param itrR $itrR
     * @param iaR $iaR
     * @param iR $iR
     * @param pymR $pymR
     */
    public function selection_inv(ValidatorInterface $validator, Request $request, pR $pR, sR $sR, trR $trR, uR $uR, iiaR $iiaR, iiR $iiR, itrR $itrR, iaR $iaR, iR $iR, pymR $pymR) : \Yiisoft\DataResponse\DataResponse {        
        $select_items = $request->getQueryParams();
        /** @var array $select_items['product_ids'] */
        $product_ids = ($select_items['product_ids'] ?: []);
        /** @var string $inv_id */
        $inv_id = $select_items['inv_id'];
        // Use Spiral||Cycle\Database\Injection\Parameter to build 'IN' array of products.
        $products = $pR->findinProducts($product_ids);
        $numberHelper = new NumberHelper($sR);
        // Format the product prices according to comma or point or other setting choice.
        $order = 1;
        /** @var Product $product */
        foreach ($products as $product) {
                $product->setProduct_price((float)$numberHelper->format_amount($product->getProduct_price()));
                $this->save_product_lookup_item_inv($order, $product, $inv_id, $pR, $sR, $trR, $uR, $iiaR, $uR, $validator);
                $order++;          
        }
        $numberHelper->calculate_inv((string)$this->session->get('inv_id'), $iiR, $iiaR, $itrR, $iaR, $iR, $pymR);
        return $this->responseFactory->createResponse(Json::encode($products));        
    }   
    
    /**
     * @param CurrentRoute $currentRoute
     * @param pR $pR
     * @return Product|null
     */
    private function product(CurrentRoute $currentRoute, pR $pR): Product|null {        
        $id = $currentRoute->getArgument('id');
        if (null!==$id) {
            $product = $pR->repoProductquery($id);
            return $product;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function products(pR $pR): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader{
        $products = $pR->findAllPreloaded();        
        return $products;
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('product/index');
        }
        return $canEdit;
    }
    
    /**
     * @param pR $pR
     * @param sR $sR
     * @param CurrentRoute $currentRoute
     */
    public function view(pR $pR,sR $sR, CurrentRoute $currentRoute
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $product = $this->product($currentRoute,$pR);
        if ($product) {
        $parameters = [
            'title' => $sR->trans('view'),
            'action' => ['product/view', ['id' =>$product->getProduct_id()]],
            'errors' => [],
            'body' => $this->body($product),
            's'=>$sR,
            //load Entity\Product BelongTo relations ie. $family, $tax_rate, $unit by means of repoProductQuery             
            'product'=>$pR->repoProductquery($product->getProduct_id()),
        ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('product/index');
    }
}

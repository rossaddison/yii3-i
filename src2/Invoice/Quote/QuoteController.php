<?php 
declare(strict_types=1); 

namespace App\Invoice\Quote;
// Entity's
use App\Invoice\Entity\CustomField;
use App\Invoice\Entity\EmailTemplate;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvCustom;
use App\Invoice\Entity\InvTaxRate;
use App\Invoice\Entity\Quote;
use App\Invoice\Entity\QuoteAmount;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Entity\QuoteCustom;
use App\Invoice\Entity\QuoteTaxRate;
use App\Invoice\Entity\TaxRate;
// Services
// Inv
use App\User\UserService;
use App\Invoice\Inv\InvService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\InvItemAmount\InvItemAmountService;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\InvCustom\InvCustomService;
// Quote
use App\Invoice\Quote\QuoteService;
use App\Invoice\QuoteAmount\QuoteAmountService;
use App\Invoice\QuoteCustom\QuoteCustomService;
use App\Invoice\QuoteItem\QuoteItemService;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as QIAS;
use App\Invoice\QuoteTaxRate\QuoteTaxRateService;
use App\Service\WebControllerService;
// Forms
use App\Invoice\Inv\InvForm;
use App\Invoice\InvAmount\InvAmountForm;
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\InvCustom\InvCustomForm;
use App\Invoice\InvTaxRate\InvTaxRateForm;
use App\Invoice\QuoteItem\QuoteItemForm;
use App\Invoice\QuoteTaxRate\QuoteTaxRateForm;
use App\Invoice\QuoteCustom\QuoteCustomForm;
use App\Invoice\Quote\MailerQuoteForm;
use App\Invoice\Quote\QuoteForm;
// Repositories
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\ClientCustom\ClientCustomRepository as CCR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PCR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserRepository as UR;
// App Helpers
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\CustomValuesHelper as CVH;
Use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\MailerHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\PdfHelper;
use App\Invoice\Helpers\TemplateHelper;
// Yii
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Html\Html;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;
use Yiisoft\Translator\TranslatorInterface as Translator; 
use Yiisoft\User\CurrentUser;
// Psr\Http
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class QuoteController
{
    private DataResponseFactoryInterface $factory;
    private NumberHelper $number_helper; 
    private InvAmountService $inv_amount_service;
    private InvService $inv_service;
    private InvCustomService $inv_custom_service;
    private InvItemService $inv_item_service;
    private InvItemAmountService $inv_item_amount_service;
    private InvTaxRateService $inv_tax_rate_service;
    private LoggerInterface $logger;    
    private MailerInterface $mailer;
    private PdfHelper $pdf_helper;
    private QuoteAmountService $quote_amount_service;
    private QuoteCustomService $quote_custom_service;
    private QuoteItemService $quote_item_service;    
    private QuoteService $quote_service;
    private QuoteTaxRateService $quote_tax_rate_service;
    private SessionInterface $session;
    private Translator $translator;
    private SR $sR;
    private UrlGenerator $url_generator;
    private UserService $user_service;
    private ViewRenderer $view_renderer;
    private WebControllerService $web_service;
    
    /**
     * 
     * @param DataResponseFactoryInterface $factory
     * @param InvAmountService $inv_amount_service
     * @param InvService $inv_service
     * @param InvCustomService $inv_custom_service
     * @param InvItemService $inv_item_service
     * @param InvItemAmountService $inv_item_amount_service
     * @param InvTaxRateService $inv_tax_rate_service
     * @param LoggerInterface $logger
     * @param MailerInterface $mailer
     * @param QuoteAmountService $quote_amount_service
     * @param QuoteCustomService $quote_custom_service
     * @param QuoteItemService $quote_item_service
     * @param QuoteService $quote_service
     * @param QuoteTaxRateService $quote_tax_rate_service
     * @param SessionInterface $session
     * @param SR $sR
     * @param Translator $translator
     * @param UserService $user_service
     * @param UrlGenerator $url_generator
     * @param ViewRenderer $view_renderer
     * @param WebControllerService $web_service
     */
    public function __construct(
        DataResponseFactoryInterface $factory,
        InvAmountService $inv_amount_service,
        InvService $inv_service,
        InvCustomService $inv_custom_service,
        InvItemService $inv_item_service,
        InvItemAmountService $inv_item_amount_service,
        InvTaxRateService $inv_tax_rate_service,
        LoggerInterface $logger,
        MailerInterface $mailer,
        QuoteAmountService $quote_amount_service,
        QuoteCustomService $quote_custom_service,    
        QuoteItemService $quote_item_service,    
        QuoteService $quote_service,
        QuoteTaxRateService $quote_tax_rate_service,
        SessionInterface $session,
        SR $sR,
        Translator $translator,
        UserService $user_service,        
        UrlGenerator $url_generator,
        ViewRenderer $view_renderer,
        WebControllerService $web_service,                        
    )    
    {
        $this->factory = $factory;
        $this->inv_amount_service = $inv_amount_service;
        $this->inv_service = $inv_service;
        $this->inv_custom_service = $inv_custom_service;
        $this->inv_item_service = $inv_item_service;
        $this->inv_item_amount_service = $inv_item_amount_service;
        $this->inv_tax_rate_service = $inv_tax_rate_service;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->number_helper = new NumberHelper($sR);
        $this->pdf_helper = new PdfHelper($sR, $session);
        $this->quote_amount_service = $quote_amount_service;
        $this->quote_custom_service = $quote_custom_service;
        $this->quote_item_service = $quote_item_service;        
        $this->quote_service = $quote_service;
        $this->quote_tax_rate_service = $quote_tax_rate_service;
        $this->session = $session;
        $this->sR = $sR;
        $this->translator = $translator;
        $this->user_service = $user_service;
        $this->url_generator = $url_generator;
        $this->view_renderer = $view_renderer;
        if ($this->user_service->hasPermission('viewInv') && !$this->user_service->hasPermission('editInv')) {
            $this->view_renderer = $view_renderer->withControllerName('invoice')
                                                 ->withLayout('@views/layout/guest.php');
        }
        if (!$this->user_service->hasPermission('viewInv') && !$this->user_service->hasPermission('editInv')) {
            $this->view_renderer = $view_renderer->withControllerName('invoice')
                                                 ->withLayout('@views/layout/guest.php');
        }        
        if ($this->user_service->hasPermission('viewInv') && $this->user_service->hasPermission('editInv')) {
            $this->view_renderer = $view_renderer->withControllerName('invoice')
                                                 ->withLayout('@views/layout/invoice.php');
        }
        $this->web_service = $web_service;
    }
    
    /**
     * 
     * @return string
     */
    private function alert() : string {
        return $this->view_renderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash'=>$this->flash('', ''),
            'errors' => [],
        ]);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param QR $qR
     * @return Response
     */
    public function approve(CurrentRoute $currentRoute, QR $qR) : Response {
        $url_key = $currentRoute->getArgument('url_key');
        if (null!==$url_key) {
            if ($qR->repoUrl_key_guest_count($url_key) > 0) { 
                $quote = $qR->repoUrl_key_guest_loaded($url_key);
                if ($quote) {
                    $quote_id = $quote->getId();
                    $quote->setStatus_id(4);
                    $qR->save($quote);
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_successful',
                    ['heading'=>$this->sR->trans('approved'),'message'=>$this->sR->trans('record_successfully_updated'),'url'=>'quote/view','id'=>$quote_id]));  
                }
                return $this->web_service->getNotFoundResponse();
            }
            return $this->web_service->getNotFoundResponse();
        }
        return $this->web_service->getNotFoundResponse();
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param QR $qR
     * @return Response
     */
    public function reject(CurrentRoute $currentRoute, QR $qR) : Response {
        $url_key = $currentRoute->getArgument('url_key');
        if (null!==$url_key) {
            if ($qR->repoUrl_key_guest_count($url_key) > 0) { 
                $quote = $qR->repoUrl_key_guest_loaded($url_key);
                if ($quote) {
                    $quote_id = $quote->getId();
                    $quote->setStatus_id(5);
                    $qR->save($quote);
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_successful',
                    ['heading'=>$this->sR->trans('rejected'),'message'=>$this->sR->trans('record_successfully_updated'),'url'=>'quote/view','id'=>$quote_id]));  
                }    
                return $this->web_service->getNotFoundResponse();
            }
            return $this->web_service->getNotFoundResponse();
        }
        return $this->web_service->getNotFoundResponse();    
    }
    
    
    /**
     * 
     * @param object $quote
     * @return array
     */
    private function body(object $quote): array {
        $body = [
          'number'=>$quote->getNumber(),
            
          'id'=>$quote->getId(),
          'inv_id'=>$quote->getInv_id(),
          'user_id'=>$quote->getUser_id(),
          
          'client_id'=>$quote->getClient_id(),          
         
          'date_created'=>$quote->getDate_created(),
          'date_modified'=>$quote->getDate_modified(),
          'date_expires'=>$quote->getDate_expires(),            
            
          'group_id'=>$quote->getGroup_id(),
          'status_id'=>$quote->getStatus_id(),  
          
          'discount_amount'=>$quote->getDiscount_amount(),
          'discount_percent'=>$quote->getDiscount_percent(),
          'url_key'=>$quote->getUrl_key(),
          'password'=>$quote->getPassword(),
          'notes'=>$quote->getNotes(),  
        ];
        return $body;
    }
    
    // Data fed from quote.js->$(document).on('click', '#quote_create_confirm', function () {
    
    public function create_confirm(CurrentUser $currentUser, Request $request, ValidatorInterface $validator, GR $gR, TRR $trR) : \Yiisoft\DataResponse\DataResponse
    {
        $body = $request->getQueryParams();
        $ajax_body = [
            'inv_id'=>null,
            'client_id'=>(int)$body['client_id'],
            'group_id'=>(int)$body['quote_group_id'],
            'status_id'=>1,
            // Generate a number based on the GroupRepository Next id value and not on a newly generated quote_id 
            // if generate_quote_number_for_draft is set to 'yes' otherwise set to empty string ie. nothing.
            // Note: Clients cannot see draft quotes
            'number'=>$this->sR->get_setting('generate_quote_number_for_draft') === '1' ? $gR->generate_number((int)$body['quote_group_id'], true):'',
            'discount_amount'=>floatval(0),
            'discount_percent'=>floatval(0),
            'url_key'=>'',
            'password'=>$body['quote_password'],              
            'notes'=>'',
        ];
        $ajax_content = new QuoteForm();
        $quote = new Quote();
        if ($ajax_content->load($ajax_body) && $validator->validate($ajax_content)->isValid()) {    
            $this->quote_service->addQuote($currentUser, $quote, $ajax_content, $this->sR);
            $this->quote_amount_service->initializeQuoteAmount(new QuoteAmount(), (int)$quote->getId());
            $this->default_taxes($quote, $trR, $validator);            
            $parameters = ['success'=>1];
            // Inform the user of generated invoice number for drat setting
            $this->flash('info', 
                  $this->sR->get_setting('generate_quote_number_for_draft') === '1' 
                  ? $this->sR->trans('generate_quote_number_for_draft').'=>'.$this->sR->trans('yes') 
                  : $this->sR->trans('generate_quote_number_for_draft').'=>'.$this->sR->trans('no') );
            //return response to quote.js to reload page at location
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
     * 
     * @param ValidatorInterface $validator
     * @param array $array
     * @param int $quote_id
     * @param QCR $qcR
     * @return void
     */
    public function custom_fields(ValidatorInterface $validator, array $array, int $quote_id, QCR $qcR) : void
    {   
        if (!empty($array['custom'])) {
            $db_array = [];
            $values = [];
            foreach ($array['custom'] as $custom) {
                if (preg_match("/^(.*)\[\]$/i", $custom['name'], $matches)) {
                    $values[$matches[1]][] = $custom['value'] ;
                } else {
                    $values[$custom['name']] = $custom['value'];
                }
            }            
            foreach ($values as $key => $value) {                
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                    // Reduce eg.  customview[4] to 4 
                    $key_value = preg_match('/\d+/', $key, $m) ? $m[0] : '';
                    $db_array[$key_value] = $value;
                }
            }            
            foreach ($db_array as $key => $value){
                if ($value !=='') { 
                    $ajax_custom = new QuoteCustomForm();
                    $quote_custom = [];
                    $quote_custom['quote_id']=$quote_id;
                    $quote_custom['custom_field_id']=$key;
                    $quote_custom['value']=$value; 
                    if ($qcR->repoQuoteCustomCount((string)$quote_id, $key) > 0) {
                       $model = $qcR->repoFormValuequery((string)$quote_id, $key);  
                    } else {
                       $model = new QuoteCustom(); 
                    }
                    if (null!==$model && $ajax_custom->load($quote_custom) && $validator->validate($ajax_custom)->isValid()) {
                        $this->quote_custom_service->saveQuoteCustom($model, $ajax_custom);
                    }
                }             
            }
        }    
    }
    
    /**
     * 
     * @param Quote $quote
     * @param TRR $trR
     * @param ValidatorInterface $validator
     * @return void
     */
    public function default_taxes(Quote $quote, TRR $trR, ValidatorInterface $validator): void{
        if ($trR->repoCountAll() > 0) {
            $taxrates = $trR->findAllPreloaded();
            foreach ($taxrates as $taxrate) {
                if ($taxrate instanceof TaxRate) {
                    $taxrate->getTax_rate_default()  == 1 ? $this->default_tax_quote($taxrate, $quote, $validator) : '';
                }
            }
        }
    }
    
    /**
     * @param object|null $taxrate
     * @param object $quote
     * @param ValidatorInterface $validator
     * @return void
     */
    public function default_tax_quote(object|null $taxrate, object $quote, ValidatorInterface $validator) : void {
        $quote_tax_rate_form = new QuoteTaxRateForm();
        $quote_tax_rate = [];
        $quote_tax_rate['quote_id'] = $quote->getId();
        if (null!==$taxrate) {
            $quote_tax_rate['tax_rate_id'] = $taxrate->getTax_rate_id();
        } else {
            $quote_tax_rate['tax_rate_id'] = 1;
        }    
        $quote_tax_rate['include_item_tax'] = 0;
        $quote_tax_rate['quote_tax_rate_amount'] = 0;
        if ($quote_tax_rate_form->load($quote_tax_rate) && $validator->validate($quote_tax_rate_form)->isValid()) { 
            $this->quote_tax_rate_service->saveQuoteTaxRate(new QuoteTaxRate(), $quote_tax_rate_form);
        }        
    }
    
   
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param QuoteRepository $quoteRepo
     * @param QCR $qcR
     * @param QuoteCustomService $qcS
     * @param QIR $qiR
     * @param QuoteItemService $qiS
     * @param QTRR $qtrR
     * @param QuoteTaxRateService $qtrS
     * @param QAR $qaR
     * @param QuoteAmountService $qaS
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, QuoteRepository $quoteRepo, 
                           QCR $qcR, QuoteCustomService $qcS, QIR $qiR, QuoteItemService $qiS, QTRR $qtrR,
                           QuoteTaxRateService $qtrS, QAR $qaR, QuoteAmountService $qaS): Response {
        try {
            $quote = $this->quote($currentRoute, $quoteRepo);
            if ($quote) {
                $this->quote_service->deleteQuote($quote, $qcR, $qcS, $qiR, $qiS, $qtrR, $qtrS, $qaR, $qaS); 
                $this->flash('info','Deleted.');
                return $this->web_service->getRedirectResponse('quote/index'); 
            }
            return $this->web_service->getNotFoundResponse();
	} catch (\Exception $e) {
            unset($e);
            $this->flash('danger', 'Cannot delete.');
            return $this->web_service->getRedirectResponse('quote/index'); 
        }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param QIR $qiR
     */
    public function delete_quote_item(CurrentRoute $currentRoute, QIR $qiR)
                                          : \Yiisoft\DataResponse\DataResponse {
        try {            
            $this->quote_item_service->deleteQuoteItem($this->quote_item($currentRoute,$qiR));
        } catch (\Exception $e) {
            unset($e);
            $this->flash('danger', 'Cannot delete.');
        }
        $quote_id = $this->session->get('quote_id');
        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_successful',
        ['heading'=>'','message'=>$this->sR->trans('record_successfully_deleted'),'url'=>'quote/view','id'=>$quote_id]));  
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param QTRR $quotetaxrateRepository
     */
    public function delete_quote_tax_rate(CurrentRoute $currentRoute, QTRR $quotetaxrateRepository)
                                          : \Yiisoft\DataResponse\DataResponse {
        try {            
            $this->quote_tax_rate_service->deleteQuoteTaxRate($this->quotetaxrate($currentRoute,$quotetaxrateRepository));
        } catch (\Exception $e) {
            unset($e);
            $this->flash('danger', 'Cannot delete.');
        }
        $quote_id = $this->session->get('quote_id');
        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
        ['heading'=>$this->sR->trans('quote_tax_rate'),'message'=>$this->sR->trans('record_successfully_deleted'),'url'=>'quote/view','id'=>$quote_id]));  
    }
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param QR $quoteRepo
     * @param IR $invRepo
     * @param CR $clientRepo
     * @param GR $groupRepo
     * @param UR $userRepo
     * @param QAR $qaR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param QCR $qcR
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        ValidatorInterface $validator,
                        QR $quoteRepo,                        
                        IR $invRepo,
                        CR $clientRepo,
                        GR $groupRepo,
                        UR $userRepo,
                        QAR $qaR,
                        CFR $cfR,
                        CVR $cvR,
                        QCR $qcR
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $quote = $this->quote($currentRoute, $quoteRepo,true);
        if ($quote) {
            $quote_id = $quote->getId(); 
            $action = ['quote/edit', ['id' => $quote_id]];
            $parameters = [
                'title' => '',
                'action' => $action,
                'errors' => [],
                'body' => $this->body($quote),
                'head'=>$head,
                's'=>$this->sR,
                'invs'=>$invRepo->findAllPreloaded(),
                'clients'=>$clientRepo->findAllPreloaded(),
                'groups'=>$groupRepo->findAllPreloaded(),
                'users'=>$userRepo->findAll(),
                'numberhelper' => new NumberHelper($this->sR),
                'quote_statuses'=> $quoteRepo->getStatuses($this->sR),            
                'cvH'=> new CVH($this->sR),
                'custom_fields'=>$cfR->repoTablequery('quote_custom'),
                // Applicable to normally building up permanent selection lists eg. dropdowns
                'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('quote_custom')),
                // There will initially be no custom_values attached to this quote until they are filled in the field on the form
                'quote_custom_values' => $this->quote_custom_values($quote_id, $qcR),
            ];
            if ($request->getMethod() === Method::POST) {   
                $edited_body = $request->getParsedBody();
                $returned_form = $this->edit_save_form_fields($edited_body, $currentRoute, $validator, $quoteRepo, $groupRepo);
                $parameters['body'] = $edited_body;
                $parameters['errors']=$returned_form->getFormErrors();
                $this->edit_save_custom_fields($edited_body, $validator, $qcR, $quote_id);            
                return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_successful',
                ['heading'=>'','message'=>
                    //VarDumper::dump($edited_body),
                    $this->sR->trans('record_successfully_updated'),
                    'url'=>'quote/view','id'=>$quote_id]));  
            }
            return $this->view_renderer->render('/invoice/quote/_form', $parameters);
        } // $quote
        return $this->web_service->getNotFoundResponse();
    }
    
    /**
     * @param array|null|object $edited_body
     */
    public function edit_save_form_fields(array|object|null $edited_body, CurrentRoute $currentRoute, ValidatorInterface $validator, QR $quoteRepo, GR $gR) : QuoteForm {
        $form = new QuoteForm();
        $quote = $this->quote($currentRoute, $quoteRepo, true);
        if ($quote && $form->load($edited_body) && $validator->validate($form)->isValid()) {
            // null => guest and guests do not have permission to save form fields
            /**
             * @psalm-suppress PossiblyNullArgument
             */
            $this->quote_service->saveQuote($this->user_service->getUser(),$quote,$form,$this->sR, $gR);
        }
        return $form;
    }
    
    /**
     * @param array|null|object $parse
     * @param null|string $quote_id
     */
    public function edit_save_custom_fields(array|object|null $parse, ValidatorInterface $validator, QCR $qcR, string|null $quote_id): void {
        $custom = $parse['custom'] ?? [];
        foreach ($custom as $custom_field_id => $value) {
            if (($qcR->repoQuoteCustomCount((string)$quote_id, (string)$custom_field_id)) == 0) {
                $quote_custom = new QuoteCustom();
                $quote_custom_input = [
                    'quote_id'=>(int)$quote_id,
                    'custom_field_id'=>(int)$custom_field_id,
                    'value'=>(string)$value
                ];
                $form = new QuoteCustomForm();
                if ($form->load($quote_custom_input) && $validator->validate($form)->isValid())
                {
                    $this->quote_custom_service->saveQuoteCustom($quote_custom, $form);     
                }
            } else {
                $quote_custom = $qcR->repoFormValuequery((string)$quote_id, (string)$custom_field_id);
                if ($quote_custom) {
                    $quote_custom_input = [
                        'quote_id'=>(int)$quote_id,
                        'custom_field_id'=>(int)$custom_field_id,
                        'value'=>(string)$value
                    ];
                    $form = new QuoteCustomForm();
                    if ($form->load($quote_custom_input) && $validator->validate($form)->isValid())
                    {
                        $this->quote_custom_service->saveQuoteCustom($quote_custom, $form);     
                    }
                }
            } 
            
        }
    }
    
    /**
     * @psalm-param 'pdf' $type
     */
    public function email_get_quote_templates(string $type = 'pdf') : array
    {
        return $this->sR->get_quote_templates($type);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param CurrentRoute $currentRoute
     * @param CCR $ccR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param ETR $etR
     * @param ICR $icR
     * @param QR $qR
     * @param PCR $pcR
     * @param QCR $qcR
     * @param UIR $uiR
     * @return Response
     */
    public function email_stage_0(ViewRenderer $head, 
                                  CurrentRoute $currentRoute, 
                                  CCR $ccR, CFR $cfR, CVR $cvR, 
                                  ETR $etR, ICR $icR, 
                                  QR $qR, PCR $pcR, QCR $qcR, UIR $uiR) : Response {
        $parameters = [];
        $mailer_helper = new MailerHelper($this->sR, $this->session, $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $cfR, $cvR);
        $template_helper = new TemplateHelper($this->sR, $ccR, $qcR, $icR, $pcR, $cfR, $cvR);
        if (!$mailer_helper->mailer_configured()) {
            $this->flash('warning', $this->sR->trans('email_not_configured'));
            return $this->web_service->getRedirectResponse('quote/index');
        }
        $quote_entity = $this->quote($currentRoute, $qR, true);
        if ($quote_entity) {
            $quote_id = $quote_entity->getId();
            $quote = $qR->repoQuoteUnLoadedquery((string)$quote_id);
            if ($quote) {
                $email_template_id = $template_helper->select_email_quote_template();        
                if ($email_template_id) {
                    $email_template = $etR->repoEmailTemplatequery($email_template_id);
                    $parameters['email_template'] = Json::encode($email_template);
                } else {
                    $parameters['email_template'] = '{}';
                }
                // Get all custom fields
                $custom_fields = [];
                $custom_tables = [
                    'client_custom' => 'client',
                    'inv_custom' => 'invoice',
                    'payment_custom' => 'payment',
                    'quote_custom' => 'quote',
                    // TODO 'user_custom' => 'user',
                ];
                foreach (array_keys($custom_tables) as $table) {
                    $custom_fields[$table] = $cfR->repoTablequery($table);
                }        
                if ($template_helper->select_email_quote_template() == '') {
                    $this->flash('warning', 'Email templates not configured. Settings...Quotes...Quote Templates...Default Email Template');
                    return $this->web_service->getRedirectResponse('setting/tab_index');
                }
                $setting_status_email_template =  $etR->repoEmailTemplatequery($template_helper->select_email_quote_template()) 
                                               ?: null;
                null===$setting_status_email_template ? $this->flash('info',
                                                  $this->sR->trans('default_email_template').'=>'.
                                                  $this->sR->trans('not_set')) : '';

                empty($template_helper->select_pdf_quote_template()) ? $this->flash('info',
                                                  $this->sR->trans('default_pdf_template').'=>'.
                                                  $this->sR->trans('not_set')) : '';
                $parameters = [
                    'head'=> $head,
                    'action' => ['quote/email_stage_2', ['id' => $quote_id]],
                    'alert'=>$this->alert(),
                    'auto_template' => null!== $setting_status_email_template 
                                           ? $this->get_inject_email_template_array($setting_status_email_template) 
                                           : [],
                    'setting_status_pdf_template' => $template_helper->select_pdf_quote_template(), 
                    'email_templates' => $etR->repoEmailTemplateType('quote'),
                    'dropdown_titles_of_email_templates' => $this->email_templates($etR),
                    'userinv' => $uiR->repoUserInvUserIdcount($quote->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($quote->getUser_id()) : null,
                    'quote' => $quote,
                    'pdf_templates' => $this->email_get_quote_templates('pdf'),
                    'template_tags' => $this->view_renderer->renderPartialAsString('/invoice/emailtemplate/template-tags',[
                            'custom_fields'=> $custom_fields,                                         
                            'template_tags_inv'=>'',  
                            'template_tags_quote'=>$this->view_renderer->renderPartialAsString('/invoice/emailtemplate/template-tags-quote', [
                                's' => $this->sR,
                                'custom_fields_quote_custom'=>$custom_fields['quote_custom']
                            ]),
                    ]),
                    'form' => new MailerQuoteForm(),
                    'custom_fields' => $custom_fields,
                ];   
                return $this->view_renderer->render('/invoice/quote/mailer_quote', $parameters);
            } // quote    
            return $this->web_service->getRedirectResponse('quote/index');
        } // quote_entity
        return $this->web_service->getRedirectResponse('quote/index');
    }
    
    public function get_inject_email_template_array(object $email_template) : array {
        $email_template_array = [
                'body' => Json::htmlEncode($email_template->getEmail_template_body()),
                'subject'=> $email_template->getEmail_template_subject() ?? '',
                'from_name'=> $email_template->getEmail_template_from_name() ?? '',
                'from_email'=> $email_template->getEmail_template_from_email() ?? '',
                'cc'=> $email_template->getEmail_template_cc() ?? '',
                'bcc'=> $email_template->getEmail_template_bcc() ?? '',
                'pdf_template'=> null!==$email_template->getEmail_template_pdf_template()?$email_template->getEmail_template_pdf_template(): '',
        ];
        return $email_template_array;  
    }
    
    /**
     * @param ETR $etR
     *
     * @return (null|string)[]
     *
     * @psalm-return array<''|int, null|string>
     */
    public function email_templates(ETR $etR) : array {
        $email_templates = $etR->repoEmailTemplateType('quote');
        $data = [];
        foreach ($email_templates as $email_template) {
            if ($email_template instanceof EmailTemplate) {
                $data[] = $email_template->getEmail_template_title();
            }
        }
        return $data;
    }
    
    /**
     * 
     * @param string|null $quote_id
     * @param array $from
     * @param string $to
     * @param string $subject
     * @param string $email_body
     * @param string $cc
     * @param string $bcc
     * @param array $attachFiles
     * @param CR $cR
     * @param CCR $ccR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param IAR $iaR
     * @param ICR $icR
     * @param QIAR $qiaR
     * @param QIR $qiR
     * @param IR $iR
     * @param QTRR $qtrR
     * @param PCR $pcR
     * @param QR $qR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param UIR $uiR
     * @param ViewRenderer $viewrenderer
     * @return bool
     */
        
    public function email_stage_1(string|null $quote_id, 
                                  array $from, 
                                  // $to can only have one email address
                                  string $to, 
                                  string $subject, 
                                  string $email_body, 
                                  string $cc, 
                                  string $bcc, 
                                  array $attachFiles,
                                  CR $cR, 
                                  CCR $ccR, 
                                  CFR $cfR,  
                                  CVR $cvR, 
                                  IAR $iaR, 
                                  ICR $icR, 
                                  QIAR $qiaR, 
                                  QIR $qiR, 
                                  IR $iR, 
                                  QTRR $qtrR, 
                                  PCR $pcR, 
                                  QR $qR, 
                                  QAR $qaR, 
                                  QCR $qcR, 
                                  UIR $uiR, 
                                  ViewRenderer $viewrenderer) : bool
    {
        // All custom repositories, including icR have to be initialised.
        $template_helper = new TemplateHelper($this->sR, $ccR, $qcR, $icR, $pcR, $cfR, $cvR);
        $mailer_helper = new MailerHelper($this->sR, $this->session, $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $cfR, $cvR);       
        if ($quote_id) {
            $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);        
            $quote_custom_values = $this->quote_custom_values($quote_id, $qcR);
            $quote_entity = $qR->repoCount($quote_id) > 0 ? $qR->repoQuoteUnLoadedquery($quote_id) : null;
            if ($quote_entity) {
                $stream = false;        
                $pdf_template_target_path = $this->pdf_helper->generate_quote_pdf($quote_id, $quote_entity->getUser_id(), $stream, true, $quote_amount, $quote_custom_values, $cR, $cvR, $cfR, $qiR, $qiaR, $qR, $qtrR, $uiR, $viewrenderer); 
                if (is_string($pdf_template_target_path)) {
                    $mail_message = $template_helper->parse_template($quote_id, false, $email_body, $cR, $cvR, $iR, $iaR, $qR,  $qaR, $uiR);
                    $mail_subject = $template_helper->parse_template($quote_id, false, $subject, $cR, $cvR, $iR, $iaR, $qR,  $qaR, $uiR);
                    $mail_cc = $template_helper->parse_template($quote_id, false, $cc, $cR, $cvR, $iR, $iaR, $qR,  $qaR, $uiR);
                    $mail_bcc = $template_helper->parse_template($quote_id, false, $bcc, $cR, $cvR, $iR, $iaR, $qR,  $qaR, $uiR);
                    $mail_from = // from[0] is the from_email and from[1] is the from_name    
                        array($template_helper->parse_template($quote_id, false, $from[0], $cR, $cvR, $iR, $iaR, $qR,  $qaR, $uiR), 
                              $template_helper->parse_template($quote_id, false, $from[1], $cR, $cvR, $iR, $iaR, $qR,  $qaR, $uiR));
                    // mail_from[0] is the from_email and mail_from[1] is the from_name
                    return $mailer_helper->yii_mailer_send($mail_from[0], $mail_from[1], 
                                                           $to, $mail_subject, $mail_message, $mail_cc, $mail_bcc, $attachFiles, $pdf_template_target_path,
                                                           $uiR);
                } // pdf_template_target_path    
            } // quote_entity
            return false;
        } // quote_id    
        return false;
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param CR $cR
     * @param CCR $ccR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param GR $gR
     * @param IAR $iaR
     * @param QIAR $qiaR
     * @param ICR $icR
     * @param QIR $qiR
     * @param IR $iR
     * @param QTRR $qtrR
     * @param PCR $pcR
     * @param QR $qR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param UIR $uiR
     * @return Response
     */
    
    public function email_stage_2(Request $request, 
                                  CurrentRoute $currentRoute, 
                                  CR $cR, CCR $ccR, CFR $cfR, CVR $cvR, 
                                  GR $gR, 
                                  IAR $iaR, QIAR $qiaR, ICR $icR, QIR $qiR, IR $iR, QTRR $qtrR, 
                                  PCR $pcR, QR $qR, QAR $qaR, QCR $qcR, UIR $uiR) : Response 
    {
        $quote_id = $currentRoute->getArgument('id'); 
        if (null!==$quote_id) {
            $mailer_helper = new MailerHelper($this->sR, $this->session, $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $cfR, $cvR);
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $body['btn_cancel'] = 0; 
                if (!$mailer_helper->mailer_configured()) {
                    $this->flash('warning', $this->sR->trans('email_not_configured'));
                    return $this->web_service->getRedirectResponse('quote/index');
                }
                $to = $body['MailerQuoteForm']['to_email'] ?? '';
                if (empty($to)) {
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_to_address_missing'),'url'=>'quote/view','id'=>$quote_id]));  
                }

                $from = [
                    $body['MailerQuoteForm']['from_email'] ?? '',
                    $body['MailerQuoteForm']['from_name'] ?? '',
                ];

                if (empty($from[0])) {
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_to_address_missing'),'url'=>'quote/view','id'=>$quote_id]));  
                }

                $pdf_template = $body['MailerQuoteForm']['pdf_template'] ?? '';

                $subject = $body['MailerQuoteForm']['subject'] ?? '';
                $email_body = $body['MailerQuoteForm']['body'] ?? '';

                if (strlen($email_body) !== strlen(strip_tags($email_body))) {
                    $email_body = htmlspecialchars_decode($email_body); 
                } else {
                    $email_body = htmlspecialchars_decode(nl2br($email_body));
                }

                $cc = $body['MailerQuoteForm']['cc'] ?? '';
                $bcc = $body['MailerQuoteForm']['bcc'] ?? '';

                $attachFiles = $request->getUploadedFiles();

                $this->generate_quote_number_if_applicable($quote_id, $qR, $this->sR, $gR);
                // Custom fields are automatically included on the quote
                if ($this->email_stage_1($quote_id, $from, $to, $subject, $email_body, $cc, $bcc, $attachFiles,$cR, $ccR, $cfR,  $cvR, $iaR, $icR, $qiaR, $qiR, $iR, $qtrR, $pcR, $qR, $qaR, $qcR, $uiR, $this->view_renderer)) {
                    $this->sR->quote_mark_sent($quote_id, $qR);            
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_successfully_sent'),'url'=>'quote/view','id'=>$quote_id]));  
                } else {
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_not_sent'),
                     'url'=>'quote/view','id'=>$quote_id]));  
                }
            } // is_array
            return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_not_sent'),
                     'url'=>'quote/view','id'=>$quote_id]));
        } // quote_id   
        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_not_sent'),
                     'url'=>'quote/view','id'=>$quote_id]));
    }     
    
    /**
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
     * @param string $quote_id
     * @param QR $qR
     * @param SR $sR
     * @param GR $gR
     * @return void
     */
    public function generate_quote_number_if_applicable(string $quote_id, QR $qR, SR $sR, GR $gR) : void
    {
        $quote = $qR->repoQuoteUnloadedquery($quote_id);
        if (!empty($quote) && ($quote->getStatus_id() == 1) && ($quote->getNumber() == "")) {
                // Generate new quote number if applicable
                if ($sR->get_setting('generate_quote_number_for_draft') == 0) {
                    $quote_number = $qR->get_quote_number($quote->getGroup_id(), $gR);
                    // Set new quote number and save
                    $quote->setNumber($quote_number);
                    $qR->save($quote);
                }            
        }
    }
   
    // users with viewInv permission access this function
    
    /**
     * @param Request $request
     * @param QAR $qaR
     * @param CurrentRoute $currentRoute
     * @param QR $qR
     * @param UCR $ucR
     * @param UIR $uiR
     */
    public function guest(Request $request, QAR $qaR, CurrentRoute $currentRoute,
                          QR $qR, UCR $ucR, UIR $uiR) : \Yiisoft\DataResponse\DataResponse|Response {
        $query_params = $request->getQueryParams();
        $pageNum = (int)$currentRoute->getArgument('page', '1');
         //status 0 => 'all';
        $status = (int)$currentRoute->getArgument('status', '0');
        $sort = Sort::only(['status_id','number','date_created','date_expires','id','client_id'])->withOrderString($query_params['sort'] ?? ''); 
                
        // Get the current user and determine from (@see Settings...User Account) whether they have been given 
        // either guest or admin rights. These rights are unrelated to rbac and serve as a second
        // 'line of defense' to support role based admin control.
         
        // Retrieve the user from Yii-Demo's list of users in the User Table
        $user = $this->user_service->getUser();         
        if ($user) {
            // Use this user's id to see whether a user has been setup under UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount((string)$user->getId()) > 0 
                     ? $uiR->repoUserInvUserIdquery((string)$user->getId()) 
                     : null);
            if ($userinv) {
                // Determine what clients have been allocated to this user (@see Settings...User Account) 
                // by looking at UserClient table        

                // eg. If the user is a guest-accountant, they will have been allocated certain clients
                // A user-quest-accountant will be allocated a series of clients
                // A user-guest-client will be allocated their client number by the administrator so that
                // they can view their quotes when they log in
                $user_clients = $ucR->get_assigned_to_user($user->getId());
                $quotes = $this->quotes_status_with_sort_guest($qR, $status, $user_clients, $sort);
                $paginator = (new OffsetPaginator($quotes))
                ->withPageSize((int)$this->sR->get_setting('default_list_limit'))
                ->withCurrentPage($pageNum);
                $parameters = [            
                    'alert'=> $this->alert(),
                    'qaR'=> $qaR,
                    'quotes' => $quotes,            
                    'quote_statuses'=> $qR->getStatuses($this->sR),            
                    'max'=>(int) $this->sR->get_setting('default_list_limit'),
                    'page'=> $pageNum,
                    'paginator'=> $paginator,
                    's'=> $this->sR,
                    'sortOrder' => $query_params['sort'] ?? '',             
                    'status'=> $status,
                ];    
                return $this->view_renderer->render('/invoice/quote/guest', $parameters);  
            } // userinv
            return $this->web_service->getNotFoundResponse();
        } //user
        return $this->web_service->getNotFoundResponse();
    }
        
    // Only users with editInv permission can access this index. Refer to config/routes accesschecker.
    
    /**
     * @param Request $request
     * @param QAR $qaR
     * @param QR $quoteRepo
     * @param CR $clientRepo
     * @param GR $groupRepo
     * @param CurrentRoute $currentRoute
     * @param sR $sR
     */
    public function index(Request $request, QAR $qaR, QR $quoteRepo, CR $clientRepo, GR $groupRepo, CurrentRoute $currentRoute, sR $sR): \Yiisoft\DataResponse\DataResponse
    {
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page','1');
        //status 0 => 'all';
        $status = (int)$currentRoute->getArgument('status','0');
        $sort = Sort::only(['id','status_id','number','date_created','date_expires','client_id'])
                    // (@see vendor\yiisoft\data\src\Reader\Sort
                    // - => 'desc'  so -id => default descending on id
                    // Show the latest quotes first => -id
                    ->withOrderString($query_params['sort'] ?? '-id');
        $quotes = $this->quotes_status_with_sort($quoteRepo, $status, $sort); 
        $paginator = (new OffsetPaginator($quotes))
        ->withPageSize((int)$this->sR->get_setting('default_list_limit'))
        ->withCurrentPage($page)
        ->withNextPageToken((string) $page);    
        $parameters = [
            'page' => $page,
            'status' => $status,
            'paginator' => $paginator,
            'sortOrder' => $query_params['sort'] ?? '', 
            'alert'=>$this->alert(),
            'client_count'=>$clientRepo->count(),
            'quotes' => $quotes,
            'quote_statuses'=> $quoteRepo->getStatuses($this->sR),
            'max'=>(int)$sR->get_setting('default_list_limit'),
            'qaR'=>$qaR,
            'modal_create_quote'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_create_quote',[
                  'clients'=>$clientRepo->findAllPreloaded(),
                  's'=>$this->sR,
                  'invoice_groups'=>$groupRepo->findAllPreloaded(),
                  'datehelper'=> new DateHelper($this->sR)
            ]),           
        ];  
        return $this->view_renderer->render('/invoice/quote/index', $parameters);  
    }
        
    /**
     * 
     * @param string $items
     * @param ValidatorInterface $validator
     * @param string $quote_id
     * @param int $order
     * @param PR $pR
     * @param QIR $qir
     * @param QIAR $qiar
     * @param TRR $trr
     * @param UNR $unR
     * @return void
     */
    
    public function  items(string $items, ValidatorInterface $validator, string $quote_id, int $order ,
                                     PR $pR, QIR $qir, QIAR $qiar, TRR $trr, UNR $unR) 
                                     : void {       
        foreach (Json::decode($items) as $item) {
            if ($item['item_name'] && (empty($item['item_id'])||!isset($item['item_id']))) {
                $ajax_content = new QuoteItemForm();
                $quoteitem = [];
                $quoteitem['name'] = $item['item_name'];
                $quoteitem['quote_id']=$item['quote_id'];
                $quoteitem['tax_rate_id']=$item['item_tax_rate_id'];
                $quoteitem['product_id']=($item['item_product_id']);
                //product_id used later to get description and name of product.
                $quoteitem['date_added']=new \DateTimeImmutable();
                $quoteitem['quantity']=($item['item_quantity'] ? $this->number_helper->standardize_amount($item['item_quantity']) : floatval(0));
                $quoteitem['price']=($item['item_price'] ? $this->number_helper->standardize_amount($item['item_price']) : floatval(0));
                $quoteitem['discount_amount']= ($item['item_discount_amount']) ? $this->number_helper->standardize_amount($item['item_discount_amount']) : floatval(0);
                $quoteitem['order']= $order;
                $quoteitem['product_unit']=$unR->singular_or_plural_name($item['item_product_unit_id'],$item['item_quantity']);
                $quoteitem['product_unit_id']= ($item['item_product_unit_id'] ? $item['item_product_unit_id'] : null);                
                unset($item['item_id']);
                ($ajax_content->load($quoteitem) && $validator->validate($ajax_content)->isValid()) ? 
                $this->quote_item_service->addQuoteItem(new QuoteItem(), $ajax_content, $quote_id, $pR, $qiar, new QIAS($qiar),$unR, $trr) : false;                 
                $order++;      
            }
            // Evaluate current items
            if ($item['item_name'] && (!empty($item['item_id'])||isset($item['item_id']))) {
                $unedited = $qir->repoQuoteItemquery($item['item_id']);  
                if ($unedited) {
                    $ajax_content = new QuoteItemForm();
                    $quoteitem = [];
                    $quoteitem['name'] = $item['item_name'];
                    $quoteitem['quote_id']=$item['quote_id'];
                    $quoteitem['tax_rate_id']=$item['item_tax_rate_id'] ? $item['item_tax_rate_id'] : null;
                    $quoteitem['product_id']=($item['item_product_id'] ? $item['item_product_id'] : null);
                    //product_id used later to get description and name of product.
                    $quoteitem['date_added']=new \DateTimeImmutable();
                    $quoteitem['quantity']=($item['item_quantity'] ? $this->number_helper->standardize_amount($item['item_quantity']) : floatval(0));
                    $quoteitem['price']=($item['item_price'] ? $this->number_helper->standardize_amount($item['item_price']) : floatval(0));
                    $quoteitem['discount_amount']= ($item['item_discount_amount']) ? $this->number_helper->standardize_amount($item['item_discount_amount']) : floatval(0);
                    $quoteitem['order']= $order;
                    $quoteitem['product_unit']=$unR->singular_or_plural_name($item['item_product_unit_id'],$item['item_quantity']);
                    $quoteitem['product_unit_id']= ($item['item_product_unit_id'] ? $item['item_product_unit_id'] : null);                
                    unset($item['item_id']);
                    ($ajax_content->load($quoteitem) && $validator->validate($ajax_content)->isValid()) ? 
                    $this->quote_item_service->saveQuoteItem($unedited, $ajax_content, $quote_id, $pR, $unR) : false;             
                } //unedited    
            } // if item      
        } // item
      }
    
    // Demo: Use form within $modalhelper using Helper/ModalHelper:  
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param CR $clientRepo
     * @param GR $groupRepo
     * @param UR $userRepo
     * @param QAR $qaR
     * @param QR $quoteRepo
     * @param SR $settingRepo
     * @return Response
     */
    public function modalcreate(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        CR $clientRepo,
                        GR $groupRepo,
                        UR $userRepo,
                        QAR $qaR,
                        QR $quoteRepo,
                        SR $settingRepo,
    ): Response
    {        
        $parameters = [
            'title' => 'Create',
            'action' => ['quote/modalcreate'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$this->sR,
            'head'=>$head,
            'quote'=>$quoteRepo->findAllPreloaded(),
            'clients'=>$clientRepo->findAllPreloaded(),
            'groups'=>$groupRepo->findAllPreloaded(),
            'users'=>$userRepo->findAll(),
            'datehelper'=> new DateHelper($settingRepo)
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new QuoteForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
               // user cannot be null => guest since a guest does not have permission to create a modal
                /**
                * @psalm-suppress PossiblyNullArgument
                */
                $this->quote_service->saveQuote($this->user_service->getUser(),new Quote(), $form, $this->sR, $groupRepo);
                return $this->web_service->getRedirectResponse('quote/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->view_renderer->renderPartial('/invoice/quote/modal_create_quote_form', $parameters);
    }
    
    // jquery function currently not used
    // Data parsed from quote.js:$(document).on('click', '#client_change_confirm', function () {
    
    /**
     * @param Request $request
     * @param CR $cR
     * @param SR $sR
     */
    public function modal_change_client(Request $request, CR $cR, SR $sR): \Yiisoft\DataResponse\DataResponse 
    { 
        $body = $request->getQueryParams();
        $client = $cR->repoClientquery((string)$body['client_id']);
        $parameters = [
            'success'=>1,
            // Set a client id on quote/view.php so that details can be saved later. 
            'pre_save_client_id'=>$body['client_id'],                
            'client_address_1'=>$client->getClient_address_1().'<br>',
            'client_address_2'=>$client->getClient_address_2().'<br>',
            'client_townline'=>$client->getClient_city().'<br>'.$client->getClient_state().'<br>'.$client->getClient_zip().'<br>',
            'client_country'=>$client->getClient_country(),
            'client_phone'=> $sR->trans('phone').'&nbsp;'.$client->getClient_phone(),
            'client_mobile'=>$sR->trans('mobile').'&nbsp;'.$client->getClient_mobile(),
            'client_fax'=>$sR->trans('fax').'&nbsp;'.$client->getClient_fax(),
            'client_email'=>$sR->trans('email').'&nbsp;'. Html::link($client->getClient_email()),                
            // Reset the a href id="after_client_change_url" link to the new client url
            'after_client_change_url'=>'/invoice/client/view/'.$body['client_id'],
            'after_client_change_name'=>$client->getClient_name(),
        ];
        // return parameters to quote.js:client_change_confirm ajax success function for processing
        return $this->factory->createResponse(Json::encode($parameters));  
    }
    
    // Called from quote.js quote_to_pdf_confirm_with_custom_fields
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param QIR $qiR
     * @param QIAR $qiaR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param SR $sR
     * @param UIR $uiR
     * @param Request $request
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function pdf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, GR $gR, QAR $qaR, QCR $qcR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, SR $sR, UIR $uiR, Request $request) : \Yiisoft\DataResponse\DataResponse|Response {
        // include is a value of 0 or 1 passed from quote.js function quote_to_pdf_with(out)_custom_fields indicating whether the user
        // wants custom fields included on the quote or not.
        $include = $currentRoute->getArgument('include');        
        $quote_id = $this->session->get('quote_id');
        $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
        if ($quote_amount) {
            $custom = (($include===(string)1) ? true : false);
            $quote_custom_values = $this->quote_custom_values($this->session->get('quote_id'),$qcR);
            // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
            $pdfhelper = new PdfHelper($sR, $this->session);
            // The quote will be streamed ie. shown, and not archived
            $stream = true;
            // If we are required to mark quotes as 'sent' when sent.
            if ($sR->get_setting('mark_quotes_sent_pdf') == 1) {
                $this->generate_quote_number_if_applicable($quote_id, $qR, $sR, $gR);
                $sR->quote_mark_sent($quote_id, $qR);
            }
            $quote = $qR->repoQuoteUnloadedquery($quote_id);        
            if ($quote) {
                $pdfhelper->generate_quote_pdf($quote_id, $quote->getUser_id(), $stream, $custom, $quote_amount, $quote_custom_values, $cR, $cvR, $cfR, $qiR, $qiaR, $qR, $qtrR, $uiR, $this->view_renderer);        
                $parameters = ($include == '1' ? ['success'=>1] : ['success'=>0]);
                return $this->factory->createResponse(Json::encode($parameters));  
            } // $inv
            return $this->factory->createResponse(Json::encode(['success'=>0]));  
        } // quote_amount 
        return $this->web_service->getNotFoundResponse();
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param QIR $qiR
     * @param QIAR $qiaR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param SR $sR
     * @param UIR $uiR
     * @return void
     */
    
    public function pdf_dashboard_include_cf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, GR $gR, QAR $qaR, QCR $qcR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, SR $sR, UIR $uiR) : void {
        $quote_id = $currentRoute->getArgument('id');
        if ($quote_id) {
            $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
            if ($quote_amount) {
                $quote_custom_values = $this->quote_custom_values($quote_id,$qcR);
                // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                $pdfhelper = new PdfHelper($sR, $this->session);
                // The quote will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark quotes as 'sent' when sent.
                if ($sR->get_setting('mark_quotes_sent_pdf') == 1) {
                    $this->generate_quote_number_if_applicable($quote_id, $qR, $sR, $gR);
                    $sR->quote_mark_sent($quote_id, $qR);
                }
                $quote = $qR->repoQuoteUnloadedquery($quote_id);        
                if ($quote) {
                    $pdfhelper->generate_quote_pdf($quote_id, $quote->getUser_id(), $stream, true, $quote_amount, $quote_custom_values, $cR, $cvR, $cfR, $qiR, $qiaR, $qR, $qtrR, $uiR, $this->view_renderer);        
                }    
            }    
        } //quote_id    
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param QIR $qiR
     * @param QIAR $qiaR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param SR $sR
     * @param UIR $uiR
     * @return void
     */
    
    public function pdf_dashboard_exclude_cf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, GR $gR, QAR $qaR, QCR $qcR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, SR $sR, UIR $uiR) : void {
        $quote_id = $currentRoute->getArgument('id');
        if ($quote_id) {
            $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
            if ($quote_amount) {
                $quote_custom_values = $this->quote_custom_values($quote_id,$qcR);
                // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                $pdfhelper = new PdfHelper($sR, $this->session);
                // The quote will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark quotes as 'sent' when sent.
                if ($sR->get_setting('mark_quotes_sent_pdf') == 1) {
                    $this->generate_quote_number_if_applicable($quote_id, $qR, $sR, $gR);
                    $sR->quote_mark_sent($quote_id, $qR);
                }
                $quote = $qR->repoQuoteUnloadedquery($quote_id);        
                if ($quote) {
                    $pdfhelper->generate_quote_pdf($quote_id, $quote->getUser_id(), $stream, false, $quote_amount, $quote_custom_values, $cR, $cvR, $cfR, $qiR, $qiaR, $qR, $qtrR, $uiR, $this->view_renderer);      
                }    
            }    
        } // quote_id    
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param QuoteRepository $quoteRepo
     * @param bool $unloaded
     * @return object|null
     */
    
    private function quote(CurrentRoute $currentRoute, 
                           QuoteRepository $quoteRepo, 
                           bool $unloaded = false): object|null 
    {
        $id = $currentRoute->getArgument('id');
        if (null!==$id) {
            $quote = ($unloaded ? $quoteRepo->repoQuoteUnLoadedquery($id) : $quoteRepo->repoQuoteLoadedquery($id));
            return $quote;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function quotes(QuoteRepository $quoteRepo, int $status): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $quotes = $quoteRepo->findAllWithStatus($status);  
        return $quotes;
    }
    
    /**
     * @param QuoteRepository $quoteRepo
     * @param int $status
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Quote>
     */
    private function quotes_status_with_sort(QuoteRepository $quoteRepo, int $status, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $quotes = $quoteRepo->findAllWithStatus($status)
                            ->withSort($sort);
        return $quotes;
    }
    
    /**
     * @param QR $qR
     * @param int $status
     * @param array $user_clients
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Quote>
     */
    private function quotes_status_with_sort_guest(QR $qR, int $status,  array $user_clients, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $quotes = $qR->repoGuest_Clients_Sent_Viewed_Approved_Rejected_Cancelled($status, $user_clients)
                     ->withSort($sort);
        return $quotes;
    }
    
    /**
     * 
     * @param string $quote_id
     * @param qcR $qcR
     * @return array
     */
    public function quote_custom_values(string $quote_id, qcR $qcR) : array
    {
        // Get all the custom fields that have been registered with this quote on creation, retrieve existing values via repo, and populate 
        // custom_field_form_values array
        $custom_field_form_values = [];
        if ($qcR->repoQuoteCount($quote_id) > 0) {
            $quote_custom_fields = $qcR->repoFields($quote_id);
            foreach ($quote_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' . $key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param QIR $quoteitemRepository
     * @return object|null
     */
    private function quote_item(CurrentRoute $currentRoute,QIR $quoteitemRepository): object|null 
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $quoteitem = $quoteitemRepository->repoQuoteItemquery($id);
            if (null!==$quoteitem) {
                return $quoteitem;
            }
            return null;
        }
        return null;
    }
    
    // Data fed from quote.js->$(document).on('click', '#quote_to_invoice_confirm', function () {
    
    /**
     * 
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param CFR $cfR
     * @param GR $gR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param InvItemAmountservice $iiaS
     * @param PR $pR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param QIR $qiR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param TRR $trR
     * @param UNR $unR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function quote_to_invoice_confirm(Request $request, ValidatorInterface $validator, CFR $cfR, 
                                             GR $gR, IIAR $iiaR, IR $iR, InvItemAmountservice $iiaS, PR $pR, QAR $qaR, QCR $qcR,
                                             QIR $qiR,QR $qR, QTRR $qtrR, TRR $trR, UNR $unR) : \Yiisoft\DataResponse\DataResponse|Response
    {
        $body = $request->getQueryParams();
        $quote_id = (string)$body['quote_id'];
        $quote = $qR->repoQuoteUnloadedquery($quote_id);
        if ($quote) {
            $ajax_body = [
                'client_id'=>$body['client_id'],
                'group_id'=>$body['group_id'],
                'status_id'=>1,
                'is_read_only'=>0,
                'password'=>$body['password'] ?? '',
                'number'=>$gR->generate_number((int)$body['group_id']),
                'discount_amount'=>floatval($quote->getDiscount_amount()),
                'discount_percent'=>floatval($quote->getDiscount_percent()),
                'url_key'=>$quote->getUrl_key(),
                'payment_method'=>0,
                'terms'=>'',
                'creditinvoice_parent_id'=>''
            ];
            $form = new InvForm();
            $inv = new Inv();
            if (($form->load($ajax_body) && $validator->validate($form)->isValid()) &&
                    // Quote has not been copied before:  inv_id = 0
                    (($quote->getInv_id()===(string)0))
                ) {
                /**
                 * @psalm-suppress PossiblyNullArgument
                 */
                $this->inv_service->addInv($this->user_service->getUser(),$inv, $form, $this->sR);
                $inv_id = $inv->getId();
                if (null!==$inv_id) {
                    // Transfer each quote_item to inv_item and the corresponding quote_item_amount to inv_item_amount for each item
                    $this->quote_to_invoice_quote_items($quote_id,$inv_id, $iiaR, $iiaS, $pR,$qiR, $trR, $validator, $this->sR, $unR);
                    $this->quote_to_invoice_quote_tax_rates($quote_id,$inv_id,$qtrR, $validator);
                    $this->quote_to_invoice_quote_custom($quote_id,$inv_id,$qcR, $cfR, $validator);
                    $this->quote_to_invoice_quote_amount($quote_id,$inv_id,$qaR, $validator);
                    // Update the quotes inv_id.
                    $quote->setInv_id($inv_id);
                    $qR->save($quote);
                    $parameters = ['success'=>1];
                    //return response to quote.js to reload page at location
                    $this->flash('info',$this->translator->translate('invoice.quote.copied.to.invoice'));
                    return $this->factory->createResponse(Json::encode($parameters));          
                }    
            } else {
                $parameters = [
                   'success'=>0,
                ];
                //return response to quote.js to reload page at location
                return $this->factory->createResponse(Json::encode($parameters));          
            }
        } // quote
        return $this->web_service->getNotFoundResponse();
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string $inv_id
     * @param IIAR $iiaR
     * @param InvItemAmountService $iiaS
     * @param PR $pR
     * @param QIR $qiR
     * @param TRR $trR
     * @param ValidatorInterface $validator
     * @param UNR $unR
     * @return void
     */
    private function quote_to_invoice_quote_items(string $quote_id, string $inv_id, IIAR $iiaR, InvItemAmountService $iiaS, PR $pR, QIR $qiR, TRR $trR, ValidatorInterface $validator, SR $sR, UNR $unR): void {
        // Get all items that belong to the quote
        $items = $qiR->repoQuoteItemIdquery($quote_id);
        foreach ($items as $quote_item) {
            if ($quote_item instanceof QuoteItem) {
                $inv_item = [
                    'inv_id'=>$inv_id,
                    'tax_rate_id'=>$quote_item->getTax_rate_id(),
                    'product_id'=>$quote_item->getProduct_id(),
                    'task_id'=>'',
                    'name'=>$quote_item->getName(),
                    'description'=>$quote_item->getDescription(),
                    'quantity'=>$quote_item->getQuantity(),
                    'price'=>$quote_item->getPrice(),
                    'discount_amount'=>$quote_item->getDiscount_amount(),
                    'order'=>$quote_item->getOrder(),
                    'is_recurring'=>0,
                    'product_unit'=>$quote_item->getProduct_unit(),
                    'product_unit_id'=>$quote_item->getProduct_unit_id(),
                    // Recurring date
                    'date'=>''
                ];
                // Create an equivalent invoice item for the quote item
                $invitem = new InvItem();
                $form = new InvItemForm();
                if ($form->load($inv_item) && $validator->validate($form)->isValid()) {
                    $this->inv_item_service->addInvItem_product($invitem, $form, $inv_id, $pR, $trR , $iiaS, $iiaR, $sR, $unR);
                }
            } // quote_item    
        } // items
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $inv_id
     * @param QTRR $qtrR
     * @param ValidatorInterface $validator
     * @return void
     */
    private function quote_to_invoice_quote_tax_rates(string $quote_id, string|null $inv_id, QTRR $qtrR, ValidatorInterface $validator): void {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);        
        foreach ($quote_tax_rates as $quote_tax_rate){ 
            if ($quote_tax_rate instanceof QuoteTaxRate) {
                $inv_tax_rate = [
                    'inv_id'=>(string)$inv_id,
                    'tax_rate_id'=>$quote_tax_rate->getTax_rate_id(),
                    'include_item_tax'=>$quote_tax_rate->getInclude_item_tax(),
                    'inv_tax_rate_amount'=>$quote_tax_rate->getQuote_tax_rate_amount(),
                ];
                $entity = new InvTaxRate();
                $form = new InvTaxRateForm();
                if ($form->load($inv_tax_rate) && $validator->validate($form)->isValid()
                ) {    
                   $this->inv_tax_rate_service->saveInvTaxRate($entity,$form);
                }
            } // quote_tax_rate   
        } // foreach        
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $inv_id
     * @param QCR $qcR
     * @param CFR $cfR
     * @param ValidatorInterface $validator
     * @return void
     */
    private function quote_to_invoice_quote_custom(string $quote_id, string|null $inv_id, 
                                                   QCR $qcR,                                                     
                                                   CFR $cfR, 
                                                   ValidatorInterface $validator) : void {
        $quote_customs = $qcR->repoFields($quote_id);
        // For each quote custom field, build a new custom field for 'inv_custom' using the custom_field_id to find details
        foreach ($quote_customs as $quote_custom) {
            if ($quote_custom instanceof QuoteCustom) {
                // For each quote custom field, build a new custom field for 'inv_custom' 
                // using the custom_field_id to find details
                $existing_custom_field = $cfR->repoCustomFieldquery($quote_custom->getCustom_field_id());
                if ($existing_custom_field) {
                    if ($cfR->repoTableAndLabelCountquery('inv_custom',$existing_custom_field->getLabel()) !== 0) {
                        // Build an identitcal custom field for the invoice
                        $custom_field = new CustomField();
                        $custom_field->setTable('inv_custom');
                        $custom_field->setLabel($existing_custom_field->getLabel());
                        $custom_field->setType($existing_custom_field->getType());
                        $custom_field->setLocation($existing_custom_field->getLocation());
                        $custom_field->setOrder($existing_custom_field->getOrder());
                        $cfR->save($custom_field);
                        // Build the inv_custom field record
                        $inv_custom = [
                            'inv_id'=>$inv_id,
                            'custom_field_id'=>$custom_field->getId(),
                            'value'=>$quote_custom->getValue(),
                        ];
                        $entity = new InvCustom();
                        $form = new InvCustomForm();
                        if ($form->load($inv_custom) && $validator->validate($form)->isValid()) {    
                            $this->inv_custom_service->saveInvCustom($entity,$form);            
                        }
                    } // cfR->repoTable
                } // existing_custom_field    
            } // instanceof    
        } // foreach        
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $inv_id
     * @param QAR $qaR
     * @param ValidatorInterface $validator
     * @return void
     */
    private function quote_to_invoice_quote_amount(string $quote_id,string|null $inv_id, QAR $qaR, ValidatorInterface $validator) : void {
        $quote_amount = $qaR->repoQuotequery($quote_id);
        $inv_amount = [];
        if ($quote_amount) {
            $inv_amount = [
                'inv_id'=>$inv_id,
                'sign'=>1,
                'item_subtotal'=>$quote_amount->getItem_subtotal(),
                'item_tax_total'=>$quote_amount->getItem_tax_total(),
                'tax_total'=>$quote_amount->getTax_total(),
                'total'=>$quote_amount->getTotal(),
                'paid'=>floatval(0.00),
                'balance'=>floatval(0.00),
            ];
        }    
        $entity = new InvAmount();
        $form = new InvAmountForm();
        if ($form->load($inv_amount) && $validator->validate($form)->isValid()) {    
                $this->inv_amount_service->saveInvAmount($entity,$form);            
        }
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $copy_id
     * @return void
     */
    private function quote_to_quote_quote_amount(string $quote_id,string|null $copy_id): void {
        $this->quote_amount_service->initializeCopyQuoteAmount(new QuoteAmount(), $quote_id, $copy_id);                
    }
    
     // Data fed from quote.js->$(document).on('click', '#quote_to_quote_confirm', function () {
    
    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param GR $gR
     * @param QIAS $qiaS
     * @param PR $pR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param QIAR $qiaR
     * @param QIR $qiR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param TRR $trR
     * @param UNR $unR
     */
    public function quote_to_quote_confirm(Request $request, ValidatorInterface $validator, 
                                           GR $gR, QIAS $qiaS, PR $pR, QAR $qaR, QCR $qcR,
                                           QIAR $qiaR, QIR $qiR, QR $qR, QTRR $qtrR, TRR $trR, UNR $unR) : \Yiisoft\DataResponse\DataResponse|Response
    {
        $data_quote_js = $request->getQueryParams();
        $quote_id = (string)$data_quote_js['quote_id'];
        $original = $qR->repoQuoteUnloadedquery($quote_id);
        if ($original) {
            $group_id = $original->getGroup_id();
            $ajax_body = [
                    'inv_id'=>null,
                    'client_id'=>$data_quote_js['client_id'],
                    'group_id'=>$group_id,
                    'status_id'=>1,
                    'number'=>$gR->generate_number((int)$group_id),  
                    'discount_amount'=>floatval($original->getDiscount_amount()),
                    'discount_percent'=>floatval($original->getDiscount_percent()),
                    'url_key'=>'',
                    'password'=>'',              
                    'notes'=>'',
            ];
            $form = new QuoteForm();
            $copy = new Quote();
            if (($form->load($ajax_body) && $validator->validate($form)->isValid())) {    
                /**
                 * @psalm-suppress PossiblyNullArgument
                 */
                $this->quote_service->addQuote($this->user_service->getUser(), $copy, $form, $this->sR);            
                // Transfer each quote_item to quote_item and the corresponding quote_item_amount to quote_item_amount for each item
                $copy_id =$copy->getId();
                if (null!==$copy_id) {
                    $this->quote_to_quote_quote_items($quote_id,$copy_id, $qiaR, $qiaS, $pR,$qiR, $trR, $unR, $validator);
                    $this->quote_to_quote_quote_tax_rates($quote_id,$copy_id,$qtrR, $validator);
                    $this->quote_to_quote_quote_custom($quote_id,$copy_id,$qcR, $validator);
                    $this->quote_to_quote_quote_amount($quote_id,$copy_id);            
                    $qR->save($copy);
                    $parameters = ['success'=>1];
                    //return response to quote.js to reload page at location
                    return $this->factory->createResponse(Json::encode($parameters));
                }    
            } else {
                $parameters = [
                   'success'=>0,
                ];
                //return response to quote.js to reload page at location
                return $this->factory->createResponse(Json::encode($parameters));          
            }
        } // original    
        return $this->web_service->getNotFoundResponse();
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $copy_id
     * @param QCR $qcR
     * @param ValidatorInterface $validator
     * @return void
     */
    private function quote_to_quote_quote_custom(string $quote_id, string|null $copy_id, QCR $qcR, ValidatorInterface $validator): void {
        $quote_customs = $qcR->repoFields($quote_id);
        foreach ($quote_customs as $quote_custom) {
            if ($quote_custom instanceof QuoteCustom) {
                $copy_custom = [
                    'quote_id'=>$copy_id,
                    'custom_field_id'=>$quote_custom->getCustom_field_id(),
                    'value'=>$quote_custom->getValue(),
                ];
                $entity = new QuoteCustom();
                $form = new QuoteCustomForm();
                if ($form->load($copy_custom) && $validator->validate($form)->isValid()) {    
                    $this->quote_custom_service->saveQuoteCustom($entity,$form);            
                }
            }
        }        
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string $copy_id
     * @param QIAR $qiaR
     * @param QIAS $qiaS
     * @param PR $pR
     * @param QIR $qiR
     * @param TRR $trR
     * @param UNR $unR
     * @param ValidatorInterface $validator
     * @return void
     */
    private function quote_to_quote_quote_items(string $quote_id, string $copy_id, QIAR $qiaR, QIAS $qiaS, PR $pR, QIR $qiR, TRR $trR, UNR $unR, ValidatorInterface $validator): void {
        // Get all items that belong to the original quote
        $items = $qiR->repoQuoteItemIdquery($quote_id);
        foreach ($items as $quote_item) {
            if ($quote_item instanceof QuoteItem) {
                $copy_item = [
                    'quote_id'=>$copy_id,
                    'tax_rate_id'=>$quote_item->getTax_rate_id(),
                    'product_id'=>$quote_item->getProduct_id(),
                    'task_id'=>'',
                    'name'=>$quote_item->getName(),
                    'description'=>$quote_item->getDescription(),
                    'quantity'=>$quote_item->getQuantity(),
                    'price'=>$quote_item->getPrice(),
                    'discount_amount'=>$quote_item->getDiscount_amount(),
                    'order'=>$quote_item->getOrder(),
                    'is_recurring'=>0,
                    'product_unit'=>$quote_item->getProduct_unit(),
                    'product_unit_id'=>$quote_item->getProduct_unit_id(),
                    // Recurring date
                    'date'=>''
                ];
                // Create an equivalent invoice item for the quote item
                $copyitem = new QuoteItem();
                $form = new QuoteItemForm();
                if ($form->load($copy_item) && $validator->validate($form)->isValid()) {
                    $this->quote_item_service->addQuoteItem($copyitem, $form, $copy_id, $pR, $qiaR, $qiaS, $unR, $trR);
                }                    
            } // instanceof quote_item
        } // items as quote_item
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $copy_id
     * @param QTRR $qtrR
     * @param ValidatorInterface $validator
     * @return void
     */
    private function quote_to_quote_quote_tax_rates(string $quote_id, string|null $copy_id, QTRR $qtrR, ValidatorInterface $validator): void {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);        
        foreach ($quote_tax_rates as $quote_tax_rate){
            if ($quote_tax_rate instanceof QuoteTaxRate) {
                $copy_tax_rate = [
                    'quote_id'=>$copy_id,
                    'tax_rate_id'=>$quote_tax_rate->getTax_rate_id(),
                    'include_item_tax'=>$quote_tax_rate->getInclude_item_tax(),
                    'amount'=>$quote_tax_rate->getQuote_tax_rate_amount(),
                ];
                $entity = new QuoteTaxRate();
                $form = new QuoteTaxRateForm();
                if ($form->load($copy_tax_rate) && $validator->validate($form)->isValid()) {    
                    $this->quote_tax_rate_service->saveQuoteTaxRate($entity,$form);
                }
            }
        }        
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param QTRR $quotetaxrateRepository
     * @return object|null
     */    
    private function quotetaxrate(CurrentRoute $currentRoute, QTRR $quotetaxrateRepository): object|null 
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $quotetaxrate = $quotetaxrateRepository->repoQuoteTaxRatequery($id);
            if (null!==$quotetaxrate) {
                return $quotetaxrate;
            }
            return null;
        }
        return null;
    }
    
    /**
     * @param array $files
     * @return mixed
     */
    private function remove_extension(array $files) : mixed
    {
        foreach ($files as $key => $file) {
            $files[$key] = str_replace('.php', '', $file);
        }

        return $files;
    }
    
    // quote/view => '#btn_save_quote_custom_fields' => quote_custom_field.js => /invoice/quote/save_custom";
    
    /**
     * @param ValidatorInterface $validator
     * @param Request $request
     * @param QCR $qcR
     */
    public function save_custom(ValidatorInterface $validator, Request $request, QCR $qcR) : \Yiisoft\DataResponse\DataResponse
    {
            $parameters = [
                'success'=>0
            ]; 
            $js_data = $request->getQueryParams();        
            $quote_id = $js_data['quote_id'];
            $custom_field_body = [            
                'custom'=>$js_data['custom'] ?: '',            
            ];
            $this->custom_fields($validator, $custom_field_body,$quote_id, $qcR);
            $parameters['success'] = 1;            
            return $this->factory->createResponse(Json::encode($parameters)); 
    }
    
    // '#quote_tax_submit' => quote.js 
    
    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     */
    public function save_quote_tax_rate(Request $request, ValidatorInterface $validator)
                                        : \Yiisoft\DataResponse\DataResponse {       
        $body = $request->getQueryParams();
        $ajax_body = [
            'quote_id'=>$body['quote_id'],
            'tax_rate_id'=>$body['tax_rate_id'],
            'include_item_tax'=>$body['include_item_tax'],
            'quote_tax_rate_amount'=>floatval(0.00),
        ];
        $ajax_content = new QuoteTaxRateForm();
        if ($ajax_content->load($ajax_body) && $validator->validate($ajax_content)->isValid()) {    
            $this->quote_tax_rate_service->saveQuoteTaxRate(new QuoteTaxRate(), $ajax_content);
            $parameters = [
                'success'=>1
            ];
            //return response to quote.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));          
        } else {
            $parameters = [
               'success'=>0
             ];
            //return response to quote.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));          
        }        
    }
    
    // When you click on Send Mail whilst in the view, you will get mailer_quote view showing with the url_key at the bottom
    // Use this url_key to test what the customer will experience eg. invoice/quote/url_key/{url_key}
    // config/routes accesschecker ensures client has viewInv permission
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param CurrentUser $currentUser
     * @param CFR $cfR
     * @param QAR $qaR
     * @param QIR $qiR
     * @param QIAR $qiaR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param UIR $uiR
     * @param PMR $pmR
     * @return Response
     */
    public function url_key(CurrentRoute $currentRoute, CurrentUser $currentUser, CFR $cfR, QAR $qaR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, UIR $uiR, UCR $ucR, PMR $pmR): Response 
    {
        // Get the url key from the browser
        $url_key = $currentRoute->getArgument('url_key');
        
        // If there is no quote with such a url_key, issue a not found response
        if ($url_key === null) {
            return $this->web_service->getNotFoundResponse();
        }
        
        // If there is a quote with the url key ... continue or else issue not found response
        if ($qR->repoUrl_key_guest_count($url_key) < 1) {
            return $this->web_service->getNotFoundResponse();
        }
        
        // If this quote has a status id that falls into the category of (just)sent, viewed(in the past), approved(in the past) then continue
        $quote = $qR->repoUrl_key_guest_loaded($url_key);
        $quote_tax_rates = null;
        if ($quote) {
            $quote_id = $quote->getId();
            if ($quote_id) {
                if ($qtrR->repoCount($quote_id) > 0)  {
                    $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
                }    
            }    
            // If the quote status is sent 2, viewed 3, or approved 4, or rejected 5
            if (in_array($quote->getStatus_id(),[2,3,4,5])) { 
                // If the user exists        
                /**
                 * @psalm-suppress PossiblyNullArgument $currentUser->getId()
                 */
                if ($uiR->repoUserInvUserIdcount($currentUser->getId()) === 1) {   
                    // After signup the user was included in the userinv using Settings...User Account...+
                    /**
                     * @psalm-suppress PossiblyNullArgument $currentUser->getId()
                     */
                    $user_inv = $uiR->repoUserInvUserIdquery($currentUser->getId());
                    // The client has been assigned to the user id using Setting...User Account...Assigned Clients
                    /**
                     * @psalm-suppress PossiblyNullArgument $currentUser->getId()
                     */                    
                    $user_client = $ucR->repoUserClientqueryCount($currentUser->getId(), $quote->getClient_id()) === 1 ? true : false;
                    if ($user_inv && $user_client) {
                        // If the userinv is a Guest => type = 1 ie. NOT an administrator =>type = 0          
                        // So if the user has a type of 1 they are a guest.
                        if ($user_inv->getType() == 1) {
                            if ($quote->getStatus_id() === 2) {
                                // The quote has just been sent so change its status otherwise leave its status alone        
                               $quote->setStatus_id(3);
                            }    
                            $qR->save($quote);
                            $custom_fields = [
                               'invoice' => $cfR->repoTablequery('inv_custom'),
                               'client' => $cfR->repoTablequery('client_custom'),
                               //'user' => $cfR->repoTablequery('user_custom'),  
                            ];

                            //TODO 
                            // $attachments;
                            if ($quote_id) {
                                $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
                                if ($quote_amount) {
                                    $parameters = [            
                                        'render'=> $this->view_renderer->renderPartialAsString('/invoice/template/quote/public/' . ($this->sR->get_setting('public_quote_template') ?: 'Quote_Web'), [
                                            'isGuest' => $currentUser->isGuest(),
                                            // TODO logo
                                            'logo'=> '',
                                            'alert'=>$this->alert(),
                                            'quote' => $quote,
                                            'quote_item_amount'=>$qiaR,
                                            'quote_amount' => $quote_amount,
                                            'items' => $qiR->repoQuotequery($quote_id),
                                            // Get all the quote tax rates that have been setup for this quote
                                            'quote_tax_rates' => $quote_tax_rates,
                                            'quote_url_key' => $url_key,
                                            'flash_message' => $this->flash('info', ''),
                                            //'attachments' => $attachments,
                                            'custom_fields' => $custom_fields,
                                            'clienthelper' => new ClientHelper($this->sR),
                                            'datehelper' => new DateHelper($this->sR),
                                            'numberhelper' => new NumberHelper($this->sR),
                                            'has_expired' => new \DateTimeImmutable('now') > $quote->getDate_expires() ? true : false,
                                            's'=>$this->sR,
                                            'client'=>$quote->getClient(),
                                            // Get the details of the user of this quote
                                            'userinv'=> $uiR->repoUserInvUserIdcount($quote->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($quote->getUser_id()) : null,                
                                        ]),        
                                    ];        
                                    return $this->view_renderer->render('/invoice/quote/url_key', $parameters);
                                } // if quote_amount    
                                return $this->web_service->getNotFoundResponse(); 
                            } // if there is a quote id 
                            return $this->web_service->getNotFoundResponse(); 
                        } // user_inv->getType
                        return $this->web_service->getNotFoundResponse(); 
                    } // user_inv
                    return $this->web_service->getNotFoundResponse(); 
                } // $uiR    
                return $this->web_service->getNotFoundResponse(); 
            } // if in_array
            return $this->web_service->getNotFoundResponse(); 
        } // if quote
        return $this->web_service->getNotFoundResponse(); 
    }
    
    /**
     * @param ViewRenderer $head
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param CFR $cfR
     * @param CVR $cvR
     * @param PR $pR
     * @param QAR $qaR
     * @param QIAR $qiaR
     * @param QIR $qiR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param TRR $trR
     * @param FR $fR
     * @param UNR $uR
     * @param CR $cR
     * @param GR $gR
     * @param QCR $qcR
     */
    public function view(ViewRenderer $head, CurrentRoute $currentRoute, Request $request,
                         CFR $cfR, CVR $cvR, PR $pR, QAR $qaR, QIAR  $qiaR, QIR $qiR, QR $qR, QTRR $qtrR, TRR $trR, FR $fR,  UNR $uR, CR $cR, GR $gR, QCR $qcR)
                         : \Yiisoft\DataResponse\DataResponse|Response {
        $quote = $this->quote($currentRoute, $qR, false);
        if ($quote) {
            $this->session->set('quote_id',$quote->getId());
            $this->number_helper->calculate_quote($this->session->get('quote_id'), $qiR, $qiaR, $qtrR, $qaR, $qR); 
            $quote_tax_rates = (($qtrR->repoCount($this->session->get('quote_id')) > 0) ? $qtrR->repoQuotequery($this->session->get('quote_id')) : null); 
            if ($quote_tax_rates) {
                $quote_amount = (($qaR->repoQuoteAmountCount($this->session->get('quote_id')) > 0) ? $qaR->repoQuotequery($this->session->get('quote_id')) : null);
                if ($quote_amount) {
                    $quote_custom_values = $this->quote_custom_values($this->session->get('quote_id'), $qcR);
                    $parameters = [
                        'title' => $this->sR->trans('view'),            
                        'body' => $this->body($quote),          
                        's'=>$this->sR,
                        'alert'=>$this->alert(),
                         // Hide buttons on the view if a 'viewInv' user does not have 'editInv' permission
                        'invEdit' => $this->user_service->hasPermission('editInv') ? true : false,       
                        'add_quote_item'=>$this->view_renderer->renderPartialAsString('/invoice/quoteitem/_item_form',[
                                'action' => ['quoteitem/add'],
                                'errors' => [],
                                'body' => $request->getParsedBody(),
                                's'=>$this->sR,
                                'head'=>$head,
                                'quote_id'=>$this->quote($currentRoute, $qR, true),
                                'tax_rates'=>$trR->findAllPreloaded(),
                                'products'=>$pR->findAllPreloaded(),
                                'units'=>$uR->findAllPreloaded(),
                                'numberhelper'=>new NumberHelper($this->sR)
                        ]),
                        // Get all the fields that have been setup for this SPECIFIC quote in quote_custom. 
                        'fields' => $qcR->repoFields($this->session->get('quote_id')),
                        // Get the standard extra custom fields built for EVERY quote. 
                        'custom_fields'=>$cfR->repoTablequery('quote_custom'),
                        'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('quote_custom')),
                        'cvH'=> new CVH($this->sR),
                        'quote_custom_values' => $quote_custom_values,
                        'quote_statuses'=> $qR->getStatuses($this->sR),  
                        'quote'=>$qR->repoQuoteLoadedquery($this->session->get('quote_id')),   
                        'partial_item_table'=>$this->view_renderer->renderPartialAsString('/invoice/quote/partial_item_table',[
                            'invEdit' => $this->user_service->hasPermission('editInv') ? true : false,    
                            'numberhelper'=> new NumberHelper($this->sR),          
                            'products'=>$pR->findAllPreloaded(),
                            'quote_items'=>$qiR->repoQuotequery($this->session->get('quote_id')),
                            'quote_item_amount'=>$qiaR,
                            'quote_tax_rates'=>$quote_tax_rates,
                            'quote_amount'=> $quote_amount,
                            'quote'=>$qR->repoQuoteLoadedquery($this->session->get('quote_id')),  
                            's'=>$this->sR,
                            'tax_rates'=>$trR->findAllPreloaded(),
                            'units'=>$uR->findAllPreloaded(),
                        ]),
                        'modal_choose_items'=>$this->view_renderer->renderPartialAsString('/invoice/product/modal_product_lookups_quote',
                        [
                            's'=>$this->sR,
                            'families'=>$fR->findAllPreloaded(),
                            'default_item_tax_rate'=> $this->sR->get_setting('default_item_tax_rate') !== '' ?: 0,
                            'filter_product'=> '',            
                            'filter_family'=> '',
                            'reset_table'=> '',
                            'products'=>$pR->findAllPreloaded(),
                            'head'=> $head,
                        ]),
                        'modal_add_quote_tax'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_add_quote_tax',['s'=>$this->sR,'tax_rates'=>$trR->findAllPreloaded()]),
                        //'modalhelper'=> new ModalHelper($this->sR),
                        'modal_copy_quote'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_copy_quote',[ 's'=>$this->sR,
                            'quote'=>$qR->repoQuoteLoadedquery($this->session->get('quote_id')),
                            'clients'=>$cR->findAllPreloaded(),                
                            'groups'=>$gR->findAllPreloaded(),
                        ]),
                        'modal_delete_quote'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_delete_quote',
                                ['action'=>['quote/delete', ['id' => $this->session->get('quote_id')]],
                                 's'=>$this->sR,   
                        ]),            
                        'modal_delete_items'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_delete_item',[
                                'partial_item_table_modal'=>$this->view_renderer->renderPartialAsString('/invoice/quoteitem/_partial_item_table_modal',[
                                    'quoteitems'=>$qiR->repoQuotequery($this->session->get('quote_id')),
                                    's'=>$this->sR,
                                    'numberhelper'=>new NumberHelper($this->sR),
                                ]),
                                's'=>$this->sR,
                        ]),
                        'modal_quote_to_invoice'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_quote_to_invoice',[
                                 's'=>$this->sR,
                                 'quote'=> $quote,                        
                                 'groups'=>$gR->findAllPreloaded(),
                        ]),
                        'modal_quote_to_pdf'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_quote_to_pdf',[
                                 's'=>$this->sR,
                                 'quote'=> $quote,                        
                        ]),
                        'view_custom_fields'=>$this->view_renderer->renderPartialAsString('/invoice/quote/view_custom_fields', [
                                 'custom_fields'=>$cfR->repoTablequery('quote_custom'),
                                 'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('quote_custom')),
                                 'quote_custom_values'=> $quote_custom_values,  
                                 'cvH'=> new CVH($this->sR),
                                 's'=>$this->sR,   
                        ]),        
                    ];
                    return $this->view_renderer->render('/invoice/quote/view', $parameters);
                } // quote_amount                   
                return $this->web_service->getNotFoundResponse();
            } //quote_tax_rates
        } //quote    
        return $this->web_service->getNotFoundResponse();
    }
}
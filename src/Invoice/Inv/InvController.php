<?php
declare(strict_types=1); 

namespace App\Invoice\Inv;
// Entity's
use App\Invoice\Entity\EmailTemplate;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvCustom;
use App\Invoice\Entity\InvTaxRate;
use App\Invoice\Entity\TaxRate;
use App\Invoice\Entity\Upload;
// Services
// Inv
use App\User\UserService;
use App\Invoice\Inv\InvService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\InvCustom\InvCustomService;
// Forms Inv
use App\Invoice\Inv\InvAttachmentsForm;
use App\Invoice\Inv\InvForm;
use App\Invoice\Inv\MailerInvForm;
use App\Invoice\InvCustom\InvCustomForm;
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\InvTaxRate\InvTaxRateForm;
// Repositories
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\ClientCustom\ClientCustomRepository as CCR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Payment\PaymentRepository as PYMR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PCR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Project\ProjectRepository as PRJCTR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Task\TaskRepository as TASKR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;
use App\Invoice\Upload\UploadRepository as UPR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserRepository as UR;
use App\Service\WebControllerService;
// App Helpers
use App\Invoice\Helpers\ClientHelper;
Use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\PdfHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\CustomValuesHelper as CVH;
use App\Invoice\Helpers\MailerHelper;
use App\Invoice\Helpers\TemplateHelper;
// Yii
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;
// Psr\Http
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class InvController
{
    private DateHelper $date_helper;
    private DataResponseFactoryInterface $factory;
    private InvAmountService $inv_amount_service;    
    private InvCustomService $inv_custom_service;
    private InvService $inv_service;
    private InvItemService $inv_item_service;
    private IIAS $inv_item_amount_service;
    private InvTaxRateService $inv_tax_rate_service;
    private LoggerInterface $logger;    
    private MailerInterface $mailer;    
    private NumberHelper $number_helper; 
    private PdfHelper $pdf_helper;  
    private SessionInterface $session;
    private SR $sR;
    private TranslatorInterface $translator;
    private UrlGenerator $url_generator;
    private UserService $user_service;
    private ViewRenderer $view_renderer;
    private WebControllerService $web_service;
    
    /**
     * @param DataResponseFactoryInterface $factory
     * @param InvAmountService $inv_amount_service
     * @param InvService $inv_service
     * @param InvCustomService $inv_custom_service
     * @param InvItemService $inv_item_service
     * @param IIAS $inv_item_amount_service
     * @param InvTaxRateService $inv_tax_rate_service
     * @param LoggerInterface $logger
     * @param MailerInterface $mailer
     * @param SessionInterface $session
     * @param SR $sR
     * @param TranslatorInterface $translator
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
        IIAS $inv_item_amount_service,
        InvTaxRateService $inv_tax_rate_service,
        LoggerInterface $logger,
        MailerInterface $mailer,    
        SessionInterface $session,
        SR $sR,
        TranslatorInterface $translator,
        UserService $user_service,        
        UrlGenerator $url_generator,
        ViewRenderer $view_renderer,
        WebControllerService $web_service,                        
    )
    {
        $this->date_helper = new DateHelper($sR);
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
        if ($this->user_service->hasPermission('viewInv') && $this->user_service->hasPermission('editInv')) {
            $this->view_renderer = $view_renderer->withControllerName('invoice')
                                                 ->withLayout('@views/layout/invoice.php');
        }
        $this->web_service = $web_service;
    }
    
    /**
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
     * @param Request $request
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function archive(Request $request): \Yiisoft\DataResponse\DataResponse{            
        // TODO filter system: Currently the filter is disabled on the archive view.
        $invoice_archive = [];
        $flash_message = '';
        if ($request->getMethod() === Method::POST) { 
            $body = $request->getParsedBody(); 
            if (is_array($body)) { 
                foreach ($body as $key => $value) {
                    if (((string)$key === 'invoice_number')) {
                       $invoice_archive = $this->sR->get_invoice_archived_files_with_filter($value);
                       $flash_message = $value;
                    }
                }
            }
        } else {
            $invoice_archive = $this->sR->get_invoice_archived_files_with_filter('');
            $flash_message = '';
        }
        $parameters = [ 
                'partial_inv_archive'=>$this->view_renderer->renderPartialAsString('/invoice/inv/partial_inv_archive',
                        [                             
                            'invoices_archive'=>$invoice_archive
                        ]),           
                'flash'=>$this->flash('',''.$flash_message),
                'body'=>$request->getParsedBody(),
        ];        
        return $this->view_renderer->render('/invoice/inv/archive', $parameters);        
    }
    
    /**
     * @param string $tmp
     * @param string $target
     * @param int $client_id
     * @param string $url_key
     * @param string $fileName
     * @param UPR $uPR
     * @return bool
     */
    
    private function attachment_move_to(string $tmp, string $target, int $client_id, string $url_key, string $fileName, UPR $uPR
    ) : bool {
        $file_exists = file_exists($target);
        // The file does not exist yet in the target path but it exists in the tmp folder on the server
        if (!$file_exists) {
            // Record the details of this upload
           
            // (@see https://www.php.net/manual/en/function.is-uploaded-file.php)
            // Returns true if the file named by filename was uploaded via HTTP POST. 
            // This is useful to help ensure that a malicious user hasn't tried to trick
            // the script into working on files upon which it should not be working--for instance, /etc/passwd.
            // This sort of check is especially important if there is any chance that anything
            // done with uploaded files could reveal their contents to the user, or even to other users on the same
            // system. For proper working, the function is_uploaded_file() needs an argument like 
            // $_FILES['userfile']['tmp_name'], - the name of the uploaded file on the client's machine
            // $_FILES['userfile']['name'] does not work.
            if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $target)) {
                $track_file = new Upload();
                $track_file->setClient_id($client_id);
                $track_file->setUrl_key($url_key);
                $track_file->setFile_name_original($fileName);
                $track_file->setFile_name_new($url_key.'_'.$fileName);
                $track_file->setUploaded_date(new \DateTime());
                $uPR->save($track_file);
                return true;                        
            } else {
                $this->flash('warning', 'Possible file upload attack: '.$tmp);
                return false;   
            }
        } else {
            $this->flash('warning', $this->sR->trans('error_duplicate_file')); 
            return false;
        }
    }    
    
    /**
     * @param int $inv_id
     * @return string
     */    
    private function attachment_not_writable(int $inv_id) : string {
        return $this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                ['heading'=>$this->sR->trans('errors'), 'message'=>$this->sR->trans('path').$this->sR->trans('is_not_writable'),
                'url'=>'inv/view', 'id'=>$inv_id]);
    }
    
    /**
     * @param int $inv_id
     * @return string
     */    
    private function attachment_successfully_created(int $inv_id) : string {
        return $this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                ['heading'=>'', 'message'=>$this->sR->trans('record_successfully_created'), 
                'url'=>'inv/view', 'id'=>$inv_id]);
    }
    
    /**
     * @param int $inv_id
     * @return string
     */
    
    private function attachment_no_file_uploaded(int $inv_id) : string {        
        return $this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                ['heading'=>$this->sR->trans('errors'), 'message'=>'No file uploaded',
                 'url'=>'inv/view', 'id'=>$inv_id]);     
    }
    
    /**
     * Upload a file
     *
     * @param CurrentRoute $currentRoute
     * @param IR $iR
     * @param UPR $uPR
     */
    public function attachment(CurrentRoute $currentRoute, IR $iR, UPR $uPR) : \Yiisoft\DataResponse\DataResponse|Response {
        $aliases = $this->sR->get_customer_files_folder_aliases();
        $targetPath = $aliases->get('@customer_files');
        $inv_id = $currentRoute->getArgument('id');
        if (null!==$inv_id) {
            if (!is_writable($targetPath)) { 
                return $this->factory->createResponse($this->attachment_not_writable((int)$inv_id));                    
            }   
            $invoice = $iR->repoInvLoadedquery($inv_id) ?: null;
            if ($invoice instanceof Inv) {
                $client_id = $invoice->getClient()?->getClient_id();
                if ($client_id) {
                    $url_key = $invoice->getUrl_key();
                    if (!empty($_FILES)) {
                        // @see https://github.com/vimeo/psalm/issues/5458

                        /**
                         * @psalm-suppress InvalidArrayOffset 
                         */
                        $temporary_file = $_FILES['InvAttachmentsForm']['tmp_name']['attachFile']; 
                        /**
                         * @psalm-suppress InvalidArrayOffset
                         */
                        $original_file_name = preg_replace('/\s+/', '_', $_FILES['InvAttachmentsForm']['name']['attachFile']);
                        $target_path_with_filename = $targetPath . '/' . $url_key .'_'.$original_file_name;
                        if ($this->attachment_move_to($temporary_file, $target_path_with_filename, $client_id, $url_key, $original_file_name, $uPR)) {       
                            return $this->factory->createResponse($this->attachment_successfully_created((int)$inv_id));
                        } else {
                            return $this->factory->createResponse($this->attachment_no_file_uploaded((int)$inv_id));
                        }            
                    } else {
                        return $this->factory->createResponse($this->attachment_no_file_uploaded((int)$inv_id));
                    }
                } // $client_id    
            } // $invoice
            return $this->web_service->getRedirectResponse('inv/index'); 
        } //null!==$inv_id 
        return $this->web_service->getRedirectResponse('inv/index');  
    }
    
    /**
     * 
     * @param object $inv
     * @return array
     */
    private function body(object $inv): array {
        $body = [
          'number'=>$inv->getNumber(),
            
          'id'=>$inv->getId(),
          'user_id'=>$inv->getUser_id(),
          
          'client_id'=>$inv->getClient_id(),          
         
          'date_created'=>$inv->getDate_created(),
          'date_modified'=>$inv->getDate_modified(),
          'date_due'=>$inv->getDate_due(),            
            
          'group_id'=>$inv->getGroup_id(),
          'status_id'=>$inv->getStatus_id(),
          'is_read_only'=>$inv->getIs_read_only(),
          'creditinvoice_parent_id'=>$inv->getCreditinvoice_parent_id(),
          
          'discount_amount'=>$inv->getDiscount_amount(),
          'discount_percent'=>$inv->getDiscount_percent(),
          'url_key'=>$inv->getUrl_key(),
          'password'=>$inv->getPassword(),
          
          'payment_method'=>$inv->getPayment_method(),
          'terms'=>$inv->getTerms()  
            
        ];
        return $body;
    }
        
    // Data fed from inv.js->$(document).on('click', '#inv_create_confirm', function () {
    
    public function create_confirm(CurrentUser $currentUser, Request $request, ValidatorInterface $validator, GR $gR, TRR $trR, IAR $iaR) : \Yiisoft\DataResponse\DataResponse
    { 
        $body = $request->getQueryParams() ?? [];        
        $ajax_body = [
            'quote_id'=>null,
            'client_id'=>$body['client_id'],
            'group_id'=>$body['inv_group_id'],
            'creditinvoice_parent_id'=>null ,
            'status_id'=>1,
            'is_read_only'=>0,
            'number'=>$this->sR->get_setting('generate_invoice_number_for_draft') === '1' ? $gR->generate_number((int)$body['inv_group_id'], true):'',
            'discount_amount'=>floatval(0),
            'discount_percent'=>floatval(0),
            'url_key'=>Random::string(32),
            'password'=>$body['inv_password'], 
            'payment_method'=>$this->sR->get_setting('default_payment_method') ?: 0, 
            'terms'=>$this->sR->get_setting('default_invoice_terms'),
        ];
        $ajax_content = new InvForm();
        $inv = new Inv();
        $invamount = new InvAmount();
        if ($ajax_content->load($ajax_body) && $validator->validate($ajax_content)->isValid()) {    
            $saved_model = $this->inv_service->addInv($currentUser, $inv, $ajax_content, $this->sR);
            $this->inv_amount_service->initializeInvAmount($invamount, $saved_model->getId());
            $this->default_taxes($inv, $trR, $validator);
            $parameters = ['success'=>1];
            // Inform the user of generated invoice number for draft setting
            $this->flash('info',$this->sR->get_setting('generate_invoice_number_for_draft') === '1' 
                  ? $this->sR->trans('generate_invoice_number_for_draft').'=>'.$this->sR->trans('yes') 
                  : $this->sR->trans('generate_invoice_number_for_draft').'=>'.$this->sR->trans('no') );
            //return response to inv.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));          
        } else {
            $parameters = [
               'success'=>0,
            ];
            //return response to inv.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));          
        } 
    }
    
    // Reverse an invoice with a credit invoice/ debtor/client/customer credit note
    
    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param IR $iR
     * @param GR $gR
     * @param IAR $iaR
     * @param IIR $iiR
     * @param IIAR $iiaR
     */
    public function create_credit_confirm(Request $request, ValidatorInterface $validator,IR $iR, GR $gR, IAR $iaR, IIR $iiR, IIAR $iiaR) : \Yiisoft\DataResponse\DataResponse|Response {
        $body = $request->getQueryParams() ?? [];
        if (is_array($body)) {
                $basis_inv = $iR->repoInvLoadedquery($body['inv_id']);
                if (null!==$basis_inv) {
                    $basis_inv_id = $body['inv_id'];
                    // Set the basis_inv to read-only;
                    $basis_inv->setIs_read_only(true);
                    $ajax_body = [
                        'client_id'=>$body['client_id'],
                        'group_id'=>$body['group_id'],
                        'user_id'=>$body['user_id'],
                        'creditinvoice_parent_id'=>$body['inv_id'],
                        'status_id'=>$basis_inv->getStatus_id(),
                        'is_read_only'=>false,
                        'number'=>$gR->generate_number($body['group_id'], true),
                        'discount_amount'=>null,
                        'discount_percent'=>null,
                        'url_key'=>'',
                        'password'=>$body['password'], 
                        'payment_method'=>0,
                        'terms'=>'',
                    ];
                    // Save the basis invoice
                    $iR->save($basis_inv);
                    $ajax_content = new InvForm();
                    $new_inv = new Inv();
                    $current_user = $this->user_service->getUser();
                    if (null!==$current_user) {
                    // guest will return null; if not null => not guest
                        if ($ajax_content->load($ajax_body) && $validator->validate($ajax_content)->isValid()) {    
                                // The current user cannot be a guest ie. null!==$current_user
                                /** @psalm-suppress PossiblyNullArgument */
                                $saved_inv = $this->inv_service->saveInv($current_user, $new_inv,$ajax_content, $this->sR, $gR);
                                $this->inv_item_service->initializeCreditInvItems((int)$basis_inv_id, $saved_inv->getId(), $iiR,$iiaR, $this->sR);
                                $this->inv_amount_service->initializeCreditInvAmount(new InvAmount(), (int)$basis_inv_id, $saved_inv->getId() );
                                $this->inv_tax_rate_service->initializeCreditInvTaxRate((int)$basis_inv_id, $saved_inv->getId());
                                $parameters = ['success'=>1];
                                //return response to inv.js to reload page at location
                                return $this->factory->createResponse(Json::encode($parameters));                                      
                        } else {
                            $parameters = [
                               'success'=>0,
                            ];
                            //return response to inv.js to reload page at location
                            return $this->factory->createResponse(Json::encode($parameters));          
                        }
                        return $this->web_service->getRedirectResponse('inv/index'); 
                    }
                    return $this->web_service->getRedirectResponse('inv/index'); 
            } //null!==$basis_inv    
            return $this->web_service->getRedirectResponse('inv/index'); 
        } // if is_array $body    
        return $this->web_service->getRedirectResponse('inv/index'); 
           
    }
    
    /**
     * 
     * @param Inv $inv
     * @param TRR $trR
     * @param ValidatorInterface $validator
     * @return void
     */
    public function default_taxes(Inv $inv, TRR $trR, ValidatorInterface $validator): void{
        if ($trR->repoCountAll() > 0) {
            $taxrates = $trR->findAllPreloaded();
            foreach ($taxrates as $taxrate) {                
              if ($taxrate instanceof TaxRate) { 
                $taxrate->getTax_rate_default()  == 1 ? $this->default_tax_inv($taxrate, $inv, $validator) : '';
              }  
            }
        }        
    }
    
    /**
     * 
     * @param object $taxrate
     * @param object $inv
     * @param ValidatorInterface $validator
     * @return void
     */
    public function default_tax_inv(object $taxrate, object $inv, ValidatorInterface $validator) : void {
            $inv_tax_rate_form = new InvTaxRateForm();
            $inv_tax_rate = [];
            $inv_tax_rate['inv_id'] = $inv->getId();
            $inv_tax_rate['tax_rate_id'] = $taxrate->getTax_rate_id();
            $inv_tax_rate['include_item_tax'] = 0;
            $inv_tax_rate['inv_tax_rate_amount'] = 0;
            ($inv_tax_rate_form->load($inv_tax_rate) && $validator->validate($inv_tax_rate_form)->isValid()) ? 
            $this->inv_tax_rate_service->saveInvTaxRate(new InvTaxRate(), $inv_tax_rate_form) : '';        
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param InvRepository $invRepo
     * @param ICR $icR
     * @param InvCustomService $icS
     * @param IIR $iiR
     * @param InvItemService $iiS
     * @param ITRR $itrR
     * @param InvTaxRateService $itrS
     * @param IAR $iaR
     * @param InvAmountService $iaS
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, InvRepository $invRepo, 
                           ICR $icR, InvCustomService $icS, IIR $iiR, InvItemService $iiS, ITRR $itrR,
                           InvTaxRateService $itrS, IAR $iaR, InvAmountService $iaS): Response {
        try {
            $inv = $this->inv($currentRoute, $invRepo);
            if ($inv) {
                $this->inv_service->deleteInv($inv, $icR, $icS, $iiR, $iiS, $itrR, $itrS, $iaR, $iaS); 
                $this->flash('info', $this->sR->trans('record_successfully_deleted'));
                return $this->web_service->getRedirectResponse('inv/index'); 
            }
            return $this->web_service->getRedirectResponse('inv/index'); 
	} catch (\Exception $e) {
            $this->flash('danger', $e->getMessage());            
            unset($e);
            return $this->web_service->getRedirectResponse('inv/index'); 
        }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param IIR $iiR
     */
    public function delete_inv_item(CurrentRoute $currentRoute, IIR $iiR ) : \Yiisoft\DataResponse\DataResponse|Response {
        try {
            $inv_item = $this->inv_item($currentRoute,$iiR);
            if ($inv_item) {
                $this->inv_item_service->deleteInvItem($inv_item);                
            }
            return $this->web_service->getRedirectResponse('inv/index');  
        } catch (\Exception $e) {
            $this->flash('danger', $e->getMessage());
            unset($e);
        }
        $inv_id = $this->session->get('inv_id');
        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
        ['heading'=>$this->sR->trans('invoice_items'),'message'=>$this->sR->trans('record_successfully_deleted'),'url'=>'inv/view','id'=>$inv_id]));  
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ITRR $invtaxrateRepository
     */
    public function delete_inv_tax_rate(CurrentRoute $currentRoute, ITRR $invtaxrateRepository) : \Yiisoft\DataResponse\DataResponse|Response {
        try {            
            $inv_tax_rate = $this->invtaxrate($currentRoute, $invtaxrateRepository);
            $this->inv_tax_rate_service->deleteInvTaxRate($inv_tax_rate);             
        } catch (\Exception $e) {           
            $this->flash('danger', $e->getMessage());
            unset($e);
        }
        $inv_id = $this->session->get('inv_id');
        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
        ['heading'=>$this->sR->trans('invoice_tax_rate'),'message'=>$this->sR->trans('record_successfully_deleted'),'url'=>'inv/view','id'=>$inv_id]));  
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UPR $upR
     *
     * @return never
     */
    public function download_file(CurrentRoute $currentRoute, UPR $upR)  {
        $upload_id = $currentRoute->getArgument('upload_id');
        if (null!==$upload_id) {
            $upload = $upR->repoUploadquery($upload_id);
            if (null!==$upload) {
                $aliases = $this->sR->get_customer_files_folder_aliases();
                $targetPath = $aliases->get('@customer_files');
                $original_file_name = $upload->getFile_name_original();
                $url_key = $upload->getUrl_key();
                $target_path_with_filename = $targetPath . '/' . $url_key .'_'.$original_file_name;
                $path_parts = pathinfo($target_path_with_filename);
                
                $file_ext = $path_parts['extension'] ?? '';
                if (file_exists($target_path_with_filename)) {
                    $file_size = filesize($target_path_with_filename);
                    $allowed_content_type_array = $upR->getContentTypes(); 
                    // Check extension against allowed content file types @see UploadRepository getContentTypes
                    $save_ctype = isset($allowed_content_type_array[$file_ext]);
                    $ctype = $save_ctype ? $allowed_content_type_array[$file_ext] : $upR->getContentTypeDefaultOctetStream();
                    // https://www.php.net/manual/en/function.header.php
                    // Remember that header() must be called before any actual output is sent, either by normal HTML tags,
                    // blank lines in a file, or from PHP.
                    header("Expires: -1");
                    header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
                    header("Content-Disposition: attachment; filename=\"$original_file_name\"");
                    header("Content-Type: " . $ctype);
                    header("Content-Length: " . $file_size);
                    echo file_get_contents($target_path_with_filename, true);
                    exit;
                } //if file_exists
                exit;
            } //null!==upload
            exit;
        } //null!==$upload_id
        exit;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @return void
     */
    public function download(CurrentRoute $currentRoute) : void
    {
        $aliases = $this->sR->get_invoice_archived_folder_aliases();
        $invoice = $currentRoute->getArgument('invoice');        
        if ($invoice) {
            header('Content-type: application/pdf');
            header('Content-Disposition: attachment; filename="' . urldecode($invoice) . '"');
            readfile($aliases->get('@archive_invoice'). DIRECTORY_SEPARATOR.urldecode($invoice));
        }        
    }
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param IR $invRepo
     * @param CR $clientRepo
     * @param GR $groupRepo
     * @param PMR $pmRepo
     * @param UR $userRepo
     * @param IAR $iaR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param ICR $icR
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        ValidatorInterface $validator,
                        IR $invRepo,
                        CR $clientRepo,
                        GR $groupRepo,
                        PMR $pmRepo,
                        UR $userRepo,
                        IAR $iaR,
                        CFR $cfR,
                        CVR $cvR,
                        ICR $icR
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $inv = $this->inv($currentRoute, $invRepo, true);
        if ($inv) {
            $inv_id = $inv->getId();
            $action = ['inv/edit', ['id' => $inv_id]];
            $parameters = [
                'title' => '',
                'action' => $action,
                'errors' => [],
                'body' => $this->body($inv),
                'head'=>$head,            
                'clients'=>$clientRepo->findAllPreloaded(),
                'groups'=>$groupRepo->findAllPreloaded(),
                'users'=>$userRepo->findAll(),
                'numberhelper' => $this->number_helper,
                'invs'=> $invRepo->findAllPreloaded(),
                'inv_statuses'=> $invRepo->getStatuses($this->sR),
                'cvH'=> new CVH($this->sR),
                'custom_fields'=>$cfR->repoTablequery('inv_custom'),
                // Applicable to normally building up permanent selection lists eg. dropdowns
                'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('inv_custom')),
                // There will initially be no custom_values attached to this invoice until they are filled in the field on the form
                'inv_custom_values' => $this->inv_custom_values($inv_id, $icR),
                'payment_methods' => $pmRepo->findAllPreloaded(),
            ];
            if ($request->getMethod() === Method::POST) {   
                $edited_body = $request->getParsedBody();
                if (is_array($edited_body)) {
                    // If the status has changed to 'paid', check that the balance on the invoice is zero 
                    if (!$this->edit_check_status_reconciling_with_balance($iaR, (int)$inv_id) && $edited_body['status_id'] === 4 ){  
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                        ['heading' => $this->sR->trans('errors'),'message'=>$this->sR->trans('error'). 'Balance does not equal zero. Status is Paid => Balance should be zero. ',
                            'url'=>'inv/view','id'=>$inv_id]));
                    }
                    $returned_form = $this->edit_save_form_fields($edited_body, $currentRoute, $validator, $invRepo, $groupRepo);
                    $parameters['body'] = $edited_body;
                    if ($returned_form instanceof InvForm) {
                        $parameters['errors']=$returned_form->getFormErrors();
                        $this->edit_save_custom_fields($edited_body, $validator, $icR, $inv_id); 
                        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                        ['heading' => '','message'=>
                            //VarDumper::dump($returned_form), 
                            $this->sR->trans('record_successfully_updated'),
                            'url'=>'inv/view','id'=>$inv_id]));
                    }    
                } //$edited_body    
                return $this->web_service->getRedirectResponse('inv/index');  
            }
            return $this->view_renderer->render('/invoice/inv/_form', $parameters);
        } // if $inv_id
        return $this->web_service->getRedirectResponse('inv/index'); 
    }
    
    /**
     * 
     * @param IAR $iaR
     * @param int $inv_id
     * @return bool
     */
    public function edit_check_status_reconciling_with_balance(IAR $iaR, int $inv_id) : bool {
        $invoice_amount = $iaR->repoInvquery($inv_id);
        if ($invoice_amount) {
        // If the invoice is fully paid up allow the status to change to 'paid'
            return ($invoice_amount->getBalance() == 0.00 ? true : false);        
        }
        return false;
    }
    
     /**
      * @param array|object|null $edited_body
      * @param CurrentRoute $currentRoute
      * @param ValidatorInterface $validator
      * @param IR $invRepo
      * @param GR $groupRepo
      * @param IAR $iaR
      * @return InvForm|null
      */
     public function edit_save_form_fields(array|object|null $edited_body, CurrentRoute $currentRoute, ValidatorInterface $validator, IR $invRepo, GR $groupRepo) : InvForm|null {
        $inv = $this->inv($currentRoute, $invRepo, true);
        if ($inv) {
            $form = new InvForm();
            if ($form->load($edited_body) && $validator->validate($form)->isValid()) {
                /**
                 * @psalm-suppress PossiblyNullArgument
                 */
                $this->inv_service->saveInv($this->user_service->getUser(), $inv,$form, $this->sR, $groupRepo);
            }
            return $form;
        }
        return null;
    }
    
    /**
     * @param array|object|null $parse
     * @param ValidatorInterface $validator
     * @param ICR $icR
     * @param string|null $inv_id
     * @return void
     */
    public function edit_save_custom_fields(array|object|null $parse, ValidatorInterface $validator, ICR $icR, string|null $inv_id): void {
        $custom = $parse['custom'] ?? [];
        if (is_array($custom)) {
            foreach ($custom as $custom_field_id => $value) {
                 if (($icR->repoInvCustomCount((string)$inv_id, (string)$custom_field_id)) == 0) {
                    $inv_custom = new InvCustom();                
                    $inv_custom_input = [
                        'inv_id'=>(int)$inv_id,
                        'custom_field_id'=>(int)$custom_field_id,
                        'value'=>(string)$value
                    ];
                    $form = new InvCustomForm();
                    if ($form->load($inv_custom_input) && $validator->validate($form)->isValid())
                    {
                        $this->inv_custom_service->saveInvCustom($inv_custom, $form);     
                    }
                 } else {
                    $inv_custom = $icR->repoFormValuequery((string)$inv_id, (string)$custom_field_id);
                    if ($inv_custom) {
                        $inv_custom_input = [
                            'inv_id'=>(int)$inv_id,
                            'custom_field_id'=>(int)$custom_field_id,
                            'value'=>(string)$value
                        ];
                        $form = new InvCustomForm();
                        if ($form->load($inv_custom_input) && $validator->validate($form)->isValid())
                        {
                            $this->inv_custom_service->saveInvCustom($inv_custom, $form);     
                        } 
                    } // inv_custom
                 } // count
            } // custom 
        } //is_array custom    
    }
    
    public function email_get_invoice_templates(string $type = 'pdf') : array
    {
        return $this->sR->get_invoice_templates($type);
    }
    
    /**
     * @param ViewRenderer $head
     * @param CurrentRoute $currentRoute
     * @param CCR $ccR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param ETR $etR
     * @param ICR $icR
     * @param IR $iR
     * @param PCR $pcR
     * @param QCR $qcR
     * @param UIR $uiR
     * @return Response
     */
    
    public function email_stage_0(ViewRenderer $head, 
                                  CurrentRoute $currentRoute, 
                                  CCR $ccR, CFR $cfR, CVR $cvR, 
                                  ETR $etR, 
                                  ICR $icR, IR $iR, 
                                  PCR $pcR, QCR $qcR, UIR $uiR) : Response 
    {
        $parameters = [];
        $mailer_helper = new MailerHelper($this->sR, $this->session, $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $cfR, $cvR);
        $template_helper = new TemplateHelper($this->sR, $ccR, $qcR, $icR, $pcR, $cfR, $cvR);
        if (!$mailer_helper->mailer_configured()) {
            $this->flash('warning', $this->sR->trans('email_not_configured'));
            return $this->web_service->getRedirectResponse('inv/index');
        }               
        $inv = $this->inv($currentRoute, $iR, true);
        if ($inv instanceof Inv) {
            $inv_id = $inv->getId();
            $invoice = $iR->repoInvUnLoadedquery((string)$inv_id);
            if ($invoice instanceof Inv) {
                $email_template_id = $template_helper->select_email_invoice_template($invoice);        
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
                    // TODO
                    //'user_custom' => 'user',
                ];
                foreach (array_keys($custom_tables) as $table) {
                    $custom_fields[$table] = $cfR->repoTablequery($table);
                }
                if ($template_helper->select_email_invoice_template($invoice) == '') {
                    $this->flash('warning', 'Email templates not configured. Settings...Invoices...Invoice Templates...Default Email Template');
                    return $this->web_service->getRedirectResponse('setting/tab_index');
                }
                $setting_status_email_template = $etR->repoEmailTemplatequery($template_helper->select_email_invoice_template($invoice)) ?: null;                                              
                null===$setting_status_email_template ? $this->flash('info',
                                                  $this->sR->trans('default_email_template').'=>'.
                                                  $this->sR->trans('not_set')) : '';        
                empty($template_helper->select_pdf_invoice_template($invoice)) ? $this->flash('info',
                                                  $this->sR->trans('default_pdf_template').'=>'.
                                                  $this->sR->trans('not_set')) : '';        
                $parameters = [
                    'head'=> $head,
                    'action' => ['inv/email_stage_2', ['id' => $inv_id]],
                    'alert'=>$this->alert(),
                    // If email templates have been built under Setting...Email Template for Normal, Overdue, and Paid
                    // and Setting...View...Invoice...Invoice Templates have been linked to these built email templates
                    // then an email template should automatically appear on the mailer_invoice form by passing the
                    // status related email template to the get_inject_email_template_array function            
                    'auto_template' => null!== $setting_status_email_template 
                                           ? $this->get_inject_email_template_array($setting_status_email_template) 
                                           : [],
                    //eg. If the invoice is overdue ie. status is 5, automatically select the 'overdue' pdf template
                    //which has 'overdue' text on it as a watermark
                    'setting_status_pdf_template' => $template_helper->select_pdf_invoice_template($invoice),            
                    'email_templates' => $etR->repoEmailTemplateType('invoice'),
                    'dropdown_titles_of_email_templates' => $this->email_templates($etR),
                    'userinv'=> $uiR->repoUserInvUserIdcount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null,
                    'invoice' => $invoice,
                    // All templates ie. overdue, paid, invoice
                    'pdf_templates'=> $this->email_get_invoice_templates('pdf'),
                    'template_tags'=> $this->view_renderer->renderPartialAsString('/invoice/emailtemplate/template-tags',[                    
                        'custom_fields'=> $custom_fields,
                        'template_tags_quote'=>'',
                        'template_tags_inv'=>$this->view_renderer->renderPartialAsString('/invoice/emailtemplate/template-tags-inv', [
                                's'=> $this->sR,
                                'custom_fields_inv_custom'=>$custom_fields['inv_custom'],
                        ]), 
                    ]),
                    'form' => new MailerInvForm(),
                    'custom_fields'=> $custom_fields,
                ];   
                return $this->view_renderer->render('/invoice/inv/mailer_invoice', $parameters);  
            }// if invoice
            return $this->web_service->getRedirectResponse('inv/index');
        } // if $inv    
        return $this->web_service->getRedirectResponse('inv/index');
    }
    
    /**
     * 
     * @param object $email_template
     * @return array
     */
    public function get_inject_email_template_array(object $email_template) : array {
        $email_template_array = [
                'body' => Json::htmlEncode($email_template->getEmail_template_body()),
                'subject'=> $email_template->getEmail_template_subject() ?? '',
                'from_name'=> $email_template->getEmail_template_from_name() ?? '',
                'from_email'=> $email_template->getEmail_template_from_email() ?? '',
                'cc'=> $email_template->getEmail_template_cc() ?? '',
                'bcc'=> $email_template->getEmail_template_bcc() ?? '',
                'pdf_template'=> null!==$email_template->getEmail_template_pdf_template()? $email_template->getEmail_template_pdf_template(): '',
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
        $email_templates = $etR->repoEmailTemplateType('invoice');
        $data = [];
        foreach ($email_templates as $email_template) {
           if ($email_template instanceof EmailTemplate) { 
               if (null!==$email_template->getEmail_template_id()) {
                   $data[] = $email_template->getEmail_template_title();
               }
           } 
        }
        return $data;
    }
    
    /**
     * @param null|string $inv_id
     *
     * @psalm-param array{0: mixed, 1: mixed} $from
     */
    public function email_stage_1(string|null $inv_id, 
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
                                  IIAR $iiaR, 
                                  IIR $iiR, 
                                  IR $iR, 
                                  ITRR $itrR, 
                                  PCR $pcR, 
                                  QR $qR, 
                                  QAR $qaR, 
                                  QCR $qcR, 
                                  UIR $uiR, 
                                  ViewRenderer $viewrenderer) : bool
    {
        $template_helper = new TemplateHelper($this->sR, $ccR, $qcR, $icR, $pcR, $cfR, $cvR);
        $mailer_helper = new MailerHelper($this->sR, $this->session, $this->logger, $this->mailer, 
                                          $ccR, $qcR, $icR, $pcR, $cfR, $cvR);
        if ($inv_id) {
            $inv_amount = (($iaR->repoInvAmountCount((int)$inv_id) > 0) ? $iaR->repoInvquery((int)$inv_id) : null);        
            $inv_custom_values = $this->inv_custom_values($inv_id, $icR);
            $inv = $iR->repoCount($inv_id) > 0 ? $iR->repoInvUnLoadedquery($inv_id) : null;
            if ($inv) {
                $stream = false;
                // true => invoice ie. not quote
                // $stream is false => pdfhelper->generate_inv_pdf => mpdfhelper->pdf_Create => filename returned 
                $pdf_template_target_path = $this->pdf_helper->generate_inv_pdf($inv_id, $inv->getUser_id(), $stream, true, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $iR, $itrR, $uiR, $viewrenderer); 
                $mail_message = $template_helper->parse_template($inv_id, true, $email_body, $cR, $cvR, $iR, $iaR, $qR,  $qaR, $uiR);
                $mail_subject = $template_helper->parse_template($inv_id, true, $subject, $cR, $cvR, $iR, $iaR, $qR,  $qaR, $uiR);
                $mail_cc = $template_helper->parse_template($inv_id, true, $cc, $cR, $cvR, $iR, $iaR, $qR,  $qaR, $uiR);
                $mail_bcc = $template_helper->parse_template($inv_id, true, $bcc, $cR, $cvR, $iR, $iaR, $qR,  $qaR, $uiR);
                $mail_from = // from[0] is the from_email and from[1] is the from_name    
                    array($template_helper->parse_template($inv_id, true, $from[0], $cR, $cvR, $iR, $iaR, $qR,  $qaR, $uiR), 
                          $template_helper->parse_template($inv_id, true, $from[1], $cR, $cvR, $iR, $iaR, $qR,  $qaR, $uiR));
                //$message = (empty($mail_message) ? 'this is a message ' : $mail_message);
                $message = $mail_message;
                // mail_from[0] is the from_email and mail_from[1] is the from_name
                return $mailer_helper->yii_mailer_send($mail_from[0], $mail_from[1], 
                                                       $to, $mail_subject, $message, $mail_cc, $mail_bcc, 
                                                       $attachFiles, $pdf_template_target_path, $uiR);
            } //inv
            return false;
        } // inv_id
        return false;
    }
    
    // The views/invoice/inv/mailer_inv form is submitted     
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param CR $cR
     * @param CCR $ccR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param GR $gR
     * @param IAR $iaR
     * @param IIAR $iiaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IR $iR
     * @param ITRR $itrR
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
                                  IAR $iaR, IIAR $iiaR, ICR $icR, IIR $iiR, IR $iR, ITRR $itrR, 
                                  PCR $pcR, QR $qR, QAR $qaR, QCR $qcR, UIR $uiR) : Response 
    {
        $inv_id = $currentRoute->getArgument('id');
        if ($inv_id) {
            $mailer_helper = new MailerHelper($this->sR, $this->session, $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $cfR, $cvR);
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $body['btn_cancel'] = 0;
                if (!$mailer_helper->mailer_configured()) {
                    $this->flash('warning', $this->sR->trans('email_not_configured'));
                    return $this->web_service->getRedirectResponse('inv/index');
                }
                $to = $body['MailerInvForm']['to_email'] ?? '';
                if (empty($to)) {
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_to_address_missing'),'url'=>'inv/view','id'=>$inv_id]));  
                }

                $from = [
                    $body['MailerInvForm']['from_email'] ?? '',
                    $body['MailerInvForm']['from_name'] ?? '',
                ];

                if (empty($from[0])) {
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_to_address_missing'),'url'=>'inv/view','id'=>$inv_id]));  
                }

                // Use the pdf template that has been selected on the Mailer Form
                // It can be either normal, paid, or overdue
                // Check that the setting email_pdf_attachment is set to yes under Settings...Email
                if ($this->sR->get_setting('email_pdf_attachment') == '1') { 
                    $pdf_template = $body['MailerInvForm']['pdf_template'] ?? '';
                }
                $subject = $body['MailerInvForm']['subject'] ?? '';
                $email_body = $body['MailerInvForm']['body'] ?? '';

                if (strlen($email_body) !== strlen(strip_tags($email_body))) {
                    $email_body = htmlspecialchars_decode($email_body); 
                } else {
                    $email_body = htmlspecialchars_decode(nl2br($email_body));
                }

                $cc = $body['MailerInvForm']['cc'] ?? '';
                $bcc = $body['MailerInvForm']['bcc'] ?? '';

                $attachFiles = $request->getUploadedFiles();

                $this->generate_inv_number_if_applicable($inv_id, $iR, $this->sR, $gR);

                // Custom fields are automatically included on the invoice
                if ($this->email_stage_1($inv_id, $from, $to, $subject, $email_body, $cc, $bcc, $attachFiles,
                                         $cR, $ccR, $cfR,  $cvR, 
                                         $iaR, $icR, $iiaR, $iiR, $iR, $itrR, 
                                         $pcR, 
                                         $qR, $qaR, $qcR, $uiR, $this->view_renderer)) {
                    $this->sR->invoice_mark_sent($inv_id, $iR);            
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_successfully_sent'),
                     'url'=>'inv/view','id'=>$inv_id]));  
                } else {
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_not_sent'),
                     'url'=>'inv/view','id'=>$inv_id]));              
                } //$this->email_stage_1
            } //is_array(body)    
            return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_not_sent'),
                     'url'=>'inv/view','id'=>$inv_id]));
        }
        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_not_sent'),
                     'url'=>'inv/view','id'=>$inv_id]));
    } // email_stage_2
    
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
     * @param Request $request
     * @param IAR $iaR
     * @param IRR $irR
     * @param CurrentRoute $currentRoute
     * @param IR $iR
     * @param UCR $ucR
     * @param UIR $uiR
     */
    public function guest(Request $request, IAR $iaR, IRR $irR, CurrentRoute $currentRoute,
                          IR $iR, UCR $ucR, UIR $uiR) : \Yiisoft\DataResponse\DataResponse|Response {
        $query_params = $request->getQueryParams() ?? [];
        $pageNum = (int)$currentRoute->getArgument('page', '1');
         //status 0 => 'all';
        $status = (int)$currentRoute->getArgument('status', '0');
        $sort = Sort::only(['status_id','number','date_created','date_due','id','client_id'])->withOrderString($query_params['sort'] ?? ''); 
                
        // Get the current user and determine from (@see Settings...User Account) whether they have been given 
        // either guest or admin rights. These rights are unrelated to rbac and serve as a second
        // 'line of defense' to support role based admin control.
         
        // Retrieve the user from Yii-Demo's list of users in the User Table
        $user = $this->user_service->getUser();         
        if ($user){
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
                // they can view their invoices and make payment
                $user_clients = $ucR->get_assigned_to_user($user->getId());
                $invs = $this->invs_status_with_sort_guest($iR, $status, $user_clients, $sort);
                $paginator = (new OffsetPaginator($invs))
                ->withPageSize((int)$this->sR->get_setting('default_list_limit'))
                ->withCurrentPage($pageNum);
                $parameters = [            
                    'alert'=> $this->alert(),
                    'iaR'=> $iaR,
                    'irR'=> $irR,
                    'invs' => $invs,            
                    'inv_statuses'=> $iR->getStatuses($this->sR),            
                    'max'=>(int) $this->sR->get_setting('default_list_limit'),
                    'page'=> $pageNum,
                    'paginator'=> $paginator,
                    's'=> $this->sR,
                    // Clicking on a grid column sort hyperlink will generate a url query_param eg. ?sort=
                    'sortOrder' => $query_params['sort'] ?? '',             
                    'status'=> $status,
                ];    
                return $this->view_renderer->render('/invoice/inv/guest', $parameters);  
            } // $user_inv 
            return $this->web_service->getNotFoundResponse();
        } // $user 
        return $this->web_service->getNotFoundResponse();
    }
    
    /**
     * @param Request $request
     * @param IAR $iaR
     * @param IR $invRepo
     * @param IRR $irR
     * @param CR $clientRepo
     * @param GR $groupRepo
     * @param CurrentRoute $currentRoute
     */
    public function index(Request $request, IAR $iaR, IR $invRepo, IRR $irR, CR $clientRepo, GR $groupRepo, CurrentRoute $currentRoute): \Yiisoft\DataResponse\DataResponse
    {
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page', '1');
        //status 0 => 'all';
        $status = (int)$currentRoute->getArgument('status', '0');
        $sort = Sort::only(['status_id','number','date_created','date_due','id','client_id'])
                // (@see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                // Show the latest quotes first => -id
                ->withOrderString($query_params['sort'] ?? '-id'); 
        $invs = $this->invs_status_with_sort($invRepo, $status, $sort); 
        $paginator = (new OffsetPaginator($invs))
        ->withPageSize((int)$this->sR->get_setting('default_list_limit'))
        ->withCurrentPage($page)        
        ->withNextPageToken((string) $page); 
        $parameters = [
            'page' => $page,
            'paginator' => $paginator,
            's'=> $this->sR,
            'sortOrder' => $query_params['sort'] ?? '', 
            'alert'=> $this->alert(),
            'client_count'=> $clientRepo->count(),
            'invs' => $invs,
            'inv_statuses'=> $invRepo->getStatuses($this->sR),
            'status'=> $status,
            'iaR'=> $iaR,
            'irR'=> $irR,
            'max'=>(int)$this->sR->get_setting('default_list_limit'),
            'modal_create_inv'=>$this->view_renderer->renderPartialAsString('/invoice/inv/modal_create_inv',[
                  'clients'=>$clientRepo->findAllPreloaded(),                    
                  'invoice_groups'=>$groupRepo->findAllPreloaded(),
                  'datehelper'=> $this->date_helper,
            ])
        ];  
        return $this->view_renderer->render('/invoice/inv/index', $parameters);  
    }
    
    /**
     * @param IR $iR
     * @param int $status
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Inv>
     */
    private function invs_status_with_sort(IR $iR, int $status, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $invs = $iR->findAllWithStatus($status)
                   ->withSort($sort);
        return $invs;
    }
    
    /**
     * @param IR $iR
     * @param int $status
     * @param array $user_clients
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Inv>
     */
    private function invs_status_with_sort_guest(IR $iR, int $status,  array $user_clients, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $invs = $iR->repoGuest_Clients_Sent_Viewed_Paid($status, $user_clients)
                   ->withSort($sort);
        return $invs;
    }
    
    /**
     * @param Request $request
     * @return bool
     */
    private function isAjaxRequest(Request $request): bool
    {
        return $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }
    
    // Called from inv.js inv_to_pdf_confirm_with_custom_fields
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param IAR $iaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param ITRR $itrR
     * @param UIR $uiR
     * @param Request $request
     */
    public function pdf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, GR $gR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, UIR $uiR, Request $request) : \Yiisoft\DataResponse\DataResponse {
        // include is a value of 0 or 1 passed from inv.js function inv_to_pdf_with(out)_custom_fields indicating whether the user
        // wants custom fields included on the inv or not.
        $include = $currentRoute->getArgument('include');        
        $inv_id = $this->session->get('inv_id');
        $inv_amount = (($iaR->repoInvAmountCount((int)$inv_id) > 0) ? $iaR->repoInvquery((int)$inv_id) : null);
        if ($inv_amount) {
            $custom = (($include===(string)1) ? true : false);
            $inv_custom_values = $this->inv_custom_values($this->session->get('inv_id'),$icR);
            // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
            $pdfhelper = new PdfHelper($this->sR, $this->session);
            // The invoice will be streamed ie. shown, and not archived
            $stream = true;
            // If we are required to mark invoices as 'sent' when sent.
            if ($this->sR->get_setting('mark_invoices_sent_pdf') == 1) {
                $this->generate_inv_number_if_applicable($inv_id, $iR, $this->sR, $gR);
                $this->sR->invoice_mark_sent($inv_id, $iR);
            }
            $inv = $iR->repoInvUnloadedquery((string)$inv_id);
            if ($inv) {
                $pdfhelper->generate_inv_pdf($inv_id, $inv->getUser_id(), $stream, $custom, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $iR, $itrR, $uiR, $this->view_renderer);        
                $parameters = ($include == '1' ? ['success'=>1] : ['success'=>0]); 
                return $this->factory->createResponse(Json::encode($parameters));  
            } // $inv
            return $this->factory->createResponse(Json::encode(['success'=>0]));  
        } // $inv_amount    
        return $this->factory->createResponse(Json::encode(['success'=>0]));  
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param IAR $iaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param ITRR $itrR
     * @param UIR $uiR
     * @return void
     */
    public function pdf_dashboard_include_cf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, GR $gR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, UIR $uiR) : void {
        $inv_id = $currentRoute->getArgument('id');
        if (null!==$inv_id) {
            $inv_amount = (($iaR->repoInvAmountCount((int)$inv_id) > 0) ? $iaR->repoInvquery((int)$inv_id) : null);
            if ($inv_amount) {
                $inv_custom_values = $this->inv_custom_values($inv_id, $icR);
                // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                $pdfhelper = new PdfHelper($this->sR, $this->session);
                // The invoice will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark invoices as 'sent' when sent.
                if ($this->sR->get_setting('mark_invoices_sent_pdf') == 1) {
                    $this->generate_inv_number_if_applicable($inv_id, $iR, $this->sR, $gR);
                    $this->sR->invoice_mark_sent($inv_id, $iR);
                }
                $inv = $iR->repoInvUnloadedquery($inv_id);        
                if ($inv) {
                    $pdfhelper->generate_inv_pdf($inv_id, $inv->getUser_id(), $stream, true, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $iR, $itrR, $uiR, $this->view_renderer);                
                } //inv
            } //$inv_amount    
        } //null!==$inv_id     
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param IAR $iaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param ITRR $itrR
     * @param UIR $uiR
     * @return void
     */    
    public function pdf_dashboard_exclude_cf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, GR $gR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, UIR $uiR) : void {
        $inv_id = $currentRoute->getArgument('id');
        if (null!==$inv_id) {
            $inv_amount = (($iaR->repoInvAmountCount((int)$inv_id) > 0) ? $iaR->repoInvquery((int)$inv_id) : null);
            if ($inv_amount) {
                $inv_custom_values = $this->inv_custom_values($inv_id, $icR);
                // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                $pdfhelper = new PdfHelper($this->sR, $this->session);
                // The invoice will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark invoices as 'sent' when sent.
                if ($this->sR->get_setting('mark_invoices_sent_pdf') == 1) {
                    $this->generate_inv_number_if_applicable($inv_id, $iR, $this->sR, $gR);
                    $this->sR->invoice_mark_sent($inv_id, $iR);
                }
                $inv = $iR->repoInvUnloadedquery($inv_id);
                if ($inv) {
                    $pdfhelper->generate_inv_pdf($inv_id, $inv->getUser_id(), $stream, false, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $iR, $itrR, $uiR, $this->view_renderer);            
                } //inv    
            } //inv_amount   
        } // inv_id   
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param IAR $iaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param ITRR $itrR
     * @param UIR $uiR
     * @param UPR $upR
     * @return mixed
     */
    public function pdf_download_include_cf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, GR $gR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, UIR $uiR, UPR $upR) : mixed {
        $url_key = $currentRoute->getArgument('url_key');
        if ($url_key) {
            // If the status is sent 2, viewed 3, or paid 4 and the url key exists
            if ($iR->repoUrl_key_guest_count($url_key) < 1) {
                return $this->web_service->getNotFoundResponse();
            }        
            // Retrieve the inv_id
            $inv_guest = $iR->repoUrl_key_guest_count($url_key) ? $iR->repoUrl_key_guest_loaded($url_key) : null;
            if ($inv_guest) {
                $inv_id = $inv_guest->getId();
                $inv_amount = (($iaR->repoInvAmountCount((int)$inv_id) > 0) ? $iaR->repoInvquery((int)$inv_id) : null);
                if ($inv_amount) {
                    $inv_custom_values = $this->inv_custom_values($inv_id, $icR);
                    // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                    $pdfhelper = new PdfHelper($this->sR, $this->session);
                    // The invoice will be not be streamed ie. shown (in a separate tab see setting), but will be downloaded
                    $stream = false;
                    $c_f = true;
                    // If we are required to mark invoices as 'sent' when sent.
                    if ($this->sR->get_setting('mark_invoices_sent_pdf') == 1) {
                        $this->generate_inv_number_if_applicable($inv_id, $iR, $this->sR, $gR);
                        $this->sR->invoice_mark_sent($inv_id, $iR);
                    }
                    $inv = $iR->repoInvUnloadedquery((string)$inv_id);
                    if ($inv) {
                        // Because the invoice is not streamed an aliase of temporary folder file location is returned        
                        $temp_aliase = $pdfhelper->generate_inv_pdf($inv_id, $inv->getUser_id(), $stream, $c_f, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $iR, $itrR, $uiR, $this->view_renderer);                
                        $path_parts = pathinfo($temp_aliase);
                        /**
                         * @psalm-suppress PossiblyUndefinedArrayOffset
                         */
                        $file_ext = $path_parts['extension'];
                        $original_file_name = $path_parts['basename'];
                        if (file_exists($temp_aliase)) {
                            $file_size = filesize($temp_aliase);
                            $allowed_content_type_array = $upR->getContentTypes(); 
                            // Check extension against allowed content file types @see UploadRepository getContentTypes
                            $save_ctype = isset($allowed_content_type_array[$file_ext]);
                            $ctype = $save_ctype ? $allowed_content_type_array[$file_ext] : $upR->getContentTypeDefaultOctetStream();
                            // https://www.php.net/manual/en/function.header.php
                            // Remember that header() must be called before any actual output is sent, either by normal HTML tags,
                            // blank lines in a file, or from PHP.
                            header("Expires: -1");
                            header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
                            header("Content-Disposition: attachment; filename=\"$original_file_name\"");
                            header("Content-Type: " . $ctype);
                            header("Content-Length: " . $file_size);
                            echo file_get_contents($temp_aliase, true);
                            exit;
                        } // file_exists 
                    } //inv    
                } // inv_amount
            } // inv_guest    
        } //url_key
        exit;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param IAR $iaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param ITRR $itrR
     * @param UIR $uiR
     * @param UPR $upR
     * @return mixed
     */
    public function pdf_download_exclude_cf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, GR $gR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, UIR $uiR, UPR $upR) : mixed {
        $url_key = $currentRoute->getArgument('url_key');
        if ($url_key) {
            // If the status is sent 2, viewed 3, or paid 4 and the url key exists
            if ($iR->repoUrl_key_guest_count($url_key) < 1) {
                return $this->web_service->getNotFoundResponse();
            }
            // Retrieve the inv_id
            $inv_guest = $iR->repoUrl_key_guest_count($url_key) ? $iR->repoUrl_key_guest_loaded($url_key) : null;
            if ($inv_guest) {
                $inv_id = $inv_guest->getId();
                $inv_amount = (($iaR->repoInvAmountCount((int)$inv_id) > 0) ? $iaR->repoInvquery((int)$inv_id) : null);
                if ($inv_amount) { 
                    $inv_custom_values = $this->inv_custom_values($inv_id, $icR);
                    // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                    $pdfhelper = new PdfHelper($this->sR, $this->session);
                    // The invoice will be not be streamed ie. shown (in a separate tab see setting), but will be downloaded
                    $stream = false;
                    $c_f = false;
                    // If we are required to mark invoices as 'sent' when sent.
                    if ($this->sR->get_setting('mark_invoices_sent_pdf') == 1) {
                        $this->generate_inv_number_if_applicable($inv_id, $iR, $this->sR, $gR);
                        $this->sR->invoice_mark_sent($inv_id, $iR);
                    }
                    $inv = $iR->repoInvUnloadedquery((string)$inv_id);
                    if ($inv) {
                        // Because the invoice is not streamed an aliase of temporary folder file location is returned        
                        $temp_aliase = $pdfhelper->generate_inv_pdf($inv_id, $inv->getUser_id(), $stream, $c_f , $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $iR, $itrR, $uiR, $this->view_renderer);                
                        $path_parts = pathinfo($temp_aliase);
                        /**
                         * @psalm-suppress PossiblyUndefinedArrayOffset
                         */
                        $file_ext = $path_parts['extension'];
                        // Do not choose 'basename' because extension pdf not necessary ie. filename is basename without extension .pdf
                        $original_file_name = $path_parts['filename'];
                        if (file_exists($temp_aliase)) {
                            $file_size = filesize($temp_aliase);
                            $allowed_content_type_array = $upR->getContentTypes(); 
                            // Check extension against allowed content file types @see UploadRepository getContentTypes
                            $save_ctype = isset($allowed_content_type_array[$file_ext]);
                            $ctype = $save_ctype ? $allowed_content_type_array[$file_ext] : $upR->getContentTypeDefaultOctetStream();
                            // https://www.php.net/manual/en/function.header.php
                            // Remember that header() must be called before any actual output is sent, either by normal HTML tags,
                            // blank lines in a file, or from PHP.
                            header("Expires: -1");
                            header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
                            header("Content-Disposition: attachment; filename=\"$original_file_name\"");
                            header("Content-Type: " . $ctype);
                            header("Content-Length: " . $file_size);
                            echo file_get_contents($temp_aliase, true);
                            exit;
                        } // file_exists
                    } // $inv
                } // inv_amount    
            } // inv_guest
        } // url_key
        exit;
    }
    
    public function pdf_sumex_generate(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, UIR $uiR, Request $request): void 
    {
        // TODO
    }
    
    public function payment_information(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, UIR $uiR, Request $request): void 
    {
        // TODO
    }
    
    // If the setting 'generate_inv_number_for_draft' has not been set, give the quote a basic number according to id, and not according to identifier format    
    
    /**
     * @param string|null $inv_id
     * @param IR $iR
     * @param SR $sR
     * @param GR $gR
     * @return void
     */
    public function generate_inv_number_if_applicable(string|null $inv_id, IR $iR, SR $sR, GR $gR) : void
    {
        if ($inv_id) {
            $inv = $iR->repoInvUnloadedquery($inv_id);
            if ($inv) {
                $group_id = $inv->getGroup_id();
                if ($iR->repoCount($inv_id)>0) {
                    if ($inv->getStatus_id() === 1 && $inv->getNumber() === "") {
                        // Generate new inv number if applicable
                        $inv->setNumber($this->generate_inv_get_number($group_id, $sR, $iR, $gR));
                        $iR->save($inv);
                    }
                }
            }    
        }    
    }
    
    /**
     * @param string $group_id
     * @param SR $sR
     * @param IR $iR
     * @param GR $gR
     * @return mixed
     */
    public function generate_inv_get_number(string $group_id, SR $sR, IR $iR, GR $gR) : mixed {
        $inv_number = '';
        if ($sR->get_setting('generate_invoice_number_for_draft') == '0') {
            $inv_number = $iR->get_inv_number($group_id, $gR);
        }
        return $inv_number;        
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param InvRepository $invRepo
     * @param bool $unloaded
     * @return object|null
     */
        
    private function inv(CurrentRoute $currentRoute, InvRepository $invRepo, bool $unloaded = false): object|null 
    {
        $id = $currentRoute->getArgument('id');
        if (null!==$id){
            $inv = ($unloaded ? $invRepo->repoInvUnLoadedquery($id) : $invRepo->repoInvLoadedquery($id));
            if (null!==$inv){
                return $inv;
            }
            return null;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function invs(InvRepository $invRepo, int $status): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader 
    {
        $invs = $invRepo->findAllWithStatus($status);    
        return $invs;
    }
    
    /**
     * 
     * @param string|null $inv_id
     * @param icR $icR
     * @return array
     */
    public function inv_custom_values(string|null $inv_id, icR $icR) : array
    {
        // Get all the custom fields that have been registered with this inv on creation, retrieve existing values via repo, and populate 
        // custom_field_form_values array
        $custom_field_form_values = [];
        if ($inv_id) {
            if ($icR->repoInvCount($inv_id) > 0) {
                $inv_custom_fields = $icR->repoFields($inv_id);
                foreach ($inv_custom_fields as $key => $val) {
                    $custom_field_form_values['custom[' . $key . ']'] = $val;
                }
            }
            return $custom_field_form_values;
        }
        return [];
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param IIR $invitemRepository
     * @return object|null
     */
    private function inv_item(CurrentRoute $currentRoute, IIR $invitemRepository) : object|null 
    {
        $id = $currentRoute->getArgument('id');
        if (null!==$id) {
            $invitem = $invitemRepository->repoInvItemquery($id) ?: null;
            if ($invitem === null) {
                return null;
            }
            return $invitem;
        }
        return null;
    } 
    
    /**
     * @param string $copy_id
     */
    private function inv_to_inv_inv_amount(string $inv_id, string $copy_id): void {
        $this->inv_amount_service->initializeCopyInvAmount(new InvAmount(), (int)$inv_id, $copy_id);
    }
    
    // Data fed from inv.js->$(document).on('click', '#inv_to_inv_confirm', function () {
    
    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param GR $gR
     * @param IIAS $iiaS
     * @param PR $pR
     * @param TASKR $taskR
     * @param IAR $iaR
     * @param ICR $icR
     * @param IIAR $iiaR
     * @param IIR $iiR
     * @param IR $iR
     * @param ITRR $itrR
     * @param TRR $trR
     * @param UNR $unR
     */
    public function inv_to_inv_confirm(Request $request, ValidatorInterface $validator, 
                                           GR $gR, IIAS $iiaS, PR $pR, TASKR $taskR, IAR $iaR, ICR $icR,
                                           IIAR $iiaR, IIR $iiR,IR $iR, ITRR $itrR, TRR $trR, UNR $unR) : \Yiisoft\DataResponse\DataResponse|Response
    {
        $data_inv_js = $request->getQueryParams() ?? [];
        $inv_id = (string)$data_inv_js['inv_id'];
        $original = $iR->repoInvUnloadedquery($inv_id);
        if ($original) {
            $group_id = $original->getGroup_id();
            $ajax_body = [
                    'quote_id'=>null,
                    'client_id'=>$data_inv_js['client_id'],
                    'group_id'=>$group_id,
                    'status_id'=> $this->sR->get_setting('mark_invoices_sent_copy') === '1' ? 2 : 1,
                    'number'=>$gR->generate_number((int)$group_id),
                    'creditinvoice_parent_id'=>null ,
                    'discount_amount'=>floatval($original->getDiscount_amount()),
                    'discount_percent'=>floatval($original->getDiscount_percent()),
                    'url_key'=>'',
                    'password'=>'',
                    'payment_method'=>1,
                    'terms'=>'',
            ];
            $form = new InvForm();
            $copy = new Inv();
            if (($form->load($ajax_body) && $validator->validate($form)->isValid())) {    
                /**
                 * @psalm-suppress PossiblyNullArgument
                 */
                $this->inv_service->addInv($this->user_service->getUser(), $copy, $form, $this->sR);
                // Transfer each inv_item to inv_item and the corresponding inv_item_amount to inv_item_amount for each item
                $copy_id = $copy->getId();
                if (null!==$copy_id) {
                    $this->inv_to_inv_inv_items($inv_id,$copy_id, $iiaR, $iiaS, $pR, $taskR, $iiR, $trR,$validator, $unR);
                    $this->inv_to_inv_inv_tax_rates($inv_id,$copy_id,$itrR, $validator);
                    $this->inv_to_inv_inv_custom($inv_id,$copy_id,$icR, $validator);
                    $this->inv_to_inv_inv_amount($inv_id,$copy_id);            
                    $iR->save($copy);
                    $parameters = ['success'=>1];
                    //return response to inv.js to reload page at location
                    $this->flash('info',$this->translator->translate('invoice.draft.guest'));
                    return $this->factory->createResponse(Json::encode($parameters));          
                }
            } else {
                $parameters = [
                   'success'=>0,
                ];
                //return response to inv.js to reload page at location
                return $this->factory->createResponse(Json::encode($parameters));          
            }
        } // if original
        return $this->web_service->getNotFoundResponse();
    }
    
    
    /**
     * @param string $copy_id
     */
    private function inv_to_inv_inv_custom(string $inv_id, string $copy_id, ICR $icR, ValidatorInterface $validator): void {
        $inv_customs = $icR->repoFields($inv_id);
        foreach ($inv_customs as $inv_custom) {
            if ($inv_custom instanceof InvCustom) {
                $copy_custom = [
                    'inv_id'=>$copy_id,
                    'custom_field_id'=>$inv_custom->getCustom_field_id(),
                    'value'=>$inv_custom->getValue(),
                ];
                $entity = new InvCustom();
                $form = new InvCustomForm();
                if ($form->load($copy_custom) && $validator->validate($form)->isValid()) {    
                    $this->inv_custom_service->saveInvCustom($entity,$form);            
                }
            }
        }        
    }
    
    /**
     * @param string $copy_id
     */
    private function inv_to_inv_inv_items(string $inv_id, string $copy_id, IIAR $iiaR, IIAS $iiaS, PR $pR, TASKR $taskR, IIR $iiR, TRR $trR, ValidatorInterface $validator, UNR $unR): void {
        // Get all items that belong to the original invoice
        $items = $iiR->repoInvItemIdquery($inv_id);
        foreach ($items as $inv_item) {
            if ($inv_item instanceof InvItem) {
                $copy_item = [
                    'inv_id'=>$copy_id,
                    'tax_rate_id'=>$inv_item->getTax_rate_id(),
                    'product_id'=>$inv_item->getProduct_id(),
                    'task_id'=>$inv_item->getTask_id(),
                    'name'=>$inv_item->getName(),
                    'description'=>$inv_item->getDescription(),
                    'quantity'=>$inv_item->getQuantity(),
                    'price'=>$inv_item->getPrice(),
                    'discount_amount'=>$inv_item->getDiscount_amount(),
                    'order'=>$inv_item->getOrder(),
                    'is_recurring'=>$inv_item->getIs_recurring(),
                    'product_unit'=>$inv_item->getProduct_unit(),
                    'product_unit_id'=>$inv_item->getProduct_unit_id(),
                    // Recurring date
                    'date'=>''
                ];
            
                // Create an equivalent invoice item for the invoice item
                $model = new InvItem();
                $form = new InvItemForm();
                if ($form->load($copy_item) && $validator->validate($form)->isValid()) {
                    if (!empty($inv_item->getProduct_id()) && empty($inv_item->getTask_id())) {  
                        // (InvItem $model, InvItemForm $form, string $inv_id,PR $pr, SR $s, UNR $unR)
                        $this->inv_item_service->addInvItem_product($model, $form, $copy_id, $pR, $trR , $iiaS, $iiaR, $this->sR, $unR);
                    } else {                           
                        $this->inv_item_service->addInvItem_task($model, $form, $copy_id, $taskR, $trR , $iiaS, $iiaR, $this->sR);    
                    }
               } else {
                    $form->getFormErrors();
               }
            } // instanceof
        } // foreach    
    }
    
    /**
     * @param string $copy_id
     */
    private function inv_to_inv_inv_tax_rates(string $inv_id, string $copy_id, ITRR $itrR, ValidatorInterface $validator): void {
        // Get all tax rates that have been setup for the invoice
        $inv_tax_rates = $itrR->repoInvquery($inv_id);        
        foreach ($inv_tax_rates as $inv_tax_rate){
            if ($inv_tax_rate instanceof InvTaxRate) {
                $copy_tax_rate = [
                    'inv_id'=>$copy_id,
                    'tax_rate_id'=>$inv_tax_rate->getTax_rate_id(),
                    'include_item_tax'=>$inv_tax_rate->getInclude_item_tax(),
                    'amount'=>$inv_tax_rate->getInv_tax_rate_amount(),
                ];
                $entity = new InvTaxRate();
                $form = new InvTaxRateForm();
                if ($form->load($copy_tax_rate) && $validator->validate($form)->isValid()) {    
                    $this->inv_tax_rate_service->saveInvTaxRate($entity,$form);
                }
            } // inv_tax_rate    
        }        
    }  
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ITRR $invtaxrateRepository
     * @return object|null
     */
    
    private function invtaxrate(CurrentRoute $currentRoute, ITRR $invtaxrateRepository): object|null 
    {
        $id = $currentRoute->getArgument('id'); 
        if (null!==$id) {
            $invtaxrate = $invtaxrateRepository->repoInvTaxRatequery($id);
            if (null!==$invtaxrate) {
                return $invtaxrate;
            }
        }
        return null;
    }
        
    /**
     * @param $files
     * @return mixed
     */
    private function remove_extension($files) : mixed
    {
        foreach ($files as $key => $file) {
            $files[$key] = str_replace('.php', '', $file);
        }
        return $files;
    }
    
    // inv/view => '#btn_save_inv_custom_fields' => inv_custom_field.js => /invoice/inv/save_custom";
    
    /**
     * @param ValidatorInterface $validator
     * @param Request $request
     * @param ICR $icR
     */
    public function save_custom(ValidatorInterface $validator, Request $request, ICR $icR) : \Yiisoft\DataResponse\DataResponse
    {
            $parameters = [
                'success'=>0
            ];
            $js_data = $request->getQueryParams() ?? [];        
            $inv_id = $js_data['inv_id'];
            $custom_field_body = [            
                'custom'=>$js_data['custom'] ?: '',            
            ];
            $this->save_custom_fields($validator, $custom_field_body,$inv_id, $icR);
            $parameters['success'] = 1;
            return $this->factory->createResponse(Json::encode($parameters)); 
    }
    
    /**
     * @param (mixed|string)[] $array
     *
     * @psalm-param array{custom: ''|mixed} $array
     */
    public function save_custom_fields(ValidatorInterface $validator, array $array, $inv_id, ICR $icR) : void
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
                    $key_value = preg_match('/\d+/', $key, $m) ? $m[0] : '';
                    $db_array[$key_value] = $value;
                }
            }            
            foreach ($db_array as $key => $value){
               if ($value !=='') { 
                $ajax_custom = new InvCustomForm();
                $inv_custom = [];
                $inv_custom['inv_id']=$inv_id;
                $inv_custom['custom_field_id']=$key;
                $inv_custom['value']=$value; 
                $model = ($icR->repoInvCustomCount($inv_id,$key) == 1 ? $icR->repoFormValuequery($inv_id,$key) : new InvCustom());
                if ($model && $ajax_custom->load($inv_custom) && $validator->validate($ajax_custom)->isValid()) {
                   $this->inv_custom_service->saveInvCustom($model, $ajax_custom);                                   
                }
               }
            } // foreach            
        } //!empty array custom 
    } // function
    
    // '#inv_tax_submit' => inv.js 
    
    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     */
    public function save_inv_tax_rate(Request $request, ValidatorInterface $validator) : \Yiisoft\DataResponse\DataResponse {
        $body = $request->getQueryParams() ?? [];
        $ajax_body = [
            'inv_id'=>$body['inv_id'],
            'tax_rate_id'=>$body['inv_tax_rate_id'],
            'include_item_tax'=>$body['include_inv_item_tax'],
            'inv_tax_rate_amount'=>floatval(0.00),
        ];
        $ajax_content = new InvTaxRateForm();
        if ($ajax_content->load($ajax_body) && $validator->validate($ajax_content)->isValid()) {    
            $this->inv_tax_rate_service->saveInvTaxRate(new InvTaxRate(), $ajax_content);
            $parameters = [
                'success'=>1,                    
            ];
            //return response to inv.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));          
        } else {
            $parameters = [
               'success'=>0
             ];
            //return response to inv.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));          
        }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CFR $cfR
     * @param IAR $iaR
     * @param IIAR $iiaR
     * @param IIR $iiR
     * @param IR $iR
     * @param ITRR $itrR
     * @param UIR $uiR
     * @param PMR $pmR
     * @return Response
     */
    
    public function url_key(CurrentRoute $currentRoute, CurrentUser $currentUser, CFR $cfR, IAR $iaR, IIAR $iiaR, IIR $iiR, IR $iR, ITRR $itrR, UIR $uiR, UCR $ucR, PMR $pmR, UPR $upR): Response 
    {
        $url_key = $currentRoute->getArgument('url_key');
        // if the current user is a guest it will return a null value
        /**
         * @psalm-suppress PossiblyNullArgument $currentUser->getId()
         */
        $currentUser_getId = $currentUser->getId();
        if ($url_key === null || $currentUser->isGuest()) {
            return $this->web_service->getNotFoundResponse();
        }
        
        $client_chosen_gateway = $currentRoute->getArgument('gateway');
        if ($client_chosen_gateway === null) {
            return $this->web_service->getNotFoundResponse();
        }
        
        // If the status is sent 2, viewed 3, or paid 4 and the url key exists
        if ($iR->repoUrl_key_guest_count($url_key) < 1) {
            return $this->web_service->getNotFoundResponse();
        }
        
        $inv = $iR->repoUrl_key_guest_loaded($url_key);
        if ($inv) {

            // After signup the user was included in the userinv using Settings...User Account...+
            /**
             * @psalm-suppress PossiblyNullArgument $currentUser->getId()
             */
            $user_inv = $uiR->repoUserInvUserIdquery($currentUser_getId);
            // The client has been assigned to the user id using Setting...User Account...Assigned Clients
            /**
             * @psalm-suppress PossiblyNullArgument $currentUser->getId()
             */
            $user_client = $ucR->repoUserClientqueryCount($currentUser_getId, $inv->getClient_id()) === 1 ? true : false;
            if ($user_inv && $user_client) {
                // If the user is not an administrator and the status is sent 2, now mark it as viewed
                $uiR->repoUserInvUserIdcount($currentUser_getId) === 1 && $user_inv->getType() !== 1 && $inv->getStatus_id() === 2 ? $inv->setStatus_id(3) : '';
                $iR->save($inv);

                $payment_method = $inv->getPayment_method() !== 0 ? $pmR->repoPaymentMethodquery((string)$inv->getPayment_method()) : null;        

                $custom_fields = [
                   'invoice' => $cfR->repoTablequery('inv_custom'),
                   'client' => $cfR->repoTablequery('client_custom'),
                   // TODO 'user' => $cfR->repoTablequery('user_custom'),  
                ];

                $attachments = $this->view_partial_inv_attachments($url_key, (int)$inv->getClient_id(), $upR);

                $inv_amount = (($iaR->repoInvAmountCount((int)$inv->getId()) > 0) ? $iaR->repoInvquery((int)$inv->getId()) : null);
                if ($inv_amount) {
                        $is_overdue = ($inv_amount->getBalance() > 0 && ($inv->getDate_due()) < (new \DateTimeImmutable('now')) ? true : false);

                        $parameters = [            
                            'render'=> $this->view_renderer->renderPartialAsString('/invoice/template/invoice/public/' . ($this->sR->get_setting('public_invoice_template') ?: 'Invoice_Web'), [
                                // TODO logo
                                'alert'=>$this->alert(),                
                                'aliases'=>$this->sR->get_img(),                
                                'attachments' => $attachments,
                                'balance' => $inv_amount->getTotal() - $inv_amount->getPaid(),                
                                // Gateway that the paying user has selected 
                                'client_chosen_gateway'=> $client_chosen_gateway,
                                'clienthelper' => new ClientHelper($this->sR),                
                                'client'=>$inv->getClient(),
                                'custom_fields' => $custom_fields,
                                'download_pdf_non_sumex_action'=>['inv/download_pdf', ['url_key' => $url_key]],
                                'download_pdf_sumex_action'=>['inv/download_pdf', ['url_key' => $url_key]],                
                                'flash_message' => $this->flash('info', ''),
                                'inv' => $inv,
                                'inv_amount' => $inv_amount,                
                                'inv_tax_rates' =>  $itrR->repoCount($inv->getId()) > 0 ? $itrR->repoInvquery($inv->getId()) : null,
                                'inv_url_key' => $url_key,
                                'inv_item_amount'=>$iiaR,                
                                'is_overdue' => $is_overdue,
                                'items' => $iiR->repoInvquery($inv->getId()),
                                'logo'=> '',
                                'payment_method' => $payment_method,
                                'userinv'=> $uiR->repoUserInvUserIdcount($inv->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($inv->getUser_id()) : null,                
                            ]),        
                    ];        
                    return $this->view_renderer->render('/invoice/inv/url_key', $parameters);
                } // if inv_amount
                $this->flash('warning','There is no invoice amount.');
                return $this->web_service->getNotFoundResponse(); 
            } // if user_inv
            $this->flash('danger','Client not allocated to user.');
            return $this->web_service->getNotFoundResponse(); 
        } // if inv
        $this->flash('danger', 'Invoice not found');
        return $this->web_service->getNotFoundResponse();
    }
    
    /**
     * @param bool $read_only
     * @return bool
     */
    private function display_edit_delete_buttons(bool $read_only) : bool {     
        if (($read_only === false) && ($this->sR->get_setting('disable_read_only') === (string)0)) { 
              return true;
        }
        // Override the invoice's readonly 
        if (($this->sR->get_setting('disable_read_only') === (string)1)) {
           return true; 
        }
        return false;
    }
    
    // The accesschecker in config/routes ensures that only users with viewInv permission can reach this 
    
    /**
     * @param ViewRenderer $head
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param CFR $cfR
     * @param CVR $cvR
     * @param PR $pR
     * @param IAR $iaR
     * @param IIAR $iiaR
     * @param IIR $iiR
     * @param IR $iR
     * @param IRR $irR
     * @param ITRR $itrR
     * @param PMR $pmR
     * @param TRR $trR
     * @param FR $fR
     * @param UNR $uR
     * @param CR $cR
     * @param GR $gR
     * @param ICR $icR
     * @param PYMR $pymR
     * @param TASKR $taskR
     * @param PRJCTR $prjctR
     * @param UPR $upR
     */
    public function view(ViewRenderer $head, CurrentRoute $currentRoute, Request $request,
                         CFR $cfR, CVR $cvR, PR $pR, IAR $iaR, IIAR  $iiaR, IIR $iiR, IR $iR, IRR $irR, ITRR $itrR,PMR $pmR,
                         TRR $trR, FR $fR,  UNR $uR, CR $cR, GR $gR, ICR $icR, PYMR $pymR, TASKR $taskR, PRJCTR $prjctR, UPR $upR)
                         : \Yiisoft\DataResponse\DataResponse|Response {
        $inv = $this->inv($currentRoute, $iR, false);
        if ($inv) {
            $read_only = $inv->getIs_read_only();
            $this->session->set('inv_id',$inv->getId());
            $this->number_helper->calculate_inv($this->session->get('inv_id'), $iiR, $iiaR, $itrR, $iaR, $iR, $pymR); 
            $inv_amount = (($iaR->repoInvAmountCount((int)$inv->getId()) > 0) ? $iaR->repoInvquery((int)$this->session->get('inv_id')) : null);
            if ($inv_amount) {
                $inv_custom_values = $this->inv_custom_values($this->session->get('inv_id'), $icR);
                $is_recurring = ($irR->repoCount($this->session->get('inv_id')) > 0 ? true : false);
                $show_buttons = $this->display_edit_delete_buttons($read_only);
                // Each file attachment is recorded in Upload table with invoice's url_key, and client_id
                $url_key = $inv->getUrl_key();
                $client_id = $inv->getClient_id();
                $parameters = [
                    'title' => $this->sR->trans('view'),
                    'body' => $this->body($inv),
                    'datehelper'=> $this->date_helper,            
                    'alert'=>$this->alert(),            
                    // Determine if a 'viewInv' user has 'editInv' permission
                    'invEdit' => $this->user_service->hasPermission('editInv') ? true : false,
                    // Determine if a 'viewInv' user has 'viewPayment' permission
                    // This permission is necessary for a guest viewing a read-only view to go to the Pay now section
                    'paymentView' => $this->user_service->hasPermission('viewPayment') ? true : false, 
                    'enabled_gateways' => $this->sR->payment_gateways_enabled_DriverList(),
                    'iaR'=>$iaR,           
                    'is_recurring'=>$is_recurring, 
                    'payment_methods'=>$pmR->findAllPreloaded(),
                    // If a custom field exists for payments, use it/them on the payment form. 
                    'payment_cf_exist' => $cfR->repoTableCountquery('payment_custom') > 0 ? true : false,
                    'read_only' => $read_only,
                    'show_buttons' => $show_buttons,
                    'payments'=>$pymR->repoCount((string)$this->session->get('inv_id')) > 0 ? $pymR->repoInvquery((string)$this->session->get('inv_id')) : null,
                    // Sits above options section of invoice allowing the adding of a new row to the invoice
                    'add_inv_item_product'=>$this->view_renderer->renderPartialAsString('/invoice/invitem/_item_form_product',[
                            'action' => ['invitem/add_product'],
                            'errors' => [],
                            'body' => $request->getParsedBody(),                    
                            'head'=>$head,
                            'inv'=>$iR->repoInvLoadedquery($this->session->get('inv_id')),
                            'is_recurring'=>$is_recurring,
                            'inv_id'=>$this->session->get('inv_id'),
                            'tax_rates'=>$trR->findAllPreloaded(),
                            // Tasks are excluded
                            'products'=>$pR->findAllPreloaded(),
                            'units'=>$uR->findAllPreloaded(),
                            'numberhelper'=>$this->number_helper
                    ]),
                    'add_inv_item_task'=>$this->view_renderer->renderPartialAsString('/invoice/invitem/_item_form_task',[
                            'action' => ['invitem/add_task'],
                            'errors' => [],
                            'body' => $request->getParsedBody(),                    
                            'head'=>$head,
                            'inv'=>$iR->repoInvLoadedquery($this->session->get('inv_id')),
                            'is_recurring'=>$is_recurring,
                            'inv_id'=>$this->session->get('inv_id'),
                            'tax_rates'=>$trR->findAllPreloaded(),
                            // Only tasks with complete or status of 3 are made available for selection
                            'tasks'=>$taskR->repoTaskStatusquery(3),
                            // Products are excluded
                            'units'=>$uR->findAllPreloaded(),
                            'numberhelper'=>$this->number_helper
                    ]),            
                    // Get all the fields that have been setup for this SPECIFIC invoice in inv_custom. 
                    'fields' => $icR->repoFields($this->session->get('inv_id')),
                    // Get the standard extra custom fields built for EVERY invoice. 
                    'custom_fields'=>$cfR->repoTablequery('inv_custom'),
                    'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('inv_custom')),
                    'cvH'=> new CVH($this->sR),
                    'inv_custom_values' => $inv_custom_values,
                    'inv_statuses'=> $iR->getStatuses($this->sR),  
                    'inv'=>$iR->repoInvLoadedquery($this->session->get('inv_id')),
                    'modal_choose_items'=>$this->view_renderer->renderPartialAsString('/invoice/product/modal_product_lookups_inv',
                    [
                        'families'=>$fR->findAllPreloaded(),
                        'default_item_tax_rate'=> $this->sR->get_setting('default_item_tax_rate') !== '' ?: 0,
                        'filter_product'=> '',            
                        'filter_family'=> '',
                        'reset_table'=> '',
                        'products'=>$pR->findAllPreloaded(),
                        'head'=> $head,
                    ]),
                    'modal_choose_tasks'=>$this->view_renderer->renderPartialAsString('/invoice/task/modal_task_lookups_inv',
                    [
                        'partial_task_table_modal'=>$this->view_renderer->renderPartialAsString('/invoice/task/partial_task_table_modal',[
                            // Only tasks with complete or status of 3 are made available for selection
                            'tasks'=>$taskR->repoTaskStatusquery(3),
                            'prjct'=>$prjctR->findAllPreloaded(),
                            'datehelper'=>$this->date_helper,
                            'numberhelper'=>$this->number_helper,
                        ]),
                        'default_item_tax_rate'=> $this->sR->get_setting('default_item_tax_rate') !== '' ?: 0,               
                        'tasks'=>$pR->findAllPreloaded(),
                        'head'=> $head,
                    ]),
                    'modal_add_inv_tax'=>$this->view_renderer->renderPartialAsString('/invoice/inv/modal_add_inv_tax',['tax_rates'=>$trR->findAllPreloaded()]),
                    'modal_copy_inv'=>$this->view_renderer->renderPartialAsString('/invoice/inv/modal_copy_inv',[ 
                        'inv'=>$iR->repoInvLoadedquery($this->session->get('inv_id')),
                        'clients'=>$cR->findAllPreloaded(),                
                        'groups'=>$gR->findAllPreloaded(),
                    ]),            
                    // Partial item table: Used to build items either products/tasks that make up the invoice
                    'partial_item_table'=>$this->view_partial_item_table($show_buttons,$currentRoute, $pR, $taskR, $iiR, $iiaR, $iR, $trR, $uR, 
                                                                                              $itrR, $inv_amount),            
                    'modal_delete_inv'=>$this->view_modal_delete_inv(),
                    'modal_delete_items'=>$this->view_modal_delete_items($iiR),
                    'modal_change_client'=>$this->view_modal_change_client($currentRoute, $cR, $iR),
                    'modal_inv_to_pdf'=>$this->view_modal_inv_to_pdf($currentRoute, $iR),
                    'modal_create_recurring'=>$this->view_modal_create_recurring($irR),
                    'modal_create_credit'=>$this->view_modal_create_credit($currentRoute, $gR, $iR),
                    'view_custom_fields'=>$this->view_custom_fields($cfR, $cvR, $inv_custom_values),
                    'partial_inv_attachments'=>$this->view_partial_inv_attachments($url_key, (int)$client_id, $upR),      
            ];
            return $this->view_renderer->render('/invoice/inv/view', $parameters);
            } // if $inv_amount 
            return $this->web_service->getNotFoundResponse(); 
        } // if $inv
        return $this->web_service->getNotFoundResponse(); 
    }
    
    /**
     * @param CFR $cfR
     * @param CVR $cvR
     * @param array $inv_custom_values
     * @return string
     */
    
    private function view_custom_fields(CFR $cfR, CVR $cvR, array $inv_custom_values) : string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/view_custom_fields', [
                     'custom_fields'=>$cfR->repoTablequery('inv_custom'),
                     'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('inv_custom')),
                     'inv_custom_values'=> $inv_custom_values,  
                     'cvH'=> new CVH($this->sR),
        ]);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CR $cR
     * @param IR $iR
     * @return string
     */
    private function view_modal_change_client(CurrentRoute $currentRoute, CR $cR, IR $iR) : string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_change_client', [
                     'inv'=> $this->inv($currentRoute, $iR, true),                        
                     'clients'=> $cR->findAllPreloaded()
        ]);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GR $gR
     * @param IR $iR
     * @return string
     */
    
    private function view_modal_create_credit(CurrentRoute $currentRoute, GR $gR, IR $iR) : string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_create_credit',[
                     'invoice_groups'=> $gR->repoCountAll() > 0 ? $gR->findAllPreloaded() : null,
                     'inv'=>$this->inv($currentRoute, $iR, false),
                     'datehelper'=>$this->date_helper
        ]);
    }
    
    /**
     * @param IRR $irR
     * @return string
     */
    
    private function view_modal_create_recurring(IRR $irR) : string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_create_recurring',[                     
                     'recur_frequencies'=>$irR->recur_frequencies(), 
                     'datehelper'=>$this->date_helper
        ]);
    }
    
    /**
     * @return string
     */
    
    private function view_modal_delete_inv() : string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_delete_inv', ['action'=>['inv/delete', ['id' => $this->session->get('inv_id')]],
                       
        ]);
    }
    
    /**
     * @param IIR $iiR
     * @return string
     */
    
    private function view_modal_delete_items(IIR $iiR) : string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_delete_item',[
                    'partial_item_table_modal'=>$this->view_renderer->renderPartialAsString('/invoice/invitem/_partial_item_table_modal',[
                        'invitems'=>$iiR->repoInvquery($this->session->get('inv_id')),                        
                        'numberhelper'=>$this->number_helper,
                    ]),                    
        ]);
    } 
    
    /**
     * @param CurrentRoute $currentRoute
     * @param IR $iR
     * @return string
     */    
    private function view_modal_inv_to_pdf(CurrentRoute $currentRoute, IR $iR) : string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_inv_to_pdf',[                     
                     'inv'=> $this->inv($currentRoute, $iR, true),                        
        ]);
    }
    
    /**
     * @param UPR $upR
     * @return string
     */
    private function view_partial_inv_attachments(string $url_key, int $client_id, UPR $upR) : string {
        $uploads = $upR->repoUploadUrlClientquery($url_key, $client_id);
        return $this->view_renderer->renderPartialAsString('/invoice/inv/partial_inv_attachments', [
            'form' => new InvAttachmentsForm(),
            'partial_inv_attachments_list' => $this->view_renderer->renderPartialAsString('/invoice/inv/partial_inv_attachments_list', [
               'paginator' => new OffsetPaginator($uploads) 
            ]),
            'action' => ['inv/attachment', ['id' => $this->session->get('inv_id')]]        
        ]);
    }
    
    /**
     * 
     * @param bool $show_buttons
     * @param CurrentRoute $currentRoute
     * @param PR $pR
     * @param TaskR $taskR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param TRR $trR
     * @param UNR $uR
     * @param ITRR $itrR
     * @param object|null $inv_amount
     * @return string
     */
    
    private function view_partial_item_table(bool $show_buttons, CurrentRoute $currentRoute, PR $pR, TaskR $taskR, IIR $iiR, IIAR $iiaR, IR $iR, TRR $trR, UNR $uR, ITRR $itrR, object|null $inv_amount) : string {
        $inv = $this->inv($currentRoute, $iR,false);
        if ($inv) {
            $inv_tax_rates = (($itrR->repoCount($this->session->get('inv_id')) > 0) ? $itrR->repoInvquery($inv->getId()) : null); 
            if ($inv_tax_rates) {
            return $this->view_renderer->renderPartialAsString('/invoice/inv/partial_item_table',[               
                    'show_buttons' => $show_buttons,
                    'numberhelper'=> $this->number_helper,
                    'datehelper'=> $this->date_helper,
                    'products'=>$pR->findAllPreloaded(),
                    // Only tasks with complete or status of 3 are made available for selection
                    'tasks'=>$taskR->repoTaskStatusquery(3),
                    'user_can_edit'=>$this->user_service->hasPermission('editInv') ? true : false,
                    'inv_items'=>$iiR->repoInvquery($this->session->get('inv_id')),
                    'inv_item_amount'=>$iiaR,
                    'inv_tax_rates'=>$inv_tax_rates,
                    'inv_amount'=> $inv_amount,
                    'inv'=>$iR->repoInvLoadedquery($this->session->get('inv_id')),
                    's'=>$this->sR,
                    'tax_rates'=>$trR->findAllPreloaded(),
                    'units'=>$uR->findAllPreloaded(),
            ]);
            } // inv_tax_rates
        } // inv
        return ''; 
    }
}
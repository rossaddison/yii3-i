<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Invoice\Entity\Client;
use App\Invoice\Entity\Family;
use App\Invoice\Entity\Group;
use App\Invoice\Entity\PaymentMethod;
use App\Invoice\Entity\Product;
use App\Invoice\Entity\Setting;
use App\Invoice\Entity\TaxRate;
use App\Invoice\Entity\Unit;

// Repositories
use App\Invoice\Client\ClientRepository;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvRecurring\InvRecurringRepository;
use App\Invoice\PaymentMethod\PaymentMethodRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Project\ProjectRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\QuoteAmount\QuoteAmountRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Task\TaskRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;

// Helpers
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\DateHelper;
// Services and forms
use App\Invoice\Setting\SettingForm;
use App\Invoice\Setting\SettingService;
use App\Service\WebControllerService;
use App\User\UserService;

// Psr
use Psr\Http\Message\ResponseInterface as Response;

// Yiisoft
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\ViewRenderer;

use Cycle\Database\DatabaseManager;

final class InvoiceController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService; 
    private TranslatorInterface $translator;
    private SettingService $settingService;
    
        
    public function __construct(
                                WebControllerService $webService, 
                                UserService $userService, 
                                TranslatorInterface $translator,
                                SettingService $settingService,
                                ViewRenderer $viewRenderer 
    )
    {
                                   
        $this->webService = $webService;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->settingService = $settingService;
        $this->viewRenderer = $viewRenderer;
        // Client / user has just been signed up and assignRole command has not yet been used at command prompt
        if (!$this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice')
                                                 ->withLayout('@views/layout/invoice.php');
        }
        
        // Client / user has been signed up and assignRole command has been used to assign permissions
        // at the command prompt
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice')
                                                 ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice')
                                                 ->withLayout('@views/layout/invoice.php');
        }
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @return string
     */
    private function alert(SessionInterface $session) : string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash'=>$this->flash($session, '', ''),
            'errors' => [],
        ]);
    }
    
    /**
     * @param SessionInterface $session
     * @param ClientRepository $cR
     * @param GroupRepository $gR
     * @param InvRepository $iR
     * @param InvAmountRepository $iaR
     * @param InvRecurringRepository $irR
     * @param QuoteRepository $qR
     * @param QuoteAmountRepository $qaR
     * @param SettingRepository $sR
     * @param TaskRepository $taskR
     * @param ProjectRepository $prjctR
     */
    public function dashboard (SessionInterface $session,
                               ClientRepository $cR,
                               GroupRepository $gR,
                               InvRepository $iR,
                               InvAmountRepository $iaR,
                               InvRecurringRepository $irR,
                               QuoteRepository $qR,
                               QuoteAmountRepository $qaR,
                               SettingRepository $sR,
                               TaskRepository $taskR,
                               ProjectRepository $prjctR
                              ) : \Yiisoft\DataResponse\DataResponse {
        $data = [
            'alerts'=>$this->alert($session),
            'clienthelper'=>new ClientHelper($sR),
            // Repositories
            'irR'=>$irR,
            'qaR'=>$qaR,
            'iaR'=>$iaR,
            
            // All invoices and quotes
            'invoices'=>$iR->findAllPreloaded(),
            'quotes'=>$qR->findAllPreloaded(),
            
            // Totals for status eg. draft, sent, viewed...
            'invoice_status_totals'=>$iaR->get_status_totals($iR, $sR, $sR->get_setting('invoice_overview_period') ?: 'this-month'),
            'quote_status_totals'=>$qaR->get_status_totals($qR, $sR, $sR->get_setting('quote_status_period') ?: 'this-month'),
            
            // Array of statuses: draft, sent, viewed, paid, cancelled
            'invoice_statuses'=>$iR->getStatuses($sR),
            
            // Array of statuses: draft, sent, viewed, approved, rejected, cancelled
            'quote_statuses'=>$qR->getStatuses($sR),
            
            // this-month, last-month, this-quarter, lsat-quarter, this-year, last-year
            'invoice_status_period'=>str_replace('-', '_', $sR->get_setting('invoice_overview_period')),
            
            // this-month, last-month, this-quarter, lsat-quarter, this-year, last-year
            'quote_status_period'=>str_replace('-', '_', $sR->get_setting('quote_overview_period')),
            
            // Projects
            'projects'=>$prjctR->findAllPreloaded(),
            
            // Current tasks
            'tasks'=>$taskR->findAllPreloaded(),
            
            'task_statuses'=>$taskR->getTask_statuses($sR),
            
            'modal_create_client'=>$this->viewRenderer->renderPartialAsString('/invoice/client/modal_create_client',[
                'datehelper'=> new DateHelper($sR)
            ]),            
            'modal_create_quote'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/modal_create_quote',[
                'clients'=>$cR->findAllPreloaded(),
                'invoice_groups'=>$gR->findAllPreloaded(),
                'datehelper'=> new DateHelper($sR)
            ]),
            'modal_create_inv'=>$this->viewRenderer->renderPartialAsString('/invoice/inv/modal_create_inv',[
                'clients'=>$cR->findAllPreloaded(),
                'invoice_groups'=>$gR->findAllPreloaded(),
                'datehelper'=> new DateHelper($sR)
            ]),
            'client_count' =>$cR->count(), 
        ];
        return $this->viewRenderer->render('/invoice/dashboard/index',$data);
    }
    
    /**
     * 
     * @param string $drop_down_locale
     * @param SettingRepository $sR
     * @return void
     */
    private function cldr(string $drop_down_locale, SettingRepository $sR) : void {
        $cldr = $sR->withKey('cldr');
        if ($cldr) {
            $cldr->setSetting_value($drop_down_locale);
            $sR->save($cldr);
        }        
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
     * @param SessionInterface $session
     * @param SettingRepository $sR
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param PaymentMethodRepository $pmR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param GroupRepository $gR
     */
    public function index(SessionInterface $session, 
                          SettingRepository $sR,
                          TaxRateRepository $trR,
                          UnitRepository $uR,
                          FamilyRepository $fR,
                          PaymentMethodRepository $pmR,
                          ProductRepository $pR,
                          ClientRepository $cR,
                          GroupRepository $gR
                         ): \Yiisoft\DataResponse\DataResponse {
        $this->flash($session, 'info' , $this->viewRenderer->renderPartialAsString('/invoice/info/invoice'));
        $gR->repoCountAll() === 0 ? $this->install_default_invoice_and_quote_group($gR) : '';
        $pmR->count() === 0 ? $this->install_default_payment_methods($pmR) : '';
        // If you want to reinstall the default settings, remove the default_settings_exist setting => its count will be zero
        $sR->repoCount('default_settings_exist') === 0 ? $this->install_default_settings_on_first_run($session, $sR) : '';
        $this->install_check_for_preexisting_test_data($sR, $fR, $uR, $pR, $trR, $cR); 
        // The cldr is saved from the $session->get('_language') parameter in the Invoice/Layout/main.php file as soon as the user changes the locale.
        // The below line is an additional line of code to ensure that the locale (session runtime file) is saved to database 'cldr' and may be removed in future. 
        // The cldr setting is not accessible in non debug mode by means of the tab-index
        $sR->repoCount('cldr') === 1 && $sR->get_setting('cldr') !== $session->get('_language') ? $this->cldr($session->get('_language') ?? 'en',$sR) : '';
        $parameters = [
            'alerts'=> $this->alert($session),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param SettingRepository $sR
     * @param FamilyRepository $fR
     * @param UnitRepository $uR
     * @param ProductRepository $pR
     * @param TaxRateRepository $trR
     * @param ClientRepository $cR
     * @return void
     */
    private function install_check_for_preexisting_test_data(SettingRepository $sR, 
                                                             FamilyRepository $fR, 
                                                             UnitRepository $uR, 
                                                             ProductRepository $pR, 
                                                             TaxRateRepository $trR, 
                                                             ClientRepository $cR) : void {
        // The setting install_test_data exists
        if ($sR->repoCount('install_test_data') === 1 
                && $fR->repoTestDataCount() == 0
                && $uR->repoTestDataCount() == 0
                && $pR->repoTestDataCount() == 0
                // The setting install_test_data has been set to Yes in Settings...View
                && $sR->get_setting('install_test_data') === '1') {
                $this->install_test_data($trR, $uR, $fR, $pR, $cR, $sR);
        } else {
                // Test Data Already exists => Settings...View install_test_data must be set back to No
                $setting = $sR->withKey('install_test_data');
                if ($setting) {
                    $setting->setSetting_value('0');
                    $sR->save($setting);
                }
        }
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param SettingRepository $sR
     * @return void
     */
    private function install_default_settings_on_first_run(SessionInterface $session, SettingRepository $sR) : void {
        $default_settings = [
            //*************************************************************************************//
            // Remove the 'default_settings_exist' setting from the settings table by manually     //
            // going into the mysql database table 'settings' and deleting it. This will remove &  //
            // reinstall the default settings listed below. The above index function will check    //
            // whether this setting exists. If not THIS function will be run.                      //
            //*************************************************************************************//
            'default_settings_exist'=>1,                  
            'cldr'=> $session->get('_language') ?? 'en',
            'cron_key' => Random::string(32),
            'currency_symbol' => '£',
            'currency_symbol_placement' => 'before',
            // default payment gateway currency code
            'currency_code' => 'GBP',
            'custom_title' => 'Yii-invoice',
            'date_format' => 'd/m/Y', 
            'decimal_point' => '.',
            'default_invoice_group' => 1,
            'default_quote_group' => 2,
            'default_language' => $sR->get_folder_language() ?: 'English', 
            //paginator list limit
            'default_list_limit'=>120,            
            // Prevent documents from being made non-editable. By default documents are made non-editable
            // according to the read_only_toggle which is set at paid ie 2.
            // By default this setting is on 0 ie. Invoices can be made read-only (through the 
            // read_only_toggle)
            'disable_read_only'=> 0,
            'disable_sidebar' => 1,
            // By default, invoice deletion is not allowed. Invoices have to be cancelled with a credit invoice.
            'enable_invoice_deletion'=>true,
            // Archived pdfs are automatically sent to customers from view/invoice...Options...Send
            // The pdf is sent along with the attachment to the invoice on the view/invoice.
            'email_pdf_attachment' => 1,
            'generate_invoice_number_for_draft' => 1,
            'generate_quote_number_for_draft' => 1,
            'install_test_data'=>0,           
            'invoices_due_after' => 30,
            'invoice_logo' => 'favicon.ico',
            'mark_invoices_sent_copy' => 0,
            // Number format Default located in SettingsRepository 
            'number_format' => 'number_format_us_uk',
            'payment_list_limit' => 20,
            // Setting => filename ... under views/invoice/template/invoice/pdf           
            'pdf_invoice_template' => 'invoice',
            'pdf_invoice_template_paid' => 'paid',
            'pdf_invoice_template_overdue' => 'overdue',
            // Setting => filename ... under views/invoice/template/quote/pdf
            'pdf_quote_template' => 'quote',            
            // Templates used for processing online payments via customers login portal
            'public_invoice_template' => 'Invoice_Web',
            'public_quote_template' => 'Invoice_Web',
            'quotes_expire_after' => 15,
            // Set the invoice to read-only on paid by default; paid => 2, sent => 1 
            'read_only_toggle' => 2,
            'reports_in_new_tab' => true,
            'tax_rate_decimal_places' => 3, 
            'thousands_separator' => ',',
        ]; 
        $this->install_default_settings($default_settings, $sR);        
    }
    
    /**
     * 
     * @param array $default_settings
     * @param SettingRepository $sR
     * @return void
     */
    private function install_default_settings(array $default_settings, SettingRepository $sR) : void
    {
        $this->remove_all_settings($sR);        
        foreach ($default_settings as $key => $value) {
            $form = new SettingForm();
            $array = [
                'setting_key'=>$key,
                'setting_value'=>$value,
            ];
            if ($form->load($array)) {
                $this->settingService->saveSetting(new Setting(), $form);
            }
        }    
    }
    
    /**
     * 
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param SettingRepository $sR
     * @return void
     */
    private function install_test_data(TaxRateRepository $trR, UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR, ClientRepository $cR, SettingRepository $sR) : void {
        $this->install($trR, $uR, $fR, $pR, $cR, $sR);
    }
    
    /**
     * 
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param SettingRepository $sR
     * @return void
     */
    private function install(TaxRateRepository $trR, UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR, ClientRepository $cR, SettingRepository $sR) : void {
        // Tax
        $this->install_zero_rate($trR);
        $this->install_standard_rate($trR);
        // Unit 
        $this->install_product_unit($uR);
        $this->install_service_unit($uR);
        // Family
        $this->install_product_family($fR);
        $this->install_service_family($fR);
        // Product
        $this->install_product($trR, $uR, $fR, $pR);
        $this->install_service($trR, $uR, $fR, $pR);
        // Client
        $this->install_foreign_client($cR, $sR);
        $this->install_non_foreign_client($cR, $sR);
    }
    
    /**
     * 
     * @param TaxRateRepository $trR
     * @return void
     */
    private function install_zero_rate(TaxRateRepository $trR) : void {
        // Only allow two tax rates initially
        // These tax rates will not be deleted when test data is reset because they are defaults
        if ($trR->repoCountAll() < 2) {
            $tax_rate = new TaxRate();
            $tax_rate->setTax_rate_name('Zero');
            $tax_rate->setTax_rate_percent(0);
            $tax_rate->setTax_rate_default(false);
            $trR->save($tax_rate);
        }    
    }
    
    /**
     * 
     * @param TaxRateRepository $trR
     * @return void
     */
    private function install_standard_rate(TaxRateRepository $trR) : void {
        // Only allow two tax rates initially        
        // These tax rates will not be deleted when test data is reset because they are defaults
        if ($trR->repoCountAll() < 2) {
            $tax_rate = new TaxRate();
            $tax_rate->setTax_rate_name('Standard');
            $tax_rate->setTax_rate_percent(20);
            $tax_rate->setTax_rate_default(true);
            $trR->save($tax_rate);
        }    
    }
    
    /**
     * 
     * @param UnitRepository $uR
     * @return void
     */
    private function install_product_unit(UnitRepository $uR) : void {
        $unit = new Unit();
        $unit->setUnit_name('unit');
        $unit->setUnit_name_plrl('units');
        $uR->save($unit);
    }
    
    /**
     * 
     * @param UnitRepository $uR
     * @return void
     */
    private function install_service_unit(UnitRepository $uR) : void {
        $unit = new Unit();
        $unit->setUnit_name('service');
        $unit->setUnit_name_plrl('services');
        $uR->save($unit);
    }
    
    /**
     * 
     * @param FamilyRepository $fR
     * @return void
     */
    private function install_product_family(FamilyRepository $fR) : void {
        $family = new Family();
        $family->setFamily_name('Product');
        $fR->save($family);
    }
    
    /**
     * 
     * @param FamilyRepository $fR
     * @return void
     */
    private function install_service_family(FamilyRepository $fR) : void {
        $family = new Family();
        $family->setFamily_name('Service');
        $fR->save($family);
    }
    
    /**
     * 
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @return void
     */
    private function install_product(TaxRateRepository $trR, UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR) : void {
        $product = new Product();
        $product->setProduct_sku('12345678rgfyr');
        $product->setProduct_name('Tuch Padd');
        $product->setProduct_description('Description of Touch Pad');
        $product->setProduct_price(100.00);
        $product->setPurchase_price(30.00);
        $product->setProvider_name('We Provide');
        $taxrate = $trR->withName('Standard');
        if ($taxrate) {
            $product->setTax_rate_id($taxrate->getTax_rate_id());
        }
        $unit = $uR->withName('unit');
        if ($unit) {
            $product->setUnit_id($unit->getUnit_id());
        }
        $family = $fR->withName('Product');
        if ($family) {
            $product->setFamily_id($family->getFamily_id());
        }
        $product->setProduct_tariff(5);
        $pR->save($product);
    }
    
    /**
     * 
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @return void
     */
    private function install_service(TaxRateRepository $trR, UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR) : void {
        $service = new Product();
        $service->setProduct_sku('d234ds678rgfyr');
        $service->setProduct_name('Cleen Screans');
        $service->setProduct_description('Clean a screen');
        $service->setProduct_price(5.00);
        $service->setPurchase_price(0.00);
        $service->setProvider_name('Employee');
        $taxrate = $trR->withName('Zero');
        if ($taxrate) {
           $service->setTax_rate_id($taxrate->getTax_rate_id());
        }
        $unit = $uR->withName('service');
        if ($unit) {
            $service->setUnit_id($unit->getUnit_id());
        }
        $family = $fR->withName('Service');
        if ($family) {
            $service->setFamily_id($family->getFamily_id());
        }
        $service->setProduct_tariff(3);
        $pR->save($service);
    }
    
    /**
     * 
     * @param ClientRepository $cR
     * @param SettingRepository $s
     * @return void
     */
    private function install_foreign_client(ClientRepository $cR, SettingRepository $s) : void {
        $client = new Client();
        $client->setClient_active(true);
        $client->setClient_name('Foreign');
        $client->setClient_surname('Client');
        $client->setClient_email('email@email.com');
        $client->setClient_language('Japanese');
        $client->setClient_birthdate(new \DateTime());
        $client->setClient_gender(2);
        $cR->save($client);
    }
    
    /**
     * 
     * @param ClientRepository $cR
     * @param SettingRepository $s
     * @return void
     */
    private function install_non_foreign_client(ClientRepository $cR, SettingRepository $s) : void {
        $client = new Client();
        $client->setClient_active(true);
        $client->setClient_name('Non');
        $client->setClient_surname('Foreign');
        $client->setClient_email('email@foreign.com');
        $client->setClient_language('English');
        $client->setClient_birthdate(new \DateTime());
        $client->setClient_gender(2);
        $cR->save($client);
    }
    
    /**
     * 
     * @param GroupRepository $gR
     * @return void
     */
    private function install_default_invoice_and_quote_group(GroupRepository $gR) : void {
        $i_group = new Group();
        $i_group->setName('Invoice Group');
        $i_group->setIdentifier_format('INV{{{id}}}');
        $i_group->setNext_id(1);
        $i_group->setLeft_pad(0);
        $gR->save($i_group);
        
        $q_group = new Group();
        $q_group->setName('Quote Group');
        $q_group->setIdentifier_format('QUO{{{id}}}');
        $q_group->setNext_id(1);
        $q_group->setLeft_pad(0);
        $gR->save($q_group);
    }
    
    /**
     * 
     * @param PaymentMethodRepository $pmR
     * @return void
     */    
    private function install_default_payment_methods(PaymentMethodRepository $pmR) : void {
        // 1
        $pm_none = new PaymentMethod();
        $pm_none->setName('None');
        $pmR->save($pm_none);
        // 2 
        $pm_cash = new PaymentMethod();
        $pm_cash->setName('Cash');
        $pmR->save($pm_cash);
        // 3
        $pm_cheque = new PaymentMethod();
        $pm_cheque->setName('Cheque');
        $pmR->save($pm_cheque);
        // 4
        $pm_succeeded = new PaymentMethod();
        $pm_succeeded->setName('Card / Direct Debit - Payment Succeeded');
        $pmR->save($pm_succeeded);
        // 5
        $pm_processing = new PaymentMethod();
        $pm_processing->setName('Card / Direct Debit - Payment Processing');
        $pmR->save($pm_processing);
        // 6
        $pm_unsuccessful = new PaymentMethod();
        $pm_unsuccessful->setName('Card / Direct Debit - Payment Unsuccessful');
        $pmR->save($pm_unsuccessful);
        // 7
        $customer_ready = new PaymentMethod();
        $customer_ready->setName('Card / Direct Debit - Customer Ready for Payment');
        $pmR->save($customer_ready);
    }
    
    /**
     * @return Response|true
     */
    private function rbac(SessionInterface $session): bool|Response {
        $canEdit = $this->userService->hasPermission('viewInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('invoice/index');
        }
        return $canEdit;
    }
    
    /**
     * 
     * @param SettingRepository $sR
     * @return void
     */
    private function remove_all_settings(SettingRepository $sR): void {
        // Completely remove any currently existing settings
        $all_settings = $sR->findAllPreloaded();
        foreach ($all_settings as $setting) {
           if ($setting instanceof Setting) { 
            $sR->delete($setting);
           } 
        }
    }
    
    /**
     * 
     * @param SettingRepository $sR
     * @return Response
     */
    public function setting_reset(SettingRepository $sR): Response{
        $canEdit = $this->userService->hasPermission('editInv');
        if ($canEdit) { 
            $this->remove_all_settings($sR);                     
        }
        return $this->webService->getRedirectResponse('invoice/index');
    }
    
    /**
     * @param SessionInterface $session
     * @param SettingRepository $sR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param QuoteRepository $qR
     * @param InvRepository $iR
     */
    public function test_data_remove(SessionInterface $session,  
                                    SettingRepository $sR,
                                    UnitRepository $uR,
                                    FamilyRepository $fR,
                                    ProductRepository $pR,
                                    ClientRepository $cR,
                                    QuoteRepository $qR,
                                    InvRepository $iR,
                                   ): \Yiisoft\DataResponse\DataResponse {
        $flash =  '';
        if (($sR->repoCount('use_test_data') > 0 && $sR->get_setting('use_test_data') == '0')) {
            // Only remove the test data if the user's test quotes and invoices have been removed FIRST else integrity constraint violations
            if (($qR->repoCountAll() > 0) || ($iR->repoCountAll() > 0)) {
                $flash = $this->translator->translate('invoice.first.reset');
            } else {
                // Note: The Tax Rates are not deleted because you must have at least one zero tax rate and one standard rate
                // for the quotes and invoices to function corrrectly
                $this->test_data_delete($uR, $fR, $pR, $cR); 
                $flash = $this->translator->translate('invoice.deleted');           
            }
        } else {
                // Settings...General...Install Test Data => change to 'no' before you remove the test data
                $flash = $this->translator->translate('invoice.install.test.data');                 
        }
        $data = [
                'alerts'=> $this->alert($session),          
        ];
        return $this->viewRenderer->render('index', $data);
    }
    
    /**
     * @param SessionInterface $session
     * @param SettingRepository $sR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param QuoteRepository $qR
     * @param InvRepository $iR
     * @param TaxRateRepository $trR
     */
    public function test_data_reset(SessionInterface $session,  
                                    SettingRepository $sR,
                                    UnitRepository $uR,
                                    FamilyRepository $fR,
                                    ProductRepository $pR,
                                    ClientRepository $cR,
                                    QuoteRepository $qR,
                                    InvRepository $iR,
                                    TaxRateRepository $trR
                                   ): \Yiisoft\DataResponse\DataResponse {        
        $flash =  '';
        if ($sR->repoCount('install_test_data') > 0 && $sR->get_setting('install_test_data') == 1) {
            // Only remove the test data if the user's test quotes and invoices have been removed FIRST else integrity constraint violations
            if (($qR->repoCountAll() > 0) || ($iR->repoCountAll() > 0)) {
                $flash = $this->translator->translate('invoice.first.reset');
            } else {
                $this->test_data_delete($uR, $fR, $pR, $cR); 
                $this->install_test_data($trR, $uR, $fR, $pR, $cR, $sR);
                $flash = $sR->trans('reset');
            }
        } else {
                $flash = $this->translator->translate('invoice.install.test.data');           
        }
        $this->flash($session, 'info', $flash);
        $data = [
                'alerts'=> $this->alert($session),          
        ];
        return $this->viewRenderer->render('index', $data);
    }   
    
    /**
     * 
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @return void
     */
    private function test_data_delete(UnitRepository $uR, FamilyRepository $fR, ProductRepository $pR, ClientRepository $cR) : void {
        // Products
        $product = (null !==$pR->withName('Tuch Padd') ? $pR->withName('Tuch Padd') : null);
        null !==$product ? $pR->delete($product) : null; 
        $service = (null !==$pR->withName('Cleen Screans') ? $pR->withName('Cleen Screans') : null);
        null !==$service ? $pR->delete($service) : null;
        // Family
        $family_product = (null !==$fR->withName('Product') ? $fR->withName('Product') : null);
        null !==$family_product ? $fR->delete($family_product) : null;
        $family_service = (null !==$fR->withName('Service') ? $fR->withName('Service') : null);
        null !==$family_service ? $fR->delete($family_service) : null;
        // Unit
        $unit = (null !==$uR->withName('unit') ? $uR->withName('unit') : null);
        null !==$unit ? $uR->delete($unit) : null;
        $unit_service = (null !==$uR->withName('service') ? $uR->withName('service') : null);
        null !==$unit_service ? $uR->delete($unit_service) : null;
        // Client
        $client_non = (null !==$cR->withName('Non') ? $cR->withName('Non') : null);
        null !==$client_non ? $cR->delete($client_non) : null;
        $client_foreign = (null !==$cR->withName('Foreign') ? $cR->withName('Foreign') : null);
        null !==$client_foreign ? $cR->delete($client_foreign) : null;
        // Group data is not deleted because these are defaults
    }
    
    /**
     * @param SessionInterface $session
     * @param CurrentUser $currentUser
     * @param DatabaseManager $dbal
     * @param SettingRepository $sR
     * @param TaxRateRepository $trR
     * @param UnitRepository $uR
     * @param FamilyRepository $fR
     * @param ProductRepository $pR
     * @param ClientRepository $cR
     * @param GroupRepository $gR
     */
    public function ubuntu(SessionInterface $session, CurrentUser $currentUser, DatabaseManager $dbal, 
                           SettingRepository $sR,
                           TaxRateRepository $trR,
                           UnitRepository $uR,
                           FamilyRepository $fR,
                           ProductRepository $pR,
                           ClientRepository $cR,                           
                           GroupRepository $gR
                          ): \Yiisoft\DataResponse\DataResponse {
        $canEdit = $this->rbac($session);
        $flash = $this->flash($session, 'info' , $this->viewRenderer->renderPartialAsString('/invoice/info/ubuntu'));        
        $gR->repoCountAll() === 0 ? $this->install_default_invoice_and_quote_group($gR) : '';
        $sR->repoCount('default_settings_exist') === 0 ? $this->install_default_settings_on_first_run($session, $sR) : ''; 
        $sR->repoCount('install_test_data') === 1 && $sR->get_setting('install_test_data') === '1' ? $this->install_test_data($trR, $uR, $fR, $pR, $cR, $sR) : '';
        $sR->repoCount('cldr') === 1 && $sR->get_setting('cldr') !== $session->get('_language') ? $this->cldr($session->get('_language') ?? 'en',$sR) : '';
        $data = [
            'isGuest' => $currentUser->isGuest(),
            'canEdit' => $canEdit,
            'tables'=> $dbal->database('default')->getTables(),
            'flash'=> $flash,           
        ];
        return $this->viewRenderer->render('index', $data);
    }
}



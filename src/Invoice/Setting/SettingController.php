<?php
declare(strict_types=1);

namespace App\Invoice\Setting;

// App
use App\Invoice\Entity\Setting;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ER;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Libraries\Crypt;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PM;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\CurrencyHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\TaxRate\TaxRateRepository as TR;
//use App\Invoice\Libraries\Sumex;
use App\Service\WebControllerService;
use App\User\UserService;
// Yii
use Yiisoft\Aliases\Aliases;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Files\FileHelper;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;
// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Miscellaneous
use \DateTimeZone;

final class SettingController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private SettingService $settingService;
    private Translator $translator;
    private UserService $userService;    
    private DataResponseFactoryInterface $factory;
    private Session $session;
    private SettingRepository $s;
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        SettingService $settingService,
        Translator $translator,
        UserService $userService,
        DataResponseFactoryInterface $factory,
        Session $session,
        SettingRepository $s,    
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/setting')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->settingService = $settingService;
        $this->translator = $translator;
        $this->userService = $userService;
        $this->factory = $factory;
        $this->session = $session;
        $this->s = $s;
    }
    
    // The debug index is simply a list of the settings that is useful to change when debugging and appears in red    
    
    /**
     * @param CurrentRoute $currentRoute
     */
    public function debug_index(CurrentRoute $currentRoute): \Yiisoft\DataResponse\DataResponse
    {  
        $pageNum = (int)$currentRoute->getArgument('page', '1');
        $paginator = (new OffsetPaginator($this->settings($this->s)))
        ->withPageSize((int)$this->s->get_setting('default_list_limit'))
        ->withCurrentPage($pageNum);
        $canEdit = $this->rbac(); 
        $parameters = [
              'paginator' => $paginator,
              's'=>$this->s,
              'canEdit' => $canEdit,
              'flash'=>$this->flash($this->session, '', ''),
              'settings' => $this->settings($this->s),
              'session'=>$this->session,
              'trans'=>$this->translator->translate('invoice.setting.translator.key'),
              'section'=>$this->translator->translate('invoice.setting.section'),
              'subsection'=>$this->translator->translate('invoice.setting.subsection'),
        ];
        return $this->viewRenderer->render('debug_index', $parameters);
    }
    
    // The tab_index is the index of settings showing in non debug mode
    
    /**
     * 
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param ViewRenderer $head
     * @param ER $eR
     * @param GR $gR
     * @param PM $pm
     * @param SettingRepository $sR
     * @param TR $tR
     * @return Response
     */
    public function tab_index(Request $request, ValidatorInterface $validator, ViewRenderer $head, 
                              ER $eR, 
                              GR $gR, 
                              PM $pm, 
                              SettingRepository $sR, 
                              TR $tR) : Response {
        
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => '@invoice/Language',
                                '@icon' => '@invoice/Uploads/Temp']);
        $datehelper = new DateHelper($this->s);
        $numberhelper = new NumberHelper($this->s);
        $countries = new CountryHelper();
        $crypt = new Crypt();
        $matrix = $this->s->expandDirectoriesMatrix($aliases->get('@language'), 0);
        /**
         * @psalm-suppress PossiblyInvalidArgument $matrix
         */
        $languages = ArrayHelper::map($matrix,'name','name');      
        $parameters = [
            'defat'=> $sR->withKey('default_language'),
            'action'=>['setting/tab_index'],
            'alert'=>$this->viewRenderer->renderPartialAsString('/invoice/layout/alert',[
                    'flash'=>$this->flash($this->session,'',''),
                    'errors' => [],
            ]),
            's'=> $this->s,
            'head' => $head,
            'body'=> $request->getParsedBody(),
            'general'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_general',[
                's'=>$this->s,
                /**
                 * @psalm-suppress PossiblyInvalidArgument
                 */
                'languages'=> $languages,
                'first_days_of_weeks'=>['0' => $this->s->lang('sunday'), '1' => $this->s->lang('monday')],
                'date_formats'=>$datehelper->date_formats(),
                // Used in ClientForm
                'time_zones'=> DateTimeZone::listIdentifiers(),
                'countries'=>$countries->get_country_list($this->session->get('_language')),
                'gateway_currency_codes'=>CurrencyHelper::all(),
                'number_formats'=>$this->s->number_formats(),
                'current_date'=>new \DateTime(),
                'icon'=>$aliases->get('@icon')
            ]),
            'invoices'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_invoices',[
                's'=>$this->s,
                'invoice_groups'=>$gR->findAllPreloaded(),
                'payment_methods'=>$pm->findAllPreloaded(),
                'public_invoice_templates'=>$this->s->get_invoice_templates('public'),
                'pdf_invoice_templates'=>$this->s->get_invoice_templates('pdf'),
                'email_templates_invoice'=>$eR->repoEmailTemplateType('invoice'),
                'roles' => [],//Sumex::ROLES,
                'places' => [],//Sumex::PLACES,
                'cantons' => [],//Sumex::CANTONS,
            ]),
            'quotes'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_quotes',[
                's'=>$this->s,
                'invoice_groups'=>$gR->findAllPreloaded(),
                'public_quote_templates'=>$this->s->get_quote_templates('public'),
                'pdf_quote_templates'=>$this->s->get_quote_templates('pdf'),
                'email_templates_quote'=>$eR->repoEmailTemplateType('quote'),
            ]),
            'taxes'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_taxes',[
                's'=>$this->s,
                'tax_rates'=>$tR->findAllPreloaded(),
            ]),
            'email'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_email',[
                's'=>$this->s,
            ]),
            'google_translate'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_google_translate',[
                'locales'=>$this->s->locales(),
                's'=>$this->s,
            ]),
            'online_payment'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_online_payment',[
                's'=>$this->s,
                'gateway_drivers'=>$this->s->payment_gateways(),
                'gateway_currency_codes'=>CurrencyHelper::all(),
                'payment_methods'=>$pm->findAllPreloaded(),                
                'crypt'=> $crypt
            ]),
            'projects_tasks'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_projects_tasks',[
                's'=>$this->s,
            ]),
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $parameters['body'];
            if (is_array($body)) {
                $settings = $body['settings'];
                foreach ($settings as $key => $value) {
                    $key === 'tax_rate_decimal_places' && $value !== 2 ? $this->tab_index_change_decimal_column((int)$value) : '';
                    // Deal with existing keys after first installation
                    if ($sR->repoCount((string)$key) > 0) {
                        if (strpos($key, 'field_is_password') !== false || strpos($key, 'field_is_amount') !== false) {
                            // Skip all meta fields
                            continue;
                        }                    
                        if (isset($settings[$key . '_field_is_password']) && empty($value)) {
                            // Password field, but empty value, let's skip it
                            continue;
                        }
                        if (isset($settings[$key . '_field_is_password']) && $value !=='') {
                            // Encrypt passwords but don't save empty passwords
                            $this->tab_index_settings_save($key, $crypt->encode(trim($value)), $sR);
                        } elseif (isset($settings[$key . '_field_is_amount'])) {
                            // Format amount inputs
                            $this->tab_index_settings_save($key, $numberhelper->standardize_amount($value), $sR);
                        } else {
                            $this->tab_index_settings_save($key, $value, $sR);
                        }  

                        if ($key == 'number_format') {
                            // Set thousands_separator and decimal_point according to number_format
                            // Derive the 'decimal_point' and 'thousands_separator' setting from the chosen ..number format eg. 1000,000.00 if it has a value
                            $this->tab_index_number_format($value, $sR);
                        }
                    }
                    else {
                       // The key does not exist because the repoCount is not greater than zero => add
                       // Note:
                       // The settings 'decimal_point' and 'thousands_separator' which are derived from number_format array
                       // and were installed on the first run in InvoiceController 
                       // will be derived automatically => their repoCount will be greater than zero and will not cause this to run
                       $this->tab_index_debug_mode_ensure_all_settings_included(true, $key, $value, $validator);
                    }                
                }
                $this->flash($this->session, 'info', $this->s->trans('settings_successfully_saved'));
                return $this->webService->getRedirectResponse('setting/tab_index');
                }
            }
            return $this->viewRenderer->render('tab_index', $parameters);        
    }
    
    public function tab_index_settings_save(string $key, string $value, SettingRepository $sR) : void {
        $setting = $sR->withKey($key);
        if ($setting) {
            $setting->setSetting_value($value);
            $sR->save($setting);
        }
    }
    
    /**
     * 
     * @param int $value
     * @return void
     */
    public function tab_index_change_decimal_column(int $value) : void {
        // Change the decimal column dynamically using cycle and the Fragment command. SyncTable has been commented out from config/params.php
        // TODO
    }
    
    /**
     * 
     * @param string $value
     * @param SettingRepository $sR
     * @return void
     */
    public function tab_index_number_format(string $value, SettingRepository $sR) : void {
        // Set thousands_separator and decimal_point according to number_format
        $number_formats = $sR->number_formats();
        if ($sR->repoCount('decimal_point') > 0) {
            $this->tab_index_settings_save('decimal_point',
                                            $number_formats[$value]['decimal_point'], 
                                            $sR);
        }
        if ($sR->repoCount('thousands_separator') > 0) {      
            $this->tab_index_settings_save('thousands_separator',
                                            $number_formats[$value]['thousands_separator'], 
                                            $sR);
        }     
    }
    
    // This procedure is used in the above procedure to ensure that all settings are being captured.
    /**
     * 
     * @param bool $bool
     * @param string $key
     * @param string $value
     * @param ValidatorInterface $validator
     * @return void
     */
    public function tab_index_debug_mode_ensure_all_settings_included(bool $bool, string $key, string $value, ValidatorInterface $validator): void {
        // The setting does not exist because repoCount is not greater than 0;
        if ($bool) {
            // Make sure the setting is available to be set in the database if there is no such like setting in the database
            $form = new SettingForm();
            $array = [
                'setting_key'=>$key,
                'setting_value'=>$value,
            ];
            if ($form->load($array) && $validator->validate($form)->isValid()) {
                $this->settingService->saveSetting(new Setting(), $form);
            }
        }
    }
    
    /**
     * 
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function add(Request $request, ValidatorInterface $validator): Response
    {
        $parameters = [
            'title' => $this->s->trans('add'),
            'action' => ['setting/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$this->s
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new SettingForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->settingService->saveSetting(new Setting(), $form);
                $this->flash($this->session, 'info', $this->s->trans('record_successfully_updated'));
                return $this->webService->getRedirectResponse('setting/debug_index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    /**
     * 
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function edit(Request $request, CurrentRoute $currentRoute, 
              ValidatorInterface $validator): Response 
    {
        $setting = $this->setting($currentRoute, $this->s);
        if ($setting) {
            $parameters = [
                'title' => $this->s->trans('edit'),
                'action' => ['setting/edit', ['setting_id' => $setting->getSetting_id()]],
                'errors' => [],
                'body' => [
                    'setting_key' => $setting->getSetting_key(),
                    'setting_value' => $setting->getSetting_value(),                
                ],
                's'=>$this->s,
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new SettingForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->settingService->saveSetting($setting, $form);
                    $this->flash($this->session, 'info', $this->s->trans('record_successfully_updated'));
                    return $this->webService->getRedirectResponse('setting/debug_index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('__form', $parameters);
        }
        return $this->webService->getRedirectResponse('setting/debug_index');
    }
    
    /**
     * @return true
     */
    public function true(): bool  {
       return true; 
    }
    
    /**
     * @return false
     */
    public function false(): bool {
        return false;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute): Response 
    {
        $setting = $this->setting($currentRoute,$this->s);
        if ($setting) {
            $this->flash($this->session,'info','This record has been deleted.');
            $this->settingService->deleteSetting($setting);               
        }
        return $this->webService->getRedirectResponse('setting/debug_index');        
    }
    
    /**
     * 
     * @param Session $session
     * @param string $level
     * @param string $message
     * @psalm-param ''|'info'|'warning' $level
     * @return Flash
     */
    private function flash(Session $session, string $level, string $message): Flash{
        $flash = new Flash($session);
        $flash->set($level, $message); 
        return $flash;
    }
    
    /**
     * 
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function save_form(Request $request, CurrentRoute $currentRoute, 
              ValidatorInterface $validator): Response 
    {
        $setting = $this->setting($currentRoute, $this->s);
        if ($setting) {
            $parameters = [
                'title' => $this->s->trans('edit'),
                'action' => ['setting/edit', ['setting_id' => $setting->getSetting_id()]],
                'errors' => [],
                'body' => [
                    'setting_key' => $setting->getSetting_key(),
                    'setting_value' => $setting->getSetting_value(),
                ],
                's'=>$this->s,
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new SettingForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->settingService->saveSetting($setting, $form);
                    return $this->webService->getRedirectResponse('setting/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('__form', $parameters);
        }
        return $this->webService->getRedirectResponse('setting/index');
    }
    
    /**
     * @param CurrentRoute $currentRoute
     */
    public function view(CurrentRoute $currentRoute)
        : \Yiisoft\DataResponse\DataResponse|Response {
        $setting = $this->setting($currentRoute, $this->s);
        if ($setting) {
            $parameters = [
                'title' => $this->s->trans('view'),
                'action' => ['setting/edit', ['setting_id' => $setting->getSetting_id()]],
                'errors' => [],
                'setting'=>$setting,
                's'=>$this->s,     
                'body' => [
                    'setting_id'=>$setting->getSetting_id(),
                    'setting_key'=>$setting->getSetting_key(),
                    'setting_value'=>$setting->getSetting_value(),
                ],            
            ];
            return $this->viewRenderer->render('__view', $parameters);
        }
        return $this->webService->getRedirectResponse('setting/index');
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($this->session, 'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('setting/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param SettingRepository $settingRepository
     * @return object|null
     */
    private function setting(CurrentRoute $currentRoute, 
                             SettingRepository $settingRepository): object|null 
    {
        $setting_id = $currentRoute->getArgument('setting_id');
        if (null!==$setting_id) {
            $setting = $settingRepository->repoSettingquery($setting_id);
            return $setting; 
        }
        return null;
    }
    
    //$settings = $this->settings();
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function settings(SettingRepository $settingRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader{
        $settings = $settingRepository->findAllPreloaded();
        return $settings;
    }
    
    public function clear() : \Yiisoft\DataResponse\DataResponse
    {
        $directory = dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR. 'assets';
        try {
            $filehelper = new FileHelper;
            $filehelper->clearDirectory($directory);
            $this->flash($this->session,'info', 'Assets cleared at '.$directory);
            return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/successful',
            ['heading'=>'Successful','message'=>'You have cleared the cache.'])); 
        } catch (\Exception $e) {            
            $this->flash($this->session,'warning', 'Assets were not cleared at '.$directory. 'due to '.$e->getMessage());            
            return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/unsuccessful',
            ['heading'=>'Unsuccessful','message'=>'You have NOT cleared the cache due to a '. $e->getMessage().' error on the public assets folder.'])); 
        }
    }
    
    public function get_cron_key() : \Yiisoft\DataResponse\DataResponse
    {       
        $parameters = [
               'success'=>1,
               'cronkey'=>Random::string(32)
        ];
        return $this->factory->createResponse(Json::encode($parameters));     
    }
}

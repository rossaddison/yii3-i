<?php
declare(strict_types=1);

namespace App\Invoice\Client;
// Entity's
use App\Invoice\Entity\Client;
use App\Invoice\Entity\ClientNote;
use App\Invoice\Entity\UserClient;
use App\Invoice\Entity\ClientCustom;
use App\Invoice\Entity\CustomField;
// Services
use App\Service\WebControllerService;
use App\Invoice\ClientCustom\ClientCustomService;
// Forms
use App\Invoice\Client\ClientForm;
use App\Invoice\ClientCustom\ClientCustomForm;
use App\Invoice\ClientNote\ClientNoteService as cnS;
use App\Invoice\ClientNote\ClientNoteForm;
use App\Invoice\UserClient\UserClientService;
use App\Invoice\UserClient\UserClientForm;
use App\User\UserService;
// Repositories
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\ClientCustom\ClientCustomRepository as ccR;
use App\Invoice\ClientNote\ClientNoteRepository as cnR;
use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\CustomField\CustomFieldRepository as cfR;
use App\Invoice\Group\GroupRepository as gR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvRecurring\InvRecurringRepository as irR;
use App\Invoice\Payment\PaymentRepository as pymtR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as qaR;
use App\Invoice\Quote\QuoteRepository as qR;
use App\Invoice\Setting\SettingRepository as sR;
// Helpers
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\CustomValuesHelper as CVH;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\GenerateCodeFileHelper;
// Psr\\Http
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Yii
use Yiisoft\Aliases\Aliases;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;
// Miscellaneous

final class ClientController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private ClientService $clientService;
    private ClientCustomService $clientCustomService;
    private UserService $userService;
    private UserClientService $userclientService;     
    private CurrentUser $currentUser;
    private DataResponseFactoryInterface $factory;
    private TranslatorInterface $translator;
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        ClientService $clientService,
        ClientCustomService $clientCustomService,
        UserService $userService,
        UserClientService $userclientService,
        CurrentUser $currentUser,
        DataResponseFactoryInterface $factory,
        TranslatorInterface $translator
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/client')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->clientService = $clientService;
        $this->clientCustomService = $clientCustomService;
        $this->userService = $userService;
        $this->userclientService = $userclientService;
        $this->currentUser = $currentUser;
        $this->factory = $factory;
        $this->translator = $translator;
    }
    
    private function alert(SessionInterface $session) : string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash'=>$this->flash($session, '', ''),
            'errors' => [],
        ]);
    }
    
    /**
     * @param Client $client
     * @return array
     */
    private function body(Client $client): array {        
        $body = [
            'client_date_created'=>$client->getClient_date_created(),
            'client_date_modified'=>$client->getClient_date_modified(),
            'client_name' => $client->getClient_name(),
            'client_address_1' => $client->getClient_address_1(),
            'client_address_2' => $client->getClient_address_2(),
            'client_city' => $client->getClient_city(),
            'client_state' => $client->getClient_state(),
            'client_zip' => $client->getClient_zip(),
            'client_country' => $client->getClient_country(),
            'client_phone' => $client->getClient_phone(),
            'client_fax' => $client->getClient_fax(),
            'client_mobile' => $client->getClient_mobile(),
            'client_email' => $client->getClient_email(),
            'client_web' => $client->getClient_web(),
            'client_vat_id' => $client->getClient_vat_id(),
            'client_tax_code' => $client->getClient_tax_code(),
            'client_language' => $client->getClient_language(),
            'client_active'=>$client->getClient_active(),
            'client_surname'=>$client->getClient_surname(),
            'client_avs' => $client->getClient_avs(),
            'client_insurednumber'=>$client->getClient_insurednumber(),
            'client_veka'=>$client->getClient_veka(),
            'client_birthdate'=>$client->getClient_birthdate(),
            'client_gender'=>$client->getClient_gender(),                
        ];
        return $body;
    }

    /**
     * 
     * @param string $generated_dir_path
     * @param string $content
     * @param string $file
     * @param string $name
     * @return GenerateCodeFileHelper
     */
    private function build_and_save(string $generated_dir_path, string $content, string $file,string $name): GenerateCodeFileHelper {
        $build_file = new GenerateCodeFileHelper("$generated_dir_path/$name$file", $content); 
        $build_file->save();
        return $build_file;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param cR $cR
     * @return Client|null
     */
    private function client(CurrentRoute $currentRoute,cR $cR): Client|null {
        $client_id = $currentRoute->getArgument('id');
        if (null!==$client_id) {
            $client = $cR->repoClientquery($client_id);
            return $client;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function clients(cR $cR, int $active): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader {
        $clients = $cR->findAllWithActive($active); 
        return $clients;
    }
    
    /**
     * 
     * @param string $client_id
     * @param ccR $ccR
     * @return array
     */
    public function client_custom_values(string $client_id, ccR $ccR) : array
    {
        // Get all the custom fields that have been registered with this client on creation, retrieve existing values via repo, and populate 
        // custom_field_form_values array
        $custom_field_form_values = [];
        if ($ccR->repoClientCount($client_id) > 0) {
            $client_custom_fields = $ccR->repoFields($client_id);
            /**
             * @var int $key
             * @var string $val
             */
            foreach ($client_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' . $key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }
    
    // Data fed from client.js->$(document).on('click', '#client_create_confirm', function () {
    public function create_confirm(Request $request, ValidatorInterface $validator, cfR $cfR, sR $sR) : \Yiisoft\DataResponse\DataResponse
    {
        $body = $request->getQueryParams();
        $datehelper = new DateHelper($sR);
        $ajax_body = [
            'client_name'=>$body['client_name'] ?? 'clientnameismissing',
            'client_email'=>$body['client_email'] ?? 'email@email.com',
            'client_surname'=>$body['client_surname'] ?? 'clientsurnameismissing',
            'client_birthdate'=> $datehelper->date_from_mysql(new \DateTimeImmutable('now')),
            // Default gender is 'other'
            'client_gender'=>2
        ];
        $ajax_content = new ClientForm();
        if ($ajax_content->load($ajax_body) && $validator->validate($ajax_content)->isValid()) {  
            $newclient = new Client();
            $this->clientService->saveClient($newclient, $ajax_content, $sR);
                $client_id = $newclient->getClient_id();
                if ($this->currentUser->getId() === '1') {
                    $user_client = [
                        'user_id'=>1,
                        'client_id'=>$newclient->getClient_id()
                    ];
                    $form = new UserClientForm();
                    ($form->load($user_client) && $validator->validate($form)->isValid()) ? 
                        $this->userclientService->saveUserClient(new UserClient(), $form) : null;                    
                }
                // Get the custom fields that are mandatory for a client and initialise the first client with an empty value for each custom field
                $custom_fields = $cfR->repoTablequery('client_custom');
                /** @var CustomField $custom_field */
                foreach($custom_fields as $custom_field){
                    $init_client_custom = new ClientCustomForm();
                    $client_custom = [];
                    $client_custom['client_id'] = $client_id;
                    $client_custom['custom_field_id'] = $custom_field->getId();                    
                    // Note: There are no Required rules for value under ClientCustomForm
                    $client_custom['value'] = '';                    
                    if ($init_client_custom->load($client_custom) && $validator->validate($init_client_custom)->isValid()) {
                        $this->clientCustomService->saveClientCustom(new ClientCustom(), $init_client_custom);
                    }
                }
                $parameters = [
                   'success'=>1,                
                ];
            
           //return response to client.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));          
        } else {
            $parameters = [
               'success'=>0,
            ];
            //return response to client.js to reload page at location (DOM debugging)
            return $this->factory->createResponse(Json::encode($parameters));          
        } 
    }
    
    /**
     * 
     * @param ValidatorInterface $validator
     * @param array $body
     * @param mixed $matches
     * @param string $client_id
     * @param ccR $ccR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function custom_fields(ValidatorInterface $validator, array $body, mixed $matches, string $client_id, ccR $ccR) : \Yiisoft\DataResponse\DataResponse
    {   
        if (!empty($body['custom'])) {
            $db_array = [];
            $values = [];
            /**
             * @var array $custom 
             * @var string $custom['name']
             */
            foreach ($body['custom'] as $custom) {
                if (preg_match("/^(.*)\[\]$/i", $custom['name'], $matches)) {
                     /**
                     * @var string $custom['value']
                     */
                    $values[$matches[1]][] = $custom['value'];
                } else {
                     /**
                     * @var string $custom['value']
                     */
                    $values[$custom['name']] = $custom['value'];
                }
            }
            
            foreach ($values as $key => $value) {
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                     /**
                     * @var string $value
                     */
                    $db_array[$matches[1]] = $value;
                }
            }
            
            foreach ($db_array as $key => $value){
                $ajax_custom = new ClientCustomForm();
                $client_custom = [];
                $client_custom['client_id']=$client_id;
                $client_custom['custom_field_id']=$key;
                $client_custom['value']=$value; 
                $model = ($ccR->repoClientCustomCount($client_id,$key) == 1 ? $ccR->repoFormValuequery($client_id,$key) : new ClientCustom());
                if ($model instanceof ClientCustom) {
                   if ($ajax_custom->load($client_custom) && $validator->validate($ajax_custom)->isValid()) { 
                        $this->clientCustomService->saveClientCustom($model, $ajax_custom);
                   }     
                }
            }
            $parameters = [
                'success'=>1,
            ];
            return $this->factory->createResponse(Json::encode($parameters)); 
        } else {
            $parameters = [
                'success'=>0,
            ];           
            return $this->factory->createResponse(Json::encode($parameters)); 
        }
    }  
    
    public function delete(SessionInterface $session,CurrentRoute $currentRoute,cR $cR, sR $sR
    ): Response {
        try {
            $this->clientService->deleteClient($this->client($currentRoute, $cR)); 
             $this->flash($session, 'info', $sR->trans('record_successfully_deleted'));
            //UserClient Entity automatically deletes the UserClient record relevant to this client 
            return $this->webService->getRedirectResponse('client/index');
	} catch (\Exception $e) {
              unset($e);
              $this->flash($session, 'danger', 'Cannot delete. Client history exists.');
              return $this->webService->getRedirectResponse('client/index');
        }
    } 
    
    public function edit(ViewRenderer $head, SessionInterface $session, Request $request, cR $cR, ccR $ccR, cfR $cfR, cvR $cvR, 
            ValidatorInterface $validator, sR $sR, CurrentRoute $currentRoute
    ): Response {
     $client = null!==$this->client($currentRoute, $cR) ? $this->client($currentRoute, $cR) : null;
     if ($client) {
        $selected_country =  $client->getClient_country(); 
        $selected_language = $client->getClient_language();
        $countries = new CountryHelper();
        $client_id = $client->getClient_id();
        if (null!==$client_id) {
            $parameters = [
                'title' => $sR->trans('edit'),
                'action' => ['client/edit', ['id' => $client_id]],
                'errors' => [],
                'head'=> $head,
                'datehelper'=> new DateHelper($sR),
                'client'=> $client,
                'body' => $this->body($client),
                'aliases'=> new Aliases(['@invoice' => dirname(__DIR__), '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR.'Language']),
                'selected_country' => $selected_country ?: $sR->get_setting('default_country'),            
                'selected_language' => $selected_language ?: $sR->get_setting('default_language'),
                'datepicker_dropdown_locale_cldr' => $session->get('_language') ?? 'en',
                'countries'=> $countries->get_country_list($sR->get_setting('cldr')),
                'custom_fields'=> $cfR->repoTablequery('client_custom'),
                'custom_values'=> $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('client_custom')),
                'cvH'=> new CVH($sR),
                'client_custom_values'=> $this->client_custom_values((string)$client_id, $ccR)
            ];
            if ($request->getMethod() === Method::POST) {            
                $edited_body = $request->getParsedBody();
                if (is_array($edited_body)) {
                    $returned_form = $this->edit_save_form_fields($edited_body, $client, $validator, $sR);
                    $parameters['body'] = $edited_body;
                    $parameters['errors']=$returned_form->getFormErrors(); 
                    // Only save custom fields if they exist
                    if ($ccR->repoClientCount((string)$client_id) > 0) { 
                      $this->edit_save_custom_fields($edited_body, $validator, $ccR, (string)$client_id); 
                    }
                }    
                $this->flash($session, 'info', $sR->trans('record_successfully_updated'));
                return $this->webService->getRedirectResponse('client/index');
            }
            return $this->viewRenderer->render('__form', $parameters);
        } // null!==client_id
    } // $client
    return $this->webService->getRedirectResponse('client/index');   
}
    
    /**
     * 
     * @param array $edited_body
     * @param Client $client
     * @param ValidatorInterface $validator
     * @param sR $sR
     * @return ClientForm
     */
    public function edit_save_form_fields(array $edited_body, Client $client, ValidatorInterface $validator, sR $sR) : ClientForm {
        $form = new ClientForm();
        if ($form->load($edited_body) && $validator->validate($form)->isValid()) {
           $this->clientService->saveClient($client, $form, $sR);
        }
        return $form;
    }
    
    /**
     * 
     * @param array $edited_body
     * @param ValidatorInterface $validator
     * @param ccR $ccR
     * @param string $client_id
     * @return void
     */
    public function edit_save_custom_fields(array $edited_body, ValidatorInterface $validator, ccR $ccR, string $client_id): void {
        $custom = (array)$edited_body['custom'];
        /** @var string $value */
        foreach ($custom as $custom_field_id => $value) {
            $client_custom = $ccR->repoFormValuequery($client_id, (string)$custom_field_id);
            if (null!==$client_custom) {
                $client_custom_input = [
                    'client_id'=>(int)$client_id,
                    'custom_field_id'=>(int)$custom_field_id,
                    'value'=>$value
                ];
                $form = new ClientCustomForm();
                if ($form->load($client_custom_input) && $validator->validate($form)->isValid())
                {
                    $this->clientCustomService->saveClientCustom($client_custom, $form);     
                }
            }
        }        
    }
    
    private function flash(SessionInterface $session, string $level, string $message): Flash{
        $flash = new Flash($session);
        $flash->set($level, $message); 
        return $flash;
    }
    
    public function index(CurrentRoute $currentRoute, SessionInterface $session, cR $cR, iaR $iaR, iR $iR, sR $sR): 
        \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac($session);
        $pageNum = (int)$currentRoute->getArgument('page', '1');        
        $active = (int)$currentRoute->getArgument('active', '2');
        $paginator = (new OffsetPaginator($this->clients($cR, $active)))
            ->withPageSize((int)$sR->get_setting('default_list_limit'))
            ->withCurrentPage($pageNum);
        $parameters = [
            'paginator'=>$paginator,
            'alert'=>$this->alert($session),
            'iR'=> $iR,
            'iaR'=> $iaR,
            'canEdit' => $canEdit,
            'active'=>$active,
            'pageNum'=>$pageNum,
            'modal_create_client'=>$this->viewRenderer->renderPartialAsString('modal_create_client',[
                'datehelper'=> new DateHelper($sR)
            ])
        ];    
        return $this->viewRenderer->render('index', $parameters);
    }    
    
    public function load_client_notes(Request $request, cnR $cnR): \Yiisoft\DataResponse\DataResponse
    {
        $body = $request->getQueryParams();
        /** @var int $body['client_id'] */
        $client_id = $body['client_id'];
        $data = $cnR->repoClientNoteCount($client_id) > 0 ? $cnR->repoClientquery((string)$client_id) : null;
        $parameters = [
            'success'=>1,
            'data'=> $data,
        ];           
        return $this->factory->createResponse(Json::encode($parameters)); 
    }
    
    /**
     * @return Response|true
     */
    private function rbac(SessionInterface $session): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('client/index');
        }
        return $canEdit;
    }
    
    /**
     * 
     * @param ValidatorInterface $validator
     * @param ccR $ccR
     * @param string $client_id
     * @param array $body
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function save_client_custom_fields(ValidatorInterface $validator, ccR $ccR, string $client_id, array $body)
                    : \Yiisoft\DataResponse\DataResponse
    {  
       $custom = (array)$body['custom'];
       $custom_field_body = [            
            'custom'=>$custom,            
       ];
       if (!empty($custom_field_body['custom'])) {
            $db_array = [];
            $values = [];
             /**
             * @var array $custom 
             * @var string $custom['name']
             */
            foreach ($custom_field_body['custom'] as $custom) {
                if (preg_match("/^(.*)\[\]$/i", $custom['name'], $matches)) {
                    /** @var string $custom['value'] */
                    $values[$matches[1]][] = $custom['value'];
                } else {
                    /** @var string $custom['value']  */
                    $values[$custom['name']] = $custom['value'];
                }
            }            
            foreach ($values as $key => $value) {
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                    $db_array[$matches[1]] = $value;
                }
            }
            /**
             * @var string $value
             */
            foreach ($db_array as $key => $value){
                $ajax_custom = new ClientCustomForm();
                $client_custom = [];
                $client_custom['client_id']=$client_id;
                $client_custom['custom_field_id']=$key;
                $client_custom['value']=$value; 
                $model = ($ccR->repoClientCustomCount($client_id,$key) == 1 ? $ccR->repoFormValuequery($client_id, $key) : new ClientCustom());
                if (null!==$model && $ajax_custom->load($client_custom) && $validator->validate($ajax_custom)->isValid()) {
                    $this->clientCustomService->saveClientCustom($model, $ajax_custom);
                }           
            }
            $parameters = [
                'success'=>1,
                'clientid'=>$client_id,
            ];
            return $this->factory->createResponse(Json::encode($parameters)); 
        } else {
            $parameters = [
                'success'=>0,
            ];           
            return $this->factory->createResponse(Json::encode($parameters)); 
        }
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param ValidatorInterface $validator
     * @param Request $request
     * @param ccR $ccR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function save_custom_fields(SessionInterface $session, ValidatorInterface $validator, Request $request, ccR $ccR)
                    : \Yiisoft\DataResponse\DataResponse
    {
       $body = $request->getQueryParams();
       $custom = $body['custom'] ? (array)$body['custom'] : '';
       $custom_field_body = [            
            'custom'=>$custom,            
        ];      
       $client_id = (string)$session->get('client_id');
       if (!empty($custom_field_body['custom'])) {
            $db_array = [];
            $values = [];
            /**
             * @var array $custom 
             * @var string $custom['name']
             */
            foreach ($custom_field_body['custom'] as $custom) {
                if (preg_match("/^(.*)\[\]$/i", $custom['name'], $matches)) {
                    /**
                     * @var string $custom['value']
                     */
                    $values[$matches[1]][] = $custom['value'];
                } else {
                    /**
                     * @var string $custom['value']
                     */
                    $values[$custom['name']] = $custom['value'];
                }
            }            
            foreach ($values as $key => $value) {
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                    $db_array[$matches[1]] = $value;
                }
            }            
            foreach ($db_array as $key => $value){
                $ajax_custom = new ClientCustomForm();
                $client_custom = [];
                $client_custom['client_id']=$client_id;
                $client_custom['custom_field_id']=$key;
                /**
                 * @var string $value
                 */
                $client_custom['value']=$value; 
                $model = ($ccR->repoClientCustomCount($client_id, $key) == 1 ? $ccR->repoFormValuequery($client_id, $key) : new ClientCustom());
                if (null!==$model && $ajax_custom->load($client_custom) && $validator->validate($ajax_custom)->isValid()) {
                    $this->clientCustomService->saveClientCustom($model, $ajax_custom);                
                }
            }
            $parameters = [
                'success'=>1,                
            ];
            return $this->factory->createResponse(Json::encode($parameters)); 
        } else {
            $parameters = [
                'success'=>0,
            ];           
            return $this->factory->createResponse(Json::encode($parameters)); 
        }
    }
    
    /**
     * 
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param cnS $cnS
     * @param cnR $cnR
     * @param sR $sR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function save_client_note_new(Request $request, ValidatorInterface $validator, cnS $cnS, cnR $cnR, sR $sR) : \Yiisoft\DataResponse\DataResponse 
    {
        $datehelper = new DateHelper($sR);
        //receive data ie. note
        $body = $request->getQueryParams();
        /**
         * @var string $body['client_id']
         */
        $client_id = $body['client_id'];
        $date = new \DateTimeImmutable('now');
        /**
         * @var string $body['client_note']
         */
        $note = $body['client_note'];
        $data = [        
            'client_id'=>$client_id,
            'date'=> $date->format($datehelper->style()),
            'note'=>$note,
        ];
        $content = new ClientNoteForm();        
        if ($content->load($data) && $validator->validate($content)->isValid()) {    
            $cnS->addClientNote(new ClientNote(), $content, $sR);
            $parameters = [
                'success' => 1,
            ];
        } else {
            $parameters = [
                'success' => 0,
                'validation_errors' => $content->getFormErrors()
            ];
        }        
        return $this->factory->createResponse(Json::encode($parameters));          
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param cR $cR
     * @param cfR $cfR
     * @param cnR $cnR
     * @param cvR $cvR
     * @param ccR $ccR
     * @param gR $gR
     * @param iR $iR
     * @param iaR $iaR
     * @param irR $irR
     * @param qR $qR
     * @param pymtR $pymtR
     * @param qaR $qaR
     * @param sR $sR
     * @return Response
     */    
    public function view(SessionInterface $session, CurrentRoute $currentRoute, cR $cR, cfR $cfR, cnR $cnR, cvR $cvR, ccR $ccR, gR $gR, iR $iR, iaR $iaR, irR $irR, qR $qR, pymtR $pymtR, qaR $qaR, sR $sR   
    ): Response {
      $client = $this->client($currentRoute, $cR);      
      if ($client instanceof Client) {
            $client_id = $client->getClient_id();  
            if (null!==$client_id) {
              $parameters = [
                  'title' => $sR->trans('client'),
                  'alert' => $this->alert($session),
                  'iR' => $iR,
                  'iaR' => $iaR,
                  'clienthelper' => new ClientHelper($sR),
                  'custom_fields'=>$cfR->repoTablequery('client_custom'),
                  'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('client_custom')),
                  'client_custom_values'=>$this->client_custom_values((string)$client_id, $ccR),
                  'client' => $client,            
                  'client_notes' => $cnR->repoClientNoteCount($client_id) > 0 ? $cnR->repoClientquery((string)$client_id) : [],
                  'cvH' => new CVH($sR),
                  'partial_client_address'=>$this->viewRenderer->renderPartialAsString('/invoice/client/partial_client_address', [
                      'client'=> $client,            
                      'countryhelper'=> new CountryHelper(),
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
                  'quote_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                      'qaR'=> $qaR,
                      'quote_count' => $qR->repoCountByClient($client_id),
                      'quotes' => $qR->repoClient($client_id),
                      'clienthelper' => new ClientHelper($sR),
                      'datehelper' => new DateHelper($sR),
                      'quote_statuses' => $qR->getStatuses($sR),
                  ]),
                  'quote_draft_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                      'qaR'=> $qaR,
                      'quote_count' => $qR->by_client_quote_status_count($client_id,1),
                      'quotes' => $qR->by_client_quote_status($client_id,1),
                      'clienthelper' => new ClientHelper($sR),
                      'datehelper' => new DateHelper($sR),
                      'quote_statuses' => $qR->getStatuses($sR),
                  ]),
                  'quote_sent_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                      'qaR'=> $qaR,
                      'quote_count' => $qR->by_client_quote_status_count($client_id,2),
                      'quotes' => $qR->by_client_quote_status($client_id,2),
                      'clienthelper' => new ClientHelper($sR),
                      'datehelper' => new DateHelper($sR),
                      'quote_statuses' => $qR->getStatuses($sR),
                  ]),
                  'quote_viewed_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                      'qaR'=> $qaR,
                      'quote_count' => $qR->by_client_quote_status_count($client_id,3),
                      'quotes' => $qR->by_client_quote_status($client_id,3),
                      'clienthelper' => new ClientHelper($sR),
                      'datehelper' => new DateHelper($sR),
                      'quote_statuses' => $qR->getStatuses($sR),
                  ]),
                  'quote_approved_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                      'qaR'=> $qaR,
                      'quote_count' => $qR->by_client_quote_status_count($client_id,4),
                      'quotes' => $qR->by_client_quote_status($client_id,4),
                      'clienthelper' => new ClientHelper($sR),
                      'datehelper' => new DateHelper($sR),
                      'quote_statuses' => $qR->getStatuses($sR),
                  ]),
                  'quote_rejected_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                      'qaR'=> $qaR,
                      'quote_count' => $qR->by_client_quote_status_count($client_id,5),
                      'quotes' => $qR->by_client_quote_status($client_id,5),
                      'clienthelper' => new ClientHelper($sR),
                      'datehelper' => new DateHelper($sR),
                      'quote_statuses' => $qR->getStatuses($sR),
                  ]),
                  'quote_cancelled_table'=>$this->viewRenderer->renderPartialAsString('/invoice/quote/partial_quote_table', [
                      'qaR'=> $qaR,
                      'quote_count' => $qR->by_client_quote_status_count($client_id,6),
                      'quotes' => $qR->by_client_quote_status($client_id,6),
                      'clienthelper' => new ClientHelper($sR),
                      'datehelper' => new DateHelper($sR),
                      'quote_statuses' => $qR->getStatuses($sR),
                  ]),
                  'invoice_table'=>$this->viewRenderer->renderPartialAsString('/invoice/inv/partial_inv_table', [
                      'iaR'=> $iaR,
                      'irR'=> $irR,
                      'invoice_count'=>$iR->repoCountByClient($client_id),
                      'invoices' => $iR->repoClient($client_id),
                      'clienthelper' => new ClientHelper($sR),
                      'datehelper' => new DateHelper($sR),
                      'inv_statuses' => $iR->getStatuses($sR)
                  ]),
                  'invoice_draft_table'=>$this->viewRenderer->renderPartialAsString('/invoice/inv/partial_inv_table', [
                      'iaR'=> $iaR,
                      'irR'=> $irR,
                      'invoice_count' => $iR->by_client_inv_status_count($client_id,1),    
                      'invoices' => $iR->by_client_inv_status($client_id,1),
                      'clienthelper' => new ClientHelper($sR),
                      'datehelper' => new DateHelper($sR),
                      'inv_statuses' => $iR->getStatuses($sR)
                  ]),
                  'invoice_sent_table'=>$this->viewRenderer->renderPartialAsString('/invoice/inv/partial_inv_table', [
                      'iaR'=> $iaR,
                      'irR'=> $irR,
                      'invoice_count' => $iR->by_client_inv_status_count($client_id,2),    
                      'invoices' => $iR->by_client_inv_status($client_id,2),
                      'clienthelper' => new ClientHelper($sR),
                      'datehelper' => new DateHelper($sR),
                      'inv_statuses' => $iR->getStatuses($sR)
                  ]),
                  'invoice_viewed_table'=>$this->viewRenderer->renderPartialAsString('/invoice/inv/partial_inv_table', [
                      'iaR'=> $iaR,
                      'irR'=> $irR,
                      'invoice_count' => $iR->by_client_inv_status_count($client_id,3),    
                      'invoices' => $iR->by_client_inv_status($client_id,3),
                      'clienthelper' => new ClientHelper($sR),
                      'datehelper' => new DateHelper($sR),
                      'inv_statuses' => $iR->getStatuses($sR)
                  ]),
                  'invoice_paid_table'=>$this->viewRenderer->renderPartialAsString('/invoice/inv/partial_inv_table', [
                      'iaR'=> $iaR,
                      'irR'=> $irR,
                      'invoice_count' => $iR->by_client_inv_status_count($client_id,4),    
                      'invoices' => $iR->by_client_inv_status($client_id,4),
                      'clienthelper' => new ClientHelper($sR),
                      'datehelper' => new DateHelper($sR),
                      'inv_statuses' => $iR->getStatuses($sR)
                  ]),
                  'partial_notes'=>$this->viewRenderer->renderPartialAsString('/invoice/clientnote/partial_notes', [
                      'client_notes' => $cnR->repoClientquery((string)$client_id),
                      'datehelper' => new DateHelper($sR),
                  ]),
                  'payment_table'=>$this->viewRenderer->renderPartialAsString('/invoice/payment/partial_payment_table', [
                      'client'=> $client,
                      // All payments from the client are loaded and filtered in the view with 
                      // if ($payment->getInv()->getClient_id() === $client->getClient_id())
                      'payments'=> $pymtR->repoPaymentInvLoadedAll((int)$sR->get_setting('payment_list_limit') ?: 10),
                      'clienthelper' => new ClientHelper($sR),
                  ]),    
              ];
              return $this->viewRenderer->render('view', $parameters);
         } else {
              return $this->webService->getRedirectResponse('client/index');
         } 
      } // if $client     
      return $this->webService->getRedirectResponse('client/index');
}
}
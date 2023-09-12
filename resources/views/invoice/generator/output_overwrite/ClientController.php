<?php

declare(strict_types=1); 

namespace App\Invoice\Client;

use App\Invoice\Entity\Client;
use App\Invoice\Client\ClientService;
use App\Invoice\Client\ClientRepository;

use App\Invoice\Setting\SettingRepository;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

use \Exception;

final class ClientController
{
    private SessionInterface $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private ClientService $clientService;
        private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        ClientService $clientService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/client')
                                           // The Controller layout dir is now redundant: replaced with an alias 
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->clientService = $clientService;
        $this->translator = $translator;
    }
    
    
    
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $settingRepository,                        

    ) : Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['client/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
            
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new ClientForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->clientService->saveClient(new Client(),$form);
                return $this->webService->getRedirectResponse('client/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
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
     * @param Client $client     * @return array
     */
    private function body(Client $client) : array {
        $body = [
                
          'client_date_created'=>$client->getClient_date_created(),
          'client_date_modified'=>$client->getClient_date_modified(),
          'id'=>$client->getId(),
          'postaladdress_id'=>$client->getPostaladdress_id(),
          'client_name'=>$client->getClient_name(),
          'client_surname'=>$client->getClient_surname(),
          'client_address_1'=>$client->getClient_address_1(),
          'client_address_2'=>$client->getClient_address_2(),
          'client_building_number'=>$client->getClient_building_number(),
          'client_city'=>$client->getClient_city(),
          'client_state'=>$client->getClient_state(),
          'client_zip'=>$client->getClient_zip(),
          'client_country'=>$client->getClient_country(),
          'client_phone'=>$client->getClient_phone(),
          'client_fax'=>$client->getClient_fax(),
          'client_mobile'=>$client->getClient_mobile(),
          'client_email'=>$client->getClient_email(),
          'client_web'=>$client->getClient_web(),
          'client_vat_id'=>$client->getClient_vat_id(),
          'client_tax_code'=>$client->getClient_tax_code(),
          'client_language'=>$client->getClient_language(),
          'client_active'=>$client->getClient_active(),
          'client_number'=>$client->getClient_number(),
          'client_avs'=>$client->getClient_avs(),
          'client_insurednumber'=>$client->getClient_insurednumber(),
          'client_veka'=>$client->getClient_veka(),
          'client_birthdate'=>$client->getClient_birthdate(),
          'client_gender'=>$client->getClient_gender()
                ];
        return $body;
    }
        
    public function index(CurrentRoute $currentRoute, ClientRepository $clientRepository, SettingRepository $settingRepository): Response
    {      
      $page = (int) $currentRoute->getArgument('page', '1');
      $client = $clientRepository->findAllPreloaded();
      $paginator = (new OffsetPaginator($client))
      ->withPageSize((int) $settingRepository->get_setting('default_list_limit'))
      ->withCurrentPage($page)
      ->withNextPageToken((string) $page);
      $parameters = [
      'clients' => $this->clients($clientRepository),
      'paginator' => $paginator,
      'alerts' => $this->alert(),
      'max' => (int) $settingRepository->get_setting('default_list_limit'),
      'grid_summary' => $settingRepository->grid_summary($paginator, $this->translator, (int) $settingRepository->get_setting('default_list_limit'), $this->translator->translate('plural'), ''),
    ];
    return $this->viewRenderer->render('/invoice/client/index', $parameters);
    }
    
    
    
    /**
     * 
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function delete(SettingRepository $settingRepository, CurrentRoute $currentRoute,ClientRepository $clientRepository 
    ): Response {
        try {
            $client = $this->client($currentRoute, $clientRepository);
            if ($client) {
                $this->clientService->deleteClient($client);               
                $this->flash('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->webService->getRedirectResponse('client/index'); 
            }
            return $this->webService->getRedirectResponse('client/index'); 
	} catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('client/index'); 
        }
    }
        
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                        ValidatorInterface $validator,
                        ClientRepository $clientRepository, 
                        SettingRepository $settingRepository,                        

    ): Response {
        $client = $this->client($currentRoute, $clientRepository);
        if ($client){
            $parameters = [
                'title' => $this->translator->translate('invoice.edit'),
                'action' => ['client/edit', ['id' => $client->getId()]],
                'errors' => [],
                'body' => $this->body($client),
                'head'=>$head,
                's'=>$settingRepository,
                
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new ClientForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->clientService->saveClient($client,$form);
                    return $this->webService->getRedirectResponse('client/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('client/index');
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
    
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ClientRepository $clientRepository
     * @return Client|null
     */
    private function client(CurrentRoute $currentRoute,ClientRepository $clientRepository) : Client|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $client = $clientRepository->repoClientquery($id);
            return $client;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function clients(ClientRepository $clientRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $clients = $clientRepository->findAllPreloaded();        
        return $clients;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param ClientRepository $clientRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,ClientRepository $clientRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $client = $this->client($currentRoute, $clientRepository); 
        if ($client) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['client/view', ['id' => $client->getId()]],
                'errors' => [],
                'body' => $this->body($client),
                'client'=>$client,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('client/index');
    }
}


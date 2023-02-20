<?php
declare(strict_types=1); 

namespace App\Invoice\UserClient;

use App\Invoice\Entity\UserClient;
use App\Invoice\Entity\UserInv;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientService;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserClient\UserClientForm;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

final class UserClientController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private UserClientService $userclientService;
    private DataResponseFactoryInterface $factory;
    private TranslatorInterface $translator;
        
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        UserClientService $userclientService,
        DataResponseFactoryInterface $factory,
        TranslatorInterface $translator,
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/userclient')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->userclientService = $userclientService;
        $this->factory = $factory;
        $this->translator = $translator;
    }
    
    /**
     * @param SessionInterface $session
     * @param UserClientRepository $userclientRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param UserClientService $service
     */
    public function index(SessionInterface $session, UserClientRepository $userclientRepository, SettingRepository $settingRepository, Request $request, UserClientService $service): \Yiisoft\DataResponse\DataResponse
    {      
        $canEdit = $this->rbac($session);
        $flash = $this->flash($session, '' , '');
        $parameters = [
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'userclients' => $this->userclients($userclientRepository),
          'flash'=> $flash,
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $settingRepository,                        

    ) : Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['userclient/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,            
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new UserClientForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->userclientService->saveUserClient(new UserClient(),$form);
                return $this->webService->getRedirectResponse('userclient/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param SettingRepository $sR
     * @param UserClientRepository $userclientRepository
     * @param UIR $uiR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function delete(CurrentRoute $currentRoute,
                           SettingRepository $sR, UserClientRepository $userclientRepository, UIR $uiR
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $user_client = $this->userclient($currentRoute, $userclientRepository);
        if (null!==$user_client) {
            $user_id = $user_client->getUser_Id();
            if (null!==$user_id) {
                $this->userclientService->deleteUserClient($user_client);               
                $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                if (null!==$user_inv) {
                    return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/userclient_successful',
                    ['heading'=>$sR->trans('client'),'message'=>$sR->trans('record_successfully_deleted'),'url'=>'userinv/client','id'=>$user_inv->getId()]));  
                }
            }
            return $this->webService->getRedirectResponse('userclient/index');
        }
        return $this->webService->getRedirectResponse('userclient/index');
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param UserClientRepository $userclientRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                        ValidatorInterface $validator,
                        UserClientRepository $userclientRepository, 
                        SettingRepository $settingRepository,                        

    ): Response {
    $user_client = $this->userclient($currentRoute, $userclientRepository);
    if ($user_client) {    
            $parameters = [
                'title' => 'Edit',
                'action' => ['userclient/edit', ['id' => $user_client->getId()]],
                'errors' => [],
                'body' => $this->body($user_client),
                'head'=>$head,
                's'=>$settingRepository,
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new UserClientForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->userclientService->saveUserClient($user_client, $form);
                    return $this->webService->getRedirectResponse('userclient/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('userclient/index');     
    }
    
    // The preceding url is userinv/client/{userinv_id} showing the currently assigned clients to this user
    
    // Retrieves userclient/new.php which offers an 'all client option' and an individual client option
    
    /**
     * 
     * @param SessionInterface $session
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param ViewRenderer $head
     * @param CurrentRoute $currentRoute
     * @param ClientRepository $cR
     * @param SettingRepository $sR
     * @param UserClientRepository $ucR
     * @param UserClientService $ucS
     * @param UIR $uiR
     * @return Response
     */
    public function new(SessionInterface $session, Request $request, ValidatorInterface $validator, ViewRenderer $head, CurrentRoute $currentRoute, 
                        ClientRepository $cR, SettingRepository $sR, UserClientRepository $ucR, UserClientService $ucS, UIR $uiR): Response {
        
        $user_id = $currentRoute->getArgument('user_id');
        if (null!==$user_id) {
            // Get possible client ids as an array that can be presented to this user
            $available_client_id_list = $ucR->get_not_assigned_to_user($user_id, $cR) ;
            $parameters = [
                'head'=>$head,
                's'=>$sR,
                'userinv'=>$this->user($currentRoute, $uiR),
                // Only provide clients NOT already included ie. available
                'clients'=>!empty($available_client_id_list) ? $cR->repoUserClient($available_client_id_list) : [],
                'flash'=>$this->flash($session,'',''),
                // Initialize the checkbox to zero so that both 'all_clients' and dropdownbox is presented on userclient/new.php
                'user_all_clients'=>'0',            
                'body'=>$request->getParsedBody()
            ];

            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if (is_array($body)) {
                    foreach ($body as $key => $value) {
                        // If the user is allowed to see all clients eg. An Accountant
                        if (((string)$key === 'user_all_clients') && ((string)$value === '1')) {
                            // Unassign currently assigned clients
                            $ucR->unassign_to_user_client($user_id);
                            // Search for all clients, including new clients and assign them aswell
                            $ucR->reset_users_all_clients($uiR, $cR, $ucS, $validator);
                            return $this->webService->getRedirectResponse('userinv/index');
                        }
                        if ((((string)$key === 'client_id'))){
                            $form_array = [
                                'user_id'=>$user_id,    
                                'client_id'=>$value
                            ];
                            $form = new UserClientForm();
                            if ($form->load($form_array) && $validator->validate($form)->isValid()
                                // Check that the user client does not exist    
                                                         && !$ucR->repoUserClientqueryCount($user_id,(string)$value) > 0){
                                $this->userclientService->saveUserClient(new UserClient(),$form);
                                $this->flash($session, 'info' , $sR->trans('record_successfully_updated'));
                                return $this->webService->getRedirectResponse('userinv/index');
                            }
                            if ($ucR->repoUserClientqueryCount($user_id,(string)$value) > 0) {
                                $this->flash($session, 'info' , $sR->trans('client_already_exists'));
                                return $this->webService->getRedirectResponse('userinv/index');
                            }
                        }
                    }
                }    
            }        
            return $this->viewRenderer->render('new', $parameters);
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UserClientRepository $userclientRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute, UserClientRepository $userclientRepository,
                         SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $user_client = $this->userclient($currentRoute, $userclientRepository);
        if ($user_client) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['userclient/view', ['id' => $user_client->getId()]],
                'errors' => [],
                'body' => $this->body($user_client),
                's'=>$settingRepository,             
                'userclient'=>$userclientRepository->repoUserClientquery($user_client->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('userclient/index');
    }   
        
    /**
     * @return Response|true
     */
    private function rbac(SessionInterface $session): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('userclient/index');
        }
        return $canEdit;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param UIR $uiR
     * @return object|null
     */
    private function user(CurrentRoute $currentRoute, UIR $uiR): object|null 
    {
        $user_id = $currentRoute->getArgument('user_id');       
        if (null!==$user_id) {
            $user = $uiR->repoUserInvUserIdquery($user_id);
            return $user;
        }
        return null;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UserClientRepository $userclientRepository
     * @return object|null
     */
    private function userclient(CurrentRoute $currentRoute,UserClientRepository $userclientRepository): object|null 
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $userclient = $userclientRepository->repoUserClientquery($id);
            return $userclient;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function userclients(UserClientRepository $userclientRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $userclients = $userclientRepository->findAllPreloaded();        
        return $userclients;
    }
    
    /**
     * 
     * @param object $userclient
     * @return array
     */
    private function body(object $userclient): array {
        $body = [
          'id'=>$userclient->getId(),
          'user_id'=>$userclient->getUser_id(),
          'client_id'=>$userclient->getClient_id()
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


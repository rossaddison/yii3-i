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
     * 
     * @param SessionInterface $session
     * @param UserClientRepository $userclientRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param UserClientService $service
     * @return Response
     */
    public function index(SessionInterface $session, UserClientRepository $userclientRepository, SettingRepository $settingRepository, Request $request, UserClientService $service): Response
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
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute,
                           SettingRepository $sR, UserClientRepository $userclientRepository, UIR $uiR
    ): Response {
        
            $user_id = ($this->userclient($currentRoute, $userclientRepository))->getUser_Id();
            $this->userclientService->deleteUserClient($this->userclient($currentRoute, $userclientRepository));               
            $user_inv = $uiR->repoUserInvUserIdquery($user_id);
            return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/userclient_successful',
            ['heading'=>$sR->trans('client'),'message'=>$sR->trans('record_successfully_deleted'),'url'=>'userinv/client','id'=>$user_inv->getId()]));  
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
        $parameters = [
            'title' => 'Edit',
            'action' => ['userclient/edit', ['id' => $this->userclient($currentRoute, $userclientRepository)->getId()]],
            'errors' => [],
            'body' => $this->body($this->userclient($currentRoute, $userclientRepository)),
            'head'=>$head,
            's'=>$settingRepository,
            
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new UserClientForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->userclientService->saveUserClient($this->userclient($currentRoute,$userclientRepository), $form);
                return $this->webService->getRedirectResponse('userclient/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
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
        // Get possible client ids as an array that can be presented to this user
        $available_client_id_list = $ucR->get_not_assigned_to_user((string)$user_id, $cR) ;
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
                                                 && !$ucR->repoUserClientqueryCount((string)$user_id,(string)$value) > 0){
                        $this->userclientService->saveUserClient(new UserClient(),$form);
                        $this->flash($session, 'info' , $sR->trans('record_successfully_updated'));
                        return $this->webService->getRedirectResponse('userinv/index');
                    }
                    if ($ucR->repoUserClientqueryCount((string)$user_id,(string)$value) > 0) {
                        $this->flash($session, 'info' , $sR->trans('client_already_exists'));
                        return $this->webService->getRedirectResponse('userinv/index');
                    }
                }
            }
        }        
        return $this->viewRenderer->render('new', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param UserClientRepository $userclientRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, UserClientRepository $userclientRepository,
                         SettingRepository $settingRepository,
        ): Response {
        $parameters = [
            'title' => $settingRepository->trans('view'),
            'action' => ['userclient/view', ['id' => $this->userclient($currentRoute, $userclientRepository)->getId()]],
            'errors' => [],
            'body' => $this->body($this->userclient($currentRoute, $userclientRepository)),
            's'=>$settingRepository,             
            'userclient'=>$userclientRepository->repoUserClientquery($this->userclient($currentRoute, $userclientRepository)->getId()),
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
            return $this->webService->getRedirectResponse('userclient/index');
        }
        return $canEdit;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param UIR $uiR
     * @return UserInv|null
     */
    private function user(CurrentRoute $currentRoute, UIR $uiR): UserInv|null 
    {
        $user_id = $currentRoute->getArgument('user_id');       
        $user = $uiR->repoUserInvUserIdquery($user_id);
        return $user;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UserClientRepository $userclientRepository
     * @return UserClient|null
     */
    private function userclient(CurrentRoute $currentRoute,UserClientRepository $userclientRepository): UserClient|null 
    {
        $id = $currentRoute->getArgument('id');       
        $userclient = $userclientRepository->repoUserClientquery((string)$id);
        return $userclient;
    }
    
    /**
     * @return Response|\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return Response|\Yiisoft\Data\Reader\DataReaderInterface<int, UserClient>
     */
    private function userclients(UserClientRepository $userclientRepository): \Yiisoft\Data\Reader\DataReaderInterface|Response 
    {
        $userclients = $userclientRepository->findAllPreloaded();        
        if ($userclients === null) {
            return $this->webService->getNotFoundResponse();
        }
        return $userclients;
    }
    
    /**
     * @return string[]
     *
     * @psalm-return array{id: string, user_id: string, client_id: string}
     */
    private function body(UserClient $userclient): array {
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


<?php
declare(strict_types=1); 

namespace App\Invoice\UserInv;

use App\Invoice\Entity\UserInv;
use App\Invoice\Entity\UserClient;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\UserInv\UserInvService;
use App\Invoice\Client\ClientRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\Setting\SettingRepository;
use App\User\UserRepository as uR;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Aliases\Aliases;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

final class UserInvController
{
    private DataResponseFactoryInterface $factory;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private UserInvService $userinvService;
    private TranslatorInterface $translator;
    private SessionInterface $session;
        
    public function __construct(
        DataResponseFactoryInterface $factory,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        UserInvService $userinvService,
        TranslatorInterface $translator,
        SessionInterface $session,    
    )    
    {
        $this->factory = $factory;
        $this->viewRenderer = $viewRenderer; 
        $this->webService = $webService;
        $this->userService = $userService;
        $this->userinvService = $userinvService;
        $this->viewRenderer = $viewRenderer;
        if ($this->userService->hasPermission('editUserInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/userinv')
                                                 ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/userinv')
                                                 ->withLayout('@views/layout/invoice.php');
        }
        $this->translator = $translator;
        $this->session = $session;
    }

    // UserInv  is the extension Table of User
    // Users that have been signed up through the demo must be added
    // to the invoicing system 
    // using Setting...User Account
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param SessionInterface $session
     * @param UserInvRepository $uiR
     * @param SettingRepository $sR
     * @param TranslatorInterface $translator
     */
    public function index(Request $request, CurrentRoute $currentRoute, SessionInterface $session,
                          UserInvRepository $uiR, SettingRepository $sR, TranslatorInterface $translator): \Yiisoft\DataResponse\DataResponse
    {      
        $canEdit = $this->rbac();
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page', '1');        
        $active = (int)$currentRoute->getArgument('active', '2');         
        /** @var string $query_params['sort'] */
        $sort = Sort::only(['user_id', 'name', 'email'])          
                     ->withOrderString($query_params['sort'] ?? '-user_id');
        $repo = $this->userinvs_active_with_sort($uiR,$active,$sort); 
        /**
         * @psalm-suppress PossiblyInvalidArgument
         */
        $paginator = (new OffsetPaginator($repo))        
        ->withPageSize((int)$sR->get_setting('default_list_limit'))
        ->withCurrentPage($page)               
        ->withNextPageToken((string) $page);   
        $parameters = [
          'uiR'=>$uiR,
          'active'=>$active,   
          'paginator'=>$paginator,
          'translator'=>$translator,
          's'=>$sR,
          'canEdit' => $canEdit,
          'grid_summary'=> $sR->grid_summary($paginator, $this->translator, (int)$sR->get_setting('default_list_limit'), $this->translator->translate('invoice.payments'), ''),
          'userinvs' => $repo,
          'locale'=>$session->get('_language'),
          'alert'=>$this->alert(),
          // Parameters for GridView->requestArguments
          'page'=> $page,
          'sortOrder' => $query_params['sort'] ?? '',
        ];
        return $this->viewRenderer->render('index', $parameters);        
    }
    
    public function guest(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        UserInvRepository $userinvRepository, 
                        SettingRepository $settingRepository,
                        uR $uR,

    ): Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR. 'Language']);
        if (null!==$this->userService->getUser()){
            $id = $this->userService->getUser()?->getId();
            if (null!==$id) {
                $user_inv = $userinvRepository->repoUserInvUserIdquery($id);
                if ($user_inv) {
                    $parameters = [
                        'title' => $settingRepository->trans('edit'),
                        'action' => ['userinv/guest'],
                        'errors' => [],
                        'body' => $this->body($user_inv),
                        'head'=>$head,
                        'aliases'=>$aliases,
                        'users'=>$uR->findAllUsers(),
                        's'=>$settingRepository,

                    ];
                    if ($request->getMethod() === Method::POST) {
                        $form = new UserInvForm();
                        $body = $request->getParsedBody();
                        if ($form->load($body) && $validator->validate($form)->isValid()) {
                            $this->userinvService->saveUserInv($user_inv, $form);
                            return $this->webService->getRedirectResponse('invoice/index');
                        }
                        $parameters['body'] = $body;
                        $parameters['errors'] = $form->getFormErrors();
                    }
                    return $this->viewRenderer->render('_form_guest', $parameters);
                }
                return $this->webService->getRedirectResponse('invoice/index');
            } // nul!== $id
            return $this->webService->getNotFoundResponse();
        } // null!==$this->userService->getUser()
        return $this->webService->getNotFoundResponse();
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
     * @param ViewRenderer $head
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param SettingRepository $sR
     * @param uR $uR
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $sR,
                        uR $uR, 
    ) : Response
    {        
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR. 'Language']);
        $countries = new CountryHelper();
        $parameters = [
            'title' => $sR->trans('add'),
            'action' => ['userinv/add'],
            'aliases'=>$aliases,
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$sR,
            'head'=>$head,            
            'users'=>$uR->findAllUsers(),
            'selected_country' => $sR->get_setting('default_country'),            
            'selected_language' => $sR->get_setting('default_language'),
            'countries'=> $countries->get_country_list($sR->get_setting('cldr'))
        ];
        
        if ($request->getMethod() === Method::POST) {            
            $form = new UserInvForm();
            $userinv = new UserInv();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->userinvService->saveUserInv($userinv,$form);
                return $this->webService->getRedirectResponse('userinv/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param UserInvRepository $userinvRepository
     * @param SettingRepository $settingRepository
     * @param uR $uR
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                        ValidatorInterface $validator,
                        UserInvRepository $userinvRepository, 
                        SettingRepository $settingRepository,
                        uR $uR,

    ): Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => dirname(__DIR__). DIRECTORY_SEPARATOR. 'Language']);
        $user_inv = $this->userinv($currentRoute, $userinvRepository);
        if ($user_inv) {
            $parameters = [
                'title' => 'Edit',
                'action' => ['userinv/edit', ['id' => $user_inv->getId()]],
                'errors' => [],
                'body' => $this->body($user_inv),
                'head'=>$head,
                'aliases'=>$aliases,
                'users'=>$uR->findAllUsers(),
                's'=>$settingRepository,

            ];
            if ($request->getMethod() === Method::POST) {
                $form = new UserInvForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->userinvService->saveUserInv($user_inv, $form);
                    return $this->webService->getRedirectResponse('userinv/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('userinv/index');         
    }
    
    /**
     * @param ViewRenderer $head
     * @param CurrentRoute $currentRoute
     * @param ClientRepository $cR
     * @param SettingRepository $sR
     * @param UserClientRepository $ucR
     * @param UserInvRepository $uiR
     */
    public function client(ViewRenderer $head, CurrentRoute $currentRoute, ClientRepository $cR,
                           SettingRepository $sR, UserClientRepository $ucR, UserInvRepository $uiR) 
        : \Yiisoft\DataResponse\DataResponse|Response {
        // Use the primary key 'id' passed in userinv/index's urlGenerator to retrieve the user_id
        $user_inv = $this->userinv($currentRoute, $uiR);
        if (null!==$user_inv) {
            $user_id = $user_inv->getUser_Id();
            if ($user_id) {
                $parameters = [
                    'head'=>$head,
                    's'=>$sR,
                    'cR'=>$cR,
                    'flash'=> $this->flash('', ''),
                    // Get all clients that this user will deal with
                    'user_clients'=>$ucR->repoClientquery($user_id),
                    'userinv'=>$uiR->repoUserInvUserIdquery($user_id),
                    'user_id'=>$user_id,
                ];
                return $this->viewRenderer->render('field', $parameters);
            }
            return $this->webService->getRedirectResponse('userinv/index');
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }
    
    /**
     * 
     * @param TranslatorInterface $translator
     * @param CurrentRoute $currentRoute
     * @param UserInvRepository $userinvRepository
     * @return Response
     */
    public function delete(TranslatorInterface $translator, CurrentRoute $currentRoute,UserInvRepository $userinvRepository 
    ): Response {
        $user_inv = $this->userinv($currentRoute,$userinvRepository); 
        if ($user_inv) {
            $this->userinvService->deleteUserInv($user_inv);               
            $this->flash('info', $translator->translate('invoice.deleted'));
            return $this->webService->getRedirectResponse('userinv/index'); 	
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UserInvRepository $userinvRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute,UserInvRepository $userinvRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $user_inv = $this->userinv($currentRoute, $userinvRepository);
        if ($user_inv) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['userinv/view', ['id' => $user_inv->getId()]],
                'errors' => [],
                'body' => $this->body($user_inv),
                's'=>$settingRepository,             
                'userinv'=>$userinvRepository->repoUserInvquery((string)$user_inv->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('userinv/index');
    }
        
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('userinv/index');
        }
        return $canEdit;
    }
    
    /**
     * @param UserInvRepository $uiR
     * @param int $active
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface|\Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface|\Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function userinvs_active_with_sort(UserInvRepository $uiR, int $active, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface|\Yiisoft\Yii\Cycle\Data\Reader\EntityReader {       
        $userinvs = $uiR->findAllWithActive($active)
                        ->withSort($sort);
        return $userinvs;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UserInvRepository $userinvRepository
     * @return UserInv|null
     */
    private function userinv(CurrentRoute $currentRoute, UserInvRepository $userinvRepository): UserInv|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $userinv = $userinvRepository->repoUserInvquery($id);
            return $userinv;
        }
        return null;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param UserClientRepository $userclientRepository
     * @return UserClient|null
     */
    private function userclient(CurrentRoute $currentRoute, UserClientRepository $userclientRepository) : UserClient|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $userclient = $userclientRepository->repoUserClientquery($id);
            if (null!==$userclient) {
                return $userclient;
            }
            return null;
        }
        return null;
    }
    
    /**
     * 
     * @param UserInv $userinv
     * @return array
     */
    private function body(UserInv $userinv): array {
        $body = [
          'id'=>$userinv->getId(),
          'user_id'=>$userinv->getUser_id(),
          'type'=>$userinv->getType(),
          'active'=>$userinv->getActive(),
          'date_created'=>$userinv->getDate_created(),
          'date_modified'=>$userinv->getDate_modified(),
          'language'=>$userinv->getLanguage(),
          'name'=>$userinv->getName(),
          'company'=>$userinv->getCompany(),
          'address_1'=>$userinv->getAddress_1(),
          'address_2'=>$userinv->getAddress_2(),
          'city'=>$userinv->getCity(),
          'state'=>$userinv->getState(),
          'zip'=>$userinv->getZip(),
          'country'=>$userinv->getCountry(),
          'phone'=>$userinv->getPhone(),
          'fax'=>$userinv->getFax(),
          'mobile'=>$userinv->getMobile(),
          'email'=>$userinv->getEmail(),
          'password'=>$userinv->getPassword(),
          'web'=>$userinv->getWeb(),
          'vat_id'=>$userinv->getVat_id(),
          'tax_code'=>$userinv->getTax_code(),
          'all_clients'=>$userinv->getAll_clients(),
          'salt'=>$userinv->getSalt(),
          'passwordreset_token'=>$userinv->getPasswordreset_token(),
          'subscribernumber'=>$userinv->getSubscribernumber(),
          'iban'=>$userinv->getIban(),
          'gln'=>$userinv->getGln(),
          'rcc'=>$userinv->getRcc()
                ];
        return $body;
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
}


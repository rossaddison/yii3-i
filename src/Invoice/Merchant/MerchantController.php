<?php

declare(strict_types=1); 

namespace App\Invoice\Merchant;

use App\Invoice\Entity\Merchant;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Merchant\MerchantService;
use App\Invoice\Merchant\MerchantRepository;
use App\Invoice\Setting\SettingRepository;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

final class MerchantController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private MerchantService $merchantService;
    private TranslatorInterface $translator;
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        MerchantService $merchantService,
        TranslatorInterface $translator
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/merchant')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->merchantService = $merchantService;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param MerchantRepository $merchantRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param MerchantService $service
     * @return Response
     */
    public function index(SessionInterface $session, MerchantRepository $merchantRepository, SettingRepository $settingRepository, Request $request, MerchantService $service): Response
    {
         $canEdit = $this->rbac($session);
         $flash = $this->flash($session, '','');
         $parameters = [      
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'merchants' => $this->merchants($merchantRepository),
          'flash'=> $flash
         ];      
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param SettingRepository $settingRepository
     * @param InvRepository $invRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $settingRepository,                        
                        InvRepository $invRepository
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['merchant/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
            
            'invs'=>$invRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new MerchantForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->merchantService->saveMerchant(new Merchant(),$form);
                return $this->webService->getRedirectResponse('merchant/index');
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
     * @param MerchantRepository $merchantRepository
     * @param SettingRepository $settingRepository
     * @param InvRepository $invRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        ValidatorInterface $validator,
                        MerchantRepository $merchantRepository, 
                        SettingRepository $settingRepository,                        
                        InvRepository $invRepository
    ): Response {
        $parameters = [
            'title' => 'Edit',
            'action' => ['merchant/edit', ['id' => $this->merchant($currentRoute, $merchantRepository)->getId()]],
            'errors' => [],
            'body' => $this->body($this->merchant($currentRoute, $merchantRepository)),
            'head'=>$head,
            's'=>$settingRepository,
            'invs'=>$invRepository->findAllPreloaded()
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new MerchantForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->merchantService->saveMerchant($this->merchant($currentRoute, $merchantRepository), $form);
                return $this->webService->getRedirectResponse('merchant/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchantRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, MerchantRepository $merchantRepository 
    ): Response {
        $this->merchantService->deleteMerchant($this->merchant($currentRoute, $merchantRepository));               
        return $this->webService->getRedirectResponse('merchant/index');        
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchantRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, MerchantRepository $merchantRepository,
        SettingRepository $settingRepository
        ): Response {
        $parameters = [
            'title' => $settingRepository->trans('view'),
            'action' => ['merchant/edit', ['id' => $this->merchant($currentRoute, $merchantRepository)->getId()]],
            'errors' => [],
            'body' => $this->body($this->merchant($currentRoute, $merchantRepository)),
            's'=>$settingRepository,             
            'merchant'=>$merchantRepository->repoMerchantquery($this->merchant($currentRoute, $merchantRepository)->getId()),
        ];
        return $this->viewRenderer->render('_view', $parameters);
    }
    
    /**
     * @param MerchantRepository $mR
     * @param SettingRepository $sR
     * @return Response
     */
    public function online_log(MerchantRepository $mR, SettingRepository $sR): Response {
        $parameters = [
            's'=>$sR,
            'payment_logs'=>$mR->findAllPreloaded(),
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
            return $this->webService->getRedirectResponse('merchant/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchantRepository
     * @return Merchant|null
     */
    private function merchant(CurrentRoute $currentRoute, MerchantRepository $merchantRepository): Merchant|null
    {
        $id = $currentRoute->getArgument('id');       
        $merchant = $merchantRepository->repoMerchantquery($id);
        return $merchant;
    }
    
    /**
     * @return Response|\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return Response|\Yiisoft\Data\Reader\DataReaderInterface<int, Merchant>
     */
    private function merchants(MerchantRepository $merchantRepository): \Yiisoft\Data\Reader\DataReaderInterface|Response 
    {
        $merchants = $merchantRepository->findAllPreloaded();        
        if ($merchants === null) {
            return $this->webService->getNotFoundResponse();
        }
        return $merchants;
    }
    
    /**
     * @return (\DateTimeImmutable|bool|string)[]
     *
     * @psalm-return array{inv_id: string, successful: bool, date: \DateTimeImmutable, driver: string, response: string, reference: string}
     */
    private function body(Merchant $merchant): array {
        $body = [
          'inv_id'=>$merchant->getInv_id(),
          'successful'=>$merchant->getSuccessful(),
          'date'=>$merchant->getDate(),
          'driver'=>$merchant->getDriver(),
          'response'=>$merchant->getResponse(),
          'reference'=>$merchant->getReference()
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
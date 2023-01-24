<?php

declare(strict_types=1); 

namespace App\Invoice\Profile;

use App\Invoice\Company\CompanyRepository;
use App\Invoice\Entity\Profile;
use App\Invoice\Profile\ProfileService;
use App\Invoice\Profile\ProfileRepository;
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

final class ProfileController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private ProfileService $profileService;
    private TranslatorInterface $translator;
        
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        ProfileService $profileService,
        TranslatorInterface $translator
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/profile')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->profileService = $profileService;
        $this->translator = $translator;
    }
    
    /**
     * @param SessionInterface $session
     * @param ProfileRepository $profileRepository
     * @param SettingRepository $settingRepository
     */
    public function index(SessionInterface $session, ProfileRepository $profileRepository, SettingRepository $settingRepository): \Yiisoft\DataResponse\DataResponse
    {      
         $canEdit = $this->rbac($session);
         $flash = $this->flash($session, 'info' , 'Create a profile with a new email address, or mobile number, make it active, '.
                 'and select the company details you wish to link it to. This information will automatically appear on the documentation eg. quotes and invoices.');
         $parameters = [
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'profiles' => $this->profiles($profileRepository),
          'flash'=> $flash
         ];
        
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param SettingRepository $settingRepository
     * @param CompanyRepository $companyRepository
     * @return Response
     */    
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $settingRepository,                        
                        CompanyRepository $companyRepository
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['profile/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,            
            'companies'=>$companyRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new ProfileForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->profileService->saveProfile(new Profile(),$form);
                return $this->webService->getRedirectResponse('profile/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param ProfileRepository $profileRepository
     * @param SettingRepository $settingRepository
     * @param CompanyRepository $companyRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        ValidatorInterface $validator,
                        ProfileRepository $profileRepository, 
                        SettingRepository $settingRepository,                        
                        CompanyRepository $companyRepository
    ): Response {
        $profile = $this->profile($currentRoute, $profileRepository);
        if ($profile) {
        $parameters = [
            'title' => 'Edit',
            'action' => ['profile/edit', ['id' => $profile->getId()]],
            'errors' => [],
            'body' => $this->body($profile),
            'head'=>$head,
            's'=>$settingRepository,
            'companies'=>$companyRepository->findAllPreloaded()
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new ProfileForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->profileService->saveProfile($profile, $form);
                return $this->webService->getRedirectResponse('profile/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    return $this->webService->getRedirectResponse('profile/index');    
}
    
    /**
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param ProfileRepository $profileRepository
     * @return Response
     */
    public function delete(SessionInterface $session, CurrentRoute $currentRoute, ProfileRepository $profileRepository 
    ): Response {
        try {
            $profile = $this->profile($currentRoute, $profileRepository);
            if ($profile) {
                if ($this->profileService->deleteProfile($profile)) {               
                    $this->flash($session, 'info', 'Deleted.');
                } else {
                    $this->flash($session, 'info', 'Profile has not been deleted.');
                }    
            }
            return $this->webService->getRedirectResponse('profile/index');
	} catch (\Exception $e) {
            unset($e);
            $this->flash($session, 'danger', 'Cannot delete. Profile history exists.');
            return $this->webService->getRedirectResponse('profile/index'); 
        }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ProfileRepository $profileRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute, ProfileRepository $profileRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $profile = $this->profile($currentRoute, $profileRepository);
        if ($profile) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['profile/view', ['id' => $profile->getId()]],
                'errors' => [],
                'body' => $this->body($profile),
                's'=>$settingRepository,             
                'profile'=>$profileRepository->repoProfilequery((string)$profile->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('profile/index'); 
    }
        
    /**
     * @return Response|true
     */
    private function rbac(SessionInterface $session): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('profile/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ProfileRepository $profileRepository
     * @return object|null
     */
    private function profile(CurrentRoute $currentRoute, ProfileRepository $profileRepository): object|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $profile = $profileRepository->repoProfilequery($id);
            return $profile;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function profiles(ProfileRepository $profileRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $profiles = $profileRepository->findAllPreloaded();        
        return $profiles;
    }
    
    /**
     * 
     * @param object $profile
     * @return array
     */
    private function body(object $profile): array {
        $body = [                
          'id'=>$profile->getId(),
          'company_id'=>$profile->getCompany_id(),
          'current'=>$profile->getCurrent(),
          'mobile'=>$profile->getMobile(),
          'email'=>$profile->getEmail(),
          'description'=>$profile->getDescription(),
          'date_created'=>$profile->getDate_created(),
          'date_modified'=>$profile->getDate_modified()
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
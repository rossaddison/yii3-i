<?php

declare(strict_types=1); 

namespace App\Invoice\CompanyPrivate;

use App\Invoice\Company\CompanyRepository;
use App\Invoice\CompanyPrivate\CompanyPrivateService;
use App\Invoice\CompanyPrivate\CompanyPrivateRepository;
use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

final class CompanyPrivateController
{
    private SessionInterface $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private CompanyPrivateService $companyprivateService;
    private TranslatorInterface $translator;
        
    public function __construct(
        SessionInterface $session,     
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        CompanyPrivateService $companyprivateService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/companyprivate')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->companyprivateService = $companyprivateService;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param CompanyPrivateRepository $companyprivateRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function index(CompanyPrivateRepository $companyprivateRepository, SettingRepository $settingRepository): Response
    {      
          $canEdit = $this->rbac();
          $parameters = [
            's'=>$settingRepository,
            'canEdit' => $canEdit,
            'companyprivates' => $this->companyprivates($companyprivateRepository),
            'company_private'=>$this->translator->translate('invoice.setting.company.private'),
            'alert'=>$this->alert()
         ];
        
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
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
        $form = new CompanyPrivateForm();
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['companyprivate/add'],
            'errors' => [],
            'form' => $form,
            'body' => $request->getParsedBody(),
            'head'=>$head,
            'companies'=>$companyRepository->findAllPreloaded(),            
            'company_public'=>$this->translator->translate('invoice.company.public'),
        ];
        $aliases = $settingRepository->get_company_private_logos_folder_aliases();
        $targetPath = $aliases->get('@company_private_logos');
        if (!is_writable($targetPath)) { 
            $this->flash('warning', $settingRepository->trans('is_not_writable'));
            return $this->webService->getRedirectResponse('companyprivate/index');
        }   
        if ($request->getMethod() === Method::POST) {
            // Filename of logo in PUBLIC folder
            $tmp = $_FILES['file']['tmp_name'];
            // Replace filename's spaces with underscore
            $modified_original_file_name = Random::string(4).'_'.preg_replace('/\s+/', '_', $_FILES['file']['name']);
            // Build a target file name
            $target_file_name = $targetPath . '/'.$modified_original_file_name;
            $parameters['body']['logo_filename'] = $modified_original_file_name;
            if (!$this->file_uploading_errors($tmp, $target_file_name, $settingRepository)) {
                if ($form->load($parameters['body']) 
                    && $validator->validate($form)->isValid()
                ) {
                    $this->companyprivateService->addCompanyPrivate(new CompanyPrivate(), $form, $settingRepository);
                    $this->flash('info',$settingRepository->trans('record_successfully_created'));
                    return $this->webService->getRedirectResponse('companyprivate/index');
                }
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param string $tmp
     * @param string $target_file_name
     * @param SettingRepository $sR
     * @return bool
     */
    public function file_uploading_errors(string $tmp,
                                          string $target_file_name,
                                          SettingRepository $sR
                                                       ) : bool {
        if  (!is_uploaded_file($tmp)                
                 || file_exists($target_file_name)        
                 || !move_uploaded_file($tmp, $target_file_name)) {
                // For Testing:
                //$isuploaded = !is_uploaded_file($tmp) ? ' not uploaded' : '';
                //$fileexists = file_exists($target_file_name) ? ' the file already exists' : '';
                //$move = !move_uploaded_file($tmp, $target_file_name) ? ' not moved' :  '';
                //$this->flash('info',$sR->trans('errors').$isuploaded.$fileexists.$move);
                return true;
        }
        return false;    
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
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param CompanyPrivateRepository $companyprivateRepository
     * @param SettingRepository $settingRepository
     * @param CompanyRepository $companyRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        ValidatorInterface $validator,
                        CompanyPrivateRepository $companyprivateRepository, 
                        SettingRepository $settingRepository,                        
                        CompanyRepository $companyRepository
    ): Response {
        $form = new CompanyPrivateForm();
        $company_private = $this->companyprivate($currentRoute, $companyprivateRepository);
        $parameters = [
            'title' => $settingRepository->trans('edit'),
            'action' => ['companyprivate/edit', ['id' => $company_private->getId()]],
            'errors' => [],
            'body' => $this->body($this->companyprivate($currentRoute, $companyprivateRepository)),
            'head'=>$head,
            'companies'=>$companyRepository->findAllPreloaded(),
            'company_public'=>$this->translator->translate('invoice.setting.company'),
        ];
        $aliases = $settingRepository->get_company_private_logos_folder_aliases();
        $targetPath = $aliases->get('@company_private_logos');
        if (!is_writable($targetPath)) { 
            $this->flash('warning', $settingRepository->trans('is_not_writable'));
            return $this->webService->getRedirectResponse('companyprivate/index');
        }   
        if ($request->getMethod() === Method::POST) {
            
            $body = $request->getParsedBody();
            if ($form->load($body) 
                && $validator->validate($form)->isValid()
            ) {
                // Replace filename's spaces with underscore and add random string preventing overwrites
                $modified_original_file_name = Random::string(4).'_'.preg_replace('/\s+/', '_', $_FILES['file']['name']);
                // Build a unique target file name
                $target_file_name = $targetPath . '/'. $modified_original_file_name; 
                
                // Save the body excluding the logo_filename field
                $this->companyprivateService->saveCompanyPrivate($company_private, $form, $settingRepository);
                
                // Prepare the after save for the logo_filename field
                $after_save = $companyprivateRepository->repoCompanyPrivatequery((string)$company_private->getId());
                
                // A new file upload must replace the previous one or keep existing file 
                $after_save->setLogo_filename(
                    // 1. tmp is an uploaded file and not a security risk
                    // 2. the target file name does not exist
                    // 3. tmp has been moved into the target destination   
                    !$this->file_uploading_errors($_FILES['file']['tmp_name'], $target_file_name, $settingRepository)
                
                    // New file upload
                    ? $modified_original_file_name 

                    // or Existing database file name        
                    :  $parameters['body']['logo_filename']
                );                
                $companyprivateRepository->save($after_save);
                
                $this->flash('info',$settingRepository->trans('record_successfully_updated'));
                return $this->webService->getRedirectResponse('companyprivate/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param CompanyPrivateRepository $companyprivateRepository
     * @param SettingRepository $sR
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute,
                           CompanyPrivateRepository $companyprivateRepository,
                           SettingRepository $sR ): Response 
    {
        $this->companyprivateService->deleteCompanyPrivate($this->companyprivate($currentRoute, $companyprivateRepository)); 
        $this->flash('info', $sR->trans('record_successfully_deleted'));
        return $this->webService->getRedirectResponse('companyprivate/index'); 
    }  
    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CompanyPrivateRepository $companyprivateRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, CompanyPrivateRepository $companyprivateRepository,
        SettingRepository $settingRepository,
        ): Response {
        $company_private = $this->companyprivate($currentRoute, $companyprivateRepository);
        $parameters = [
            'title' => $settingRepository->trans('view'),
            'action' => ['companyprivate/view', ['id' => $company_private->getId()]],
            'errors' => [],
            'body' => $this->body($this->companyprivate($currentRoute, $companyprivateRepository)),
            's'=>$settingRepository,             
            'companyprivate'=>$companyprivateRepository->repoCompanyPrivatequery((string)$company_private->getId()),
        ];
        return $this->viewRenderer->render('_view', $parameters);
    }
        
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('companyprivate/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CompanyPrivateRepository $companyprivateRepository
     * @return CompanyPrivate|null
     */
    private function companyprivate(CurrentRoute $currentRoute, CompanyPrivateRepository $companyprivateRepository): CompanyPrivate|null
    {
        $id = $currentRoute->getArgument('id');       
        $companyprivate = $companyprivateRepository->repoCompanyPrivatequery($id);
        return $companyprivate;
    }
    
    /**
     * @return \Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\DataReaderInterface<int, CompanyPrivate>
     */
    private function companyprivates(CompanyPrivateRepository $companyprivateRepository): \Yiisoft\Data\Reader\DataReaderInterface 
    {
        $companyprivates = $companyprivateRepository->findAllPreloaded();        
        return $companyprivates;
    }
    
    /**
     * @return (int|string)[]
     *
     * @psalm-return array{id: string, company_id: string, vat_id: string, tax_code: string, iban: string, gln: int, rcc: string, logo_filename: string, start_date: \DateTimeImmutable|null, end_date: \DateTimeImmutable|null}
     */
    private function body(CompanyPrivate $companyprivate): array {
        $body = [                
                    'id'=>$companyprivate->getId(),
                    'company_id'=>$companyprivate->getCompany_id(),
                    'vat_id'=>$companyprivate->getVat_id(),
                    'tax_code'=>$companyprivate->getTax_code(),
                    'iban'=>$companyprivate->getIban(),
                    'gln'=>$companyprivate->getGln(),
                    'rcc'=>$companyprivate->getRcc(),
                    'logo_filename'=>$companyprivate->getLogo_filename(),
                    'start_date'=>$companyprivate->getStart_date(),
                    'end_date'=>$companyprivate->getEnd_date(),
        ];
        return $body;
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
}


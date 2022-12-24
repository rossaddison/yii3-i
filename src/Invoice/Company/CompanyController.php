<?php

declare(strict_types=1); 

namespace App\Invoice\Company;

use App\Invoice\Company\CompanyService;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\Entity\Company;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

final class CompanyController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private CompanyService $companyService;
    private TranslatorInterface $translator;
        
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        CompanyService $companyService,
        TranslatorInterface $translator
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/company')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->companyService = $companyService;
        $this->translator = $translator;
    }
    
    public function index(SessionInterface $session, CompanyRepository $companyRepository, SettingRepository $settingRepository, Request $request, CompanyService $service): Response
    {      
         $canEdit = $this->rbac($session);
         $flash = $this->flash($session, '','');
         $parameters = [
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'companies' => $this->companies($companyRepository),
          'company_public'=>$this->translator->translate('invoice.company.public'),   
          'flash'=> $flash
         ];
        return $this->viewRenderer->render('index', $parameters);
    }
    
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $settingRepository,                   
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['company/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'company_public'=>$this->translator->translate('invoice.company.public'),
            'head'=>$head,
            
        ];
        
        if ($request->getMethod() === Method::POST) {
            $form = new CompanyForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->companyService->saveCompany(new Company(),$form);
                return $this->webService->getRedirectResponse('company/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    public function edit(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        CompanyRepository $companyRepository, 
                        SettingRepository $settingRepository,
                        CurrentRoute $currentRoute

    ): Response {
        $parameters = [
            'title' => 'Edit',
            'action' => ['company/edit', ['id' => $this->company($currentRoute, $companyRepository)->getId()]],
            'errors' => [],
            'body' => $this->body($this->company($currentRoute, $companyRepository)),
            'head'=>$head,
            'company_public'=>$this->translator->translate('invoice.company.public'),
            's'=>$settingRepository,
            
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new CompanyForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->companyService->saveCompany($this->company($currentRoute,$companyRepository), $form);
                return $this->webService->getRedirectResponse('company/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    public function delete(SessionInterface $session,CurrentRoute $currentRoute, CompanyRepository $companyRepository 
    ): Response {
        if ($this->companyService->deleteCompany($this->company($currentRoute, $companyRepository))) {               
            $this->flash($session, 'info', 'Deleted.'); 
            return $this->webService->getRedirectResponse('company/index'); 
        } else {
            $this->flash($session, 'warning', 'Not deleted because you have a profile attached.');
            return $this->webService->getRedirectResponse('company/index');   
        } 
    }
    
    public function view(CurrentRoute $currentRoute, CompanyRepository $companyRepository,
        SettingRepository $settingRepository,
        ): Response {
        $parameters = [
            'title' => $settingRepository->trans('view'),
            'action' => ['company/view', ['id' => $this->company($currentRoute, $companyRepository)->getId()]],
            'errors' => [],
            'body' => $this->body($this->company($currentRoute, $companyRepository)),
            's'=>$settingRepository,             
            'company'=>$companyRepository->repoCompanyquery((string)$this->company($currentRoute, $companyRepository)->getId()),
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
            return $this->webService->getRedirectResponse('company/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CompanyRepository $companyRepository
     * @return Company|null
     */
    private function company(CurrentRoute $currentRoute, CompanyRepository $companyRepository): Company|null 
    {
        $id = $currentRoute->getArgument('id');       
        $company = $companyRepository->repoCompanyquery($id);
        return $company;
    }
    
    /**
     * @return \Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\DataReaderInterface<int, Company>
     */
    private function companies(CompanyRepository $companyRepository): \Yiisoft\Data\Reader\DataReaderInterface 
    {
        $companies = $companyRepository->findAllPreloaded();        
        return $companies;
    }
    
    /**
     * @return (\DateTimeImmutable|int|string)[]
     *
     * @psalm-return array{id: string, current: int, name: string, address_1: string, address_2: string, city: string, state: string, zip: string, country: string, phone: string, fax: string, email: string, web: string, date_created: \DateTimeImmutable, date_modified: \DateTimeImmutable}
     */
    private function body(Company $company): array {
        $body = [
                
          'id'=>$company->getId(),
          'current'=>$company->getCurrent(),
          'name'=>$company->getName(),
          'address_1'=>$company->getAddress_1(),
          'address_2'=>$company->getAddress_2(),
          'city'=>$company->getCity(),
          'state'=>$company->getState(),
          'zip'=>$company->getZip(),
          'country'=>$company->getCountry(),
          'phone'=>$company->getPhone(),
          'fax'=>$company->getFax(),
          'email'=>$company->getEmail(),
          'web'=>$company->getWeb(),
          'date_created'=>$company->getDate_created(),
          'date_modified'=>$company->getDate_modified()
                ];
        return $body;
    }
    
    private function flash(SessionInterface $session, string $level, string $message): Flash{
        $flash = new Flash($session);
        $flash->set($level, $message); 
        return $flash;
    }
}


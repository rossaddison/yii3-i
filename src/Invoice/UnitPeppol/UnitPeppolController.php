<?php

declare(strict_types=1); 

namespace App\Invoice\UnitPeppol;

use App\Invoice\Entity\UnitPeppol;
use App\Invoice\Helpers\Peppol\Peppol_UNECERec20_11e;
use App\Invoice\UnitPeppol\UnitPeppolService;
use App\Invoice\UnitPeppol\UnitPeppolRepository;

use App\Invoice\Setting\SettingRepository;
use App\Invoice\Unit\UnitRepository;
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

final class UnitPeppolController
{
    private SessionInterface $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private UnitPeppolService $unitpeppolService;
        private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        UnitPeppolService $unitpeppolService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/unitpeppol')
                                           // The Controller layout dir is now redundant: replaced with an alias 
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->unitpeppolService = $unitpeppolService;
        $this->translator = $translator;
    }
    
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $settingRepository,                        
                        UnitRepository $unitRepository
    ) : Response
    {
        $enece = new Peppol_UNECERec20_11e();
        /** @var array $enece_array */
        $enece_array = $enece->getUNECERec20_11e();
        $parameters = [
            'title' => $this->translator->translate('invoice.unit.peppol.add'),
            'action' => ['unitpeppol/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            'eneces' => $enece_array,
            's'=>$settingRepository,
            'head'=>$head,
            'units'=>$unitRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            $form = new UnitPeppolForm();
            /** 
             * @var string $parameters['body']['code']
             * @var string $key 
             */
            $key = $parameters['body']['code'];
            /**
             *  @var array $enece_array[$key] 
             *  @var string $enece_array[$key]['Name'] 
             *  @psalm-suppress PossiblyInvalidArrayAssignment $parameters['body']['name'] 
             */ 
            $parameters['body']['name'] = $enece_array[$key]['Name'];
            
            /** 
             * @var string $enece_array[$key]['Description']
             * @var string $parameters['body']['description']
             * @psalm-suppress PossiblyInvalidArrayAssignment $parameters['body']['description']  
             */       
            array_key_exists('Description', $enece_array[$key]) ?
             $parameters['body']['description'] = $enece_array[$key]['Description'] : '';
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->unitpeppolService->saveUnitPeppol(new UnitPeppol(),$form);
                return $this->webService->getRedirectResponse('unitpeppol/index');
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
     * @param UnitPeppol $unitpeppol     
     * @return array
     */
    private function body(UnitPeppol $unitpeppol) : array {
        $body = [
          'id'=>$unitpeppol->getId(),
          'unit_id'=>$unitpeppol->getUnit_id(),
          'code'=>$unitpeppol->getCode(),
          'name'=>$unitpeppol->getName(),
          'description'=>$unitpeppol->getDescription()
        ];
        return $body;
    }
    
    public function index(UnitPeppolRepository $unitpeppolRepository, SettingRepository $settingRepository): Response
    {      
      $flash = $this->flash('' , '');
      $paginator = new OffsetPaginator($this->unitpeppols($unitpeppolRepository));
      $parameters = [
          'alert' => $this->alert(),  
          'unitpeppols' => $this->unitpeppols($unitpeppolRepository),
          'grid_summary'=> $settingRepository->grid_summary($paginator, $this->translator, (int)$settingRepository->get_setting('default_list_limit'), $this->translator->translate('invoice.unit.peppol'), ''),
          'paginator'=> $paginator,
          'flash'=> $flash
      ];
      return $this->viewRenderer->render('index', $parameters);
    }
        
    /**
     * 
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @param UnitPeppolRepository $unitpeppolRepository
     * @return Response
     */
    public function delete(SettingRepository $settingRepository, CurrentRoute $currentRoute,UnitPeppolRepository $unitpeppolRepository 
    ): Response {
        try {
            $unitpeppol = $this->unitpeppol($currentRoute, $unitpeppolRepository);
            if ($unitpeppol) {
                $this->unitpeppolService->deleteUnitPeppol($unitpeppol);               
                $this->flash('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->webService->getRedirectResponse('unitpeppol/index'); 
            }
            return $this->webService->getRedirectResponse('unitpeppol/index'); 
	} catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('unitpeppol/index'); 
        }
    }
        
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                        ValidatorInterface $validator,
                        UnitPeppolRepository $unitpeppolRepository, 
                        SettingRepository $settingRepository,                        
                        UnitRepository $unitRepository
    ): Response {
        $unitpeppol = $this->unitpeppol($currentRoute, $unitpeppolRepository);
        $enece = new Peppol_UNECERec20_11e();
        $enece_array = $enece->getUNECERec20_11e();
        if ($unitpeppol){
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['unitpeppol/edit', ['id' => $unitpeppol->getId()]],
                'eneces' => $enece_array,                
                'errors' => [],
                'body' => $this->body($unitpeppol),
                'head'=>$head,
                's'=>$settingRepository,
                'units'=>$unitRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new UnitPeppolForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->unitpeppolService->saveUnitPeppol($unitpeppol,$form);
                    return $this->webService->getRedirectResponse('unitpeppol/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('unitpeppol/index');
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
     * @param UnitPeppolRepository $unitpeppolRepository
     * @return UnitPeppol|null
     */
    private function unitpeppol(CurrentRoute $currentRoute,UnitPeppolRepository $unitpeppolRepository) : UnitPeppol|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $unitpeppol = $unitpeppolRepository->repoUnitPeppolLoadedquery($id);
            return $unitpeppol;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function unitpeppols(UnitPeppolRepository $unitpeppolRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $unitpeppols = $unitpeppolRepository->findAllPreloaded();        
        return $unitpeppols;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param UnitPeppolRepository $unitpeppolRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,UnitPeppolRepository $unitpeppolRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $unitpeppol = $this->unitpeppol($currentRoute, $unitpeppolRepository); 
        if ($unitpeppol) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['unitpeppol/view', ['id' => $unitpeppol->getId()]],
                'errors' => [],
                'body' => $this->body($unitpeppol),
                'unitpeppol'=>$unitpeppol,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('unitpeppol/index');
    }
}


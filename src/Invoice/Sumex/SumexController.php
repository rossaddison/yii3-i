<?php
declare(strict_types=1); 

namespace App\Invoice\Sumex;

use App\Invoice\Entity\Sumex;
use App\Invoice\Sumex\SumexService;
use App\Invoice\Sumex\SumexForm;
use App\Invoice\Sumex\SumexRepository;
use App\Invoice\Setting\SettingRepository;
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
use Yiisoft\Yii\View\ViewRenderer;
use Yiisoft\Validator\ValidatorInterface;

final class SumexController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private SumexService $sumexService;
    private TranslatorInterface $translator;
    private DataResponseFactoryInterface $factory;

    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        SumexService $sumexService,
        TranslatorInterface $translator,
        DataResponseFactoryInterface $factory,
    )    
    {
        $this->viewRenderer = $viewRenderer;      
        $this->webService = $webService;
        $this->userService = $userService;
        $this->sumexService = $sumexService;
        $this->translator = $translator;
        $this->factory = $factory;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/sumex')
                                               ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/sumex')
                                               ->withLayout('@views/layout/invoice.php');
        }
    }
    
    /**
     * @param SessionInterface $session
     * @param SumexRepository $sumexRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param SumexService $service
     */
    public function index(SessionInterface $session, SumexRepository $sumexRepository, SettingRepository $settingRepository, Request $request, SumexService $service): \Yiisoft\DataResponse\DataResponse
    {
         $canEdit = $this->rbac($session);
         $flash = $this->flash($session, '','');
         $parameters = [
      
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'sumexs' => $this->sumexs($sumexRepository),
          'flash'=> $flash
         ];
        
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param SettingRepository $settingRepository
     * @return Response
     */    
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $settingRepository
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['sumex/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
            
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new SumexForm();
            $model = new Sumex();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->sumexService->saveSumex($model, $form, $settingRepository);
                return $this->webService->getRedirectResponse('sumex/index');
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
     * @param SumexRepository $sumexRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        ValidatorInterface $validator,
                        SumexRepository $sumexRepository, 
                        SettingRepository $settingRepository
    ): Response {
        $sumex = $this->sumex($currentRoute, $sumexRepository);
        if ($sumex) {
            $parameters = [
                'title' => $settingRepository->get_setting('edit'),
                'action' => ['sumex/edit', ['invoice' => $sumex->getInvoice()]],
                'errors' => [],
                'body' => $this->body($sumex),
                's'=>$settingRepository,
                'head'=>$head,

            ];
            if ($request->getMethod() === Method::POST) {
                $form = new SumexForm();
                $body = $request->getParsedBody();
                if (is_array($body) && $form->load($body) && $validator->validate($form)->isValid()) {
                $this->sumexService->saveSumex($sumex, $form, $settingRepository);
                return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading' => '','message'=>
                    $settingRepository->trans('record_successfully_updated'),
                    /**
                     * @var int $body['invoice'] 
                     */
                    'url'=>'inv/view','id'=>$body['invoice']]));
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('sumex/index');
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param SumexRepository $sumexRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, SumexRepository $sumexRepository 
    ): Response {
        $sumex = $this->sumex($currentRoute, $sumexRepository);
        if ($sumex) {
            $this->sumexService->deleteSumex($sumex);               
        }
        return $this->webService->getRedirectResponse('sumex/index');        
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param SumexRepository $sumexRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, SumexRepository $sumexRepository,
        SettingRepository $settingRepository
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $sumex = $this->sumex($currentRoute, $sumexRepository);
        if ($sumex) {        
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['sumex/edit', ['id' => $sumex->getId()]],
                'errors' => [],
                'body' => $this->body($sumex),
                's'=>$settingRepository,             
                'sumex'=>$sumexRepository->repoSumexquery($sumex->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('sumex/index');         
    }
    
    /**
     * @return Response|true
     */
    private function rbac(SessionInterface $session): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('sumex/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param SumexRepository $sumexRepository
     * @return Sumex|null
     */
    private function sumex(CurrentRoute $currentRoute, SumexRepository $sumexRepository): Sumex|null
    {
        $invoice = $currentRoute->getArgument('invoice');       
        if (null!==$invoice) {
            $sumex = $sumexRepository->repoSumexInvoicequery($invoice);
            return $sumex;            
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function sumexs(SumexRepository $sumexRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $sumexs = $sumexRepository->findAllPreloaded();        
        return $sumexs;
    }
    
    /**
     * 
     * @param Sumex $sumex
     * @return array
     */
    private function body(Sumex $sumex): array {
        $body = [
          'invoice'=>$sumex->getInvoice(),
          'reason'=>$sumex->getReason(),
          'diagnosis'=>$sumex->getDiagnosis(),
          'observations'=>$sumex->getObservations(),
          'treatmentstart'=>$sumex->getTreatmentstart(),
          'treatmentend'=>$sumex->getTreatmentend(),
          'casedate'=>$sumex->getCasedate(),
          'casenumber'=>$sumex->getCasenumber()
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
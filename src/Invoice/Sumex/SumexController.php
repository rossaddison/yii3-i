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
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        SumexService $sumexService,
        TranslatorInterface $translator
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/sumex')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->sumexService = $sumexService;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param SumexRepository $sumexRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param SumexService $service
     * @return Response
     */
    public function index(SessionInterface $session, SumexRepository $sumexRepository, SettingRepository $settingRepository, Request $request, SumexService $service): Response
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
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->sumexService->saveSumex(new Sumex(),$form);
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
        $parameters = [
            'title' => 'Edit',
            'action' => ['sumex/edit', ['id' => $this->sumex($currentRoute, $sumexRepository)->getId()]],
            'errors' => [],
            'body' => $this->body($this->sumex($currentRoute, $sumexRepository)),
            's'=>$settingRepository,
            'head'=>$head,
            
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new SumexForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->sumexService->saveSumex($this->sumex($currentRoute, $sumexRepository), $form);
                return $this->webService->getRedirectResponse('sumex/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param SumexRepository $sumexRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, SumexRepository $sumexRepository 
    ): Response {
        $this->sumexService->deleteSumex($this->sumex($currentRoute, $sumexRepository));               
        return $this->webService->getRedirectResponse('sumex/index');        
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param SumexRepository $sumexRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, SumexRepository $sumexRepository,
        SettingRepository $settingRepository
        ): Response {
        $parameters = [
            'title' => $settingRepository->trans('view'),
            'action' => ['sumex/edit', ['id' => $this->sumex($currentRoute, $sumexRepository)->getId()]],
            'errors' => [],
            'body' => $this->body($this->sumex($currentRoute, $sumexRepository)),
            's'=>$settingRepository,             
            'sumex'=>$sumexRepository->repoSumexquery($this->sumex($currentRoute, $sumexRepository)->getId()),
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
        $id = $currentRoute->getArgument('id');       
        $sumex = $sumexRepository->repoSumexquery($id);
        return $sumex;
    }
    
    /**
     * @return \Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\DataReaderInterface<int, Sumex>
     */
    private function sumexs(SumexRepository $sumexRepository): \Yiisoft\Data\Reader\DataReaderInterface 
    {
        $sumexs = $sumexRepository->findAllPreloaded();        
        return $sumexs;
    }
    
    /**
     * @return (\DateTimeImmutable|int|null|string)[]
     *
     * @psalm-return array{invoice: int, reason: int, diagnosis: string, observations: string, treatmentstart: \DateTimeImmutable|null, treatmentend: \DateTimeImmutable, casedate: \DateTimeImmutable, casenumber: null|string}
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
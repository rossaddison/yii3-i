<?php

declare(strict_types=1);

namespace App\Invoice\Unit;

use App\Invoice\Entity\Unit;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Unit\UnitRepository;
use App\Service\WebControllerService;
use App\User\UserService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

final class UnitController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UnitService $unitService;    
    private UserService $userService;
    private TranslatorInterface $translator;

    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UnitService $unitService,
        UserService $userService,
        TranslatorInterface $translator
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/unit')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->unitService = $unitService;
        $this->userService = $userService;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param Session $session
     * @param UnitRepository $unitRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function index(CurrentRoute $currentRoute, Session $session, UnitRepository $unitRepository, SettingRepository $settingRepository): Response
    {
        $units = $this->units($unitRepository);
        $pageNum = (int)$currentRoute->getArgument('page', '1');
        $paginator = (new OffsetPaginator($units))
            ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
            ->withCurrentPage($pageNum);
        $parameters = [
            'alert'=>$this->viewRenderer->renderPartialAsString('/invoice/layout/alert',[
                     'flash'=>$this->flash($session, '', ''),
            ]),      
            'paginator'=> $paginator,
            's'=> $settingRepository,
            'units' => $units, 
        ]; 
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
     * @param Session $session
     * @param Request $request
     * @param SettingRepository $settingRepository
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function add(Session $session, Request $request, SettingRepository $settingRepository, ValidatorInterface $validator): Response
    {
        $parameters = [
            'title' => 'Add Unit',
            'action' => ['unit/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new UnitForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->unitService->saveUnit(new Unit(), $form);
                $this->flash($session, 'info', $settingRepository->trans('record_successfully_created'));
                return $this->webService->getRedirectResponse('unit/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    /**
     * 
     * @param Session $session
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param UnitRepository $unitRepository
     * @param SettingRepository $settingRepository
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function edit(Session $session, Request $request, CurrentRoute $currentRoute,
            UnitRepository $unitRepository, SettingRepository $settingRepository, ValidatorInterface $validator): Response 
    {
        $unit = $this->unit($currentRoute, $unitRepository);
        $parameters = [
            'title' => 'Edit unit',
            'action' => ['unit/edit', ['id' => $unit->getUnit_id()]],
            'errors' => [],
            'body' => [
                'unit_name' => $this->unit($currentRoute, $unitRepository)->getUnit_name(),
                'unit_name_plrl' => $this->unit($currentRoute, $unitRepository)->getUnit_name_plrl(),
            ],
            's'=>$settingRepository,
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new UnitForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->unitService->saveUnit($unit, $form);
                $this->flash($session, 'info', $settingRepository->trans('record_successfully_updated'));
                return $this->webService->getRedirectResponse('unit/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    /**
     * 
     * @param Session $session
     * @param CurrentRoute $currentRoute
     * @param UnitRepository $unitRepository
     * @return Response
     */
    public function delete(Session $session, CurrentRoute $currentRoute, UnitRepository $unitRepository): Response 
    {
        try {
              $unit = $this->unit($currentRoute, $unitRepository);              
              $this->unitService->deleteUnit($unit);
              return $this->webService->getRedirectResponse('unit/index');
	} catch (\Exception $e) {
              unset($e);
              $this->flash($session, 'danger', 'Cannot delete. Unit history exists.');
              return $this->webService->getRedirectResponse('unit/index');
        }
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param UnitRepository $unitRepository
     * @param SettingRepository $settingRepository
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, UnitRepository $unitRepository,SettingRepository $settingRepository, ValidatorInterface $validator): Response {
        $unit = $this->unit($currentRoute, $unitRepository);
        $parameters = [
            'title' => $settingRepository->trans('edit_setting'),
            'action' => ['unit/edit', ['unit_id' => $unit->getUnit_id()]],
            'errors' => [],
            'unit'=>$unit,
            's'=>$settingRepository,     
            'body' => [
                'unit_id'=>$unit->getUnit_id(),
                'unit_name'=>$unit->getUnit_name(),
                'unit_name_plrl'=>$unit->getUnit_name_plrl(),               
            ],            
        ];
        return $this->viewRenderer->render('__view', $parameters);
    } 
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UnitRepository $unitRepository
     * @return Unit|null
     */
    private function unit(CurrentRoute $currentRoute, UnitRepository $unitRepository): Unit|null
    {
        $unit_id = $currentRoute->getArgument('id');
        $unit = $unitRepository->repoUnitquery($unit_id);
        return $unit; 
    }
    
    
    /**
     * @return Response|\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return Response|\Yiisoft\Data\Reader\DataReaderInterface<int, Unit>
     */
    private function units(UnitRepository $unitRepository): \Yiisoft\Data\Reader\DataReaderInterface|Response{
        $units = $unitRepository->findAllPreloaded();
        if ($units === null) {
            return $this->webService->getNotFoundResponse();
        }
        return $units;
    }
    
    /**
     * 
     * @param Session $session
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash(Session $session, string $level, string $message): Flash {
        $flash = new Flash($session);
        $flash->set($level, $message); 
        return $flash;
    }
}
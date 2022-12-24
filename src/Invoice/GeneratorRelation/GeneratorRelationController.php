<?php

declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

use App\Invoice\Entity\GentorRelation;
use App\Invoice\Generator\GeneratorRepository;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;

use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


final class GeneratorRelationController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private GeneratorRelationService $generatorrelationService;    
    private UserService $userService;
    private TranslatorInterface $translator;

    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        GeneratorRelationService $generatorrelationService,
        UserService $userService,
        TranslatorInterface $translator
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/generatorrelation')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->generatorrelationService = $generatorrelationService;
        $this->userService = $userService;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param Session $session
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function index(Session $session,GeneratorRelationRepository $generatorrelationRepository, SettingRepository $settingRepository): Response
    {
        $canEdit = $this->rbac($session);
        $generatorrelations = $this->generatorrelations($generatorrelationRepository);
        // $generator = $this->generatorrelation($generatorrelationRepository);
        $parameters = [
            's'=>$settingRepository,
            'canEdit' => $canEdit,
            'generatorrelations' => $generatorrelations
        ]; 
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * 
     * @param Request $request
     * @param GeneratorRepository $generatorRepository
     * @param SettingRepository $settingRepository
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function add(Request $request, GeneratorRepository $generatorRepository, SettingRepository $settingRepository,ValidatorInterface $validator): Response
    {
        $parameters = [
            'title' => $settingRepository->trans('Add'),
            'action' => ['generatorrelation/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'generators'=>$generatorRepository->findAllPreloaded()
        ];
        
        if ($request->getMethod() === Method::POST) {
            $form = new GeneratorRelationForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->generatorrelationService->saveGeneratorRelation(new GentorRelation(), $form);
                return $this->webService->getRedirectResponse('generatorrelation/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    /**
     * 
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @param GeneratorRepository $generatorRepository
     * @param SettingRepository $settingRepository
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function edit(Request $request, CurrentRoute $currentRoute,GeneratorRelationRepository $generatorrelationRepository, GeneratorRepository $generatorRepository, SettingRepository $settingRepository, ValidatorInterface $validator): Response 
    {
        $generatorrelation = $this->generatorrelation($currentRoute, $generatorrelationRepository);
        $parameters = [
            'title' => $settingRepository->trans('edit'),
            'action' => ['generatorrelation/edit', ['id' => $generatorrelation->getRelation_id()]],
            'errors' => [],
            'body' => [
                'id'=>$generatorrelation->getRelation_id(),
                'lowercasename'=>$generatorrelation->getLowercase_name(),               
                'camelcasename'=>$generatorrelation->getCamelcase_name(),
                'view_field_name'=>$generatorrelation->getView_field_name(),
                'gentor_id'=>$generatorrelation->getGentor_id(),
            ],
            //relation generator
            'generators'=>$generatorRepository->findAllPreloaded(),
            's'=>$settingRepository,
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new GeneratorRelationForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->generatorrelationService->saveGeneratorRelation($generatorrelation, $form);
                return $this->webService->getRedirectResponse('generatorrelation/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, GeneratorRelationRepository $generatorrelationRepository): Response 
    {
        $generatorrelation = $this->generatorrelation($currentRoute, $generatorrelationRepository);
        $this->generatorrelationService->deleteGeneratorRelation($generatorrelation);               
        return $this->webService->getRedirectResponse('generatorrelation/index');        
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @param SettingRepository $settingRepository
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, GeneratorRelationRepository $generatorrelationRepository,SettingRepository $settingRepository,ValidatorInterface $validator): Response {
        $generatorrelation = $this->generatorrelation($currentRoute, $generatorrelationRepository);
        $parameters = [
            'title' => $settingRepository->trans('view'),
            'action' => ['generatorrelation/view', ['id' => $generatorrelation->getRelation_id()]],
            'errors' => [],
            'generatorrelation'=>$this->generatorrelation($currentRoute, $generatorrelationRepository),
            's'=>$settingRepository,     
            'body' => [
                'id'=>$generatorrelation->getRelation_id(),
                'lowercasename'=>$generatorrelation->getLowercase_name(),               
                'camelcasename'=>$generatorrelation->getCamelcase_name(),
                'view_field_name'=>$generatorrelation->getView_field_name(),
                'gentor_id'=>$generatorrelation->getGentor_id()                
            ],
            'egrs'=>$generatorrelationRepository->repoGeneratorRelationquery($this->generatorrelation($currentRoute, $generatorrelationRepository)->getRelation_id()),
        ];
        return $this->viewRenderer->render('__view', $parameters);
    }
    
    /**
     * @return Response|true
     */
    private function rbac(Session $session): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('generatorrelation/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @return GentorRelation|null
     */
    private function generatorrelation(CurrentRoute $currentRoute, GeneratorRelationRepository $generatorrelationRepository): GentorRelation|null
    {
        $generatorrelation_id = $currentRoute->getArgument('id');
        $generatorrelation = $generatorrelationRepository->repoGeneratorRelationquery($generatorrelation_id);
        return $generatorrelation; 
    }
    
    //$generatorrelations = $this->generatorrelations();
    /**
     * @return \Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\DataReaderInterface<int, GentorRelation>
     */
    private function generatorrelations(GeneratorRelationRepository $generatorrelationRepository): \Yiisoft\Data\Reader\DataReaderInterface{
        $generatorrelations = $generatorrelationRepository->findAllPreloaded();
        return $generatorrelations;
    }
    
    //$this->flash
    /**
     * @psalm-param 'warning' $level
     */
    private function flash(Session $session, string $level, string $message): Flash{
        $flash = new Flash($session);
        $flash->set($level, $message); 
        return $flash;
    }
}

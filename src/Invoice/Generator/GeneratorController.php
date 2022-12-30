<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Invoice\Entity\Gentor;
use App\Invoice\Generator\GeneratorForm;
use App\Invoice\Generator\GeneratorRepository;
use App\Invoice\Generator\GeneratorService;
use App\Invoice\GeneratorRelation\GeneratorRelationRepository;
use App\Invoice\Helpers\CaCertFileNotFoundException;
use App\Invoice\Helpers\GoogleTranslateJsonFileNotFoundException;
use App\Invoice\Helpers\GoogleTranslateLocaleSettingNotFoundException;
use App\Invoice\Helpers\GenerateCodeFileHelper;
use App\Invoice\Libraries\Lang;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;

use Google\Cloud\Translate\V3\TranslationServiceClient;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Cycle\Database\DatabaseManager;

use Yiisoft\Aliases\Aliases;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\View\View;
use Yiisoft\Yii\View\ViewRenderer;
use Yiisoft\Json\Json;
use Yiisoft\Files\FileHelper;

final class GeneratorController
{
    private DataResponseFactoryInterface $factory;
    private GeneratorService $generatorService;   
    private Session $session;
    private TranslatorInterface $translator;
    private UserService $userService;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    const ENTITY = 'Entity.php';
    const REPO = 'Repository.php';
    const FORM = 'Form.php';
    const SERVICE = 'Service.php';
    const MAPPER = 'Mapper.php';
    const SCOPE = 'Scope.php';
    const CONTROLLER = 'Controller.php';
    const INDEX = 'index.php';
    const INDEX_ADV_PAGINATOR = 'index_adv_paginator.php';
    const INDEX_ADV_PAGINATOR_WITH_FILTER = 'index_adv_paginator_with_filter.php';
    const _FORM = '_form.php';     
    const _VIEW = '_view.php';
    const _ROUTE = '_route.php';
    const _IP = '_ip_lang.php';
    const _GATEWAY = '_gateway_lang.php';
    const _APP = '_app.php';
    
    public function __construct(
        DataResponseFactoryInterface $factory,    
        GeneratorService $generatorService,
        Session $session,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
    ) {
        $this->factory = $factory;
        $this->generatorService = $generatorService;
        $this->session = $session;
        $this->translator = $translator;
        $this->userService = $userService;
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/generator')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
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
     * @param GeneratorRepository $generatorRepository
     * @param GeneratorRelationRepository $grr
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function index(GeneratorRepository $generatorRepository, GeneratorRelationRepository $grr, SettingRepository $settingRepository): Response
    {
        $canEdit = $this->rbac();
        $generators = $this->generators($generatorRepository);
        $flash = $this->flash('info' , $this->viewRenderer->renderPartialAsString('/invoice/info/generator'));        
        $parameters = [
            's'=>$settingRepository,
            'canEdit' => $canEdit,
            'generators' => $generators,
            'grr'=>$grr,
            'flash'=> $flash
        ]; 
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
     * @param Request $request
     * @param SettingRepository $settingRepository
     * @param ValidatorInterface $validator
     * @param DatabaseManager $dbal
     * @return Response
     */
    public function add(Request $request, SettingRepository $settingRepository,ValidatorInterface $validator, DatabaseManager $dbal): Response
    {
        $parameters = [
            'title' => $settingRepository->trans('add'),
            'action' => ['generator/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'tables'=>$dbal->database('default')->getTables(),
            'selected_table'=>'',
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new GeneratorForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->generatorService->saveGenerator(new Gentor(), $form);
                return $this->webService->getRedirectResponse('generator/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param GeneratorRepository $generatorRepository
     * @param SettingRepository $s
     * @param ValidatorInterface $validator
     * @param DatabaseManager $dbal
     * @return Response
     */
    public function edit(CurrentRoute $currentRoute, Request $request, GeneratorRepository $generatorRepository, SettingRepository $s, ValidatorInterface $validator, DatabaseManager $dbal): Response 
    {
        $generator = $this->generator($currentRoute, $generatorRepository);
        $parameters = [
            'title' => $s->trans('edit'),
            'action' => ['generator/edit', ['id' => $generator->getGentor_id()]],
            'errors' => [],
            'body' => $this->body($this->generator($currentRoute, $generatorRepository)),
            's'=>$s,
            'tables'=>$dbal->database('default')->getTables(),
            'selected_table'=>$this->generator($currentRoute, $generatorRepository)->getPre_entity_table(),
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new GeneratorForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->generatorService->saveGenerator($generator, $form);
                $this->flash('warning', $s->trans('record_successfully_updated'));
                return $this->webService->getRedirectResponse('generator/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $generatorRepository
     * @param SettingRepository $s
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, GeneratorRepository $generatorRepository, SettingRepository $s): Response 
    {
        $generator = $this->generator($currentRoute, $generatorRepository);
        $this->flash('danger', $s->trans('record_successfully_deleted'));
        try {
           $this->generatorService->deleteGenerator($generator);
        }
        catch (\Exception $e) {
           unset($e);  
           $this->flash('danger','This record has existing Generator Relations so it cannot be deleleted. Delete these relations first.');
        }
        return $this->webService->getRedirectResponse('generator/index');   
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $generatorRepository
     * @param SettingRepository $settingRepository
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, GeneratorRepository $generatorRepository, SettingRepository $settingRepository,ValidatorInterface $validator): Response {
        $generator = $this->generator($currentRoute, $generatorRepository);
        $parameters = [
            'title' => $settingRepository->trans('view'),
            'action' => ['generator/view', ['id' => $generator->getGentor_id()]],
            'errors' => [],
            'generator'=>$this->generator($currentRoute, $generatorRepository),
            's'=>$settingRepository,     
            'body' => $this->body($this->generator($currentRoute, $generatorRepository)),            
            'selected_table'=>$this->generator($currentRoute, $generatorRepository)->getPre_entity_table(),            
        ];
        return $this->viewRenderer->render('__view', $parameters);
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('generator/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $generatorRepository
     * @return Gentor|null
     */
    private function generator(CurrentRoute $currentRoute, GeneratorRepository $generatorRepository): Gentor|null{
        $id = $currentRoute->getArgument('id');
        $generator = $generatorRepository->repoGentorQuery($id);
        return $generator; 
    }
   
    /**
     * @return \Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\DataReaderInterface<int, Gentor>
     */
    private function generators(GeneratorRepository $generatorRepository): \Yiisoft\Data\Reader\DataReaderInterface{
        $generators = $generatorRepository->findAllPreloaded();
        return $generators;
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
    
    /**
     * @return (bool|int|null|string)[]
     *
     * @psalm-return array{route_prefix: string, route_suffix: string, camelcase_capital_name: string, small_singular_name: string, small_plural_name: string, namespace_path: string, controller_layout_dir: string, controller_layout_dir_dot_path: string, repo_extra_camelcase_name: string, paginator_next_page_attribute: string, pre_entity_table: string, created_include: bool, modified_include: bool, updated_include: bool, deleted_include: bool, constrain_index_field: string, keyset_paginator_include: bool, offset_paginator_include: bool, filter_field: string, filter_field_start_position: int|null, filter_field_end_position: int|null, flash_include: bool, headerline_include: bool}
     */
    private function body(Gentor $generator): array {
        $body = [
                'route_prefix' => $generator->getRoute_prefix(),
                'route_suffix' => $generator->getRoute_suffix(),
                'camelcase_capital_name' => $generator->getCamelcase_capital_name(),
                'small_singular_name' => $generator->getSmall_singular_name(),
                'small_plural_name' => $generator->getSmall_plural_name(),
                'namespace_path' => $generator->getNamespace_path(),
                'controller_layout_dir' => $generator->getController_layout_dir(),
                'controller_layout_dir_dot_path' => $generator->getController_layout_dir_dot_path(),
                'repo_extra_camelcase_name' => $generator->getRepo_extra_camelcase_name(),
                'paginator_next_page_attribute' => $generator->getPaginator_next_page_attribute(),
                'pre_entity_table' => $generator->getPre_entity_table(),
                'created_include' => $generator->isCreated_include(),
                'modified_include' => $generator->isModified_include(),
                'updated_include' => $generator->isUpdated_include(),
                'deleted_include' => $generator->isDeleted_include(),
                'constrain_index_field'=> $generator->getConstrain_index_field(),
                'keyset_paginator_include' => $generator->isKeyset_paginator_include(),
                'offset_paginator_include' => $generator->isOffset_paginator_include(),
                'filter_field' => $generator->getFilter_field(),           
                'filter_field_start_position' => $generator->getFilter_field_start_position(),
                'filter_field_end_position' => $generator->getFilter_field_end_position(),
                'flash_include' => $generator->isFlash_include(),
                'headerline_include' => $generator->isHeaderline_include(),
        ];
        return $body;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */
    public function entity(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::ENTITY;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */
    public function repo(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::REPO;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */
    public function service(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::SERVICE;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */
    public function form(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::FORM;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */            
    public function mapper(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::MAPPER;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */
    public function scope(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::SCOPE;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */
    public function controller(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::CONTROLLER;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */            
    public function _index(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::INDEX;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$file.' generated at '.$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */
    public function _index_adv_paginator(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::INDEX_ADV_PAGINATOR;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$file.' generated at '.$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */
    public function _index_adv_paginator_with_filter(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::INDEX_ADV_PAGINATOR_WITH_FILTER;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$file.' generated at '.$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */
    public function _form(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::_FORM;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$file.' generated at '.$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */            
    public function _view(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                            ): Response {
        $file = self::_VIEW;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$file.' generated at '.$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    //generate this individual route. Append to config/routes file.  
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRepository $gr
     * @param SettingRepository $settingRepository
     * @param GeneratorRelationRepository $grr
     * @param DatabaseManager $dbal
     * @param View $view
     * @return Response
     */
    public function _route(CurrentRoute $currentRoute, GeneratorRepository $gr, 
                             SettingRepository $settingRepository, GeneratorRelationRepository $grr,
                             DatabaseManager $dbal, View $view
                           ): Response {
        $file = self::_ROUTE;
        $path = $this->getAliases();
        $g = $this->generator($currentRoute, $gr);
        $id = $g->getGentor_id();
        $relations = $grr->findRelations($id);
        $orm = $dbal->database('default')
                    ->table($g->getPre_entity_table());
        $content = $this->getContent($view,$g,$relations,$orm,$file);
        $flash = $this->flash('success',$file.' generated at '.$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac(),
            's'=> $settingRepository,
            'title' => 'Generate '.$file,
            'body' => $this->body($g),
            'generator'=> $g,
            'orm_schema'=>$orm,
            'relations'=>$relations,
            'flash'=> $flash,
            'generated'=>$build_file,
        ];
        return $this->viewRenderer->render('__results', $parameters);
    }
    
    /**
     * 
     * @param CurrentUser $currentUser
     * @param DatabaseManager $dba
     * @return Response
     */
    public function quick_view_schema(CurrentUser $currentUser, DatabaseManager $dba) : Response{
        $parameters = [
            'alerts' => $this->alert(),
            'isGuest' => $currentUser->isGuest(),
            'tables' => $dba->database('default')->getTables(),
        ];
        return $this->viewRenderer->render('__schema', $parameters);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param SettingRepository $sR
     * @return Response
     */
    public function google_translate(CurrentRoute $currentRoute, SettingRepository $sR) : Response {
        // Call the API and handle any network failures.
        $type = $currentRoute->getArgument('type');
        try {
            switch ($type) {
                case 'ip':
                    $return = $this->google_translate_lang('ip', $sR);
                    break;
                case 'gateway':
                    $return = $this->google_translate_lang('gateway', $sR);
                    break;
                case 'app':
                    $return = $this->google_translate_lang('app', $sR);
                    break;
                default:
                    break;
            }            
        } catch (\Exception $ex) {
            printf('Call failed with message: %s' . PHP_EOL, $ex->getMessage());
        }
        return $return;        
    }
    
    /**
     * 
     * @param string $type
     * @param SettingRepository $sR
     * @return Response
     * @throws CaCertFileNotFoundException
     * @throws GoogleTranslateJsonFileNotFoundException
     * @throws GoogleTranslateLocaleSettingNotFoundException
     */
    public function google_translate_lang(string $type, SettingRepository $sR) : Response {
        // ? Downloaded https://curl.haxx.se/ca/cacert.pem" into 
        // c:\wamp64\bin\php\{active_php}  
        !empty(\ini_get('curl.cainfo')) ?  $curlcertificate = true : false;
        if ($curlcertificate == false) {
            throw new CaCertFileNotFoundException(); 
        }
        // ? Downloaded json file at 
        // https://console.cloud.google.com/iam-admin/serviceaccounts/details/
        // {unique_project_id}/keys?project={your_project_name}
        // into ..src/Invoice/Google_translate_unique_folder
        $aliases = $sR->get_google_translate_json_file_aliases();
        $targetPath = $aliases->get('@google_translate_json_file_folder');
        $path_and_filename = $targetPath .DIRECTORY_SEPARATOR.$sR->get_setting('google_translate_json_filename');
        if (empty($path_and_filename)){
            throw new GoogleTranslateJsonFileNotFoundException(); 
        }
        $data = file_get_contents(FileHelper::normalizePath($path_and_filename));
        $json = Json::decode($data, true);
        $projectId = $json['project_id']; 
        putenv("GOOGLE_APPLICATION_CREDENTIALS=$path_and_filename");
        $translationClient = new TranslationServiceClient();
        // Use the ..src/Invoice/Language/English/ip_lang.php associative array as template
        $folder_language = 'English';           
        $lang = new Lang();
        // type eg. 'ip', 'gateway'  of ip_lang.php or gateway_lang.php
        $lang->load($type, $folder_language);
        $content = $lang->_language;
        // Build a template array using keys from $content
        // These keys will be filled with the associated translated text values
        // generated below by merging the two arrays.
        $content_keys_array = array_keys($content);
        // Retrieve the selected new language according to locale in Settings View Google Translate
        // eg. 'es' ie. Spanish
        $targetLanguage = $sR->get_setting('google_translate_locale');
        if (empty($targetLanguage)){
            throw new GoogleTranslateLocaleSettingNotFoundException(); 
        }
        // https://github.com/googleapis/google-cloud-php-translate
        $response = $translationClient->translateText(
            $content,
            $targetLanguage,
            TranslationServiceClient::locationName($projectId, 'global')
        );
        $result_array = [];
        foreach ($response->getTranslations() as $key => $translation) {
            $result_array[$key] = $translation->getTranslatedText();
        }
        $combined_array = array_combine($content_keys_array, $result_array);
        $file = $this->google_translate_get_file_from_type($type);
        $path = $this->getAliases();
        $content_params = [
            'combined_array' => $combined_array
        ];
        $dti = new \DateTimeImmutable('now');
        $file_content = $this->viewRenderer->renderPartialAsString(
        '/invoice/generator/templates_protected/'.$file, $content_params);
        $this->flash('success', $file.' generated at '. $path .'/'.$file);
        $this->build_and_save($path, $file_content, $file, $type);
        $parameters = [
           'alert' => $this->alert(),
           'combined_array' => $combined_array
        ];      
        return $this->viewRenderer->render('__google_translate_lang', $parameters);
    }
    
    /**
     * 
     * @param string $type
     * @return string
     */
    private function google_translate_get_file_from_type(string $type) : string {
        switch ($type) {
            case 'ip':
                $file = self::_IP;
                break;
            case 'gateway':
                $file = self::_GATEWAY;
                break;
            case 'app':
                $file = self::_APP;
                break;
            default:
                break;
        }
        return $file;
    }
    
   /**
    * 
    * @return string
    */    
    private function getAliases(): string{
         $view_generator_dir_path = new Aliases([
            '@generators' => dirname(dirname(dirname(__DIR__))).'/resources/views/invoice/generator/templates_protected',
            '@generated' => dirname(dirname(dirname(__DIR__))).'/resources/views/invoice/generator/output_overwrite']);            
         return $view_generator_dir_path->get('@generated');
    }
    
    /**
     * @param Gentor|Response $generator
     */
    private function getContent(View $view,Response|Gentor $generator,\Yiisoft\Data\Reader\DataReaderInterface $relations,$orm_schema,string $file): string{
        return $content = $view->render("//invoice/generator/templates_protected/".$file,['generator' => $generator,
                'relations'=>$relations,
                'orm_schema'=>$orm_schema,
                'body'=>$this->body($generator)]);
    }
    
    /**
     * @psalm-param '' $name
     */
    private function build_and_save(string $generated_dir_path,string $content, string $file,string $name): GenerateCodeFileHelper{
        echo $generated_dir_path;
        $build_file = new GenerateCodeFileHelper("$generated_dir_path/$file", $content); 
        $build_file->save();
        return $build_file;
    }
}

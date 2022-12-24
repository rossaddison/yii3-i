<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Invoice\Entity\Gentor;
use App\Invoice\Generator\GeneratorForm;
use App\Invoice\Generator\GeneratorRepository;
use App\Invoice\Generator\GeneratorService;
use App\Invoice\GeneratorRelation\GeneratorRelationRepository;
use App\Invoice\Helpers\GenerateCodeFileHelper;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Cycle\Database\DatabaseManager;

use Yiisoft\Aliases\Aliases;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\View\View;
use Yiisoft\Yii\View\ViewRenderer;

final class GeneratorController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private GeneratorService $generatorService;    
    private UserService $userService;
    private TranslatorInterface $translator;
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
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        GeneratorService $generatorService,
        UserService $userService,
        TranslatorInterface $translator,
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/generator')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->generatorService = $generatorService;
        $this->userService = $userService;
        $this->translator = $translator;
    }
    
    private function alert(Session $session) : string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash'=>$this->flash($session, '', ''),
            'errors' => [],
        ]);
    }

    public function index(Session $session, GeneratorRepository $generatorRepository, GeneratorRelationRepository $grr, SettingRepository $settingRepository): Response
    {
        $canEdit = $this->rbac($session);
        $generators = $this->generators($generatorRepository);
        $flash = $this->flash($session, 'info' , $this->viewRenderer->renderPartialAsString('/invoice/info/generator'));        
        $parameters = [
            's'=>$settingRepository,
            'canEdit' => $canEdit,
            'generators' => $generators,
            'grr'=>$grr,
            'flash'=> $flash
        ]; 
        return $this->viewRenderer->render('index', $parameters);
    }

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

    public function edit(Session $session, CurrentRoute $currentRoute, Request $request, GeneratorRepository $generatorRepository, SettingRepository $s, ValidatorInterface $validator, DatabaseManager $dbal): Response 
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
                $this->flash($session,'warning', $s->trans('record_successfully_updated'));
                return $this->webService->getRedirectResponse('generator/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    public function delete(Session $session, CurrentRoute $currentRoute, GeneratorRepository $generatorRepository, SettingRepository $s): Response 
    {
        $generator = $this->generator($currentRoute, $generatorRepository);
        $this->flash($session,'danger', $s->trans('record_successfully_deleted'));
        try {
           $this->generatorService->deleteGenerator($generator);
        }
        catch (\Exception $e) {
           unset($e);  
           $this->flash($session,'danger','This record has existing Generator Relations so it cannot be deleleted. Delete these relations first.');
        }
        return $this->webService->getRedirectResponse('generator/index');   
    }
    
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
    private function rbac(Session $session): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
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
    
    //$this->flash
    private function flash(Session $session, string $level, string $message): Flash{
        $flash = new Flash($session);
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
    
    public function entity(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
    
    public function repo(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
    
    public function service(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
    
    public function form(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
        
    public function mapper(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
    
    public function scope(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
    
    public function controller(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$g->getCamelcase_capital_name().$file.' generated at '.$path.'/'.$g->getCamelcase_capital_name().$file);
        $build_file = $this->build_and_save($path,$content,$file,$g->getCamelcase_capital_name());
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
        
    public function _index(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$file.' generated at '.$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
    
    public function _index_adv_paginator(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$file.' generated at '.$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
    
    public function _index_adv_paginator_with_filter(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$file.' generated at '.$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
    
    public function _form(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$file.' generated at '.$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
        
    public function _view(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$file.' generated at '.$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
    public function _route(Session $session,CurrentRoute $currentRoute, GeneratorRepository $gr, 
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
        $flash = $this->flash($session,'success',$file.' generated at '.$path.'/'.$file);
        $build_file = $this->build_and_save($path,$content,$file,'');
        $parameters = [
            'canEdit'=>$this->rbac($session),
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
    
    public function quick_view_schema(Session $session, CurrentUser $currentUser, DatabaseManager $dba){
        $parameters = [
            'alerts' => $this->alert($session),
            'isGuest' => $currentUser->isGuest(),
            'tables' => $dba->database('default')->getTables(),
        ];
        return $this->viewRenderer->render('__schema', $parameters);
    }
        
    private function getAliases(): string{
         $view_generator_dir_path = new Aliases([
            '@generators' => dirname(dirname(dirname(__DIR__))).'/views/invoice/generator/templates_protected',
            '@generated' => dirname(dirname(dirname(__DIR__))).'/views/invoice/generator/output_overwrite']);            
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
        $build_file = new GenerateCodeFileHelper("$generated_dir_path/$name$file", $content); 
        $build_file->save();
        return $build_file;
    }
}

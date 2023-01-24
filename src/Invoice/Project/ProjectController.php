<?php

declare(strict_types=1); 

namespace App\Invoice\Project;


use App\Invoice\Client\ClientRepository;
use App\Invoice\Entity\Project;
use App\Invoice\Project\ProjectService;
use App\Invoice\Project\ProjectRepository;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;

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

final class ProjectController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private ProjectService $projectService;
    private TranslatorInterface $translator;
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        ProjectService $projectService,
        TranslatorInterface $translator,
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/project')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->projectService = $projectService;
        $this->translator = $translator;
    }
    
    /**
     * @param SessionInterface $session
     * @param ProjectRepository $projectRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param ProjectService $service
     */
    public function index(SessionInterface $session, ProjectRepository $projectRepository, SettingRepository $settingRepository, Request $request, ProjectService $service): \Yiisoft\DataResponse\DataResponse
    {            
        $pageNum = (int)$request->getAttribute('page', '1');
        $paginator = (new OffsetPaginator($this->projects($projectRepository)))
        ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
        ->withCurrentPage($pageNum);      
        $canEdit = $this->rbac($session);
        $flash = $this->flash($session, '','');
        $parameters = [
              'paginator' => $paginator,  
              's'=>$settingRepository,
              'canEdit' => $canEdit,
              'projects' => $this->projects($projectRepository),
              'flash'=> $flash
        ];  
        return $this->viewRenderer->render('index', $parameters);
  
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param SettingRepository $settingRepository
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $settingRepository,                        
                        ClientRepository $clientRepository
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['project/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
            
            'clients'=>$clientRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new ProjectForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->projectService->saveProject(new Project(),$form);
                return $this->webService->getRedirectResponse('project/index');
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
     * @param ProjectRepository $projectRepository
     * @param SettingRepository $settingRepository
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        ValidatorInterface $validator,
                        ProjectRepository $projectRepository, 
                        SettingRepository $settingRepository,                        
                        ClientRepository $clientRepository
    ): Response {
        $project = $this->project($currentRoute, $projectRepository);
        if ($project) {
            $parameters = [
                'title' => 'Edit',
                'action' => ['project/edit', ['id' =>$project->getId()]],
                'errors' => [],
                'body' => $this->body($project),
                'head'=>$head,
                's'=>$settingRepository,
                'clients'=>$clientRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new ProjectForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->projectService->saveProject($project, $form);
                    return $this->webService->getRedirectResponse('project/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('project/index');
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param ProjectRepository $projectRepository
     * @param SettingRepository $sR
     * @return Response
     */
    public function delete(SessionInterface $session, CurrentRoute $currentRoute, 
                           ProjectRepository $projectRepository,
                           SettingRepository $sR): Response {
        $project = $this->project($currentRoute, $projectRepository);
        if ($project) {
            $this->projectService->deleteProject($project);               
            $this->flash($session, 'success', $sR->trans('record_successfully_deleted'));
        }
        return $this->webService->getRedirectResponse('project/index'); 
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ProjectRepository $projectRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute, ProjectRepository $projectRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $project = $this->project($currentRoute, $projectRepository);
        if ($project) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['project/view', ['id' =>$project->getId()]],
                'errors' => [],
                'body' => $this->body($project),
                's'=>$settingRepository,             
                'project'=>$projectRepository->repoProjectquery($project->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('project/index'); 
    }
        
    /**
     * @return Response|true
     */
    private function rbac(SessionInterface $session): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('project/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ProjectRepository $projectRepository
     * @return object|null
     */
    private function project(CurrentRoute $currentRoute, ProjectRepository $projectRepository): object|null
    {
        $id = $currentRoute->getArgument('id');  
        if (null!==$id) {
            $project = $projectRepository->repoProjectquery($id);
            return $project;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function projects(ProjectRepository $projectRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $projects = $projectRepository->findAllPreloaded();        
        return $projects;
    }
    
    /**
     * 
     * @param object $project
     * @return array
     */
    private function body(object $project): array {
        $body = [                
          'id'=>$project->getId(),
          'client_id'=>$project->getClient_id(),
          'name'=>$project->getName()
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
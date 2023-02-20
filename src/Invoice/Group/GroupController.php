<?php

declare(strict_types=1); 

namespace App\Invoice\Group;

use App\Invoice\Entity\Group;
use App\Invoice\Group\GroupService;
use App\Invoice\Group\GroupRepository;
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

final class GroupController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private GroupService $groupService;
    private TranslatorInterface $translator;
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        GroupService $groupService,
        TranslatorInterface $translator
    )    
    {
        $this->webService = $webService;
        $this->userService = $userService;
        $this->viewRenderer = $viewRenderer;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice')
                                                 ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice')
                                                 ->withLayout('@views/layout/invoice.php');
        }
        $this->groupService = $groupService;
        $this->translator = $translator;
    }
    
    /**
     * @param SessionInterface $session
     * @param GroupRepository $groupRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param GroupService $service
     */
    public function index(SessionInterface $session, GroupRepository $groupRepository, SettingRepository $settingRepository, Request $request, GroupService $service): \Yiisoft\DataResponse\DataResponse
    {    
        $pageNum = (int)$request->getAttribute('page', '1');
        $paginator = (new OffsetPaginator($this->groups($groupRepository)))
        ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
        ->withCurrentPage($pageNum);
        $canEdit = $this->rbac($session);
        $flash = $this->flash($session, '','');
        $parameters = [
              'paginator' => $paginator,
              's'=>$settingRepository,
              'canEdit' => $canEdit,
              'groups' => $this->groups($groupRepository),
              'flash'=> $flash
        ];  
        return $this->viewRenderer->render('group/index', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param SettingRepository $SettingRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $SettingRepository                        

    ) : Response 
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['group/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$SettingRepository,
            'head'=>$head
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new GroupForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->groupService->saveGroup(new Group(),$form);
                return $this->webService->getRedirectResponse('group/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('/invoice/group/_form', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param GroupRepository $groupRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        ValidatorInterface $validator,
                        GroupRepository $groupRepository, 
                        SettingRepository $settingRepository                       

    ): Response {
        $group = $this->group($currentRoute, $groupRepository);
        if ($group) {
            $parameters = [
                'title' => 'Edit',
                'action' => ['group/edit', ['id' => $group->getId()]],
                'errors' => [],
                'body' => $this->body($group),
                's'=>$settingRepository,
                'head'=>$head

            ];
            if ($request->getMethod() === Method::POST) {
                $form = new GroupForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->groupService->saveGroup($group, $form);
                    return $this->webService->getRedirectResponse('group/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('/invoice/group/_form', $parameters);
        }
        return $this->webService->getRedirectResponse('group/index');
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param GroupRepository $groupRepository
     * @return Response
     */
    public function delete(SessionInterface $session, CurrentRoute $currentRoute, GroupRepository $groupRepository 
    ): Response {
        try {
              $group = $this->group($currentRoute, $groupRepository);
              if ($group) {
                $this->groupService->deleteGroup($group);               
                return $this->webService->getRedirectResponse('group/index'); 
              }
              return $this->webService->getRedirectResponse('group/index'); 
	} catch (\Exception $e) {
              unset($e);
              $this->flash($session, 'danger', 'Cannot delete. Group history exists.');
              return $this->webService->getRedirectResponse('group/index'); 
        }
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GroupRepository $groupRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, GroupRepository $groupRepository,
        SettingRepository $settingRepository
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $group = $this->group($currentRoute, $groupRepository);
        if ($group) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['invoice/edit', ['id' => $group->getId()]],
                'errors' => [],
                'body' => $this->body($group),
                's'=>$settingRepository,            
                'group'=>$groupRepository->repoGroupquery($group->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('group/index');  
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @return bool|Response
     */
    private function rbac(SessionInterface $session) : bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('group/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GroupRepository $groupRepository
     * @return Group|null
     */
    private function group(CurrentRoute $currentRoute, GroupRepository $groupRepository) : Group|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $group = $groupRepository->repoGroupquery($id);
            return $group;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function groups(GroupRepository $groupRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $groups = $groupRepository->findAllPreloaded();        
        return $groups;
    }
    
    /**
     * 
     * @param Group $group
     * @return array
     */
    private function body(Group $group): array {
        $body = [
          'id'=>$group->getId(),
          'name'=>$group->getName(),
          'identifier_format'=>$group->getIdentifier_format(),
          'next_id'=>$group->getNext_id(),
          'left_pad'=>$group->getLeft_pad()
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
<?php

declare(strict_types=1); 

namespace App\Invoice\CustomValue;

use App\Invoice\Entity\CustomValue;
use App\Invoice\CustomValue\CustomValueService;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\CustomField\CustomFieldRepository;
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

final class CustomValueController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private CustomValueService $customvalueService;
    private TranslatorInterface $translator;
        
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        CustomValueService $customvalueService,
        TranslatorInterface $translator,
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/customvalue')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->customvalueService = $customvalueService;
        $this->translator = $translator;
    }
    
    /**
     * @param SessionInterface $session
     * @param CustomValueRepository $customvalueRepository
     * @param CustomFieldRepository $customfieldRepository
     * @param SettingRepository $settingRepository
     * @param Request $request
     * @param CustomValueService $service
     */
    public function index(SessionInterface $session, CustomValueRepository $customvalueRepository, CustomFieldRepository $customfieldRepository, SettingRepository $settingRepository, Request $request, CustomValueService $service): \Yiisoft\DataResponse\DataResponse
    {
         $canEdit = $this->rbac($session);
         $flash = $this->flash($session, '','');
         $custom_field_id = $session->get('custom_field_id');
         $custom_values = $customvalueRepository->repoCustomFieldquery((int)$custom_field_id);
         $parameters = [
          'custom_field' => $customfieldRepository->repoCustomFieldquery($custom_field_id),
          'custom_field_id' => $custom_field_id,
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'custom_values' => $custom_values,
          'custom_values_types'=> array_merge($this->user_input_types(), $this->custom_value_fields()), 
          'flash'=> $flash
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
     
    /**
     * @param SessionInterface $session
     * @param CustomFieldRepository $customfieldRepository
     * @param CustomValueRepository $customvalueRepository
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @param CustomValueService $service
     */
    public function field(SessionInterface $session, CustomFieldRepository $customfieldRepository, CustomValueRepository $customvalueRepository, SettingRepository $settingRepository, CurrentRoute $currentRoute, CustomValueService $service): \Yiisoft\DataResponse\DataResponse
    {      
        $canEdit = $this->rbac($session);
        $flash = $this->flash($session, '','');
        $id = $currentRoute->getArgument('id');
        null!==($session->get('custom_field_id')) ?: $session->set('custom_field_id', $id);
        $custom_field = $customfieldRepository->repoCustomFieldquery($id);
        $customvalues = $customvalueRepository->repoCustomFieldquery((int)$id);    
        $parameters = [
            's'=>$settingRepository,
            'canEdit' => $canEdit,
            'custom_field' => $custom_field,
            'custom_values_types' => array_merge($this->user_input_types(), $this->custom_value_fields()), 
            'custom_values'=> $customvalues,
            'flash'=> $flash
        ];
        return $this->viewRenderer->render('field', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param SettingRepository $settingRepository
     * @param CustomFieldRepository $custom_fieldRepository
     * @return Response
     */
    public function new(ViewRenderer $head, Request $request, SessionInterface $session, CurrentRoute $currentRoute, 
                        ValidatorInterface $validator,
                        SettingRepository $settingRepository,                        
                        CustomFieldRepository $custom_fieldRepository
    ): Response
    {
        $field_id = $currentRoute->getArgument('id');        
        $session->set('custom_field_id', $field_id);
        $custom_field = $custom_fieldRepository->repoCustomFieldquery($field_id);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['customvalue/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'custom_field'=>$custom_field, 
            'header_buttons'=>$this->viewRenderer->renderPartialAsString('/invoice/layout/header_buttons',['hide_submit_button'=>false, 
                                                                                 'hide_cancel_button'=>false,'s'=>$settingRepository]),
            'head'=>$head,
            'custom_fields'=>$custom_fieldRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {            
            $form = new CustomValueForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->customvalueService->saveCustomValue(new CustomValue(),$form);
                return $this->webService->getRedirectResponse('customvalue/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('new', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param CustomValueRepository $customvalueRepository
     * @param SettingRepository $settingRepository
     * @param CustomFieldRepository $custom_fieldRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, SessionInterface $session, Request $request, CurrentRoute $currentRoute, 
                        ValidatorInterface $validator,
                        CustomValueRepository $customvalueRepository, 
                        SettingRepository $settingRepository,                        
                        CustomFieldRepository $custom_fieldRepository
    ): Response {
        $custom_field_id = $session->get('custom_field_id');
        $custom_field = $custom_fieldRepository->repoCustomFieldquery($custom_field_id);
        $parameters = [
            'title' => 'Edit',
            'action' => ['customvalue/edit', ['id' => $this->customvalue($currentRoute, $customvalueRepository)->getId()]],
            'errors' => [],
            'body' => $this->body($this->customvalue($currentRoute, $customvalueRepository)),
            'header_buttons'=>$this->viewRenderer->renderPartialAsString('/invoice/layout/header_buttons',
                      ['hide_submit_button'=>false, 'hide_cancel_button'=>false,'s'=>$settingRepository]),
            'head'=>$head,
            's'=>$settingRepository,
            'custom_field' => $custom_field,
            'custom_fields'=>$custom_fieldRepository->findAllPreloaded()
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new CustomValueForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->customvalueService->saveCustomValue($this->customvalue($currentRoute,$customvalueRepository), $form);
                return $this->webService->getRedirectResponse('customvalue/index');                 
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('edit', $parameters);
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param CustomValueRepository $customvalueRepository
     * @param SettingRepository $sR
     * @return Response
     */
    public function delete(SessionInterface $session,CurrentRoute $currentRoute,
                           CustomValueRepository $customvalueRepository,
                           SettingRepository $sR
    ): Response {
        try {
            $this->customvalueService->deleteCustomValue($this->customvalue($currentRoute,$customvalueRepository));               
            $this->flash($session, 'info', $sR->trans('record_successfully_deleted'));
            return $this->webService->getRedirectResponse('customvalue/index'); 
	} catch (\Exception $e) {
            $this->flash($session, 'danger', $e->getMessage());
            unset($e);
            return $this->webService->getRedirectResponse('customvalue/index'); 
        }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CustomValueRepository $customvalueRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute, CustomValueRepository $customvalueRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse {
        $parameters = [
            'title' => $settingRepository->trans('view'),
            'action' => ['customvalue/view', ['id' => $this->customvalue($currentRoute, $customvalueRepository)->getId()]],
            'errors' => [],
            'body' => $this->body($this->customvalue($currentRoute, $customvalueRepository)),
            's'=>$settingRepository,             
            'customvalue'=>$customvalueRepository->repoCustomValuequery($this->customvalue($currentRoute, $customvalueRepository)->getId()),
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
            return $this->webService->getRedirectResponse('customvalue/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param CustomValueRepository $customvalueRepository
     * @return CustomValue|null
     */
    private function customvalue(CurrentRoute $currentRoute,CustomValueRepository $customvalueRepository): CustomValue|null
    {
        $id = $currentRoute->getArgument('id');       
        $customvalue = $customvalueRepository->repoCustomValuequery($id);
        return $customvalue;
    }  
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function customvalues(CustomValueRepository $customvalueRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader 
    {
        $customvalues = $customvalueRepository->findAllPreloaded();        
        return $customvalues;
    }
    
    /**
     * @return (int|string)[]
     *
     * @psalm-return array{id: string, custom_field_id: int, value: string}
     */
    private function body(CustomValue $customvalue): array {
        $body = [                
          'id'=>$customvalue->getId(),             
          'custom_field_id'=>$customvalue->getCustom_field_id(),
          'value'=>$customvalue->getValue()
        ];
        return $body;
    }
    
    private function flash(SessionInterface $session, string $level, string $message): Flash{
        $flash = new Flash($session);
        $flash->set($level, $message); 
        return $flash;
    }
    
    /**
     * @return string[]
     *
     * @psalm-return list{'TEXT', 'DATE', 'BOOLEAN'}
     */
    public function user_input_types() : array
    {
        return array(
            'TEXT',
            'DATE',
            'BOOLEAN'
        );
    }

    /**
     * @return string[]
     *
     * @psalm-return list{'SINGLE-CHOICE', 'MULTIPLE-CHOICE'}
     */
    public function custom_value_fields() : array
    {
        return array(
            'SINGLE-CHOICE',
            'MULTIPLE-CHOICE'
        );
    }
}


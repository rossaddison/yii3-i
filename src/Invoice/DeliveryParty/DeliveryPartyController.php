<?php

declare(strict_types=1); 

namespace App\Invoice\DeliveryParty;

use App\Invoice\Entity\DeliveryParty;
use App\Invoice\DeliveryParty\DeliveryPartyService;
use App\Invoice\DeliveryParty\DeliveryPartyRepository;

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
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

use \Exception;

final class DeliveryPartyController
{
    private SessionInterface $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private DeliveryPartyService $deliverypartyService;
    private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        DeliveryPartyService $deliverypartyService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/deliveryparty')
                                           // The Controller layout dir is now redundant: replaced with an alias 
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->deliverypartyService = $deliverypartyService;
        $this->translator = $translator;
    }
    
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $settingRepository,                        

    ) : Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['deliveryparty/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new DeliveryPartyForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->deliverypartyService->saveDeliveryParty(new DeliveryParty(),$form);
                return $this->webService->getRedirectResponse('deliveryparty/index');
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
     * @param DeliveryParty $deliveryparty
     * @return array
     */
    private function body(DeliveryParty $deliveryparty) : array {
        $body = [
          'id'=>$deliveryparty->getId(),
          'party_name'=>$deliveryparty->getPartyName()
        ];
        return $body;
    }
    
    public function index(DeliveryPartyRepository $deliverypartyRepository, SettingRepository $settingRepository, Request $request, DeliveryPartyService $service): Response
    {      
      $flash = $this->flash('' , '');
        $parameters = [
          'deliveryparties' => $this->deliveryparties($deliverypartyRepository),
          'flash'=> $flash
        ];

        
        return $this->viewRenderer->render('index', $parameters);
    }
    
    public function index_adv_paginator(DeliveryPartyRepository $deliverypartyRepository, SettingRepository $settingRepository, CurrentRoute $currentRoute, DeliveryPartyService $service): Response
    {
                  
        $flash = $this->flash('' , '');
        $parameters = [        
              's'=>$settingRepository,
              'deliveryparties' => $this->deliveryparties($deliverypartyRepository),
              'flash'=> $flash
      ];
      return $this->viewRenderer->render('index', $parameters);
  
    }
    
    /**
     * 
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @return Response
     */
    public function delete(SettingRepository $settingRepository, CurrentRoute $currentRoute,DeliveryPartyRepository $deliverypartyRepository 
    ): Response {
        try {
            $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
            if ($deliveryparty) {
                $this->deliverypartyService->deleteDeliveryParty($deliveryparty);               
                $this->flash('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->webService->getRedirectResponse('deliveryparty/index'); 
            }
            return $this->webService->getRedirectResponse('deliveryparty/index'); 
	} catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('deliveryparty/index'); 
        }
    }
        
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                        ValidatorInterface $validator,
                        DeliveryPartyRepository $deliverypartyRepository, 
                        SettingRepository $settingRepository,                        

    ): Response {
        $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository);
        if ($deliveryparty){
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['deliveryparty/edit', ['id' => $deliveryparty->getId()]],
                'errors' => [],
                'body' => $this->body($deliveryparty),
                'head'=>$head,
                's'=>$settingRepository,
                
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new DeliveryPartyForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->deliverypartyService->saveDeliveryParty($deliveryparty, $form);
                    return $this->webService->getRedirectResponse('deliveryparty/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('deliveryparty/index');
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
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @return DeliveryParty|null
     */
    private function deliveryparty(CurrentRoute $currentRoute,DeliveryPartyRepository $deliverypartyRepository) : DeliveryParty|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $deliveryparty = $deliverypartyRepository->repoDeliveryPartyquery($id);
            return $deliveryparty;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function deliveryparties(DeliveryPartyRepository $deliverypartyRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $deliveryparties = $deliverypartyRepository->findAllPreloaded();        
        return $deliveryparties;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryPartyRepository $deliverypartyRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,DeliveryPartyRepository $deliverypartyRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $deliveryparty = $this->deliveryparty($currentRoute, $deliverypartyRepository); 
        if ($deliveryparty) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['deliveryparty/view', ['id' => $deliveryparty->getId()]],
                'errors' => [],
                'body' => $this->body($deliveryparty),
                'deliveryparty'=>$deliveryparty,
            ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('deliveryparty/index');
    }
}


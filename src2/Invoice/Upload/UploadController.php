<?php

declare(strict_types=1); 

namespace App\Invoice\Upload;

use App\Invoice\Entity\Upload;
use App\Invoice\Upload\UploadService;
use App\Invoice\Upload\UploadRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Client\ClientRepository;
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
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

use \Exception;

final class UploadController
{
    private SessionInterface $session;
    private DataResponseFactoryInterface $factory;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private UploadService $uploadService;
    private TranslatorInterface $translator;
        
    public function __construct(
        SessionInterface $session, 
        DataResponseFactoryInterface $factory,    
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        UploadService $uploadService,
        TranslatorInterface $translator,
    )    
    {
        $this->session = $session;
        $this->factory = $factory;
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/upload')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->uploadService = $uploadService;
        $this->translator = $translator;
    }
    
    /**
     * @param UploadRepository $uploadRepository
     */
    public function index(UploadRepository $uploadRepository): \Yiisoft\DataResponse\DataResponse
    {      
        $parameters = [
          'uploads' => $this->uploads($uploadRepository),
          'alert'=> $this->alert()  
        ];
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param ClientRepository $clientRepository
     * @return Response
     */    
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,                     
                        ClientRepository $clientRepository                        
    ) : Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['upload/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            'head'=>$head,
            'clients'=>$clientRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new UploadForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->uploadService->saveUpload(new Upload(),$form);
                return $this->webService->getRedirectResponse('upload/index');
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
     * @param CurrentRoute $currentRoute
     * @param UploadRepository $uploadRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute,
                           UploadRepository $uploadRepository,
                           SettingRepository $settingRepository
    ): Response {
        try {
            $upload = $this->upload($currentRoute, $uploadRepository);
            if ($upload) {
                $this->uploadService->deleteUpload($upload, $settingRepository);
                $inv_id = $this->session->get('inv_id');
                $this->flash('info', $settingRepository->trans('record_successfully_deleted'));
                 return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                ['heading'=>'','message'=>$settingRepository->trans('record_successfully_deleted'),'url'=>'inv/view','id'=>$inv_id]));  
            }
            return $this->webService->getRedirectResponse('upload/index'); 
        } catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('upload/index'); 
        }
    }
    
    /**
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ValidatorInterface $validator
     * @param UploadRepository $uploadRepository
     * @param SettingRepository $settingRepository
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                        ValidatorInterface $validator,
                        UploadRepository $uploadRepository, 
                        SettingRepository $settingRepository,                        
                        ClientRepository $clientRepository
    ): Response {
        $upload = $this->upload($currentRoute, $uploadRepository); 
        if ($upload) {
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['upload/edit', ['id' => $upload->getId()]],
                'errors' => [],
                'body' => $this->body($upload),
                'head'=>$head,
                'clients'=>$clientRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new UploadForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->uploadService->saveUpload($upload, $form);
                    return $this->webService->getRedirectResponse('upload/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('upload/index');
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param UploadRepository $uploadRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute, UploadRepository $uploadRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $upload = $this->upload($currentRoute, $uploadRepository);
        if ($upload) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['upload/view', ['id' => $upload->getId()]],
                'errors' => [],
                'body' => $this->body($upload),
                's'=>$settingRepository,             
                'upload'=>$uploadRepository->repoUploadquery($upload->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('upload/index');
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param UploadRepository $uploadRepository
     * @return object|null
     */
    public function upload(CurrentRoute $currentRoute, UploadRepository $uploadRepository) : object|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $upload = $uploadRepository->repoUploadquery($id);
            return $upload;
        }
        return null;
    }
    
    /**
     * @param UploadRepository $uploadRepository
     *
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function uploads(UploadRepository $uploadRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $uploads = $uploadRepository->findAllPreloaded();        
        return $uploads;
    }
    
    /**
     * 
     * @param object $upload
     * @return array
     */
    private function body(object $upload) : array {
        $body = [
          'id'=>$upload->getId(),
          'client_id'=>$upload->getClient_id(),
          'url_key'=>$upload->getUrl_key(),
          'file_name_original'=>$upload->getFile_name_original(),
          'file_name_new'=>$upload->getFile_name_new(),
          'uploaded_date'=>$upload->getUploaded_date()
        ];
        return $body;
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
}


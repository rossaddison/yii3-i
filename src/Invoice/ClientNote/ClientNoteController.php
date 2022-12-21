<?php

declare(strict_types=1); 

namespace App\Invoice\ClientNote;

use App\Invoice\Entity\ClientNote;
use App\Invoice\ClientNote\ClientNoteService;
use App\Invoice\ClientNote\ClientNoteRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Helpers\DateHelper;
use App\User\UserService;
use Yiisoft\Validator\ValidatorInterface;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

final class ClientNoteController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private ClientNoteService $clientnoteService;
    private TranslatorInterface $translator;
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        ClientNoteService $clientnoteService,
        TranslatorInterface $translator
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/clientnote')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->clientnoteService = $clientnoteService;
        $this->translator = $translator;
    }
    
    public function index(SessionInterface $session, ClientNoteRepository $clientnoteRepository, DateHelper $dateHelper, SettingRepository $settingRepository, Request $request, ClientNoteService $service): Response
    {
         $canEdit = $this->rbac($session);
         $flash = $this->flash($session, '','');
         $parameters = [
          'd'=>$dateHelper,
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'clientnotes' => $this->clientnotes($clientnoteRepository),
          'flash'=> $flash
         ];
        
        return $this->viewRenderer->render('index', $parameters);
    }
    
    public function add(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        DateHelper $dateHelper, 
                        SettingRepository $settingRepository,                        
                        ClientRepository $clientRepository
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['clientnote/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            'd'=>$dateHelper,
            's'=>$settingRepository,
            'head'=>$head,
            'clients'=>$clientRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new ClientNoteForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->clientnoteService->addClientNote(new ClientNote(),$form, $settingRepository);
                return $this->webService->getRedirectResponse('clientnote/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    public function edit(ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        ClientNoteRepository $clientnoteRepository, 
                        SettingRepository $settingRepository,                        
                        ClientRepository $clientRepository,
                        DateHelper $dateHelper, 
                        CurrentRoute $currentRoute
    ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        $parameters = [
            'title' => $settingRepository->trans('edit'),
            'action' => ['clientnote/edit', ['id' => $client_note->getId()]],
            'errors' => [],
            'body' => $this->body($this->clientnote($currentRoute, $clientnoteRepository)),
            'head'=>$head,
            'd'=>$dateHelper,
            's'=>$settingRepository,
            'clients'=>$clientRepository->findAllPreloaded()
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new ClientNoteForm();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this->clientnoteService->saveClientNote($this->clientnote($currentRoute, $clientnoteRepository), $form, $settingRepository);
                return $this->webService->getRedirectResponse('clientnote/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    public function delete(ClientNoteRepository $clientnoteRepository, CurrentRoute $currentRoute
    ): Response {       
        $this->clientnoteService->deleteClientNote($this->clientnote($currentRoute,$clientnoteRepository));               
        return $this->webService->getRedirectResponse('clientnote/index');        
    }
    
    public function view(CurrentRoute $currentRoute, ClientNoteRepository $clientnoteRepository, DateHelper $dateHelper,
        SettingRepository $settingRepository
        ): Response {
        $client_note = $this->clientnote($currentRoute, $clientnoteRepository);
        $parameters = [
            'title' => $settingRepository->trans('view'),
            'action' => ['clientnote/edit', ['id' => $client_note->getId()]],
            'errors' => [],
            'body' => $this->body($this->clientnote($currentRoute, $clientnoteRepository)),
            'd'=>$dateHelper,
            's'=>$settingRepository,             
            'clientnote'=>$clientnoteRepository->repoClientNotequery($this->clientnote($currentRoute, $clientnoteRepository)->getId()),
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
            return $this->webService->getRedirectResponse('clientnote/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ClientNoteRepository $clientnoteRepository
     * @return ClientNote|null
     */
    private function clientnote(CurrentRoute $currentRoute, ClientNoteRepository $clientnoteRepository): ClientNote|null
    {
        $id = $currentRoute->getArgument('id');       
        $clientnote = $clientnoteRepository->repoClientNotequery($id);
        return $clientnote;
    }
    
    /**
     * @return Response|\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return Response|\Yiisoft\Data\Reader\DataReaderInterface<int, ClientNote>
     */
    private function clientnotes(ClientNoteRepository $clientnoteRepository): \Yiisoft\Data\Reader\DataReaderInterface|Response 
    {
        $clientnotes = $clientnoteRepository->findAllPreloaded();        
        if ($clientnotes === null) {
            return $this->webService->getNotFoundResponse();
        }
        return $clientnotes;
    }
    
    /**
     * @return (\DateTimeImmutable|string)[]
     *
     * @psalm-return array{id: string, client_id: string, date: \DateTimeImmutable, note: string}
     */
    private function body(ClientNote $clientnote): array {
        $body = [
          'id'=>$clientnote->getId(),
          'client_id'=>$clientnote->getClient_id(),
          'date'=>$clientnote->getDate(),
          'note'=>$clientnote->getNote()
        ];
        return $body;
    }
    
    private function flash(SessionInterface $session, string $level, string $message): Flash{
        $flash = new Flash($session);
        $flash->set($level, $message); 
        return $flash;
    }
}
<?php

declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use App\Invoice\Entity\EmailTemplate;
use App\Invoice\EmailTemplate\EmailTemplateRepository;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Invoice\EmailTemplate\EmailTemplateForm;
use App\Invoice\Setting\SettingRepository;

use App\Service\WebControllerService;
use App\User\UserService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Aliases\Aliases;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface as Factory;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

final class EmailTemplateController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private EmailTemplateService $emailtemplateService;
    private UserService $userService;
    private TranslatorInterface $translator;
    private Factory $factory;

    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        EmailTemplateService $emailtemplateService,
        UserService $userService,
        TranslatorInterface $translator,
        Factory $factory
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/emailtemplate')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->emailtemplateService = $emailtemplateService;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->factory = $factory;
    }
    
    /**
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param EmailTemplateRepository $emailtemplateRepository
     * @param SettingRepository $settingRepository
     */
    public function index(SessionInterface $session, CurrentRoute $currentRoute, EmailTemplateRepository $emailtemplateRepository, SettingRepository $settingRepository): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac($session); 
        $parameters = [              
            'paginator' => (new OffsetPaginator($this->emailtemplates($emailtemplateRepository)))
                            ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
                            ->withCurrentPage((int)$currentRoute->getArgument('page', '1')),
            's'=> $settingRepository,
            'alert' => $this->alert($session),
            'canEdit' => $canEdit,
            'email_templates' => $this->emailtemplates($emailtemplateRepository), 
            'flash'=> $this->flash($session,'','')
        ];    
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * 
     * @param ViewRenderer $tag
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param SettingRepository $settingRepository
     * @param CustomFieldRepository $customfieldRepository
     * @param EmailTemplateRepository $emailtemplateRepository
     * @return Response
     */
    public function add(ViewRenderer $tag, Request $request, ValidatorInterface $validator, 
                        SettingRepository $settingRepository, 
                        CustomFieldRepository $customfieldRepository,
                        EmailTemplateRepository $emailtemplateRepository,
                        ): Response
    {
        $parameters = [
            'action' => ['emailtemplate/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,            
            'email_template_tags' => $this->viewRenderer->renderPartialAsString('/invoice/emailtemplate/template-tags', [
                    's'=>$settingRepository,
                    'template_tags_quote'=>$this->viewRenderer->renderPartialAsString('/invoice/emailtemplate/template-tags-quote', [
                        's'=>$settingRepository,
                        'custom_fields_quote_custom'=>$customfieldRepository->repoTablequery('quote_custom'),
                    ]),
                    'template_tags_inv'=>$this->viewRenderer->renderPartialAsString('/invoice/emailtemplate/template-tags-inv', [
                        's'=>$settingRepository,
                        'custom_fields_inv_custom'=>$customfieldRepository->repoTablequery('inv_custom'),
                    ]), 
                    'custom_fields' => [                        
                        'client_custom'=>$customfieldRepository->repoTablequery('client_custom')
                    ],            
            ]),
             //Email templates can be built for either a quote or an invoice.
            'invoice_templates'=>$emailtemplateRepository->get_invoice_templates('pdf'),
            'quote_templates'=>$emailtemplateRepository->get_quote_templates('pdf'),
            'selected_pdf_template'=>'',
            'tag'=>$tag
        ];
        
        if ($request->getMethod() === Method::POST) {
            $form = new EmailTemplateForm();
            if (null!==$this->userService->getUser() && $form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->emailtemplateService->saveEmailTemplate(new EmailTemplate(),$form);
                return $this->webService->getRedirectResponse('emailtemplate/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('__form', $parameters, );
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @return string
     */
    private function alert(SessionInterface $session) : string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash'=>$this->flash($session,'', ''),
            'errors' => [],
        ]);
    }

    /**
     * 
     * @param ViewRenderer $tag
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param EmailTemplateRepository $emailtemplateRepository
     * @param CustomFieldRepository $customfieldRepository
     * @param SettingRepository $settingRepository
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function edit(ViewRenderer $tag, CurrentRoute $currentRoute, Request $request, 
                         EmailTemplateRepository $emailtemplateRepository, 
                         CustomFieldRepository $customfieldRepository,
                         SettingRepository $settingRepository,
                         ValidatorInterface $validator,
    ): Response {
        $email_template = $this->emailtemplate($currentRoute, $emailtemplateRepository);
        if ($email_template) { 
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['emailtemplate/edit', ['email_template_id' => $email_template->getEmail_template_id()]],
                'errors' => [],
                'email_template'=> $email_template,
                'body' => $this->body($email_template),
                'aliases'=> new Aliases(['@invoice' => dirname(__DIR__), '@language' => '@invoice/Language']),
                's'=>$settingRepository,
                'email_template_tags' => $this->viewRenderer->renderPartialAsString('/invoice/emailtemplate/template-tags', [
                        's'=>$settingRepository,
                        'template_tags_quote'=>$this->viewRenderer->renderPartialAsString('/invoice/emailtemplate/template-tags-quote', [
                            's'=>$settingRepository,
                            'custom_fields_quote_custom'=>$customfieldRepository->repoTablequery('quote_custom'),
                        ]),
                        'template_tags_inv'=>$this->viewRenderer->renderPartialAsString('/invoice/emailtemplate/template-tags-inv', [
                            's'=>$settingRepository,
                            'custom_fields_inv_custom'=>$customfieldRepository->repoTablequery('inv_custom'),
                        ]), 
                        'custom_fields' => [                        
                            'client_custom'=>$customfieldRepository->repoTablequery('client_custom')
                        ],            
                ]),    
                'invoice_templates'=>$emailtemplateRepository->get_invoice_templates('pdf'),
                'quote_templates'=>$emailtemplateRepository->get_quote_templates('pdf'),
                'selected_pdf_template'=>$email_template->getEmail_template_pdf_template(),
                'tag'=>$tag
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new EmailTemplateForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->emailtemplateService->saveEmailTemplate($email_template, $form);
                    return $this->webService->getRedirectResponse('emailtemplate/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('__form', $parameters);
        }
        return $this->webService->getRedirectResponse('emailtemplate/index');   
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param EmailTemplateRepository $emailtemplateRepository
     * @return Response
     */
    
    public function delete(CurrentRoute $currentRoute, EmailTemplateRepository $emailtemplateRepository 
    ): Response {       
        $email_template = $this->emailtemplate($currentRoute, $emailtemplateRepository);
        if ($email_template) {
            $this->emailtemplateService->deleteEmailTemplate($email_template);               
            return $this->webService->getRedirectResponse('emailtemplate/index');        
        }
        return $this->webService->getRedirectResponse('emailtemplate/index');        
    }
    
    /**
     * @param Request $request
     * @param EmailTemplateRepository $etR
     */
    public function get_content(Request $request, EmailTemplateRepository $etR) : \Yiisoft\DataResponse\DataResponse {
        //views/invoice/inv/mailer_invoice'
        $get_content = $request->getQueryParams();
        $email_template_id = $get_content['email_template_id'];
        $email_template = $etR->repoEmailTemplateCount((string)$email_template_id) > 0 ? $etR->repoEmailTemplatequery((string)$email_template_id) : null;
        return $this->factory->createResponse(Json::htmlEncode(($email_template ? 
            ['email_template' => [
                'email_template_body' => $email_template->getEmail_template_body(),
                'email_template_subject'=> $email_template->getEmail_template_subject(),
                'email_template_from_name'=> $email_template->getEmail_template_from_name(),
                'email_template_from_email'=> $email_template->getEmail_template_from_email(),
                'email_template_cc'=> $email_template->getEmail_template_cc() ?? '',
                'email_template_bcc'=> $email_template->getEmail_template_bcc() ?? '',
                'email_template_pdf_template'=> null!==$email_template->getEmail_template_pdf_template()?$email_template->getEmail_template_pdf_template(): '',
            ],
            'success'=>1]
            :
            ['success'=>0])));  
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param EmailTemplateRepository $emailtemplateRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute, EmailTemplateRepository $emailtemplateRepository, SettingRepository $settingRepository   
    ): Response {
        $email_template = $this->emailtemplate($currentRoute, $emailtemplateRepository);
        if ($email_template) { 
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['emailtemplate/edit', ['email_template_id' => $email_template->getEmail_template_id()]],
                'errors' => [],
                'emailtemplate'=>$email_template,
                'body' => $this->body($email_template),
                'aliases'=>new Aliases(['@invoice' => dirname(__DIR__), '@language' => '@invoice/Language']),
                's'=>$settingRepository,
            ];
            return $this->viewRenderer->render('__view', $parameters); 
        }
        return $this->webService->getRedirectResponse('emailtemplate/index');
    }
    
    /**
     * @return Response|true
     */
    private function rbac(SessionInterface $session): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('emailtemplate/index');
        }
        return $canEdit;
    }
    
    private function emailtemplate(CurrentRoute $currentRoute, 
                                   EmailTemplateRepository $emailtemplateRepository): object|null {
        $email_template_id = $currentRoute->getArgument('email_template_id');       
        if (null!==$email_template_id) {
            $emailtemplate = $emailtemplateRepository->repoEmailTemplatequery($email_template_id);
            return $emailtemplate;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function emailtemplates(EmailTemplateRepository $emailtemplateRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader {
        $emailtemplates = $emailtemplateRepository->findAllPreloaded();        
        return $emailtemplates;
    }
    
    /**
     * 
     * @param object $emailtemplate
     * @return array
     */
    private function body(object $emailtemplate): array {
        $body = [
                'email_template_title'=>$emailtemplate->getEmail_template_title(),
                'email_template_type'=>$emailtemplate->getEmail_template_type(),
                'email_template_body'=>$emailtemplate->getEmail_template_body(),
                'email_template_subject'=>$emailtemplate->getEmail_template_subject(),
                'email_template_from_name'=>$emailtemplate->getEmail_template_from_name(),
                'email_template_from_email'=>$emailtemplate->getEmail_template_from_email(),
                'email_template_cc'=>$emailtemplate->getEmail_template_cc(),
                'email_template_bcc'=>$emailtemplate->getEmail_template_bcc(),
                'email_template_pdf_template'=>$emailtemplate->getEmail_template_pdf_template(),
        ];
        return $body;
    }
    
    /**
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

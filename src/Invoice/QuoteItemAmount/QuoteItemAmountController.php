<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteItemAmount;

use App\Invoice\Entity\QuoteItemAmount;
use App\Invoice\QuoteItem\QuoteItemRepository;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository;
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

final class QuoteItemAmountController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private QuoteItemAmountService $quoteitemamountService;
    private TranslatorInterface $translator;
        
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        QuoteItemAmountService $quoteitemamountService,
        TranslatorInterface $translator
    )    
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/quoteitemamount')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->quoteitemamountService = $quoteitemamountService;
        $this->translator = $translator;
    }
    
    public function index(SessionInterface $session, QuoteItemAmountRepository $quoteitemamountRepository, SettingRepository $settingRepository, Request $request, QuoteItemAmountService $service): \Yiisoft\DataResponse\DataResponse
    {      
         $canEdit = $this->rbac($session);
         $flash = $this->flash($session, '','');
         $parameters = [
      
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'quoteitemamounts' => $this->quoteitemamounts($quoteitemamountRepository),
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
     * @param QuoteItemRepository $quote_itemRepository
     * @return Response
     */
    public function add(ViewRenderer $head,Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $settingRepository,                        
                        QuoteItemRepository $quote_itemRepository
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['quoteitemamount/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,
            
            'quote_items'=>$quote_itemRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new QuoteItemAmountForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->quoteitemamountService->saveQuoteItemAmount(new QuoteItemAmount(),$form);
                return $this->webService->getRedirectResponse('quoteitemamount/index');
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
     * @param QuoteItemAmountRepository $quoteitemamountRepository
     * @param SettingRepository $settingRepository
     * @param QuoteItemRepository $quote_itemRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                        ValidatorInterface $validator,
                        QuoteItemAmountRepository $quoteitemamountRepository, 
                        SettingRepository $settingRepository,                        
                        QuoteItemRepository $quote_itemRepository
    ): Response {
        $quote_item_amount = $this->quoteitemamount($currentRoute, $quoteitemamountRepository);
        if ($quote_item_amount) {
            $parameters = [
                'title' => 'Edit',
                'action' => ['quoteitemamount/edit', ['id' => $quote_item_amount->getId()]],
                'errors' => [],
                'body' => $this->body($quote_item_amount),
                'head'=>$head,
                's'=>$settingRepository,
                            'quote_items'=>$quote_itemRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new QuoteItemAmountForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->quoteitemamountService->saveQuoteItemAmount($quote_item_amount, $form);
                    return $this->webService->getRedirectResponse('quoteitemamount/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('_form', $parameters);
        } // quote_item_amount    
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param CurrentRoute $currentRoute
     * @param QuoteItemAmountRepository $quoteitemamountRepository
     * @param SettingRepository $sR
     * @return Response
     */
    public function delete(SessionInterface $session, CurrentRoute $currentRoute, QuoteItemAmountRepository $quoteitemamountRepository, SettingRepository $sR 
    ): Response {
        $quote_item_amount = $this->quoteitemamount($currentRoute, $quoteitemamountRepository);
        if ($quote_item_amount) {
            $this->quoteitemamountService->deleteQuoteItemAmount($quote_item_amount);               
            $this->flash($session, 'success', $sR->trans('record_successfully_deleted'));
            return $this->webService->getRedirectResponse('quoteitemamount/index'); 
        }
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param QuoteItemAmountRepository $quoteitemamountRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, QuoteItemAmountRepository $quoteitemamountRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $quote_item_amount = $this->quoteitemamount($currentRoute, $quoteitemamountRepository);
        if ($quote_item_amount) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['quoteitemamount/view', ['id' => $quote_item_amount->getId()]],
                'errors' => [],
                'body' => $this->body($quote_item_amount),
                's'=>$settingRepository,             
                'quoteitemamount'=>$quoteitemamountRepository->repoQuoteItemAmountquery($quote_item_amount->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }   
        
    /**
     * @return Response|true
     */
    private function rbac(SessionInterface $session): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash($session,'warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('quoteitemamount/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param QuoteItemAmountRepository $quoteitemamountRepository
     * @return object|null
     */
    private function quoteitemamount(CurrentRoute $currentRoute, 
                                     QuoteItemAmountRepository $quoteitemamountRepository): object|null 
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $quoteitemamount = $quoteitemamountRepository->repoQuoteItemAmountquery($id);
            return $quoteitemamount;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function quoteitemamounts(QuoteItemAmountRepository $quoteitemamountRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $quoteitemamounts = $quoteitemamountRepository->findAllPreloaded();        
        return $quoteitemamounts;
    }
    
    /**
     * 
     * @param object $quoteitemamount
     * @return array
     */
    private function body(object $quoteitemamount): array {
        $body = [
          'id'=>$quoteitemamount->getId(),
          'quote_item_id'=>$quoteitemamount->getQuote_item_id(),
          'subtotal'=>$quoteitemamount->getSubtotal(),
          'tax_total'=>$quoteitemamount->getTax_total(),
          'discount'=>$quoteitemamount->getDiscount(),
          'total'=>$quoteitemamount->getTotal()
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

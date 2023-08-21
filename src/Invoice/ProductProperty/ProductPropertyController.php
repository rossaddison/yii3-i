<?php

declare(strict_types=1); 

namespace App\Invoice\ProductProperty;

use App\Invoice\Entity\ProductProperty;
use App\Invoice\ProductProperty\ProductPropertyService;
use App\Invoice\ProductProperty\ProductPropertyRepository;

use App\Invoice\Setting\SettingRepository;
use App\Invoice\Product\ProductRepository;
use App\User\UserService;
use App\Service\WebControllerService;

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

use \Exception;

final class ProductPropertyController
{
    private SessionInterface $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private ProductPropertyService $productpropertyService;
    private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        ProductPropertyService $productpropertyService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/productproperty')
                                           // The Controller layout dir is now redundant: replaced with an alias 
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->productpropertyService = $productpropertyService;
        $this->translator = $translator;
    }
    
    public function add(CurrentRoute $currentRoute, ViewRenderer $head, Request $request, 
                        ValidatorInterface $validator,
                        SettingRepository $settingRepository,                        
                        ProductRepository $productRepository
    ) : Response
    {
        $product_id = $currentRoute->getArgument('product_id');
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['productproperty/add',['product_id'=>$product_id]],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'product_id'=>$product_id,
            'head'=>$head,
            
            'products'=>$productRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new ProductPropertyForm();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this->productpropertyService->saveProductProperty(new ProductProperty(),$form);
                return $this->webService->getRedirectResponse('productproperty/index');
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
     * @param ProductProperty $productproperty     * @return array
     */
    private function body(ProductProperty $productproperty) : array {
        $body = [
          'id'=>$productproperty->getProperty_id(),
          'product_id'=>$productproperty->getProduct_id(),
          'name'=>$productproperty->getName(),
          'value'=>$productproperty->getValue()
        ];
        return $body;
    }
        
    public function index(CurrentRoute $currentRoute, ProductPropertyRepository $productpropertyRepository, SettingRepository $settingRepository): Response
    {      
      $page = (int) $currentRoute->getArgument('page', '1');
      $productproperty = $productpropertyRepository->findAllPreloaded();
      $paginator = (new OffsetPaginator($productproperty))
      ->withPageSize((int) $settingRepository->get_setting('default_list_limit'))
      ->withCurrentPage($page)
      ->withNextPageToken((string) $page);
      $parameters = [
      'productpropertys' => $this->productpropertys($productpropertyRepository),
      'paginator' => $paginator,
      'alerts' => $this->alert(),
      'max' => (int) $settingRepository->get_setting('default_list_limit'),
      'grid_summary' => $settingRepository->grid_summary($paginator, $this->translator, (int) $settingRepository->get_setting('default_list_limit'), $this->translator->translate('plural'), ''),
    ];
    return $this->viewRenderer->render('/invoice/productproperty/index', $parameters);
    }
        
    /**
     * 
     * @param SettingRepository $settingRepository
     * @param CurrentRoute $currentRoute
     * @param ProductPropertyRepository $productpropertyRepository
     * @return Response
     */
    public function delete(SettingRepository $settingRepository, CurrentRoute $currentRoute,ProductPropertyRepository $productpropertyRepository 
    ): Response {
        try {
            $productproperty = $this->productproperty($currentRoute, $productpropertyRepository);
            if ($productproperty) {
                $this->productpropertyService->deleteProductProperty($productproperty);               
                $this->flash('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->webService->getRedirectResponse('productproperty/index'); 
            }
            return $this->webService->getRedirectResponse('productproperty/index'); 
	} catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('productproperty/index'); 
        }
    }
        
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute, 
                        ValidatorInterface $validator,
                        ProductPropertyRepository $productpropertyRepository, 
                        SettingRepository $settingRepository,                        
                        ProductRepository $productRepository
    ): Response {
        $productproperty = $this->productproperty($currentRoute, $productpropertyRepository);
        if ($productproperty){
            $parameters = [
                'title' => $this->translator->translate('invoice.edit'),
                'action' => ['productproperty/edit', ['id' => $productproperty->getProperty_id()]],
                'errors' => [],
                'body' => $this->body($productproperty),
                'head'=>$head,
                's'=>$settingRepository,
                'products'=>$productRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new ProductPropertyForm();
                $body = $request->getParsedBody();
                if ($form->load($body) && $validator->validate($form)->isValid()) {
                    $this->productpropertyService->saveProductProperty($productproperty,$form);
                    return $this->webService->getRedirectResponse('productproperty/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = $form->getFormErrors();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('productproperty/index');
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
     * @param ProductPropertyRepository $productpropertyRepository
     * @return ProductProperty|null
     */
    private function productproperty(CurrentRoute $currentRoute,ProductPropertyRepository $productpropertyRepository) : ProductProperty|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $productproperty = $productpropertyRepository->repoProductPropertyLoadedquery($id);
            return $productproperty;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function productpropertys(ProductPropertyRepository $productpropertyRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $productproperties = $productpropertyRepository->findAllPreloaded();        
        return $productproperties;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param ProductPropertyRepository $productpropertyRepository
     * @param SettingRepository $settingRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute,ProductPropertyRepository $productpropertyRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $productproperty = $this->productproperty($currentRoute, $productpropertyRepository); 
        if ($productproperty) {
          $parameters = [
            'title' => $settingRepository->trans('view'),
            'action' => ['productproperty/view', ['id' => $productproperty->getProperty_id()]],
            'errors' => [],
            'body' => $this->body($productproperty),
            'productproperty'=>$productproperty,
        ];        
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('productproperty/index');
    }
}


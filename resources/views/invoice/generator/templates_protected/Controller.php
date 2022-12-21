<?php
   echo "<?php\n";             
?>

declare(strict_types=1); 

namespace <?= $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>;

use <?= $generator->getNamespace_path(). DIRECTORY_SEPARATOR. 'Entity'. DIRECTORY_SEPARATOR. $generator->getCamelcase_capital_name(); ?>;
use <?= $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>Service;
use <?= $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>Repository;

<?php 
  if (!empty($generator->getRepo_extra_camelcase_name())) {
    echo 'use ' . $generator->getNamespace_path() .DIRECTORY_SEPARATOR.$generator->getRepo_extra_camelcase_name().DIRECTORY_SEPARATOR.$generator->getRepo_extra_camelcase_name() . 'Repository;'."\n"; 
  }
  foreach ($relations as $relation) { 
    echo 'use ' . $generator->getNamespace_path() .DIRECTORY_SEPARATOR. $relation->getCamelcase_name().DIRECTORY_SEPARATOR.$relation->getCamelcase_name() .'Repository;'."\n"; 
  } 
?>
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
<?php 
  if (!empty($generator->isOffset_paginator_include())) {
    echo 'use Yiisoft\Data\Paginator\OffsetPaginator;'."\n"; 
  }
?>

final class <?= $generator->getCamelcase_capital_name(); ?>Controller
{
    private SessionInterface $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private <?= $generator->getCamelcase_capital_name(); ?>Service $<?= $generator->getSmall_singular_name(); ?>Service;
    <?php if ($generator->isOffset_paginator_include()) {
            echo 'private const '.strtoupper($generator->getSmall_plural_name())."_PER_PAGE = 1;"."\n";
          }
    ?>
    private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        <?= $generator->getCamelcase_capital_name(); ?>Service $<?= $generator->getSmall_singular_name(); ?>Service,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->viewRenderer = $viewRenderer->withControllerName('<?= $generator->getRoute_prefix().'/'.$generator->getRoute_suffix(); ?>')
                                           // The Controller layout dir is now redundant: replaced with an alias 
                                           ->withLayout('@<?= $generator->getController_layout_dir_dot_path() ?>');
        $this->webService = $webService;
        $this->userService = $userService;
        $this-><?= $generator->getSmall_singular_name(); ?>Service = $<?= $generator->getSmall_singular_name(); ?>Service;
        $this->translator = $translator;
    }
    
    public function index(SessionInterface $session, <?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name(); ?>Repository, <?= $generator->getRepo_extra_camelcase_name(); ?>Repository $<?= strtolower($generator->getRepo_extra_camelcase_name()); ?>Repository, Request $request, <?= $generator->getCamelcase_capital_name(); ?>Service $service): Response
    {      
      <?php
              echo '         $flash = $this->flash($session, '."''"." , '');"."\n";
              echo '         $parameters = ['."\n";
      ?>      
      <?php if ($generator->getRepo_extra_camelcase_name()) {
           echo "\n";
           echo "          '".$generator->getSmall_plural_name()."'".' => $'.'this->'.$generator->getSmall_plural_name().'($'.$generator->getSmall_singular_name().'Repository),'."\n"; 
           echo "          'flash'=> ".'$flash'."\n";
           echo "         ];"."\n";
           echo "\n";
        }
      ?>
        
        return $this->viewRenderer->render('index', $parameters);
    }
    
    public function index_adv_paginator(SessionInterface $session, <?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name(); ?>Repository, <?= $generator->getRepo_extra_camelcase_name(); ?>Repository $<?= strtolower($generator->getRepo_extra_camelcase_name()); ?>Repository, CurrentRoute $currentRoute, <?= $generator->getCamelcase_capital_name(); ?>Service $service): Response
    {
      <?php if ($generator->isKeyset_paginator_include()) { 
            echo "\n";
            echo '        $paginator = $service->getFeedPaginator();'."\n";
            echo '        if ($currentRoute->getArgument('."'".$generator->getPaginator_next_page_attribute()."') !== null) {"."\n";
            echo '         $paginator = $paginator->withNextPageToken((string)$currentRoute->getArgument('."'".$generator->getPaginator_next_page_attribute()."'));"."\n";
            }
      ?>
      <?php if ($generator->isOffset_paginator_include()) { 
            echo "\n";
            echo '        $pageNum = (int)$currentRoute->getArgument('."'".'page'."', '1');"."\n";
            echo '        $paginator = (new OffsetPaginator($this->'.$generator->getSmall_plural_name().'($'.$generator->getSmall_singular_name().'Repository)))'."\n";
            echo '        ->withPageSize(self::'.strtoupper($generator->getSmall_plural_name())."_PER_PAGE)"."\n";
            echo '        ->withCurrentPage($pageNum);'."\n";
            }
      ?>
      <?php
            echo "\n";        
            echo '        $flash = $this->flash($session, '."''"." , '');"."\n";
            echo '        $parameters = ['."\n";            
      ?>
      <?php if (($generator->isKeyset_paginator_include()) || ($generator->isOffset_paginator_include())) {
            echo "        'paginator' => ".'$paginator,'."\n";
      } ?>  
      <?php if ($generator->getRepo_extra_camelcase_name()) {  
            echo "        's'=>". '$'.lcfirst($generator->getRepo_extra_camelcase_name()).'Repository,'."\n";
      } ?>
      <?php 
            echo "        '".$generator->getSmall_plural_name()."'".' => $'.'this->'.$generator->getSmall_plural_name().'($'.$generator->getSmall_singular_name().'Repository),'."\n"; 
            echo "        'flash'=> ".'$flash'."\n";
            echo "      ];"."\n";
            echo "\n";
      ?>      
      <?php if ($generator->isKeyset_paginator_include()) { 
            echo '       if ($this->isAjaxRequest($request)) {'."\n";
            echo '         return $this->viewRenderer->renderPartial('."'".$generator->getSmall_plural_name()."'". ', ['."'".'data'."'".' => $paginator]);'."\n";
            echo '}'."\n";
      }
      ?>
      <?php 
            echo "\n";
            echo '        return $this->viewRenderer->render('."'index'".', $parameters);'."\n";
      ?>  
    }
    
    public function add(ViewRenderer $head,SessionInterface $session, Request $request, 
                        ValidatorInterface $validator,
                        <?php if ($generator->getRepo_extra_camelcase_name()) {  
                            echo $generator->getRepo_extra_camelcase_name().'Repository '. '$'.lcfirst($generator->getRepo_extra_camelcase_name()).'Repository,';
                        }    
                        ?>
                        <?php
                        $rel = '';
                        echo "\n";
                        foreach ($relations as $relation) {
                            $rel .= '                        '.$relation->getCamelcase_name().'Repository $'.$relation->getLowercase_name().'Repository,'."\n";
                        }
                        echo rtrim($rel,",\n")."\n";        
                        ?>
    ) : Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['<?= $generator->getSmall_singular_name(); ?>/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            <?php if ($generator->getRepo_extra_camelcase_name()) {  
                echo "'s'=>". '$'.lcfirst($generator->getRepo_extra_camelcase_name()).'Repository,'."\n";
            }
            ?>
            'head'=>$head,
            <?php echo "\n";
            foreach ($relations as $relation) {
                echo "            '".$relation->getLowercase_name()."s'=>".'$'.$relation->getLowercase_name().'Repository->findAllPreloaded(),'."\n";
            }
            ?>
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new <?= $generator->getCamelcase_capital_name(); ?>Form();
            if ($form->load($parameters['body']) && $validator->validate($form)->isValid()) {
                $this-><?= $generator->getSmall_singular_name(); ?>Service->save<?= $generator->getCamelcase_capital_name(); ?>(new <?= $generator->getCamelcase_capital_name(); ?>(),$form);
                return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name(); ?>/index');
            }
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    public function edit(ViewRenderer $head, SessionInterface $session, Request $request, CurrentRoute $currentRoute, 
                        ValidatorInterface $validator,
                        <?php if ($generator->getCamelcase_capital_name()) {  
                            echo $generator->getCamelcase_capital_name().'Repository '. '$'.$generator->getSmall_singular_name().'Repository,';
                        }
                        ?> 
                        <?php if ($generator->getRepo_extra_camelcase_name()) {  
                            echo $generator->getRepo_extra_camelcase_name().'Repository '. '$'.strtolower($generator->getRepo_extra_camelcase_name()).'Repository,';
                        }
                        ?>
                        <?php
                        $rel = '';
                        echo "\n";
                        foreach ($relations as $relation) {
                            $rel .= '                        '.$relation->getCamelcase_name().'Repository $'.$relation->getLowercase_name().'Repository,'."\n";
                        }
                        echo rtrim($rel,",\n")."\n";
                        ?>
    ): Response {
        $parameters = [
            'title' => $settingRepository->trans('edit'),
            'action' => ['<?= $generator->getSmall_singular_name(); ?>/edit', ['id' => $this-><?= $generator->getSmall_singular_name();?>($currentRoute, $<?= $generator->getSmall_singular_name(); ?>Repository)->getId()]],
            'errors' => [],
            'body' => $this->body($this-><?= $generator->getSmall_singular_name();?>($currentRoute, $<?= $generator->getSmall_singular_name();?>Repository)),
            'head'=>$head,
            <?php if ($generator->getRepo_extra_camelcase_name()) {  
                 echo "'s'=>". '$'.lcfirst($generator->getRepo_extra_camelcase_name()).'Repository,'."\n";
            }
            ?>
            <?php
                $rel = '';
                foreach ($relations as $relation) {
                  $rel .= "            '".$relation->getLowercase_name()."s'=>".'$'.$relation->getLowercase_name().'Repository->findAllPreloaded(),'."\n";
                }
                echo rtrim($rel,",\n")."\n";
            ?>
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new <?= $generator->getCamelcase_capital_name(); ?>Form();
            $body = $request->getParsedBody();
            if ($form->load($body) && $validator->validate($form)->isValid()) {
                $this-><?= $generator->getSmall_singular_name();?>Service->save<?= $generator->getCamelcase_capital_name(); ?>($this-><?= $generator->getSmall_singular_name();?>($currentRoute,$<?= $generator->getSmall_singular_name();?>Repository), $form);
                return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name(); ?>/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = $form->getFormErrors();
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    public function delete(SessionInterface $session, SettingRepository $settingRepository, CurrentRoute $currentRoute,<?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name();?>Repository 
    ): Response {
        try {
            $this-><?= $generator->getSmall_singular_name();?>Service->delete<?= $generator->getCamelcase_capital_name(); ?>($this-><?= $generator->getSmall_singular_name();?>($currentRoute,$<?= $generator->getSmall_singular_name();?>Repository));               
            $this->flash('info', $settingRepository->trans('record_successfully_deleted');
            return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name();?>/index'); 
	} catch (Exception $e) {
            $this->flash($session, 'danger', $e->getMessage());
            return $this->webService->getRedirectResponse('<?= $generator->getSmall_singular_name();?>/index'); 
        }
    }
    
    public function view(SessionInterface $session, CurrentRoute $currentRoute,<?= $generator->getCamelcase_capital_name(); ?>Repository $<?= $generator->getSmall_singular_name();?>Repository,
        <?php if ($generator->getRepo_extra_camelcase_name()) {  
            echo $generator->getRepo_extra_camelcase_name().'Repository '. '$'.strtolower($generator->getRepo_extra_camelcase_name()).'Repository,'."\n";
        }
        ?>
        ): Response {
        $parameters = [
            'title' => $settingRepository->trans('view'),
            'action' => ['<?= $generator->getSmall_singular_name(); ?>/view', ['id' => $this-><?= $generator->getSmall_singular_name();?>($currentRoute, $<?= $generator->getSmall_singular_name();?>Repository)->getId()]],
            'errors' => [],
            'body' => $this->body($this-><?= $generator->getSmall_singular_name();?>($currentRoute, $<?= $generator->getSmall_singular_name();?>Repository)),
            '<?= $generator->getSmall_singular_name();?>'=>$<?= $generator->getSmall_singular_name();?>Repository->repo<?= $generator->getCamelcase_capital_name();?>query($this-><?= $generator->getSmall_singular_name();?>($request, $<?= $generator->getSmall_singular_name();?>Repository)->getId()),
        ];
        return $this->viewRenderer->render('_view', $parameters);
    }
    
    //For rbac refer to AccessChecker    
    
     /**
     * @param CurrentRoute $currentRoute
     * @param <?= $generator->getCamelcase_capital_name();?>Repository $<?= $generator->getSmall_singular_name();?>Repository
     * @return <?= $generator->getCamelcase_capital_name();?>|null
     */
    private function <?= $generator->getSmall_singular_name();?>(CurrentRoute $currentRoute,<?= $generator->getCamelcase_capital_name();?>Repository $<?= $generator->getSmall_singular_name();?>Repository) : <?= $generator->getCamelcase_capital_name();?>|null
    {
        $id = $currentRoute->getArgument('id');       
        $<?= $generator->getSmall_singular_name();?> = $<?= $generator->getSmall_singular_name();?>Repository->repo<?= $generator->getCamelcase_capital_name();?>query($id);
        return $<?= $generator->getSmall_singular_name();?>;
    }

    /**
     * @return Response|\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return Response|\Yiisoft\Data\Reader\DataReaderInterface<int,<?= $generator->getCamelcase_capital_name();?> >
     */
    private function <?= $generator->getSmall_plural_name();?>(<?= $generator->getCamelcase_capital_name();?>Repository $<?= $generator->getSmall_singular_name();?>Repository) : \Yiisoft\Data\Reader\DataReaderInterface|Response
    {
        $<?= $generator->getSmall_plural_name();?> = $<?= $generator->getSmall_singular_name();?>Repository->findAllPreloaded();        
        if ($<?= $generator->getSmall_plural_name();?> === null) {
            return $this->webService->getNotFoundResponse();
        }
        return $<?= $generator->getSmall_plural_name();?>;
    }
    
    private function body($<?= $generator->getSmall_singular_name();?>) {
        $body = [
                <?php
                  echo "\n";
                  $bo = '';
                    foreach ($orm_schema->getColumns() as $column) {
                    $bo .= "          '".$column->getName()."'=>$".$generator->getSmall_singular_name()."->get".ucfirst($column->getName())."(),\n";
                  }
                  echo rtrim($bo,",\n")."\n";        
                ?>
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
}


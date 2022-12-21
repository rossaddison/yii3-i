<?php
declare(strict_types=1);

use App\Invoice\Asset\InvoiceAsset;
use App\Invoice\Asset\MonospaceAsset;

// DatePicker Assets available for dropdown locale/cldr selection
use App\Invoice\Asset\i18nAsset\ar_Asset;
use App\Invoice\Asset\i18nAsset\en_GB_Asset;
use App\Invoice\Asset\i18nAsset\id_Asset;
use App\Invoice\Asset\i18nAsset\ja_Asset;
use App\Invoice\Asset\i18nAsset\nl_Asset;
use App\Invoice\Asset\i18nAsset\ru_Asset;
use App\Invoice\Asset\i18nAsset\zh_CN_Asset;
// PCI Compliant Payment Gateway Assets
use App\Invoice\Asset\pciAsset\stripe_v10_Asset;
use App\Invoice\Asset\pciAsset\amazon_pay_v2_4_Asset;
use App\Invoice\Asset\pciAsset\braintree_dropin_1_33_7_Asset;

use App\Asset\AppAsset;
use App\Widget\PerformanceMetrics;

use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Yii\Bootstrap5\Nav;
use Yiisoft\Yii\Bootstrap5\NavBar;
use Yiisoft\Yii\Bootstrap5\Offcanvas;

/**
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\Router\CurrentRoute $currentRoute
 * @var \Yiisoft\View\WebView $this
 * @var \Yiisoft\Assets\AssetManager $assetManager
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Config\Config $config
 * @var \Yiisoft\Config\ConfigPaths $configPaths
 * @var \App\Invoice\Setting\SettingRepository $s
 * @var \App\Invoice\Helpers\DateHelper $datehelper
 * @see \App\ApplicationViewInjection
 * @var \App\User\User|null $user
 * @var string $csrf 
 * @var string $content
 * @var string $brandLabel
 */

$assetManager->register(AppAsset::class);
$assetManager->register(InvoiceAsset::class);
$assetManager->register(Yiisoft\Yii\Bootstrap5\Assets\BootstrapAsset::class);
$s->get_setting('monospace_amounts') == 1 ? $assetManager->register(MonospaceAsset::class) : '';
// '0' => PCI Compliant version 
$s->get_setting('gateway_stripe_version') == '0' ? $assetManager->register(stripe_v10_Asset::class) : '';
$s->get_setting('gateway_amazon_pay_version') == '0' ? $assetManager->register(amazon_pay_v2_4_Asset::class) : '';
$s->get_setting('gateway_braintree_version') == '0' ? $assetManager->register(braintree_dropin_1_33_7_Asset::class) : '';
// The InvoiceController/index receives the $session->get('_language') or 'drop-down' locale user selection and saves it into a setting called 'cldr'
// The $s value is configured for the layout in config/params.php yii-soft/view Reference::to and NOT by means of the InvoiceController

switch ($session->get('_language') ?? $session->set('_language','en')) {
    case 'ar' : $assetManager->register(ar_Asset::class); $locale = 'Arabic'; break;
    case 'en' : $assetManager->register(en_GB_Asset::class); $locale = 'English'; break;
    case 'id' : $assetManager->register(id_Asset::class); $locale = 'Indonesian'; break;
    case 'ja' : $assetManager->register(ja_Asset::class); $locale = 'Japanese'; break;
    case 'nl' : $assetManager->register(nl_Asset::class); $locale = 'Dutch'; break;
    case 'ru' : $assetManager->register(ru_Asset::class); $locale = 'Russian'; break;
    case 'zh' : $assetManager->register(zh_CN_Asset::class); $locale = 'Chinese Simplified'; break;
    default   : $assetManager->register(en_GB_Asset::class); $locale = 'English'; break;
}

// If the dropdown locale has not been set on login => use the cldr setting value. If the cldr does not exist => use the 'en' value
$s->save_session_locale_to_cldr($session->get('_language') ?? ($s->get_setting('cldr') ?: 'en'));

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());

$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$currentRouteName = $currentRoute->getName() ?? '';

$isGuest = $user === null || $user->getId() === null;

$xdebug = extension_loaded('xdebug') ? 'php.ini zend_extension Installed : Performance compromised!' : 'php.ini zend_extension Commented out: Performance NOT compromised';

// Platform, Performance, and Clear Assets Cache, and links Menu will disappear if set to false;
$debug_mode = true;

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= $s->get_setting('cldr') !== $session->get('_language')  ? $session->get('_language') : $s->get_setting('cldr'); ?>">
<head>
    <?= Meta::documentEncoding('utf-8')?>
    <?= Meta::pragmaDirective('X-UA-Compatible', 'IE=edge') ?>
    <?= Meta::data('viewport', 'width=device-width, initial-scale=1') ?>
    <title>
        <?= $s->get_setting('custom_title') ?: 'Yii-Invoice'; ?>
    </title>
    <?php $this->head() ?>    
</head>
<body>
<?php
   Html::tag('Noscript',Html::tag('Div',$s->trans('please_enable_js'),['class'=>'alert alert-danger no-margin']));
?>
<?php
$this->beginBody();

$offcanvas = new Offcanvas();

echo NavBar::widget()
      // public folder ie. public/yii3_sign  
      ->brandImage('/favicon')
      ->brandImageAttributes(['width'=>40,'height'=>20])
      ->brandUrl($urlGenerator->generate('invoice/index'))
      ->offCanvas(
          // If not full screen => 'burger icon ie. 3 horizontal lines' represents menu and
          // navbar moves in from left
          $offcanvas->title($s->get_setting('custom_title') ?: 'Yii-Invoice')
      )
      ->begin();

echo Nav::widget()
        ->currentPath($currentRoute->getUri()->getPath())        
        ->options(['class'=>'navbar fs-4']) 
        ->items( 
            $isGuest
                ? [] :
            [               
                    ['label' => $s->trans('dashboard'),'url'=>$urlGenerator->generate('invoice/dashboard')],
                    ['label' => $translator->translate('invoice.client'), 
                     'items' => [
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),'url'=>$urlGenerator->generate('client/index')],                                
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.client.note.add'),'url'=>$urlGenerator->generate('clientnote/add')],
                               ],
                    ],
                    ['label' => $translator->translate('invoice.quote'), 
                     'items' => [
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),'url'=>$urlGenerator->generate('quote/index')],
                               ],
                    ],
                    ['label' => $translator->translate('invoice.invoice'), 
                     'items' => [
                                ['options'=>['class'=>'nav fs-4'], 'label' => $translator->translate('invoice.view'),'url'=>$urlGenerator->generate('inv/index')],
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.recurring'),'url'=>$urlGenerator->generate('invrecurring/index')], 
                               ],
                    ],
                    ['label' => $translator->translate('invoice.payment'), 
                     'items' => [
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.enter'),'url'=>$urlGenerator->generate('payment/add')],
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),'url'=>$urlGenerator->generate('payment/index')],
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.online.log'),'url'=>$urlGenerator->generate('payment/online_log')] 
                               ],
                    ],
                    ['label' => $translator->translate('invoice.product'), 
                     'items' => [
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.create'),'url'=>$urlGenerator->generate('product/add')],
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),'url'=>$urlGenerator->generate('product/index')],
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.family'),'url'=>$urlGenerator->generate('family/index')],
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.unit'),'url'=>$urlGenerator->generate('unit/index')],
                               ],
                    ],
                    ['label' => $translator->translate('invoice.task'), 
                     'items' => [
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.create'),'url'=>$urlGenerator->generate('task/add')],
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),'url'=>$urlGenerator->generate('task/index')],
                               ],
                    ],
                    ['label' => $translator->translate('invoice.project'), 
                     'items' => [
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.create'),'url'=>$urlGenerator->generate('project/add')],
                                ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),'url'=>$urlGenerator->generate('project/index')],
                               ],
                    ],
                    ['label' => $translator->translate('invoice.report'), 
                     'items' => [
                                ['options'=>['class'=>'nav fs-4'],'label' => $s->trans('sales_by_client'),'url'=>$urlGenerator->generate('report/sales_by_client_index')],
                                ['options'=>['class'=>'nav fs-4'],'label' => $s->trans('sales_by_date'), 'url' =>$urlGenerator->generate('report/sales_by_year_index')],
                                ['options'=>['class'=>'nav fs-4'],'label' => $s->trans('payment_history'), 'url' =>$urlGenerator->generate('report/payment_history_index')],
                                ['options'=>['class'=>'nav fs-4'],'label' => $s->trans('invoice_aging'), 'url' =>$urlGenerator->generate('report/invoice_aging_index')],
                               ],
                    ],
                    ['label' => $translator->translate('invoice.setting'), 
                     'items' => [['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),'options'=>['style'=>'background-color: #ffcccb'],'url'=>$urlGenerator->generate('setting/debug_index'),'visible'=>$debug_mode],
                                 ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.setting.add'),'options'=>['style'=>'background-color: #ffcccb'], 'url'=>$urlGenerator->generate('setting/add'),'visible'=>$debug_mode],    
                                 ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),'url'=>$urlGenerator->generate('setting/tab_index')],                         
                                 ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.email.template'),'url'=>$urlGenerator->generate('emailtemplate/index')],
                                 ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.custom.field'),'url'=>$urlGenerator->generate('customfield/index')],
                                 ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.group'),'url'=>$urlGenerator->generate('group/index')],
                                 ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.archive'),'url'=>$urlGenerator->generate('inv/archive')],
                                 ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.payment.method'),'url'=>$urlGenerator->generate('paymentmethod/index')],   
                                 ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.tax.rate'),'url'=>$urlGenerator->generate('taxrate/index')],
                                 ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.user.account'),'url'=>$urlGenerator->generate('userinv/index')],
                                 ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.setting.company'),'url'=>$urlGenerator->generate('company/index')],
                                 ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.setting.company.private'),'url'=>$urlGenerator->generate('companyprivate/index')],
                                 ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.setting.company.profile'),'url'=>$urlGenerator->generate('profile/index')],
                               ],
                    ],
                    ['label' => $translator->translate('invoice.platform'), 'options'=>['style'=>'background-color: #ffcccb'],'visible'=>$debug_mode,
                     'items' => [
                                 ['label' => 'WAMP'],
                                 ['label' => $translator->translate('invoice.platform.editor'). ': Apache Netbeans 12.4 64 bit'], 
                                 ['label' => $translator->translate('invoice.platform.server'). ': Wampserver 3.2.9 64 bit'],
                                 ['label' => 'Apache: 2.4.54 64 bit'],
                                 ['label' => $translator->translate('invoice.platform.mySqlVersion'). ': 5.7.31 || 8.0.30 '],
                                 ['label' => $translator->translate('invoice.platform.windowsVersion'). ': Windows 10 Home Edition'],
                                 ['label' => $translator->translate('invoice.platform.PhpVersion'). ': 8.1.11 (Compatable with PhpAdmin 5.2.0)'],
                                 ['label' => $translator->translate('invoice.platform.PhpMyAdmin'). ': 5.2.0 (Compatable with php 8.1.12)'],
                                 ['label' => $translator->translate('invoice.platform.PhpSupport'), 'url'=>'https://php.net/supported-versions'],
                                 ['label' => $translator->translate('invoice.platform.update'), 'url'=>'https://wampserver.aviatechno.net/'], 
                                 ['label' => $translator->translate('invoice.vendor.nikic.fast-route'), 'url'=>'https://github.com/nikic/FastRoute'],
                                 ['label' => $translator->translate('invoice.platform.netbeans.UTF-8'), 'url'=>'https://stackoverflow.com/questions/59800221/gradle-netbeans-howto-set-encoding-to-utf-8-in-editor-and-compiler'],
                                 ['label' => $translator->translate('invoice.platform.csrf'), 'url'=>'https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#use-of-custom-request-headers'],
                                 ['label' => 'LAMP'],
                                 ['label' => $translator->translate('invoice.platform.editor'). ': Apache Netbeans 12.4 64 bit'], 
                                 ['label' => $translator->translate('invoice.platform.server'). ': Ubuntu LTS 22.04 64 bit'],
                                 ['label' => 'Apache: 2.4.52 64 bit'],
                                 ['label' => $translator->translate('invoice.platform.mySqlVersion'). ': 5.7.31 || 8.0.29 '],
                                 ['label' => $translator->translate('invoice.platform.PhpVersion'). ': 8.1.2 (Compatable with PhpAdmin 5.2.0)'],
                                 ['label' => $translator->translate('invoice.platform.PhpMyAdmin'). ': 5.2.0 (Compatable with php 8.1.2)'],
                                 ['label' => $translator->translate('invoice.development.progress'), 'url'=>$urlGenerator->generate('invoice/ubuntu')],
                               ],
                     ],   
                     ['label' => $translator->translate('invoice.performance'),  'options'=>['style'=>'background-color: #ffcccb',],'visible'=>$debug_mode,
                     'items' => [
                                 ['label' => $translator->translate('invoice.platform.xdebug'). ' '.$xdebug, 'options'=>['class'=>'nav fs-4','data-toggle'=>'tooltip','title'=>'Via Wampserver Menu: Icon..Php 8.1.8-->Php extensions-->xdebug 3.1.5(click)-->Allow php command prompt to restart automatically-->(click)Restart All Services-->No typing in or editing of a php.ini file!!']],  
                                 ['label' => '...config/params.php SyncTable currently commented out and PhpFileSchemaProvider::MODE_READ_AND_WRITE'],
                                 ['label' => 'php.ini: opcache.memory_consumption=128'],
                                 ['label' => 'php.ini: oopcache.interned_strings_buffer=8'],
                                 ['label' => 'php.ini: opcache.max_accelerated_files=4000'],
                                 ['label' => 'php.ini: opcache.revalidate_freq=60'],
                                 ['label' => 'php.ini: opcache.enable=1'],
                                 ['label' => 'php.ini: opcache.enable_cli=1'],
                                 ['label' => 'config.params: yiisoft/yii-debug: enabled , disable for improved performance'], 
                                 ['label' => 'config.params: yiisoft/yii-debug-api: enabled, disable for improved performance'],
                               ],
                    ],
                     ['label' => $translator->translate('invoice.generator'),  'options'=>['style'=>'background-color: #ffcccb'],'visible'=>$debug_mode,
                     'items' => [
                                   ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.generator'),'url'=>$urlGenerator->generate('generator/index')],
                                   ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.generator.add'),'url'=>$urlGenerator->generate('generator/add')],
                                   ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.generator.relations.add'),'url'=>$urlGenerator->generate('generatorrelation/add')],                                                                    
                                   ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.development.progress'),'url'=>$urlGenerator->generate('invoice/index')],
                                   ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.development.schema'),'url'=>$urlGenerator->generate('generator/quick_view_schema')],
                                   ['label' => $translator->translate('invoice.test.reset.setting'),'url'=>$urlGenerator->generate('invoice/setting_reset'),
                                    'options'=>['class'=>'nav fs-4','data-toggle'=>'tooltip','title'=>$translator->translate('invoice.test.reset.setting.tooltip')]],
                                   ['label' => $translator->translate('invoice.test.reset'),'url'=>$urlGenerator->generate('invoice/test_data_reset'),
                                    'options'=>['class'=>'nav fs-4','data-toggle'=>'tooltip','title'=>$translator->translate('invoice.test.reset.tooltip')]],
                                   ['label' => $translator->translate('invoice.test.remove'),'url'=>$urlGenerator->generate('invoice/test_data_remove'),
                                    'options'=>['class'=>'nav fs-4','data-toggle'=>'tooltip','title'=>$translator->translate('invoice.test.remove.tooltip')]]
                                   
                               ],
                    ],
                    ['label' => $translator->translate('invoice.utility.assets.clear'),
                     'url'=>$urlGenerator->generate('setting/clear'),'options'=>['class'=>'nav fs-4','data-toggle'=>'tooltip', 
                     'title'=>'Clear the assets cache which resides in /public/assets.','style'=>'background-color: #ffcccb'],
                     'visible'=>$debug_mode],
                    ['label' => $translator->translate('invoice.debug'),
                     'url'=>'',
                     'options'=>['class'=>'nav fs-4','data-toggle'=>'tooltip', 'title'=>'Disable in views/layout/invoice.php. Red background links and menus will disappear.','style'=>'background-color: #ffcccb'],
                     'visible'=>$debug_mode],
                    ['label' => 'Locale => '. $locale,   
                     'url'=>'',
                     'options'=>['class'=>'nav fs-4','data-toggle'=>'tooltip', 'title'=>'Storage: session/runtime file.','style'=>'background-color: #90EE90'],
                     'visible'=>$debug_mode],
                    ['label' => 'cldr => '. $s->get_setting('cldr'),   
                     'url'=>'',
                     'options'=>['class'=>'nav fs-4','data-toggle'=>'tooltip', 'title'=>'Storage: database','style'=>'background-color: #ffffe0'],
                     'visible'=>$debug_mode], 
            ]       
        );

echo Nav::widget()
    ->currentPath($currentRoute
        ->getUri()
        ->getPath())
    ->options(['class' => 'navbar-nav'])
    ->items(
        [
            [
                'label' => $translator->translate('menu.language'),
                'url' => '#',
                'items' => [
                    [
                        'label' => 'English',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'en'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Ру�?�?кий',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'ru'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Slovenský',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'sk'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Indonesia',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'id'], fallbackRouteName: 'site/index'),
                    ],
                ],
            ],
            [
                'label' => $translator->translate('menu.login'),
                'url' => $urlGenerator->generate('auth/login'),
                'visible' => $isGuest,
            ],
            [
                'label' => $translator->translate('menu.signup'),
                'url' => $urlGenerator->generate('auth/signup'),
                'visible' => $isGuest,
            ],
            $isGuest ? '' : Form::tag()
                    ->post($urlGenerator->generate('auth/logout'))
                    ->csrf($csrf)
                    ->open()
                . '<div class="mb-1">'
                . Button::submit(
                    $translator->translate('menu.logout', ['login' => Html::encode($user->getLogin())])
                )
                    ->class('btn btn-primary')
                . '</div>'
                . Form::tag()->close(),
        ],
);

echo NavBar::end();
?>
    
<div id="main-area">
    <?php
        // Display the sidebar if enabled
        if ($s->get_setting('disable_sidebar') !== (string)1) {
            include dirname(__DIR__).'/invoice/layout/sidebar.php';
        }
    ?>
    <main class="container py-4">
        <?php echo $content; ?>
    </main>

</div>
<footer class="container py-4">
    <?= PerformanceMetrics::widget() ?>
</footer> 
    <?php
        $this->endBody();
    ?>
</body>
</html>
 
<?php             
    // https://api.jqueryui.com/datepicker
    $js1 = "$(function () {".
       '$(".form-control.input-sm.datepicker").datepicker({dateFormat:"'.$datehelper->datepicker_dateFormat()
                                                        .'", firstDay:'.$datehelper->datepicker_firstDay()
                                                        .', changeMonth: true'
                                                        .', changeYear: true'
                                                        .', yearRange: "-110:+10"'
                                                        .', clickInput: true'
                                                        .', constrainInput: false'
                                                        .', highlightWeek: true'
                                                        .' });'.
    '});';
    echo Html::script($js1)->type('module');
?>
<?php
   $this->endPage(true);     
?>


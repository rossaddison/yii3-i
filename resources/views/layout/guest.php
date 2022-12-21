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

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Meta;

use Yiisoft\Yii\Bootstrap5\Nav;
use Yiisoft\Yii\Bootstrap5\NavBar;

/**
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\Router\CurrentRoute $currentRoute
 * @var \Yiisoft\Assets\AssetManager $assetManager
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var \App\Invoice\Setting\SettingRepository $s
 * @var \App\Invoice\Helpers\DateHelper $datehelper
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
    case 'ar' : $assetManager->register(ar_Asset::class); $locale = $translator->translate('layout.language.arabic'); break;
    case 'en' : $assetManager->register(en_GB_Asset::class); $locale = $translator->translate('layout.language.english'); break;
    case 'id' : $assetManager->register(id_Asset::class); $locale = $translator->translate('layout.language.indonesian'); break;
    case 'ja' : $assetManager->register(ja_Asset::class); $locale = $translator->translate('layout.language.japanese'); break;
    case 'nl' : $assetManager->register(nl_Asset::class); $locale = $translator->translate('layout.language.dutch'); break;
    case 'ru' : $assetManager->register(ru_Asset::class); $locale = $translator->translate('layout.language.russian'); break;
    case 'zh' : $assetManager->register(zh_CN_Asset::class); $locale = $translator->translate('layout.language.chinese.simplified'); break;
    default   : $assetManager->register(en_GB_Asset::class); $locale = $translator->translate('layout.language.english'); break;
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

$this->beginPage();
?>

<!DOCTYPE html>
<html lang="<?= $s->get_setting('cldr') !== $session->get('_language')  ? $session->get('_language') : $s->get_setting('cldr'); ?>">
<head>
    <?= Meta::documentEncoding('utf-8')?>
    <?= Meta::pragmaDirective('X-UA-Compatible', 'IE=edge,chrome=1') ?>
    <?= Meta::data('viewport', 'width=device-width, initial-scale=1') ?>
    <?= Meta::data('robots', 'NOINDEX,NOFOLLOW') ?>
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

echo NavBar::widget()
->brandText($brandLabel)
->brandUrl($urlGenerator->generate('site/index'))
->begin();

echo Nav::widget()
->currentPath($currentRoute->getUri()->getPath())        
->options(['class'=>'navbar fs-4']) 
->items( 
    $isGuest
        ? [
        ['label' => $translator->translate('invoice.invoice'), 'url' => $urlGenerator->generate('invoice/index'),],
    ] :
    [               
        ['label' => $translator->translate('invoice.quote'), 
          'items' => [
                     ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),
                      'url'=>$urlGenerator->generate('quote/guest')],
                    ],
         ],
         ['label' => $translator->translate('invoice.invoice'), 
          'items' => [
                     ['options'=>['class'=>'nav fs-4'], 'label' => $translator->translate('invoice.view'),
                      'url'=>$urlGenerator->generate('inv/guest')],
                    ],
         ],
         ['label' => $translator->translate('invoice.payment'), 
          'items' => [
                     ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),
                      'url'=>$urlGenerator->generate('payment/guest')],
                     ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.online.log'),
                      'url'=>$urlGenerator->generate('payment/guest_online_log')] 
                    ],
         ],
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
    ],
);        

echo Nav::widget()
->currentPath($currentRoute->getUri()->getPath())
->options(['class' => 'navbar-nav'])
->items(
    [
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
            . Form::tag()->close()
    ],
);

echo NavBar::end();

?>

<div id="main-area">
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
    $js1 = "$(function () {".
       '$(".form-control.input-sm.datepicker").datepicker({dateFormat:"'.$datehelper->datepicker_dateFormat()
                                                        .'", firstDay:'.$datehelper->datepicker_firstDay()
                                                        .', changeMonth: true'
                                                        .', changeYear: true'
                                                        .', yearRange: "-50:+10"'
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

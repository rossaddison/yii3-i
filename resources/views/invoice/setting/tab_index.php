<?php
declare(strict_types=1);  

 /**
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var App\Invoice\Setting\SettingRepository $s
 * @var string $csrf
 */

?>

<div id="headerbar">
    <h1 class="headerbar-title"><?= $s->trans('settings'); ?></h1>
    <?php
        $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]);
        echo (string)$response->getBody();
    ?>
</div>

<ul id="settings-tabs" class="nav nav-tabs nav-tabs-noborder">
    <li class="active">
        <a data-toggle="tab" href="#settings-general" style="text-decoration: none"><?= $s->trans('general'); ?> </a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-invoices" style="text-decoration: none"><?= $s->trans('invoices'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-quotes" style="text-decoration: none"><?= $s->trans('quotes'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-taxes" style="text-decoration: none"><?= $s->trans('taxes'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-email" style="text-decoration: none"><?= $s->trans('email'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-online-payment" style="text-decoration: none"><?= $s->trans('online_payment'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-projects-tasks" style="text-decoration: none"><?= $s->trans('projects'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-google-translate" style="text-decoration: none"><?= 'Google Translate' ?></a>
    </li>
</ul>

<form method="post" id="form-settings" action="<?= $urlGenerator->generate(...$action) ?>"  enctype="multipart/form-data">

    <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">   

    <div class="tabbable tabs-below">

        <div class="tab-content">

            <div class="">
                <?= $alert; ?> 
            </div>

            <div id="settings-general" class="tab-pane active">
                <?= $general; ?>
            </div>

            <div id="settings-invoices" class="tab-pane">
                <?= $invoices; ?>
            </div>

            <div id="settings-quotes" class="tab-pane">
                <?= $quotes; ?>
            </div>

            <div id="settings-taxes" class="tab-pane">
                <?= $taxes; ?>
            </div>

            <div id="settings-email" class="tab-pane">
                <?= $email; ?>
            </div>

            <div id="settings-online-payment" class="tab-pane">
                <?= $online_payment; ?>
            </div>

            <div id="settings-projects-tasks" class="tab-pane">
                <?= $projects_tasks; ?>
            </div>
            
            <div id="settings-google-translate" class="tab-pane">
                <?= $google_translate; ?>
            </div>

        </div>

    </div>

</form>



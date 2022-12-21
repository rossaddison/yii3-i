<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\DateHelper;


/**
 * @var \Yiisoft\View\WebView $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $title 
 * @var \Yiisoft\Session\Flash\FlashInterface $flash_interface
 */

?>
<div class="panel panel-default">
<div class="panel-heading">
    <?= $s->trans('invoice'); ?>
</div>
    <?php
        $clienthelper = new ClientHelper($s);
        $countryhelper = new CountryHelper();          
        echo $modal_delete_inv; 
        echo $modal_add_inv_tax; 
        echo $modal_change_client;  
        // modal_product_lookups is performed using below $modal_choose_items
        echo $modal_choose_items;
        // modal_task_lookups is performed using below $modal_choose_tasks
        echo $modal_choose_tasks;
        echo $modal_inv_to_pdf;
        echo $modal_copy_inv;
        echo $modal_delete_items;
        echo $modal_create_recurring;
        echo $modal_create_credit;
    ?>
<div>
</div>
<?php if ($payments) { ?>
    <br>
    <br>    
    <div class="panel-heading"><b><h2><?= Html::encode($s->trans('payments')); ?></h2></b></div>
    <div class="table-responsive">
        <table class="table table-hover table-striped">
        <thead>
        <tr>    
            <th><?= Html::encode($s->trans('date')); ?></th>
            <th><?= Html::encode($s->trans('amount')); ?></th>
            <th><?= Html::encode($s->trans('note')); ?></th>    
        </tr>
        </thead>
        <tbody>    
            <?php
                foreach ($payments as $payment) { ?>
                <tr>
                    <td><?= Html::encode($payment->getPayment_date()->format($datehelper->style())); ?></td>
                    <td><?= Html::encode($s->format_currency($payment->getAmount())); ?></td> 
                    <td><?= Html::encode($payment->getNote()); ?></td>
                </tr>    
            <?php } ?>
        </tbody>        
        </table>
    </div>
<?php } ?>    
<?php if ($read_only === false && $invEdit) { ?>
    <br>
    <br>    
    <div class="panel-heading">
        <?= $add_inv_item_product; ?>
    </div> 
    <div class="panel-heading">
        <?= $add_inv_item_task; ?>
    </div>
<?php } ?>    
<input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">   
<div id="headerbar">
    <h1 class="headerbar-title">
    <?php
        echo Html::encode($s->trans('invoice')) . ' ';
        echo(Html::encode($inv->getNumber() ? '#' . $inv->getNumber() :  $inv->getId()));
    ?>
    </h1>
    <div class="headerbar-item pull-right <?php if ($inv->getIs_read_only() === false  || $inv->getStatus_id() !== 4) { ?>btn-group<?php } ?>">
        <div class="options btn-group">
            <a class="btn btn-default" data-toggle="dropdown" href="#">
                <i class="fa fa-chevron-down"></i><?= $s->trans('options'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                <?php
                if ($show_buttons && $invEdit) { ?> 
                <li>
                    <a href="<?= $urlGenerator->generate('inv/edit',['id'=>$inv->getId()]) ?>" style="text-decoration:none">
                        <i class="fa fa-edit fa-margin"></i>
                        <?= Html::encode($s->trans('edit')); ?>
                    </a>
                </li>
                <li>
                    <a href="#add-inv-tax" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-plus fa-margin"></i>
                        <?= Html::encode($s->trans('add_invoice_tax')); ?>
                    </a>
                </li> 
                <?php } ?>
                <?php  
                // Show the create credit invoice button if the invoice is read-only or if it is paid 
                // and the user is allowed to edit.
                if (( $read_only === true || $inv->getStatus_id() === 4) && $invEdit) { ?>
                    <li>
                        <a href="#create-credit-inv" data-toggle="modal" data-invoice-id="<?= $inv->getId(); ?>" style="text-decoration:none">
                            <i class="fa fa-minus fa-margin"></i> <?= Html::encode($s->trans('create_credit_invoice')); ?>
                        </a>
                    </li>
                <?php } ?>                
                <?php 
                $inv_amount = ($iaR->repoInvAmountcount((int)$inv->getId()) > 0 ? $iaR->repoInvquery((int)$inv->getId()) : '');
                // If there is a balance outstanding and the invoice is not a draft ie. at least sent, allow a payment to be allocated against it.
                if (!empty($inv_amount) && $inv_amount->getBalance() > 0 && $inv->getStatus_id() !== 1 && $invEdit) : ?>
                    <li>
                        <a href="<?= $urlGenerator->generate('payment/add'); ?>" style="text-decoration:none" class="invoice-add-payment"
                           data-invoice-id="<?= Html::encode($inv->getId()); ?>"
                           data-invoice-balance="<?= Html::encode($inv_amount->getBalance() ?? 0.00); ?>"
                           data-invoice-payment-method="<?= Html::encode($inv->getPayment_method()); ?>"
                           data-payment-cf-exisst="<?= Html::encode($payment_cf_exist); ?>">
                           <i class="fa fa-credit-card fa-margin"></i>
                           <?= Html::encode($s->trans('enter_payment')); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php  
                // Show the pay now button if not a draft and the user has viewPayment permission ie. not editPayment permission
                if (($read_only === false && in_array($inv->getStatus_id(),[2,3]) && $inv_amount->getBalance() > 0) && $paymentView) { ?>
                    <?php foreach ($enabled_gateways as $gateway) { ?>
                        <li>
                            <a href="<?= $urlGenerator->generate('inv/url_key',['url_key'=>$inv->getUrl_key(), 'gateway' => $gateway]); ?>" style="text-decoration:none">
                                <i class="fa fa-minus fa-margin"></i> <?= Html::encode($s->trans('pay_now').'-'. ucfirst($gateway)); ?>
                            </a>
                        </li>
                    <?php } ?>
                <?php } ?>                         
                <li>
                    <a href="#inv-to-pdf"  data-toggle="modal" style="text-decoration:none">
                        <i class="fa fa-print fa-margin"></i>
                        <?= Html::encode($s->trans('download_pdf')); ?>
                        <!-- 
                            views/invoice/inv/modal_inv_to_pdf   ... include custom fields or not on pdf
                            src/Invoice/Inv/InvController/pdf ... calls the src/Invoice/Helpers/PdfHelper->generate_inv_pdf
                            src/Invoice/Helpers/PdfHelper ... calls the src/Invoice/Helpers/MpdfHelper->pdf_create
                            src/Invoice/Helpers/MpdfHelper ... saves folder in src/Invoice/Uploads/Archive
                            using 'pdf_invoice_template' setting or 'default' views/invoice/template/invoice/invoice.pdf
                        -->
                    </a>
                </li>
                <?php if ($invEdit) { ?>
                <li>
                    <a href="#create-recurring-inv" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-refresh fa-margin"></i>
                        <?= Html::encode($s->trans('create_recurring')); ?>
                    </a>
                </li>
                <li>
                    <a href="<?= $urlGenerator->generate('inv/email_stage_0',['id'=> $inv->getId()]); ?>" style="text-decoration:none">
                        <i class="fa fa-send fa-margin"></i>
                        <?= Html::encode($s->trans('send_email')); ?>
                    </a>
                </li>
                <li>                    
                    <a href="#inv-to-inv" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-copy fa-margin"></i>
                         <?= Html::encode($s->trans('copy_invoice')); ?>
                    </a>
                </li>
                <?php } ?>
                <?php 
                // Invoices can be deleted if:
                // the user has invEdit permission
                // it is a draft ie. status => 1, or
                // the system has been overridden and the invoices read only status has been set to false
                if ($invEdit && $inv->getStatus_id() === 1 || ($s->get_setting('enable_invoice_deletion') === true && $inv->getIs_read_only() === false)) { ?>
                    <li>
                        <a href="#delete-inv" data-toggle="modal"  style="text-decoration:none">
                            <i class="fa fa-trash fa-margin"></i> <?= Html::encode($s->trans('delete')); ?>
                        </a>
                    </li>
                    <li>      
                        <a href="#delete-items"  data-toggle="modal" style="text-decoration:none">
                            <i class="fa fa-trash fa-margin"></i>
                            <?= Html::encode($s->trans('delete')." ".$s->trans('item')); ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
        <div class="headerbar-item invoice-labels pull-right">
            <?php if ($is_recurring) { ?>
                <span class="label label-info">
                    <i class="fa fa-refresh"></i>
                    <?= Html::encode($s->trans('recurring')); ?>
                </span>
            <?php } ?>
            <?php if ($inv->getIs_read_only() === true) { ?>
                <span class="label label-danger">
                    <i class="fa fa-read-only"></i><?= Html::encode($s->trans('read_only')); ?>
                </span>
            <?php } ?>
        </div>
    </div>
</div>

<div id="content">    
<?= $alert; ?>
    <div id="inv_form">
        <div class="inv">
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-5">
                    <h3>
                        <a href="<?= $urlGenerator->generate('client/view',['id' => $inv->getClient()->getClient_id()]); ?>">
                            <?= Html::encode($clienthelper->format_client($inv->getClient())) ?>
                        </a>
                    </h3>
                    <br>
                    <div id="pre_save_client_id" value="<?php echo $inv->getClient()->getClient_id(); ?>" hidden></div>
                    <div class="client-address">
                        <span class="client-address-street-line-1">
                            <?php echo($inv->getClient()->getClient_address_1() ? Html::encode($inv->getClient()->getClient_address_1()) . '<br>' : ''); ?>
                        </span>
                        <span class="client-address-street-line-2">
                            <?php echo($inv->getClient()->getClient_address_2() ? Html::encode($inv->getClient()->getClient_address_2()) . '<br>' : ''); ?>
                        </span>
                        <span class="client-address-town-line">
                            <?php echo($inv->getClient()->getClient_city() ? Html::encode($inv->getClient()->getClient_city()) . '<br>' : ''); ?>
                            <?php echo($inv->getClient()->getClient_state() ? Html::encode($inv->getClient()->getClient_state()) . '<br>' : ''); ?>
                            <?php echo($inv->getClient()->getClient_zip() ? Html::encode($inv->getClient()->getClient_zip()) : ''); ?>
                        </span>
                        <span class="client-address-country-line">
                            <?php echo($inv->getClient()->getClient_country() ? '<br>' . $countryhelper->get_country_name($s->trans('cldr'), $inv->getClient()->getClient_country()) : ''); ?>
                        </span>
                    </div>
                    <hr>
                    <?php if ($inv->getClient()->getClient_phone()): ?>
                        <div class="client-phone">
                            <?= $s->trans('phone'); ?>:&nbsp;
                            <?= Html::encode($inv->getClient()->getClient_phone()); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($inv->getClient()->getClient_mobile()): ?>
                        <div class="client-mobile">
                            <?= $s->trans('mobile'); ?>:&nbsp;
                            <?= Html::encode($inv->getClient()->getClient_mobile()); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($inv->getClient()->getClient_email()): ?>
                        <div class='client-email'>
                            <?= $s->trans('email'); ?>:&nbsp;
                            <?php echo $inv->getClient()->getClient_email(); ?>
                        </div>
                    <?php endif; ?>
                    <br>
                </div>

                <div class="col-xs-12 visible-xs"><br></div>

                <div class="col-xs-12 col-sm-6 col-md-7">
                    <div class="details-box">
                        <div class="row">

                            <div class="col-xs-12 col-md-6">

                                <div class="invoice-properties">
                                    <label for="inv_number">
                                        <?= $s->trans('invoice'); ?> #
                                    </label>
                                    <input type="text" id="inv_number" class="form-control input-sm" readonly
                                        <?php if ($inv->getNumber()) : ?> value="<?= $inv->getNumber(); ?>"
                                        <?php else : ?> placeholder="<?= Html::encode($s->trans('not_set')); ?>"
                                        <?php endif; ?>>
                                </div>
                                <div class="invoice-properties has-feedback">
                                    <label for="date_created">
                                        <?= $s->trans('date'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input id="inv_date_created" disabled
                                               class="form-control input-sm datepicker"  
                                               <?php $dc_datehelper = new DateHelper($s); ?>
                                               value="<?= $dc_datehelper->date_from_mysql($inv->getDate_created()); ?>"/>
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar fa-fw"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="invoice-properties has-feedback">
                                    <label for="inv_date_due">
                                        <?= $s->trans('expires'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input name="inv_date_due" id="inv_date_due" disabled
                                               class="form-control input-sm datepicker"
                                               value="<?php echo $datehelper->date_from_mysql($inv->getDate_due()); ?>">
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar fa-fw"></i>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <?php foreach ($custom_fields as $custom_field): ?>
                                        <?php if ($custom_field->getLocation() !== 1) {continue;} ?>
                                        <?php  $cvH->print_field_for_view($inv_custom_values, $custom_field, $custom_values); ?>                                   
                                    <?php endforeach; ?>
                                </div>    
                            </div>
                            <div class="col-xs-12 col-md-6">

                                <div class="invoice-properties">
                                    <label for="inv_status_id">
                                        <?= $s->trans('status'); ?>
                                    </label>
                                    <select name="inv_status_id" id="inv_status_id" disabled
                                            class="form-control">
                                        <?php foreach ($inv_statuses as $key => $status) { ?>
                                            <option value="<?php echo $key; ?>" <?php if ($key === $body['status_id']) {  $s->check_select(Html::encode($body['status_id'] ?? ''), $key);} ?>>
                                                <?= Html::encode($status['label']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="invoice-properties">
                                    <label><?= $s->trans('payment_method'); ?></label>
                                    <?php if ($inv->getPayment_method() !== 0) { ?>
                                        <select name="payment_method" id="payment_method" class="form-control" disabled="disabled">
                                            <option value="0"><?= Html::encode($s->trans('select_payment_method')); ?></option>
                                            <?php foreach ($payment_methods as $payment_method) { ?>
                                                <option <?php $s->check_select((string)$inv->getPayment_method(),
                                                    $payment_method->getId()) ?>
                                                    value="<?= $payment_method->getId(); ?>">
                                                    <?= $payment_method->getName(); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    <?php } else { ?>
                                        <select name="payment_method" id="payment_method" class="form-control"
                                            <?= 'disabled="disabled"'; ?>>
                                            <option "0" ><?= Html::encode($s->trans('none')); ?></option> 
                                        </select>    
                                    <?php } ?>
                                </div>
                                <?php if (($inv->getStatus_id() != 1) && ($invEdit)) { ?>
                                <div class="invoice-properties">
                                    <label for="inv_password"><?= Html::encode($s->trans('password')); ?></label>
                                    <input type="text" id="inv_password" class="form-control input-sm" disabled value="<?= Html::encode($body['password'] ?? ''); ?>">
                                </div>                                
                                <div class="invoice-properties">
                                    <div class="form-group">
                                        <label for="guest-url"><?= Html::encode($s->trans('guest_url')); ?></label>
                                        <div class="input-group">
                                            <input type="text" id="guest-url" name="guest-url" readonly class="form-control" value="<?= '/invoice/inv/url_key/'.$inv->getUrl_key(); ?>">
                                            <span class="input-group-text to-clipboard cursor-pointer"
                                                  data-clipboard-target="#guest-url">
                                                <i class="fa fa-clipboard fa-fw"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                <input type="text" id="dropzone_client_id" readonly class="form-control" value="<?=  $inv->getClient()->getClient_id(); ?>" hidden>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
   <div id="partial_item_table_parameters" inv_items="<?php $inv_items; ?>" disabled>
    <?= $partial_item_table; ?>     
   </div>
    
   <div class="row">
            <div class="col-xs-12 col-md-6">
                <div class="panel panel-default no-margin">
                    <div class="panel-heading">
                        <?= Html::encode($s->trans('terms')); ?>
                    </div>
                    <div class="panel-body">
                        <textarea name="terms" id="terms" rows="3" disabled
                                  class="input-sm form-control"><?= Html::encode($body['terms'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="col-xs-12 visible-xs visible-sm"><br></div>

            </div>
            <div id="view_custom_fields" class="col-xs-12 col-md-6">               
                <?= $view_custom_fields; ?>
            </div>
            <div id="view_partial_inv_attachments" class="col-xs-12 col-md-6">               
                <?= $partial_inv_attachments; ?>
            </div>
    </div>
</div>
</div>    
<div> 
</div>


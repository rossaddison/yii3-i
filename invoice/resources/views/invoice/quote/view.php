<?php
declare(strict_types=1);

/**
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\View\WebView $this
 * @var array $body
 * @var string $csrf
 * @var string $title 
 * @var \Yiisoft\Session\Flash\FlashInterface $flash_interface
 */

$this->setTitle($s->trans('quote'));

use Yiisoft\Html\Html;
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Widget\LabelSwitch;

$vat = $s->get_setting('enable_vat_registration');
?>
<div class="panel panel-default">
<div class="panel-heading">
    <?= Html::encode($this->getTitle()); ?>
</div>
    <?php
        $clienthelper = new ClientHelper($s);
        $countryhelper = new CountryHelper();  
        $datehelper = new DateHelper($s);  
        $numberhelper = new NumberHelper($s);
        echo $modal_delete_quote;
        if ($vat === '0') {
            echo $modal_add_quote_tax;
        }  
        // modal_product_lookups is performed using below $modal_choose_items
        echo $modal_choose_items;
        echo $modal_quote_to_invoice;
        echo $modal_quote_to_so;
        echo $modal_quote_to_pdf;
        echo $modal_copy_quote;
        echo $modal_delete_items;
    ?>
<div>
<br>
<br>
</div>
<div>
    <?php if ($invEdit && $quote->getStatus_id() === 1) { 
        echo $add_quote_item; 
    } ?>
</div> 
<input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">   
<div id="headerbar">
    <h1 class="headerbar-title">
    <?php
        echo $s->trans('quote') . ' ';
        echo($quote->getNumber() ? '#' . $quote->getNumber() :  $quote->getId());
    ?>
    </h1>
        <div class="headerbar-item pull-right">

        <?php
            // Purpose: To remind the user that VAT is enabled
            $s->get_setting('display_vat_enabled_message') === '1' ?
            LabelSwitch::checkbox(
                    'quote-view-label-switch',
                    $s->get_setting('enable_vat_registration'),
                    $translator->translate('invoice.quote.label.switch.on'),
                    $translator->translate('invoice.quote.label.switch.off'),
                    'quote-view-label-switch-id',
                    '16'
            ) : '';
        ?>    
        <div class="options btn-group">
            <a class="btn btn-default" data-toggle="dropdown" href="#">
                <i class="fa fa-chevron-down"></i><?= $s->trans('options'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                <?php
                if ($invEdit) { ?> 
                <li>
                    <a href="<?= $urlGenerator->generate('quote/edit',['id'=>$quote->getId()]) ?>" style="text-decoration:none">
                        <i class="fa fa-edit fa-margin"></i>
                        <?= $s->trans('edit'); ?>
                    </a>
                </li>
                <li>
                    <?php if ($vat === '0') { ?>
                    <a href="#add-quote-tax" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-plus fa-margin"></i>
                        <?= $s->trans('add_quote_tax'); ?>
                    </a>
                    <?php }?>
                </li>
                <?php } ?>
                <li>
                    <a href="#quote-to-pdf"  data-toggle="modal" style="text-decoration:none">
                        <i class="fa fa-print fa-margin"></i>
                        <!-- 
                            views/invoice/quote/modal_quote_to_pdf   ... include custom fields or not on pdf
                            src/Invoice/Quote/QuoteController/pdf ... calls the src/Invoice/Helpers/PdfHelper->generate_quote_pdf
                            src/Invoice/Helpers/PdfHelper ... calls the src/Invoice/Helpers/MpdfHelper
                            src/Invoice/Helpers/MpdfHelper ... saves folder in src/Invoice/Uploads/Archive
                            using 'pdf_quote_template' setting or 'default' views/invoice/template/quote/quote.pdf
                        -->
                        <?= $s->trans('download_pdf'); ?>
                    </a>
                </li>
                <?php if ($invEdit  && $quote->getStatus_id() === 1 && ($quote_amount_total > 0)) { ?>
                <li>
                    <a href="<?= $urlGenerator->generate('quote/email_stage_0',['id'=> $quote->getId()]); ?>" style="text-decoration:none">
                        <i class="fa fa-send fa-margin"></i>
                        <?= $s->trans('send_email'); ?>
                    </a>
                </li>
                <?php // if quote has been approved (ie status 4) by the client without po number do not show quote to sales order again   
                     if ($quote->getSo_id() === '0' && $quote->getStatus_id() === 4) { ?>
                <li>
                    <a href="#quote-to-so" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-refresh fa-margin"></i>
                        <?= $translator->translate('invoice.quote.to.so'); ?>
                    </a>
                </li>
                <?php } ?>
                <li>
                    <a href="#quote-to-invoice" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-refresh fa-margin"></i>
                        <?= $s->trans('quote_to_invoice'); ?>
                    </a>
                </li>
                <li>                    
                    <a href="#quote-to-quote" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-copy fa-margin"></i>
                         <?= $s->trans('copy_quote'); ?>
                    </a>
                </li>
                <li>
                    <a href="#delete-quote" data-toggle="modal"  style="text-decoration:none">
                        <i class="fa fa-trash fa-margin"></i> <?= $s->trans('delete_quote'); ?>
                    </a>
                </li>
                <li>      
                    <a href="#delete-items"  data-toggle="modal" style="text-decoration:none">
                        <i class="fa fa-trash fa-margin"></i>
                        <?= $s->trans('delete')." ".$s->trans('item'); ?>
                    </a>
                </li>
                <?php } ?>
            </ul>
        </div>        
    </div>
</div>

<div id="content">    
    <?= $alert; ?>  
    <div id="quote_form">
        <div class="quote">
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-5">
                    <h3>
                        <a href="<?= $urlGenerator->generate('client/view',['id' => $quote->getClient()->getClient_id()]); ?>">
                            <?= Html::encode($clienthelper->format_client($quote->getClient())); ?>
                        </a>
                    </h3>
                    <br>
                    <div id="pre_save_client_id" value="<?php echo $quote->getClient()->getClient_id(); ?>" hidden></div>
                    <div class="client-address">
                        <span class="client-address-street-line-1">
                            <?php echo($quote->getClient()->getClient_address_1() ? Html::encode($quote->getClient()->getClient_address_1()) . '<br>' : ''); ?>
                        </span>
                        <span class="client-address-street-line-2">
                            <?php echo($quote->getClient()->getClient_address_2() ? Html::encode($quote->getClient()->getClient_address_2()) . '<br>' : ''); ?>
                        </span>
                        <span class="client-address-town-line">
                            <?php echo($quote->getClient()->getClient_city() ? Html::encode($quote->getClient()->getClient_city()) . '<br>' : ''); ?>
                            <?php echo($quote->getClient()->getClient_state() ? Html::encode($quote->getClient()->getClient_state()) . '<br>' : ''); ?>
                            <?php echo($quote->getClient()->getClient_zip() ? Html::encode($quote->getClient()->getClient_zip()) : ''); ?>
                        </span>
                        <span class="client-address-country-line">
                            <?php echo($quote->getClient()->getClient_country() ? '<br>' . $countryhelper->get_country_name($s->trans('cldr'), $quote->getClient()->getClient_country()) : ''); ?>
                        </span>
                    </div>
                    <hr>
                    <?php if ($quote->getClient()->getClient_phone()): ?>
                        <div class="client-phone">
                            <?= $s->trans('phone'); ?>:&nbsp;
                            <?= Html::encode($quote->getClient()->getClient_phone()); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($quote->getClient()->getClient_mobile()): ?>
                        <div class="client-mobile">
                            <?= $s->trans('mobile'); ?>:&nbsp;
                            <?= Html::encode($quote->getClient()->getClient_mobile()); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($quote->getClient()->getClient_email()): ?>
                        <div class='client-email'>
                            <?= $s->trans('email'); ?>:&nbsp;
                            <?php echo $quote->getClient()->getClient_email(); ?>
                        </div>
                    <?php endif; ?>
                    <br>
                </div>

                <div class="col-xs-12 visible-xs"><br></div>

                <div class="col-xs-12 col-sm-6 col-md-7">
                    <div class="details-box">
                        <div class="row">

                            <div class="col-xs-12 col-md-6">

                                <div class="quote-properties">
                                    <label for="quote_number">
                                        <?= $s->trans('quote'); ?> #
                                    </label>
                                    <input type="text" id="quote_number" class="form-control input-sm" readonly
                                        <?php if ($quote->getNumber()) : ?> value="<?= $quote->getNumber(); ?>"
                                        <?php else : ?> placeholder="<?= $s->trans('not_set'); ?>"
                                        <?php endif; ?>>
                                </div>
                                <div class="quote-properties has-feedback">
                                    <label for="quote_date_created">
                                        <?= $vat == '0' ? $translator->translate('invoice.invoice.date.issued') : $s->trans('quote_date'); ?>
                                    </label>
                                    <div class="input-group">
                                        <?php  $date = $quote->getDate_created() ?? null; 
                                            if ($date && $date !== "0000-00-00") { 
                                                //use the DateHelper
                                                $datehelper = new DateHelper($s); 
                                                $qdate = $datehelper->date_from_mysql($date); 
                                            } else { 
                                                $qdate = null; 
                                            }
                                        ?>
                                        <input name="quote_date_created" id="quote_date_created" disabled
                                               class="form-control input-sm datepicker"
                                               value="<?= Html::encode($qdate); ?>"/>
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar fa-fw"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="quote-properties has-feedback">
                                    <label for="quote_date_expires">
                                        <?= $s->trans('expires'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input name="quote_date_expires" id="quote_date_expires" readonly
                                               class="form-control input-sm datepicker"
                                               value="<?php echo $datehelper->date_from_mysql($quote->getDate_expires()); ?>">
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar fa-fw"></i>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <?php foreach ($custom_fields as $custom_field): ?>
                                        <?php if ($custom_field->getLocation() !== 1) {continue;} ?>
                                        <?php  $cvH->print_field_for_view($quote_custom_values, $custom_field, $custom_values); ?>                                   
                                    <?php endforeach; ?>
                                </div>    
                            </div>
                            <div class="col-xs-12 col-md-6">

                                <div class="quote-properties">
                                    <label for="status_id">
                                        <?= $s->trans('status'); ?>
                                    </label>
                                    <select name="status_id" id="status_id" disabled
                                            class="form-control">
                                        <?php foreach ($quote_statuses as $key => $status) { ?>
                                            <option value="<?php echo $key; ?>" <?php if ($key === $body['status_id']) {  $s->check_select(Html::encode($body['status_id'] ?? ''), $key);} ?>>
                                                <?= Html::encode($status['label']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="quote-properties">
                                    <label for="quote_password" hidden>
                                        <?= $s->trans('quote_password'); ?>
                                    </label>
                                    <input type="text" id="quote_password" class="form-control input-sm" disabled value="<?= Html::encode($body['password'] ?? ''); ?>" hidden>
                                </div>

                                <?php
                                    // show the guest url which the customer will click on to gain access to the site and this quote
                                    // there is no need to show it if it has not been sent yet ie. 1 => draft, 2 => sent
                                    // Update: This button has been replaced with the below button
                                    if ($quote->getStatus_id() !== 1) { ?>
                                    <div class="quote-properties">
                                        <label for="quote_guest_url" hidden><?php echo $s->trans('guest_url'); ?></label>
                                        <div class="input-group" hidden>
                                            <input type="text" id="quote_guest_url" readonly class="form-control" value="<?=  '/invoice/quote/url_key/'.$quote->getUrl_key(); ?>" hidden>
                                            <span class="input-group-text to-clipboard cursor-pointer"
                                                  data-clipboard-target="#quote_guest_url">
                                                <i class="fa fa-clipboard fa-fw"></i>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php
                                        if (($quote->getStatus_id() === 2 | $quote->getStatus_id() === 3 | $quote->getStatus_id() === 5)  && !$invEdit && ($quote->getSo_id() === '0' || $quote->getSo_id() === null)) 
                                    { ?>
                                    <div>
                                        <br>
                                        <a href="<?= $urlGenerator->generate('quote/url_key',['url_key' => $quote->getUrl_key()]); ?>" class="btn btn-success">  
                                            <?= $s->trans('approve_this_quote') ; ?></i>    
                                        </a>
                                    </div>
                                    <?php } ?>
                                    <?php                                        
                                        if (($quote->getStatus_id() === 2 | $quote->getStatus_id() === 3 | $quote->getStatus_id() === 4 )  && !$invEdit && ($quote->getSo_id() === '0' || $quote->getSo_id() === null)) 
                                    { ?>
                                    <div>
                                        <br>
                                        <a href="<?= $urlGenerator->generate('quote/url_key',['url_key' => $quote->getUrl_key()]); ?>" class="btn btn-danger">  
                                            <?= $s->trans('reject_this_quote') ; ?></i>    
                                        </a>
                                    </div>
                                    <?php } ?>
                                <?php } else {?>
                                    <div class="quote-properties">
                                        <label for="quote_guest_url"><?php echo $s->trans('guest_url'); ?></label>
                                        <div class="input-group">
                                            <input type="text" id="quote_guest_url" readonly  class="form-control" value="">                                            
                                        </div>
                                    </div>
                                <?php } ?>
                                <input type="text" id="dropzone_client_id" readonly  hidden class="form-control" value="<?= $quote->getClient()->getClient_id(); ?>">
                                <?php if ($quote->getSo_id()) { ?>  
                                <div has-feedback">
                                    <label for="salesorder_to_url"><?= $translator->translate('invoice.salesorder'); ?></label>
                                    <div class="input-group">
                                        <?= Html::a($sales_order_number, $urlGenerator->generate('salesorder/view',['id'=>$quote->getSo_id()]), ['class'=>'btn btn-success']); ?>
                                    </div>
                                </div>
                                <?php } ?>
                            
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
   <div id="partial_item_table_parameters" quote_items="<?php $quote_items; ?>" disabled>
    <?=
       $partial_item_table;
    ?>     
   </div>
    
   <div class="row">
            <div class="col-xs-12 col-md-6">
                <div class="panel panel-default no-margin">
                    <div class="panel-heading">
                        <?= $s->trans('notes'); ?>
                    </div>
                    <div class="panel-body">
                        <textarea name="notes" id="notes" rows="3" disabled
                                  class="input-sm form-control"><?= Html::encode($body['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="col-xs-12 visible-xs visible-sm"><br></div>

            </div>
            <div id="view_custom_fields" class="col-xs-12 col-md-6">
                <?php //echo $dropzone_quote_html; ?>
                <?php echo $view_custom_fields; ?>
            </div>
    </div>
</div>
</div>    
<div>  
</div>
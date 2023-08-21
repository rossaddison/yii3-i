<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use App\Invoice\Helpers\DateHelper;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

if (!empty($errors)) {
    foreach ($errors as $field => $error) {
        echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
    }
}

?>
<h1><?= Html::encode($title) ?></h1>
<form id="DeliveryForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
<h1 class="headerbar-title"><?= $s->trans('deliveries_form'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<div class="row">
<div class="form-group has-feedback"> 
<?php  $date = $body['date_created'] ?? null; 
$datehelper = new DateHelper($s); 
if ($date && $date !== "0000-00-00") { 
    $date = $datehelper->date_from_mysql($date); 
} else { 
    $date = null; 
} 
   ?> 
</div>
<div class="form-group">
   <label for="date_created"><?= $translator->translate('invoice.invoice.delivery.date.created'); ?></label>
   <input type="text" name="date_created" id="date_created" class="form-control input-sm datepicker"
    value="<?= Html::encode($body['date_created'] ??  ''); ?>">
</div>
<div class="form-group has-feedback"> 
<?php  $datem = $body['date_modified'] ?? null; 
if ($datem && $datem !== "0000-00-00") { 
    $mdate = $datehelper->date_from_mysql($datem); 
} else { 
    $mdate = null; 
} 
   ?>  
<div class="form-group">
   <label hidden for="date_modified"><?= $translator->translate('invoice.invoice.delivery.date.modified'); ?></label>
   <input hidden type="text" name="date_modified" id="date_modified" class="form-control"
 value="<?= Html::encode($body['date_modified'] ??  ''); ?>">
 </div>
 <div class="form-group">
   <input type="hidden" name="id" id="id" class="form-control"
 value="<?= Html::encode($body['id'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <input type="hidden" name="inv_id" id="inv_id" class="form-control"
 value="<?= $body['inv_id'] ?? $inv_id; ?>">
 </div>
 <div class="form-group has-feedback"> 
<?php  $dated = $body['actual_delivery_date'] ?? null; 
 
if ($dated && $date !== "0000-00-00") { 
    $ddate = $datehelper->date_from_mysql($dated); 
} else { 
    $ddate = null; 
} 
?>  
<div class="form-group">
   <label for="actual_delivery_date"><?= $translator->translate('invoice.invoice.delivery.actual.delivery.date') ?></label>
   <input type="text" name="actual_delivery_date" id="actual_delivery_date" class="form-control input-sm datepicker"
 value="<?= Html::encode($body['actual_delivery_date'] ??  ''); ?>">
 </div>
 <div class="mb-3 form-group has-feedback"> 
     
<?php  $dates = $body['start_date'] ?? null; 
 
if ($dates && $dates !== "0000-00-00") { 
    $sdate = $datehelper->date_from_mysql($dates); 
} else { 
    $sdate = null; 
} 
   ?>  
<div class="form-group">
   <label for="start_date"><?= $translator->translate('invoice.invoice.delivery.start.date'); ?></label>
   <input type="text" name="start_date" id="start_date" class="form-control input-sm datepicker"
 value="<?= Html::encode($body['start_date'] ??  ''); ?>">
 </div>
 <div class="mb-3 form-group has-feedback"> 

<?php  $datee = $body['end_date'] ?? null; 

if ($datee && $datee !== "0000-00-00") { 
    $edate = $datehelper->date_from_mysql($datee); 
} else { 
    $edate = null; 
} 
   ?>  
 <div class="form-group">
   <label for="end_date"><?= $translator->translate('invoice.invoice.delivery.end.date'); ?></label>
   <input type="text" name="end_date" id="end_date" class="form-control input-sm datepicker"
   value="<?= Html::encode($body['end_date'] ??  ''); ?>">
 </div>
     
</div>
<div class="form-group">
        <div>
            <label for="delivery_location_id"><?= $translator->translate('invoice.invoice.delivery.location'); ?>: </label>
        </div>        
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <?php if ($del_count > 0) { ?>
                <select name="delivery_location_id" id="delivery_location_id"
                        class="form-control">
                    <?php foreach ($dels as $del) { ?>
                        <option value="<?php echo $del->getId(); ?>"
                            <?php echo $s->check_select(Html::encode($body['delivery_location_id'] ?? $del->getId()), $del->getId()); ?>>
                            <?php echo $del->getAddress_1(). ', '.$del->getAddress_2() .', '. $del->getCity() .', '. $del->getZip() ; ?>
                        </option>
                    <?php } ?>
                </select>
                <?php } else {
                    echo Html::a($translator->translate('invoice.invoice.delivery.location.add'), $urlGenerator->generate('del/add',['client_id'=>$inv->getClient_id()]),['class'=>'btn btn-danger btn-lg mt-3']);
                }
                ?>
            </div>
        </div>    
    </div>     

</div>

</div>
</form>

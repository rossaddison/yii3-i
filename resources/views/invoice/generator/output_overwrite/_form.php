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
<form id="ClientForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
<h1 class="headerbar-title"><?= $s->trans('clients_form'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<div class="row">
 <div class="mb-3 form-group has-feedback"> <?php  $date = $body['client_date_created'] ?? null; 
$datehelper = new DateHelper($s); 
if ($date && $date !== "0000-00-00") { 
    $date = $datehelper->date_from_mysql($date); 
} else { 
    $date = null; 
} 
   ?>  
<label form-label for="client_date_created"><?= $s->trans('client_date_created') ." (".  $datehelper->display().") "; ?></label><div class="mb3 input-group"> 
<input type="text" name="client_date_created" id="client_date_created" placeholder="<?= $datehelper->display(); ?>" 
       class="form-control data-datepicker" 
       value="<?php if ($date <> null) {echo Html::encode($date);} ?>"> 
<span class="input-group-text"> 
<i class="fa fa-calendar fa-fw"></i> 
 </span> 
</div>
</div>   <div class="row mb3 form-group">
   <label for="client_date_created"><?= $s->trans('client_date_created'); ?></label>
   <input type="text" name="client_date_created" id="client_date_created" class="form-control"
 value="<?= Html::encode($body['client_date_created'] ??  ''); ?>">
 </div>
 <div class="mb-3 form-group has-feedback"> <?php  $date = $body['client_date_modified'] ?? null; 
$datehelper = new DateHelper($s); 
if ($date && $date !== "0000-00-00") { 
    $date = $datehelper->date_from_mysql($date); 
} else { 
    $date = null; 
} 
   ?>  
<label form-label for="client_date_modified"><?= $s->trans('client_date_modified') ." (".  $datehelper->display().") "; ?></label><div class="mb3 input-group"> 
<input type="text" name="client_date_modified" id="client_date_modified" placeholder="<?= $datehelper->display(); ?>" 
       class="form-control data-datepicker" 
       value="<?php if ($date <> null) {echo Html::encode($date);} ?>"> 
<span class="input-group-text"> 
<i class="fa fa-calendar fa-fw"></i> 
 </span> 
</div>
</div>   <div class="row mb3 form-group">
   <label for="client_date_modified"><?= $s->trans('client_date_modified'); ?></label>
   <input type="text" name="client_date_modified" id="client_date_modified" class="form-control"
 value="<?= Html::encode($body['client_date_modified'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <input type="hidden" name="id" id="id" class="form-control"
 value="<?= Html::encode($body['id'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_name"><?= $s->trans('client_name'); ?></label>
   <input type="text" name="client_name" id="client_name" class="form-control"
 value="<?= Html::encode($body['client_name'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_surname"><?= $s->trans('client_surname'); ?></label>
   <input type="text" name="client_surname" id="client_surname" class="form-control"
 value="<?= Html::encode($body['client_surname'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_address_1"><?= $s->trans('client_address_1'); ?></label>
   <input type="text" name="client_address_1" id="client_address_1" class="form-control"
 value="<?= Html::encode($body['client_address_1'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_address_2"><?= $s->trans('client_address_2'); ?></label>
   <input type="text" name="client_address_2" id="client_address_2" class="form-control"
 value="<?= Html::encode($body['client_address_2'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_building_number"><?= $s->trans('client_building_number'); ?></label>
   <input type="text" name="client_building_number" id="client_building_number" class="form-control"
 value="<?= Html::encode($body['client_building_number'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_city"><?= $s->trans('client_city'); ?></label>
   <input type="text" name="client_city" id="client_city" class="form-control"
 value="<?= Html::encode($body['client_city'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_state"><?= $s->trans('client_state'); ?></label>
   <input type="text" name="client_state" id="client_state" class="form-control"
 value="<?= Html::encode($body['client_state'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_zip"><?= $s->trans('client_zip'); ?></label>
   <input type="text" name="client_zip" id="client_zip" class="form-control"
 value="<?= Html::encode($body['client_zip'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_country"><?= $s->trans('client_country'); ?></label>
   <input type="text" name="client_country" id="client_country" class="form-control"
 value="<?= Html::encode($body['client_country'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_phone"><?= $s->trans('client_phone'); ?></label>
   <input type="text" name="client_phone" id="client_phone" class="form-control"
 value="<?= Html::encode($body['client_phone'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_fax"><?= $s->trans('client_fax'); ?></label>
   <input type="text" name="client_fax" id="client_fax" class="form-control"
 value="<?= Html::encode($body['client_fax'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_mobile"><?= $s->trans('client_mobile'); ?></label>
   <input type="text" name="client_mobile" id="client_mobile" class="form-control"
 value="<?= Html::encode($body['client_mobile'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_email"><?= $s->trans('client_email'); ?></label>
   <input type="text" name="client_email" id="client_email" class="form-control"
 value="<?= Html::encode($body['client_email'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_web"><?= $s->trans('client_web'); ?></label>
   <input type="text" name="client_web" id="client_web" class="form-control"
 value="<?= Html::encode($body['client_web'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_tax_code"><?= $s->trans('client_tax_code'); ?></label>
   <input type="text" name="client_tax_code" id="client_tax_code" class="form-control"
 value="<?= Html::encode($body['client_tax_code'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_language"><?= $s->trans('client_language'); ?></label>
   <input type="text" name="client_language" id="client_language" class="form-control"
 value="<?= Html::encode($body['client_language'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="client_active" class="form-label"><?= $s->trans('client_active'); ?></label>
   <input type="hidden" name="client_active" value="0">
   <input type="checkbox" name="client_active" id="client_active" value="1" <?php $s->check_select(Html::encode($body['client_active'] ??'' ), 1, '==', true) ?>>
 </div>
 <div class="row mb3 form-group">
   <label for="client_number"><?= $s->trans('client_number'); ?></label>
   <input type="text" name="client_number" id="client_number" class="form-control"
 value="<?= Html::encode($body['client_number'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_avs"><?= $s->trans('client_avs'); ?></label>
   <input type="text" name="client_avs" id="client_avs" class="form-control"
 value="<?= Html::encode($body['client_avs'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_insurednumber"><?= $s->trans('client_insurednumber'); ?></label>
   <input type="text" name="client_insurednumber" id="client_insurednumber" class="form-control"
 value="<?= Html::encode($body['client_insurednumber'] ??  ''); ?>">
 </div>
 <div class="row mb3 form-group">
   <label for="client_veka"><?= $s->trans('client_veka'); ?></label>
   <input type="text" name="client_veka" id="client_veka" class="form-control"
 value="<?= Html::encode($body['client_veka'] ??  ''); ?>">
 </div>
 <div class="mb-3 form-group has-feedback"> <?php  $date = $body['client_birthdate'] ?? null; 
$datehelper = new DateHelper($s); 
if ($date && $date !== "0000-00-00") { 
    $date = $datehelper->date_from_mysql($date); 
} else { 
    $date = null; 
} 
   ?>  
<label form-label for="client_birthdate"><?= $s->trans('client_birthdate') ." (".  $datehelper->display().") "; ?></label><div class="mb3 input-group"> 
<input type="text" name="client_birthdate" id="client_birthdate" placeholder="<?= $datehelper->display(); ?>" 
       class="form-control data-datepicker" 
       value="<?php if ($date <> null) {echo Html::encode($date);} ?>"> 
<span class="input-group-text"> 
<i class="fa fa-calendar fa-fw"></i> 
 </span> 
</div>
</div>   <div class="row mb3 form-group">
   <label for="client_gender"><?= $s->trans('client_gender'); ?></label>
   <input type="text" name="client_gender" id="client_gender" class="form-control"
 value="<?= Html::encode($body['client_gender'] ??  ''); ?>">
 </div>

</div>

</div>

</div>
</form>

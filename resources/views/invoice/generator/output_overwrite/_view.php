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
<div class="row">
 <div class="row mb3 form-group">
<label for="client_date_created" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_date_created'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_date_created'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_date_modified" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_date_modified'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_date_modified'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_name" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_name'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_name'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_surname" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_surname'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_surname'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_address_1" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_address_1'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_address_1'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_address_2" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_address_2'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_address_2'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_building_number" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_building_number'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_building_number'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_city" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_city'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_city'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_state" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_state'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_state'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_zip" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_zip'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_zip'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_country" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_country'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_country'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_phone" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_phone'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_phone'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_fax" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_fax'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_fax'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_mobile" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_mobile'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_mobile'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_email" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_email'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_email'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_web" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_web'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_web'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_tax_code" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_tax_code'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_tax_code'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_language" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_language'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_language'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_active" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_active'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_active'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_number" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_number'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_number'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_avs" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_avs'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_avs'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_insurednumber" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_insurednumber'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_insurednumber'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="client_veka" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_veka'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_veka'] ?? ''); ?></label>
 </div>
<div class="row mb3 form-group">
  <label for="client_birthdate" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $s->trans('client_birthdate'); ?>  </label>
<?php $date = $body['client_birthdate']; if ($date && $date != "0000-00-00") {    $datehelper = new DateHelper($s);  $date = $datehelper->date_from_mysql($date);} else {  $date = null;}?><label class="text-bg col-sm-10 col-form-label"><?= Html::encode($date); ?></label></div>
 <div class="row mb3 form-group">
<label for="client_gender" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('client_gender'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['client_gender'] ?? ''); ?></label>
 </div>
</div>

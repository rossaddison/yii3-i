<?php
  declare(strict_types=1);
  
  use Yiisoft\Html\Html;
?>
<div class="panel panel-default no-margin">
    <div class="panel-heading">
      <i tooltip="data-bs-toggle" title="<?= $s->isDebugMode(6);?>"><?= Html::a($title, $delivery_location_url,['style'=>'text-decoration:none']); ?></i>
    </div>
    <div class="panel-body clearfix">
        <div class="container">
          <div class="row">
              <div class="row mb3 form-group">
                  <label for="building_number" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.building.number'); ?></label>
                  <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($building_number ?? ''); ?></label>
              </div>
              <div class="row mb3 form-group">
                  <label for="address_1" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.street.name'); ?></label>
                  <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($address_1 ?? ''); ?></label>
              </div>
              <div class="row mb3 form-group">
                  <label for="address_2" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.additional.street.name'); ?></label>
                  <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($address_2 ?? ''); ?></label>
              </div>
              <div class="row mb3 form-group">
                  <label for="city" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.city.name'); ?></label>
                  <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($city ?? ''); ?></label>
              </div>
              <div class="row mb3 form-group">
                  <label for="state" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.countrysubentity'); ?></label>
                  <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($state ?? ''); ?></label>
              </div>
              <div class="row mb3 form-group">
                  <label for="zip" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.postalzone'); ?></label>
                  <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($zip ?? ''); ?></label>
              </div>
              <div class="row mb3 form-group">
                  <label for="country" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.country'); ?></label>
                  <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($country ?? ''); ?></label>
              </div>
              <div class="row mb3 form-group">
                  <label for="global_location_number" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.delivery.location.global.location.number'); ?></label>
                  <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($global_location_number ?? ''); ?></label>
              </div>
          </div>
        </div>
      </div> 
</div>    
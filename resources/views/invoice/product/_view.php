<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
?>

<h1><?= str_repeat("&nbsp;", 2).Html::encode($title) ?></h1>

  <div class="row">
    <div class="row mb-3 form-group">
        <label for="product_sku" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $s->trans('product_sku'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_sku'] ?? '') ?>
    </div>
    <div class="row mb-3 form-group">
        <label for="product_sii_schemeid" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.product.sii.schemeid'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_sii_schemeid'] ?? '') ?>
    </div>  
    <div class="row mb-3 form-group">
        <label for="product_sii_id" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.product.sii.id'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_sii_id'] ?? '') ?>
    </div>
    <div class="row mb-3 form-group">
        <label for="product_icc_listid" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.product.icc.listid'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_icc_listid'] ?? '') ?>
    </div>
    <div class="row mb-3 form-group">
        <label for="product_icc_id" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.product.icc.id'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_icc_id'] ?? '') ?>
    </div>  
    <div class="row mb-3 form-group">
        <label for="product_country_of_origin_code" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.product.country.of.origin.code'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_country_of_origin_code'] ?? '') ?>
    </div>  
    <div class="row mb-3 form-group">
        <label for="product_name" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $s->trans('product_name'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_name'] ?? '') ?>
    </div>
    <div class="row mb-3 form-group">
        <label for="product_description" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?php echo $s->trans('product_description'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_description'] ?? '') ?>         
    </div>  
  </div>
  <div class="row">
    <div class="row mb-3 form-group">
        <label for="product_price" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $s->trans('product_price'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_price'] ?? '') ?>
    </div>
    <div class="row mb-3 form-group">
        <label for="product_price_base_quantity" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.product.price.base.quantity'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_price_base_quantity'] ?? '') ?>
    </div>      
    <div class="row mb-3 form-group">
        <label for="purchase_price" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $s->trans('purchase_price'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['purchase_price'] ?? '') ?>
    </div>    
    <div class="row mb-3 form-group">
        <label for="provider_name" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $s->trans('provider_name'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['provider_name'] ?? '') ?>
    </div>
    <div class="row mb-3 form-group">
        <label for="product_additional_item_property_name" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.product.additional.item.property.name'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_additional_item_property_name'] ?? '') ?>
    </div>
    <div class="row mb-3 form-group">
        <label for="product_additional_item_property_value" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.product.additional.item.property.value'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_additional_item_property_value'] ?? '') ?>
    </div>  
    <div class="row mb-3 form-group">
        <label for="tax_rate_id" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $s->trans('tax_rate'); ?></label>
        <?= $product->getTaxrate()->getTax_rate_name();?>
    </div>    
    <div class="row mb-3 form-group">
        <label for="unit_id" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $s->trans('unit'); ?></label>
        <?= $product->getUnit()->getUnit_name();?>
    </div>
    <div class="row mb-3 form-group">
        <label for="family_id" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $s->trans('family'); ?></label>
        <?= $product->getFamily()->getFamily_name();?>
    </div>        
    <div class="row mb-3 form-group">
        <label for="product_tariff" class="text-bg col-sm-8 col-form-label" style="background:lightblue"><?= $s->trans('product_tariff'); ?></label>
        <?= str_repeat("&nbsp;", 2).Html::encode($body['product_tariff'] ?? '') ?>            
    </div>   
  </div>
<br>
<br>
<div>
<label class="btn btn-info"><?php echo Html::a($translator->translate('invoice.product.property.table'),$urlGenerator->generate('productproperty/index'), ['style' => 'text-decoration:none']); ?></label>
<?= Html::a($translator->translate('invoice.product.property'),$urlGenerator->generate('productproperty/add',['product_id' => $product->getProduct_id()]),['class' => 'btn btn-primary fa fa-plus']); ?>
</div>
<br>
<div class="table-responsive btn btn-info">
<table class="table">
     <thead>
     <tr>
     <th><?php echo $translator->translate('invoice.product.property.name'); ?></th>
     <th><?php echo $translator->translate('invoice.product.property.value'); ?></th>
     </tr>
     </thead>
     <?php echo $productpropertys; ?>
</table>
</div>    
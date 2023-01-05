<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\Entity\Product;


final class ProductService
{
    private ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }
    
    public function addProduct(Product $model, ProductForm $form): void
    {
        null!==$form->getProduct_sku() ? $model->setProduct_sku($form->getProduct_sku()) : '';
        null!==$form->getProduct_name() ? $model->setProduct_name($form->getProduct_name()): '';
        null!==$form->getProduct_description() ? $model->setProduct_description($form->getProduct_description()): '';
        null!==$form->getProduct_price() ? $model->setProduct_price($form->getProduct_price()): '';
        null!==$form->getPurchase_price() ? $model->setPurchase_price($form->getPurchase_price()): '';
        null!==$form->getProvider_name() ? $model->setProvider_name($form->getProvider_name()): '';
        null!==$form->getProduct_tariff() ? $model->setProduct_tariff($form->getProduct_tariff()): '';        
        null!==$form->getTax_rate_id() ? $model->setTax_rate_id((int)$form->getTax_rate_id()): '';               
        null!==$form->getunit_id() ? $model->setUnit_id((int)$form->getUnit_id()): '';
        null!==$form->getFamily_id() ? $model->setFamily_id((int)$form->getFamily_id()): '';
        $this->repository->save($model);
    }

    public function editProduct(Product $model, ProductForm $form): void
    {
        null!==$form->getProduct_sku() ? $model->setProduct_sku($form->getProduct_sku()) : '';
        null!==$form->getProduct_name() ? $model->setProduct_name($form->getProduct_name()) : '';
        null!==$form->getProduct_description() ? $model->setProduct_description($form->getProduct_description()) : '';
        null!==$form->getProduct_price() ? $model->setProduct_price($form->getProduct_price()) : '';
        null!==$form->getPurchase_price() ? $model->setPurchase_price($form->getPurchase_price()) : '';
        null!==$form->getProvider_name() ? $model->setProvider_name($form->getProvider_name()) : '';
        null!==$form->getProduct_tariff() ? $model->setProduct_tariff($form->getProduct_tariff()) : '';        
        
        //Psalm Level 3: ERROR: PossiblyNullReference - src/Invoice/Product/ProductService.php:46:34 - Cannot call method getTax_rate_id on possibly null value (see https://psalm.dev/083)
        //&& $model->getTaxrate()->getTax_rate_id() == $form->getTax_rate_id()
        // https://stackoverflow.com/questions/12351737/is-there-a-nullsafe-operator-in-php
        // Use the null safe operator ie. '?->' to remove this error instead of psalm-suppress
        
        // if the dropdown value has changed,
        // reset the relation to null before using setter of individual field
        null!==$form->getTax_rate_id() 
        && $model->getTaxrate()?->getTax_rate_id() == $form->getTax_rate_id()
        ? $model->setTaxrate($model->getTaxrate()) : $model->setTaxrate(null);
        
        null!==$form->getTax_rate_id() ? $model->setTax_rate_id((int)$form->getTax_rate_id()) : '';       
        
        //Psalm Level 3: ERROR: PossiblyNullReference - src/Invoice/Product/ProductService.php:52:31 - Cannot call method getUnit_id on possibly null value (see https://psalm.dev/083)
        //&& $model->getUnit()->getUnit_id() == $form->getUnit_id()
        // https://stackoverflow.com/questions/12351737/is-there-a-nullsafe-operator-in-php
        // Use the null safe operator ie. '?->' to remove this error instead of psalm-suppress
        
        // if the dropdown value has changed,
        // reset the relation to null before using setter of individual field
        null!==$form->getUnit_id() 
        && $model->getUnit()?->getUnit_id() == $form->getUnit_id()
        ? $model->setUnit($model->getUnit()) : $model->setUnit(null);
        
        null!==$form->getUnit_id() ? $model->setUnit_id((int)$form->getUnit_id()) : '';
        
        //Psalm Level 3: ERROR: PossiblyNullReference - src/Invoice/Product/ProductService.php:58:33 - Cannot call method getFamily_id on possibly null value (see https://psalm.dev/083)
        //&& $model->getFamily()->getFamily_id() == $form->getFamily_id()
        // https://stackoverflow.com/questions/12351737/is-there-a-nullsafe-operator-in-php
        // Use the null safe operator to remove this error instead of psalm-suppress
        
        // if the dropdown value has changed,
        // reset the relation to null before using setter of individual field
        null!== $form->getFamily_id()
        && $model->getFamily()?->getFamily_id() == $form->getFamily_id()
        ? $model->setFamily($model->getFamily()) : $model->setFamily(null);
        
        null!== $form->getFamily_id() ? $model->setFamily_id((int)$form->getFamily_id()) : '';
        
        $this->repository->save($model);
    }
    
    public function deleteProduct(Product $model): void
    {
        $this->repository->delete($model);
    }
}

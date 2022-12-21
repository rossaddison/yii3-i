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

    public function editProduct(Product $model, ProductForm $form): void
    {
        $model->setProduct_sku($form->getProduct_sku());
        $model->setProduct_name($form->getProduct_name());
        $model->setProduct_description($form->getProduct_description());
        $model->setProduct_price($form->getProduct_price());
        $model->setPurchase_price($form->getPurchase_price());
        $model->setProvider_name($form->getProvider_name());
        $model->setProduct_tariff($form->getProduct_tariff());        
        null!==$form->getTax_rate_id() ? $model->setTaxrate($model->getTaxrate()->getTax_rate_id() == $form->getTax_rate_id() ? $model->getTaxrate() : null): '';
        $model->setTax_rate_id((int)$form->getTax_rate_id());       
        null!==$form->getUnit_id() ? $model->setUnit($model->getUnit()->getUnit_id() == $form->getUnit_id() ? $model->getUnit() : null) : '';
        $model->setUnit_id((int)$form->getUnit_id());
        null!== $form->getFamily_id() ?$model->setFamily($model->getFamily()->getFamily_id() == $form->getFamily_id() ? $model->getFamily() : null) : '';
        $model->setFamily_id((int)$form->getFamily_id());
        
        $this->repository->save($model);
    }
    
    public function addProduct(Product $model, ProductForm $form): void
    {
        $model->setProduct_sku($form->getProduct_sku());
        $model->setProduct_name($form->getProduct_name());
        $model->setProduct_description($form->getProduct_description());
        $model->setProduct_price($form->getProduct_price());
        $model->setPurchase_price($form->getPurchase_price());
        $model->setProvider_name($form->getProvider_name());
        $model->setProduct_tariff($form->getProduct_tariff());        
        $model->setTax_rate_id((int)$form->getTax_rate_id());               
        $model->setUnit_id((int)$form->getUnit_id());
        $model->setFamily_id((int)$form->getFamily_id());
        $this->repository->save($model);
    }
    
    public function deleteProduct(Product $model): void
    {
        $this->repository->delete($model);
    }
}

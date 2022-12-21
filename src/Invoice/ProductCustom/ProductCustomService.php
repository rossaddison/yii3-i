<?php

declare(strict_types=1); 

namespace App\Invoice\ProductCustom;

use App\Invoice\Entity\ProductCustom;


final class ProductCustomService
{
    private ProductCustomRepository $repository;

    public function __construct(ProductCustomRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveProductCustom(ProductCustom $model, ProductCustomForm $form): void
    { 
       $model->setProduct_id($form->getProduct_id());
       $model->setCustom_field_id($form->getCustom_field_id());
       $model->setValue($form->getValue());
       $this->repository->save($model);
    }
    
    public function deleteProductCustom(ProductCustom $model): void
    {
        $this->repository->delete($model);
    }
}
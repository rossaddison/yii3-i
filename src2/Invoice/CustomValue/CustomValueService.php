<?php

declare(strict_types=1); 

namespace App\Invoice\CustomValue;

final class CustomValueService
{
    private CustomValueRepository $repository;

    public function __construct(CustomValueRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveCustomValue(object $model, CustomValueForm $form): void
    { 
       null!== $form->getCustom_field_id() ? $model->setCustom_field_id($form->getCustom_field_id()) : '';
       null!== $form->getValue() ? $model->setValue($form->getValue()) : '';
       $this->repository->save($model);
    }
    
    public function deleteCustomValue(array|object|null $model): void
    {
       $this->repository->delete($model);
    }
}
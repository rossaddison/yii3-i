<?php

declare(strict_types=1); 

namespace App\Invoice\CustomField;

use App\Invoice\Entity\CustomField;


final class CustomFieldService
{

    private CustomFieldRepository $repository;

    public function __construct(CustomFieldRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveCustomField(object $model, CustomFieldForm $form): void
    {
       null!==$form->getTable() ? $model->setTable($form->getTable()) : '';
       null!==$form->getLabel() ? $model->setLabel($form->getLabel()) : '';
       null!==$form->getType() ? $model->setType($form->getType()) : '';
       null!==$form->getLocation() ? $model->setLocation($form->getLocation()) : '';
       null!==$form->getOrder() ? $model->setOrder($form->getOrder()) : '';
       $this->repository->save($model);
    }
    
    public function deleteCustomField(object $model): void
    {
        $this->repository->delete($model);
    }
}
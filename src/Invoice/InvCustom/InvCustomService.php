<?php

declare(strict_types=1); 

namespace App\Invoice\InvCustom;

use App\Invoice\Entity\InvCustom;


final class InvCustomService
{
    private InvCustomRepository $repository;

    public function __construct(InvCustomRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveInvCustom(object $model, InvCustomForm $form): void
    { 
       $form->getInv_id() ? $model->setInv_id($form->getInv_id()) : '';
       $form->getCustom_field_id() ? $model->setCustom_field_id($form->getCustom_field_id()) : '';
       $form->getValue() ? $model->setValue($form->getValue()) : '';
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param object $model
     * @return void
     */
    public function deleteInvCustom(object $model): void
    {
        $this->repository->delete($model);
    }
}
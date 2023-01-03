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

    public function saveInvCustom(InvCustom $model, InvCustomForm $form): void
    { 
       $model->setInv_id($form->getInv_id());
       $model->setCustom_field_id($form->getCustom_field_id());
       $model->setValue($form->getValue());
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|object|null $model
     * @return void
     */
    public function deleteInvCustom(array|object|null $model): void
    {
        $this->repository->delete($model);
    }
}
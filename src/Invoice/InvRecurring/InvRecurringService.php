<?php

declare(strict_types=1); 

namespace App\Invoice\InvRecurring;

use App\Invoice\Entity\InvRecurring;


final class InvRecurringService
{

    private InvRecurringRepository $repository;

    /**
     * 
     * @param InvRecurringRepository $repository
     */
    public function __construct(InvRecurringRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param InvRecurring $model
     * @param InvRecurringForm $form
     * @return void
     */
    public function saveInvRecurring(InvRecurring $model, InvRecurringForm $form): void
    {
       $model->setInv_id($form->getInv_id());
       $model->setStart($form->getStart());
       $model->setEnd($form->getEnd());
       $model->setFrequency($form->getFrequency());
       $model->setNext($form->getNext());
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param InvRecurring $model
     * @return void
     */
    public function deleteInvRecurring(InvRecurring $model): void
    {
        $this->repository->delete($model);
    }
}
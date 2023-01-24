<?php

declare(strict_types=1); 

namespace App\Invoice\InvItemAmount;

use App\Invoice\Entity\InvItemAmount;


final class InvItemAmountService
{
    private InvItemAmountRepository $repository;    

    public function __construct(InvItemAmountRepository $repository)
    {
        $this->repository = $repository;
    }
    
    public function saveInvItemAmountNoForm(object $model, array $invitem): void
    {        
       $model->setInv_item_id((int)$invitem['inv_item_id']);
       $model->setSubtotal($invitem['subtotal']);
       $model->setTax_total($invitem['taxtotal']);
       $model->setDiscount($invitem['discount']);
       $model->setTotal($invitem['total']); 
       $this->repository->save($model);
    }
    
    public function deleteInvItemAmount(object $model): void
    {
       $this->repository->delete($model);
    }
}
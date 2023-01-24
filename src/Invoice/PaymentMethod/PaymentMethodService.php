<?php

declare(strict_types=1); 

namespace App\Invoice\PaymentMethod;

final class PaymentMethodService
{

    private PaymentMethodRepository $repository;

    public function __construct(PaymentMethodRepository $repository)
    {
        $this->repository = $repository;
    }

    public function savePaymentMethod(object $model, PaymentMethodForm $form): void
    {
        
       $form->getName() ? $model->setName($form->getName()) : '';
 
       $this->repository->save($model);
    }
    
    public function deletePaymentMethod(object $model): void
    {
        $this->repository->delete($model);
    }
}
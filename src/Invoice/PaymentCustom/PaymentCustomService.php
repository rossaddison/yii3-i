<?php

declare(strict_types=1); 

namespace App\Invoice\PaymentCustom;

use App\Invoice\Entity\PaymentCustom;


final class PaymentCustomService
{

    private PaymentCustomRepository $repository;

    public function __construct(PaymentCustomRepository $repository)
    {
        $this->repository = $repository;
    }

    public function savePaymentCustom(PaymentCustom $model, PaymentCustomForm $form): void
    {
        
       $model->setPayment_id((int)$form->getPayment_id());
       $model->setCustom_field_id((int)$form->getCustom_field_id());
       $model->setValue($form->getValue());
 
       $this->repository->save($model);
    }
    
    public function editPaymentCustom(PaymentCustom $model, PaymentCustomForm $form): void
    {
       null!==$form->getPayment_id() ? $model->setPayment((int)$model->getPayment()->getId() 
                                     == $form->getPayment_id() 
                                     ? $model->getPayment() 
                                     : null)
                                     : ''; 
       $model->setPayment_id((int)$form->getPayment_id());
       
       null!==$form->getCustom_field_id() ? $model->setCustomField($model->getCustomField()->getId() 
                                          == $form->getCustom_field_id() 
                                          ? $model->getCustomField() 
                                          : null)
                                          : ''; 
       $model->setCustom_field_id((int)$form->getCustom_field_id());
       
       $model->setValue($form->getValue());
       
       $this->repository->save($model);
    }
    
    
    public function deletePaymentCustom(PaymentCustom $model): void
    {
        $this->repository->delete($model);
    }
}
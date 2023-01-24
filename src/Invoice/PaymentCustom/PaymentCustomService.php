<?php

declare(strict_types=1); 

namespace App\Invoice\PaymentCustom;

final class PaymentCustomService
{

    private PaymentCustomRepository $repository;

    public function __construct(PaymentCustomRepository $repository)
    {
        $this->repository = $repository;
    }

    public function savePaymentCustom(object $model, PaymentCustomForm $form): void
    {
        
       $form->getPayment_id() ? $model->setPayment_id($form->getPayment_id()) : '';
       $form->getCustom_field_id() ? $model->setCustom_field_id($form->getCustom_field_id()) : '';
       $form->getValue() ? $model->setValue($form->getValue()) : '';
 
       $this->repository->save($model);
    }
    
    public function editPaymentCustom(object $model, PaymentCustomForm $form): void
    {
       null!==$form->getPayment_id() ? $model->setPayment((int)$model->getPayment()?->getId() 
                                     == $form->getPayment_id() 
                                     ? $model->getPayment() 
                                     : null)
                                     : ''; 
       $model->setPayment_id((int)$form->getPayment_id());
       
       null!==$form->getCustom_field_id() ? $model->setCustomField($model->getCustomField()?->getId() 
                                          == $form->getCustom_field_id() 
                                          ? $model->getCustomField() 
                                          : null)
                                          : ''; 
       $model->setCustom_field_id((int)$form->getCustom_field_id());
       
       $form->getValue() ? $model->setValue($form->getValue()) : '';
       
       $this->repository->save($model);
    }
    
    
    public function deletePaymentCustom(object $model): void
    {
        $this->repository->delete($model);
    }
}
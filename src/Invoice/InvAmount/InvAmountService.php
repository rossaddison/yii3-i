<?php

declare(strict_types=1); 

namespace App\Invoice\InvAmount;

use App\Invoice\Entity\InvAmount;


final class InvAmountService
{

    private InvAmountRepository $repository;

    public function __construct(InvAmountRepository $repository)
    {
        $this->repository = $repository;
    }
    
    public function initializeInvAmount(object $model, string $inv_id) : void
    {
       $inv_id ? $model->setInv_id((int)$inv_id) : '';
       $model->setSign(1);
       $model->setItem_subtotal(0.00);
       $model->setItem_tax_total(0.00);
       $model->setTax_total(0.00);
       $model->setTotal(0.00); 
       $model->setPaid(0.00);
       $model->setBalance(0.00); 
       $this->repository->save($model);
    }

    public function initializeCreditInvAmount(object $model, int $basis_inv_id, string $new_inv_id) : void
    {
       $basis_invoice = $this->repository->repoInvquery($basis_inv_id);
       if (null!==$basis_invoice) {
        $new_inv_id ? $model->setInv_id((int)$new_inv_id) : '';
        $model->setSign(1);
        null!==$basis_invoice->getItem_subtotal() ? $model->setItem_subtotal($basis_invoice->getItem_subtotal()*-1) : '';
        null!==$basis_invoice->getItem_tax_total() ? $model->setItem_tax_total($basis_invoice->getItem_tax_total()*-1) : '';
        null!==$basis_invoice->getTax_total() ? $model->setTax_total($basis_invoice->getTax_total()*-1) : '';
        null!==$basis_invoice->getTotal() ? $model->setTotal($basis_invoice->getTotal()*-1) : ''; 
        $model->setPaid(0.00);
        null!==$basis_invoice->getBalance() ? $model->setBalance($basis_invoice->getBalance()*-1) : ''; 
        $this->repository->save($model);
       }
    }

    public function initializeCopyInvAmount(object $model, int $basis_inv_id, string $new_inv_id) : void
    {
        $basis_invoice = $this->repository->repoInvquery($basis_inv_id);
        if ($basis_invoice) {
            $new_inv_id ? $model->setInv_id((int)$new_inv_id) : '';
            $model->setSign(1);
            /** @psalm-suppress PossiblyNullArgument, PossiblyNullReference */
            $model->setItem_subtotal($basis_invoice->getItem_subtotal());
            $model->setItem_tax_total($basis_invoice->getItem_tax_total() ?: 0.00);
            $model->setTax_total($basis_invoice->getTax_total() ?: 0.00);
            $model->setTotal($basis_invoice->getTotal() ?: 0.00); 
            $model->setPaid(0.00);
            $model->setBalance($basis_invoice->getTotal() ?: 0.00); 
            $this->repository->save($model);
        }
    } 

    public function saveInvAmount(object $model, InvAmountForm $form): void
    {  
       $form->getInv_id() ? $model->setInv_id($form->getInv_id()) : '';
       $model->setSign(1);
       $form->getItem_subtotal() ? $model->setItem_subtotal($form->getItem_subtotal()) : '';
       $form->getItem_tax_total() ? $model->setItem_tax_total($form->getItem_tax_total()) : '';
       $form->getTax_total() ? $model->setTax_total($form->getTax_total()) : '';
       $form->getTotal() ? $model->setTotal($form->getTotal()) : ''; 
       $form->getPaid() ? $model->setPaid($form->getPaid()) : '';
       $form->getBalance() ? $model->setBalance($form->getBalance()) : ''; 
       $this->repository->save($model);
    }
    
    public function saveInvAmountViaCalculations(object $model, array  $array): void
    {        
       $model->setInv_id($array['inv_id']);
       $model->setItem_subtotal($array['item_subtotal']);
       $model->setItem_tax_total($array['item_taxtotal']);
       $model->setTax_total($array['tax_total']);
       $model->setTotal($array['total']);
       $model->setPaid($array['paid']);
       $model->setBalance($array['balance']);
       $this->repository->save($model);
    }
    
    public function deleteInvAmount(object $model): void
    {
        $this->repository->delete($model);
    }
}
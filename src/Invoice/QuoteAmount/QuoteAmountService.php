<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteAmount;

final class QuoteAmountService
{

    private QuoteAmountRepository $repository;

    public function __construct(QuoteAmountRepository $repository)
    {
        $this->repository = $repository;
    }
    
    public function initializeQuoteAmount(object $model, int $quote_id) : void
    {
       $model->setQuote_id($quote_id);
       $model->setItem_subtotal(0.00);
       $model->setItem_tax_total(0.00);
       $model->setTax_total(0.00);
       $model->setTotal(0.00); 
       $this->repository->save($model);
    }

    /**
     * @param null|string $new_quote_id
     */
    public function initializeCopyQuoteAmount(object $model, string $basis_quote_id, string|null $new_quote_id) : void
    {
       $basis_quote = $this->repository->repoQuotequery($basis_quote_id);
       if ($basis_quote) {
        $model->setQuote_id((int)$new_quote_id);
        $model->setItem_subtotal($basis_quote->getItem_subtotal());
        $model->setItem_tax_total($basis_quote->getItem_tax_total());
        $model->setTax_total($basis_quote->getTax_total());
        $model->setTotal($basis_quote->getTotal()); 
        $this->repository->save($model);
       } 
    } 

    public function saveQuoteAmount(object $model, QuoteAmountForm $form): void
    {        
       $model->setQuote_id($form->getQuote_id());
       $model->setItem_subtotal($form->getItem_subtotal());
       $model->setItem_tax_total($form->getItem_tax_total());
       $model->setTax_total($form->getTax_total());
       $model->setTotal($form->getTotal()); 
       $this->repository->save($model);
    }
    
    public function saveQuoteAmountViaCalculations(object $model, $array): void
    {        
       $model->setQuote_id($array['quote_id']);
       $model->setItem_subtotal($array['item_subtotal']);
       $model->setItem_tax_total($array['item_taxtotal']);
       $model->setTax_total($array['tax_total']);
       $model->setTotal($array['total']);
       $this->repository->save($model);
    }
    
    public function deleteQuoteAmount(object|null $model): void
    {
        $this->repository->delete($model);
    }
}
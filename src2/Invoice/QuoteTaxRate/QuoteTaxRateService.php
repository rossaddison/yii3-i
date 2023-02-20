<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteTaxRate;


final class QuoteTaxRateService
{

    private QuoteTaxRateRepository $repository;

    public function __construct(QuoteTaxRateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveQuoteTaxRate(object $model, QuoteTaxRateForm $form): void
    {
        
       $model->setQuote_id($form->getQuote_id());
       $model->setTax_rate_id($form->getTax_rate_id());
       $model->setInclude_item_tax($form->getInclude_item_tax());
       $model->setQuote_tax_rate_amount($form->getQuote_tax_rate_amount());
 
        $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|object|null $model
     * @return void
     */
    public function deleteQuoteTaxRate(array|object|null $model): void
    {
        $this->repository->delete($model);
    }
}
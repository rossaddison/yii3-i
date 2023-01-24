<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteCustom;


final class QuoteCustomService
{
    private QuoteCustomRepository $repository;

    public function __construct(QuoteCustomRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveQuoteCustom(object $model, QuoteCustomForm $form): void
    { 
       $model->setQuote_id($form->getQuote_id());
       $model->setCustom_field_id($form->getCustom_field_id());
       $model->setValue($form->getValue());
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|object|null $model
     * @return void
     */
    public function deleteQuoteCustom(array|object|null $model): void
    {
        $this->repository->delete($model);
    }
}
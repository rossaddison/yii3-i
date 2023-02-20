<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use App\Invoice\Entity\TaxRate;

final class TaxRateService
{
    private TaxRateRepository $repository;

    public function __construct(TaxRateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveTaxRate(object $model, TaxRateForm $form): void
    {
        $form->getTax_rate_name() ? $model->setTax_rate_name($form->getTax_rate_name()) : '';
        $form->getTax_rate_percent() ? $model->setTax_rate_percent($form->getTax_rate_percent()) : '';
        $form->getTax_rate_default() ? $model->setTax_rate_default($form->getTax_rate_default()) : '';        
        
        if ($model->isNewRecord()) {
            $model->setTax_rate_default(false);
        }
        
        $this->repository->save($model);
    }
    
    public function deleteTaxRate(object $model): void
    {
        $this->repository->delete($model);
    }
}

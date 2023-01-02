<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use Yiisoft\Form\FormModel;

final class TaxRateForm extends FormModel
{
    private ?string $tax_rate_name = null;
    
    private ?float $tax_rate_percent = 0.00;
    
    private ?bool $tax_rate_default = false;
    
    public function getTax_rate_name(): string|null
    {
        return $this->tax_rate_name;
    }
    
    public function getTax_rate_percent() : float|null
    {
        return $this->tax_rate_percent;
    }
    
    public function getTax_rate_default() : bool|null
    {
        return $this->tax_rate_default;
    }
    
    /**
     * @return string
     *
     * @psalm-return ''
     */
    public function getFormName(): string
    {
        return '';
    }
}

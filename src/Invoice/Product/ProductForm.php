<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ProductForm extends FormModel
{
    private ?string $product_sku = null;
    private ?string $product_name = null;
    private ?string $product_description = null;
    private ?float $product_price = 0.00;
    private ?float $purchase_price = 0.00;
    private ?string $provider_name = null;
    
    // Get => string;  Set => int
    private ?string $family_id = '';
    
    // Get => string;  Set => int
    private ?string $tax_rate_id = '';
    
    // Get => string;  Set => int
    private ?string $unit_id = '';
    
    private ?int $product_tariff = null;
               
    public function getProduct_sku(): string|null
    {
        return $this->product_sku;
    }
    
    public function getProduct_name(): string|null
    {
        return $this->product_name;
    }
    
    public function getProduct_description(): string|null
    {
        return $this->product_description;
    }
    
    public function getProduct_price(): float|null
    {
        return $this->product_price;
    }
    
    public function getPurchase_price(): float|null
    {
        return $this->purchase_price;
    }
    
    public function getProvider_name(): string|null
    {
        return $this->provider_name;
    }
    
    public function getFamily_id(): string|null
    {
        return $this->family_id;
    }
    
    public function getTax_rate_id(): string|null
    {
        return $this->tax_rate_id;
    }
    
    public function getUnit_id(): string|null
    {
        return $this->unit_id;
    }  
    
    public function getProduct_tariff(): int|null
    {
        return $this->product_tariff;
    }
    
    public function getFormName(): string
    {
        return '';
    }
    
    public function getRules(): array
    {
        return [
            'family_id' => [new Required()],
            'product_name' => [new Required()],
            'tax_rate_id' => [new Required()],
            'unit_id'=>[new Required()],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Invoice\PaymentCustom;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class PaymentCustomForm extends FormModel
{    
    
    private ?int $payment_id=null;
    private ?int $custom_field_id=null;
    private ?string $value='';

    public function getPayment_id() : int|null
    {
      return $this->payment_id;
    }

    public function getCustom_field_id() : int|null
    {
      return $this->custom_field_id;
    }

    public function getValue() : string|null
    {
      return $this->value;
    }

    public function getFormName(): string
    {
      return '';
    }

    public function getRules(): array    {
      return [
        'payment_id' => [new Required()],
        'custom_field_id' => [new Required()],
        'value' => [new Required()]
    ];
}
}

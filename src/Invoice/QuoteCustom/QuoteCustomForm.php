<?php

declare(strict_types=1);

namespace App\Invoice\QuoteCustom;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class QuoteCustomForm extends FormModel
{    
    
    private ?int $quote_id=null;
    private ?int $custom_field_id=null;
    private ?string $value='';

    public function getQuote_id() : int|null
    {
      return $this->quote_id;
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
        'value' => [new Required()],
    ];
}
}

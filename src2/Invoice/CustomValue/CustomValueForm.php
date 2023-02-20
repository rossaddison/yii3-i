<?php

declare(strict_types=1);

namespace App\Invoice\CustomValue;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class CustomValueForm extends FormModel
{    
    
    private ?int $custom_field_id=null;
    private ?string $value='';

    public function getCustom_field_id() : int|null
    {
      return $this->custom_field_id;
    }

    public function getValue() : string|null
    {
      return $this->value;
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

    /**
     * @return Required[][]
     *
     * @psalm-return array{value: list{Required}}
     */
    public function getRules(): array    {
      return [
        'value' =>[new Required()],
    ];
}
}

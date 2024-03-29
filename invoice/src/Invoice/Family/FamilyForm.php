<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class FamilyForm extends FormModel
{
    private string $family_name = '';
    
    public function getFamily_name(): string
    {
        return $this->family_name;
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
     * @psalm-return array{family_name: list{Required}}
     */
    public function getRules(): array
    {
        return [
            'family_name' => [new Required()],
        ];
    }
}

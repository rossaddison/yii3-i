<?php

declare(strict_types=1);

namespace App\Invoice\UserCustom;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class UserCustomForm extends FormModel
{    
    
    private ?int $user_id=null;
    private ?int $fieldid=null;
    private ?string $fieldvalue='';

    public function getUser_id() : int|null
    {
      return $this->user_id;
    }

    public function getFieldid() : int|null
    {
      return $this->fieldid;
    }

    public function getFieldvalue() : string|null
    {
      return $this->fieldvalue ?? '';
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
     * @return Required[][][]
     *
     * @psalm-return array{fieldvalue: list{list{Required}}}
     */
    public function getRules(): array    {
      return [
        'fieldvalue' => [
             [new Required()],
        ],
    ];
}
}

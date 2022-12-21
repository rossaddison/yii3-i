<?php

declare(strict_types=1);

namespace App\Invoice\Merchant;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class MerchantForm extends FormModel
{    
    
    private ?int $inv_id=null;
    private ?bool $successful=true;
    private ?string $date='';
    private ?string $driver='';
    private ?string $response='';
    private ?string $reference='';

    public function getInv_id() : int|null
    {
      return $this->inv_id;
    }

    public function getSuccessful() : bool|null
    {
      return $this->successful;
    }

    public function getDate() : ?\DateTime
    {
        return new \DateTime($this->date);       
    }

    public function getDriver() : string|null
    {
      return $this->driver;
    }

    public function getResponse() : string|null
    {
      return $this->response;
    }

    public function getReference() : string|null
    {
      return $this->reference;
    }

    public function getFormName(): string
    {
      return '';
    }

    public function getRules(): array    {
      return [
        'successful' => [new Required()],
        'date' => [new Required()],
        'driver' => [new Required()],
        'response' => [new Required()],
        'reference' => [new Required()],
    ];
}
}

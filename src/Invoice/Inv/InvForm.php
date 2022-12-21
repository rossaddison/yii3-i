<?php
declare(strict_types=1);

namespace App\Invoice\Inv;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class InvForm extends FormModel
{    
    private ?string $number ='';
    private ?string $date_created='';
    private ?string $quote_id='';
    private ?string $group_id='';
    private ?string $client_id='';    
    private ?int $creditinvoice_parent_id=null;
    private ?int $status_id=1;
    private ?float $discount_amount=0.00;
    private ?float $discount_percent=0.00;
    private ?string $url_key='';
    private ?string $password='';
    private ?int $payment_method=0;
    private ?string $terms='';    

    public function getDate_created() : string|null 
    {
        return $this->date_created;
    }
    
    public function getQuote_id() : string|null
    {
      return $this->quote_id;
    }

    public function getClient_id() : string|null
    {
      return $this->client_id;
    }

    public function getGroup_id() : string|null
    {
      return $this->group_id;
    }
    
    public function getCreditinvoice_parent_id() : int|null
    {
      return $this->creditinvoice_parent_id;
    }

    public function getStatus_id() : int|null
    {
      return $this->status_id;
    }
        
    public function getNumber() : string|null
    {
      return $this->number;
    }
    
    public function getDiscount_amount() : float|null
    {
      return $this->discount_amount;
    }

    public function getDiscount_percent() : float|null
    {
      return $this->discount_percent;
    }

    public function getUrl_key() : string|null
    {
      return $this->url_key;
    }

    public function getPassword() : string|null
    {
      return $this->password;
    }
    
    public function getPayment_method() : int|null
    {
      return $this->payment_method;
    }

    public function getTerms() : string|null
    {
      return $this->terms;
    }

    public function getFormName(): string
    {
      return '';
    }
    
    public function getRules(): array    {
      return [
        'client_id' => [new Required()],
        'group_id' => [new Required()],
      ];
    }
}

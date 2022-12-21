<?php

declare(strict_types=1);

namespace App\Invoice\Task;

use App\Invoice\Helpers\DateHelper;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class TaskForm extends FormModel
{        
    private ?int $project_id=null;
    private ?string $name='';
    private ?string $description='';
    private ?float $price=null;
    private ?string $finish_date='';
    private ?int $status=null;
    private ?int $tax_rate_id=null;

    public function getProject_id() : int|null
    {
      return $this->project_id;
    }

    public function getName() : string|null
    {
      return $this->name;
    }

    public function getDescription() : string|null
    {
      return $this->description;
    }

    public function getPrice() : float|null
    {
      return $this->price;
    }
    
    public function getFinish_date(\App\Invoice\Setting\SettingRepository $s) : \DateTime|false
    {
        $datehelper = new DateHelper($s);         
        $finish_date = $datehelper->datetime_zone_style($this->finish_date);
        return $finish_date;
    }

    public function getStatus() : int|null
    {
      return $this->status;
    }

    public function getTax_rate_id() : int|null
    {
      return $this->tax_rate_id;
    }

    public function getFormName(): string
    {
      return '';
    }

    public function getRules(): array    {
    return [
      'name' => [new Required()],
      'description' => [new Required()],
      'price' => [new Required()],
      'tax_rate_id' => [new Required()],
      'finish_date' => [new Required()],
      // The project_id is nullable (see #[BelongsTo(target:Project::class, nullable: true)
    ];
    }
}

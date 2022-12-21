<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use App\Invoice\Entity\Gentor;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\GeneratorRelation\GeneratorRelationRepository::class)] 
class GentorRelation
{
    #[Column(type: 'primary')]
    private ?int $id = null;
    
    #[Column(type: 'text',nullable: true)]
    private ?string $lowercasename = null;
    
    #[Column(type: 'text',nullable: true)]
    private ?string $camelcasename = null;
    
    #[Column(type: 'text',nullable: true)]
    private ?string $view_field_name = null;
    
    #[BelongsTo(target: Gentor::class, nullable: false, fkAction:'NO ACTION')]
    private ?Gentor $gentor = null;
    
    #[Column(type: 'integer(11)',nullable: true, default: null)]
    private ?int $gentor_id = null;
    
    public function __construct(
            string $lowercasename='',
            string $camelcasename='',
            string $view_field_name='',
            int $gentor_id=null
    )
    {
        $this->lowercasename = $lowercasename;
        $this->camelcasename = $camelcasename;
        $this->view_field_name = $view_field_name;
        $this->gentor_id = $gentor_id;
    }
    
    public function getRelation_id(): ?string
    {
        return (string)$this->id;
    }
    
    //relation $gentor
    public function getGentor(): Gentor|null
    {
        return $this->gentor;
    }

    public function getLowercase_name(): string|null
    {
        return $this->lowercasename;
    }

    public function setLowercase_name(string $lowercasename): void
    {
        $this->lowercasename = $lowercasename;
    }
    
    public function getCamelcase_name(): string|null
    {
        return $this->camelcasename;
    }

    public function setCamelcase_name(string $camelcasename): void
    {
        $this->camelcasename = $camelcasename;
    }
    
    public function getView_field_name(): string|null
    {
        return $this->view_field_name;
    }

    public function setView_field_name(string $view_field_name): void
    {
        $this->view_field_name = $view_field_name;
    }
    
    public function getGentor_id(): int|null
    {
        return $this->gentor_id;
    }

    public function setGentor_id(int $gentor_id): void
    {
        $this->gentor_id = $gentor_id;
    }
}

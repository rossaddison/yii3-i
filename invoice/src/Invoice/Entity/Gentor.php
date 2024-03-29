<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\Generator\GeneratorRepository::class)] 
class Gentor
{
    #[Column(type: 'primary')]
    private ?int $id = null;
    
    #[Column(type: 'string(20)')]
    private ?string $route_prefix = '';
    
    #[Column(type: 'string(20)')]
    private ?string $route_suffix = '';
    
    #[Column(type: 'string(50)')]
    private ?string $camelcase_capital_name = '';
    
    #[Column(type: 'string(20)')]
    private ?string $small_singular_name = '';
    
    #[Column(type: 'string(20)')]
    private ?string $small_plural_name = '';
    
    #[Column(type: 'string(100)')]
    private ?string $namespace_path = '';
    
    #[Column(type: 'string(100)')]
    private ?string $controller_layout_dir = '';
    
    #[Column(type: 'string(100)')]
    private ?string $controller_layout_dir_dot_path = '';  
    
    #[Column(type: 'string(50)')]
    private ?string $repo_extra_camelcase_name = '';
    
    #[Column(type: 'string(50)')]
    private ?string $paginator_next_page_attribute = '';
    
    #[Column(type: 'string(50)')]
    private string $constrain_index_field = '';
    
    #[Column(type: 'string(20)')]
    private ?string $filter_field = '';
    
    #[Column(type: 'tinyInteger(4)', nullable:true)]
    private ?int $filter_field_start_position = null;
    
    #[Column(type: 'tinyInteger(4)', nullable:true)]
    private ?int $filter_field_end_position = null;
    
    #[Column(type: 'string(50)')]
    private ?string $pre_entity_table = '';
    
    #[Column(type: 'bool', default: false)]
    private bool $modified_include = false;
    
    #[Column(type: 'bool', default: false)]
    private bool $created_include = false;
    
    #[Column(type: 'bool', default: false)]
    private bool $updated_include = false;
    
    #[Column(type: 'bool', default: false)]
    private bool $deleted_include = false;
    
    #[Column(type: 'bool', default: false)]
    private bool $keyset_paginator_include = false;
    
    #[Column(type: 'bool', default: false)]
    private bool $offset_paginator_include = false;
    
    #[Column(type: 'bool', default: true)]
    private bool $flash_include = true;
    
    #[Column(type: 'bool', default: false)]
    private bool $headerline_include = false;
    
    public function __construct(
      string $route_prefix='',
      string $route_suffix='',
      string $camelcase_capital_name = '',
      string $small_singular_name ='',
      string $small_plural_name ='',
      string $namespace_path ='',
      string $controller_layout_dir = 'dirname(dirname(__DIR__)',
      string $controller_layout_dir_dot_path = '/Invoice/Layout/main.php',
      string $repo_extra_camelcase_name ='',
      string $paginator_next_page_attribute = '',
      string $pre_entity_table = '',
      string $constrain_index_field = '',
      string $filter_field = '',
      int $filter_field_start_position = null,
      int $filter_field_end_position = null,      
      bool $created_include = false,
      bool $updated_include = false,
      bool $modified_include = false,
      bool $deleted_include = false,      
      bool $keyset_paginator_include = false,
      bool $offset_paginator_include = false,      
      bool $flash_include = false,
      bool $headerline_include = false
    )
    {
      $this->route_prefix = $route_prefix;
      $this->route_suffix = $route_suffix;
      $this->camelcase_capital_name = $camelcase_capital_name;
      $this->small_singular_name = $small_singular_name;
      $this->small_plural_name = $small_plural_name;
      $this->namespace_path = $namespace_path;
      $this->controller_layout_dir = $controller_layout_dir;
      $this->controller_layout_dir_dot_path = $controller_layout_dir_dot_path;
      $this->repo_extra_camelcase_name = $repo_extra_camelcase_name;
      $this->paginator_next_page_attribute = $paginator_next_page_attribute;
      $this->pre_entity_table = $pre_entity_table;      
      $this->constrain_index_field = $constrain_index_field;      
      $this->filter_field = $filter_field;
      $this->filter_field_start_position = $filter_field_start_position;
      $this->filter_field_end_position = $filter_field_end_position;
      $this->created_include = $created_include;
      $this->updated_include = $updated_include;
      $this->modified_include = $modified_include;
      $this->deleted_include = $deleted_include;
      $this->keyset_paginator_include = $keyset_paginator_include;
      $this->offset_paginator_include = $offset_paginator_include;
      $this->flash_include = $flash_include;
      $this->headerline_include = $headerline_include;
    }
    public function getGentor_id(): string
    {
        return (string)$this->id;
    }    
    
    public function getRoute_prefix(): string|null
    {
        return $this->route_prefix;
    }
    
    public function setRoute_prefix(string $route_prefix): void
    {
        $this->route_prefix = $route_prefix;
    }
    
    public function getRoute_suffix(): string|null
    {
        return $this->route_suffix;
    }
    
    public function setRoute_suffix(string $route_suffix): void
    {
        $this->route_suffix = $route_suffix;
    }
    
    public function getCamelcase_capital_name(): string|null
    {
        return $this->camelcase_capital_name;
    }
    
    public function setCamelcase_capital_name(string $camelcase_capital_name): void
    {
        $this->camelcase_capital_name = $camelcase_capital_name;     
    }
    
    public function getSmall_singular_name(): string|null
    {
        return $this->small_singular_name;
    }
    
    public function setSmall_singular_name(string $small_singular_name): void
    {
        $this->small_singular_name = $small_singular_name;
    }
    
    public function getSmall_plural_name(): string|null
    {
        return $this->small_plural_name;
    }
    
    public function setSmall_plural_name(string $small_plural_name): void
    {
        $this->small_plural_name = $small_plural_name;
    }
    
    public function getNamespace_path(): string|null
    {
        return $this->namespace_path;
    }
    
    public function setNamespace_path(string $namespace_path): void
    {
        $this->namespace_path = $namespace_path;
    }
    
    public function getController_layout_dir(): string|null
    {
        return $this->controller_layout_dir;
    }
    
    public function setController_layout_dir(string $controller_layout_dir): void
    {
        $this->controller_layout_dir = $controller_layout_dir;
    }
    
    public function getController_layout_dir_dot_path(): string|null
    {
        return $this->controller_layout_dir_dot_path;
    }
    
    public function setController_layout_dir_dot_path(string $controller_layout_dir_dot_path): void
    {
        $this->controller_layout_dir_dot_path = $controller_layout_dir_dot_path;
    }
    
    public function getRepo_extra_camelcase_name(): string|null
    {
        return $this->repo_extra_camelcase_name;
    }
    
    public function setRepo_extra_camelcase_name(string $repo_extra_camelcase_name): void
    {
        $this->repo_extra_camelcase_name = $repo_extra_camelcase_name;
    }
    
    public function getPaginator_next_page_attribute(): string|null
    {
        return $this->paginator_next_page_attribute;
    }
    
    public function setPaginator_next_page_attribute(string $paginator_next_page_attribute): void
    {
        $this->paginator_next_page_attribute = $paginator_next_page_attribute;
    }
    
    public function getPre_entity_table(): string|null
    {
        return $this->pre_entity_table;
    }
    
    public function setPre_entity_table(string $pre_entity_table): void
    {
        $this->pre_entity_table = $pre_entity_table;
    }
    
    public function getConstrain_index_field(): string
    {
        return $this->constrain_index_field;
    }
    
    public function setConstrain_index_field(string $constrain_index_field): void
    {
        $this->constrain_index_field = $constrain_index_field;
    }
    
    public function getFilter_field(): string|null
    {
        return $this->filter_field;
    }
    
    public function setFilter_field(string $filter_field): void
    {
        $this->filter_field = $filter_field;
    }
    
    public function getFilter_field_start_position(): ?int
    {
        return $this->filter_field_start_position;
    }
    
    public function setFilter_field_start_position (?int $filter_field_start_position): void
    {
        $this->filter_field_start_position = $filter_field_start_position;
    }
    
    public function getFilter_field_end_position(): ?int
    {
        return $this->filter_field_end_position;
    }
    
    public function setFilter_field_end_position (?int $filter_field_end_position): void
    {
        $this->filter_field_end_position = $filter_field_end_position;
    }
    
    public function isCreated_include(): bool
    {
        return $this->created_include;
    }
    
    public function setCreated_include(bool $created_include): void
    {
        $this->created_include = $created_include;
    }
    
    public function isUpdated_include(): bool
    {
        return $this->updated_include;
    }
    
    public function setUpdated_include(bool $updated_include): void
    {
        $this->updated_include = $updated_include;
    }
    
    public function isModified_include(): bool
    {
        return $this->modified_include;
    }
    
    public function setModified_include(bool $modified_include): void
    {
        $this->modified_include = $modified_include;
    }
    
    public function isDeleted_include(): bool
    {
        return $this->deleted_include;
    }
    
    public function setDeleted_include(bool $deleted_include): void
    {
        $this->deleted_include = $deleted_include;
    }
    
    public function isKeyset_paginator_include(): bool
    {
        return $this->keyset_paginator_include;
    }
    
    public function setKeyset_paginator_include(bool $keyset_paginator_include): void
    {
        $this->keyset_paginator_include = $keyset_paginator_include;
    }
    
    public function isOffset_paginator_include(): bool
    {
        return $this->offset_paginator_include;
    }
    
    public function setOffset_paginator_include(bool $offset_paginator_include): void
    {
        $this->offset_paginator_include = $offset_paginator_include;
    }
    
    public function isFlash_include(): bool
    {
        return $this->flash_include;
    }
    
    public function setFlash_include(bool $flash_include): void
    {
        $this->flash_include = $flash_include;
    }
    
    public function isHeaderline_include(): bool
    {
        return $this->headerline_include;
    }
    
    public function setHeaderline_include(bool $headerline_include): void
    {
        $this->headerline_include = $headerline_include;
    }
}

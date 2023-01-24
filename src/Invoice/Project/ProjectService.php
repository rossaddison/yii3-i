<?php

declare(strict_types=1); 

namespace App\Invoice\Project;

use App\Invoice\Entity\Project;


final class ProjectService
{

    private ProjectRepository $repository;

    public function __construct(ProjectRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveProject(object $model, ProjectForm $form): void
    {
       $form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
       $form->getName() ? $model->setName($form->getName()) : ''; 
       $this->repository->save($model);
    }
    
    public function deleteProject(object $model): void
    {
        $this->repository->delete($model);
    }
}
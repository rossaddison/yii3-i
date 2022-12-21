<?php

declare(strict_types=1); 

namespace App\Invoice\Group;

use App\Invoice\Entity\Group;


final class GroupService
{

    private GroupRepository $repository;

    public function __construct(GroupRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveGroup(Group $model, GroupForm $form): void
    {
       $model->setName($form->getName() ?: 'Name');
       $model->setIdentifier_format($form->getIdentifier_format() ?: 'AAA{{{id}}}');
       $model->setNext_id($form->getNext_id() ?: 0);
       $model->setLeft_pad($form->getLeft_pad() ?: 0);
 
       $this->repository->save($model);
    }
    
    public function deleteGroup(Group $model): void
    {
       $this->repository->delete($model);
    }
}
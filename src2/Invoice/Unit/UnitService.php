<?php

declare(strict_types=1);

namespace App\Invoice\Unit;

final class UnitService
{
    private UnitRepository $repository;

    public function __construct(UnitRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveUnit(object $model, UnitForm $form): void
    {
        $form->getUnit_name() ? $model->setUnit_name($form->getUnit_name()) : '';
        $form->getUnit_name_plrl() ? $model->setUnit_name_plrl($form->getUnit_name_plrl()) : '';
        $this->repository->save($model);
    }
    
    public function deleteUnit(object $model): void
    {
        $this->repository->delete($model);
    }
}

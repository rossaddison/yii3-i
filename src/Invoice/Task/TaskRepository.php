<?php

declare(strict_types=1); 

namespace App\Invoice\Task;

use App\Invoice\Entity\Task;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\DataReaderInterface;
use Cycle\Database\Injection\Parameter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

final class TaskRepository extends Select\Repository
{
private EntityWriter $entityWriter;

    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }

    /**
     * Get tasks  without filter
     *
     * @psalm-return DataReaderInterface<int,Task>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select()->load('tax_rate');
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, Task>
     */
    public function getReader(): DataReaderInterface
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }
    
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }
    
    /**
     * @throws Throwable
     */
    public function save(Task $task): void
    {
        $this->entityWriter->write([$task]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(Task $task): void
    {
        $this->entityWriter->delete([$task]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    public function repoTaskquery(string $id): object|null    {
        $query = $this->select()->load('tax_rate')
                                ->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    
    /**
     * Get tasks  with filter
     *
     * @psalm-return DataReaderInterface<int,Task>
     */
    public function repoTaskStatusquery(int $status): DataReaderInterface {
        $query = $this->select()->load('tax_rate')
                                ->where(['status' =>$status]);
        return $this->prepareDataReader($query);        
    }
    
     /**
     * Get selection of tasks from all tasks
     *
     * @psalm-return DataReaderInterface<int, Task>
     */
    
    public function findinTasks($task_ids) : DataReaderInterface {
        $query = $this->select()
                      ->where(['id'=>['in'=> new Parameter($task_ids)]]);
        return $this->prepareDataReader($query);    
    } 
    
    public function repoCount(int $task_id) : int {
        $count = $this->select()
                      ->where(['id'=>$task_id])
                      ->count();
        return $count;
    }
    
    /**
     * @return array
     */
    public function getTask_statuses($sR)
    {
        return [
            '1' => [
                'label' => $sR->trans('not_started'),
                'class' => 'draft'
            ],
            '2' => [
                'label' => $sR->trans('in_progress'),
                'class' => 'viewed'
            ],
            '3' => [
                'label' => $sR->trans('complete'),
                'class' => 'sent'
            ],
            '4' => [
                'label' => $sR->trans('invoiced'),
                'class' => 'paid'
            ]
        ];
    }
}
<?php

declare(strict_types=1); 

namespace App\Invoice\Task;

use Cycle\ORM\Select;
use Throwable;
use Cycle\Database\Injection\Parameter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class TaskRepository extends Select\Repository
{
private EntityWriter $entityWriter;
    
     /**
     * 
     * @param Select<TEntity> $select     
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }

    /**
     * Get tasks  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('tax_rate');
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }
    
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $task
     * @throws Throwable 
     * @return void
     */
    public function save(array|object|null $task): void
    {
        $this->entityWriter->write([$task]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $task
     * @throws Throwable 
     * @return void
     */
    public function delete(array|object|null $task): void
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
    
    /**
     * @return null|object
     *
     * @psalm-return TEntity|null
     */
    public function repoTaskquery(string $id):object|null    {
        $query = $this->select()->load('tax_rate')
                                ->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    
    /**
     * Get tasks  with filter
     *
     * @psalm-return EntityReader
     */
    public function repoTaskStatusquery(int $status): EntityReader {
        $query = $this->select()->load('tax_rate')
                                ->where(['status' =>$status]);
        return $this->prepareDataReader($query);        
    }
    
     /**
     * Get selection of tasks from all tasks
     *
     * @psalm-return EntityReader
     */
    
    public function findinTasks($task_ids) : EntityReader {
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
     * @return (mixed|string)[][]
     *
     * @psalm-param \App\Invoice\Setting\SettingRepository<object> $sR
     *
     * @psalm-return array{1: array{label: mixed, class: 'draft'}, 2: array{label: mixed, class: 'viewed'}, 3: array{label: mixed, class: 'sent'}, 4: array{label: mixed, class: 'paid'}}
     */
    public function getTask_statuses(\App\Invoice\Setting\SettingRepository $sR): array
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
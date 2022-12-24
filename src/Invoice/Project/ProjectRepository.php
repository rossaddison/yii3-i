<?php

declare(strict_types=1); 

namespace App\Invoice\Project;

use App\Invoice\Entity\Project;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class ProjectRepository extends Select\Repository
{
private EntityWriter $entityWriter;
    
    /**
     * @param Select<TEntity> $select     
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }

    /**
     * Get projects  without filter
     *
     * @psalm-return DataReaderInterface<int,Project>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select()->load('client');
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, Project>
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
    public function save(Project $project): void
    {
        $this->entityWriter->write([$project]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(Project $project): void
    {
        $this->entityWriter->delete([$project]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    public function repoProjectquery(string $id): object|null    {
        $query = $this->select()->load('client')->where(['id' =>$id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $project_id
     * @return int
     */
    public function count(string $project_id) : int {
        $count = $this->select()
                      ->where(['id'=>$project_id])
                      ->count();
        return $count;
    }
}
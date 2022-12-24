<?php

declare(strict_types=1);

namespace App\Invoice\Generator;

use App\Invoice\Entity\Gentor;
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
final class GeneratorRepository extends Select\Repository
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
     * Get generators without filter
     *
     * @psalm-return DataReaderInterface<int, Gentor>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }
            
    /**
     * @throws Throwable
     */
    public function save(Gentor $generator): void
    {
        $this->entityWriter->write([$generator]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(Gentor $generator): void
    {
        $this->entityWriter->delete([$generator]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'small_singular_name', 'pre_entity_table'])
                ->withOrder(['small_singular_name' => 'asc'])
        );
    }
    
    public function repoGentorQuery(string $id): object|null
    {
        $query = $this
            ->select()
            ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
}



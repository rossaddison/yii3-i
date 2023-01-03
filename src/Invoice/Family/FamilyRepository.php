<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use App\Invoice\Entity\Family;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class FamilyRepository extends Select\Repository
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
     * Get families without filter
     *
     * @psalm-return EntityReader
     * 
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }
            
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $family
     * @throws Throwable 
     * @return void
     */
    public function save(array|object|null $family): void
    {
        $this->entityWriter->write([$family]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $family
     * @throws Throwable 
     * @return void
     */
    public function delete(array|object|null $family): void
    {
        $this->entityWriter->delete([$family]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'family_name'])
                ->withOrder(['family_name' => 'asc'])
        );
    }
    
    /**
     * @return null|object
     *
     * @psalm-return TEntity|null
     */
    public function repoFamilyquery(string $family_id): object|null
    {
        $query = $this
            ->select()
            ->where(['id' => $family_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    public function withName(string $family_name): ?Family
    {
        $query = $this
            ->select()
            ->where(['family_name' => $family_name]);
        return  $query->fetchOne() ?: null;
    }
    
    /**
     * @return int
     */
    public function repoTestDataCount(): int {
        $count = $this->select()
                      ->count();
        return $count;   
    }
}

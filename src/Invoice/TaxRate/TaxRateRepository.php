<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use App\Invoice\Entity\TaxRate;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class TaxRateRepository extends Select\Repository
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
     * Get taxrates without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }
            
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $taxrate
     * @throws Throwable 
     * @return void
     */
    public function save(array|object|null $taxrate): void
    {
        $this->entityWriter->write([$taxrate]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $taxrate
     * @throws Throwable 
     * @return void
     */
    public function delete(array|object|null $taxrate): void
    {
        $this->entityWriter->delete([$taxrate]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'tax_rate_name'])
                ->withOrder(['tax_rate_name' => 'asc'])
        );
    }
    
    
    public function repoTaxRatequery(string $tax_rate_id): null|object
    {
        $query = $this
            ->select()
            ->where(['id' => $tax_rate_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    public function withName(string $tax_rate_name): object|null
    {
        $query = $this
            ->select()
            ->where(['tax_rate_name' => $tax_rate_name]);
        return  $query->fetchOne() ?: null;
    }
    
    public function repoCount($tax_rate_id): int {
        $count = $this->select()
                      ->where(['id' => $tax_rate_id])
                      ->count();
        return $count;   
    }
    
    public function repoCountAll(): int {
        $countall = $this->select()
                         ->count();
        return $countall;
    }
}

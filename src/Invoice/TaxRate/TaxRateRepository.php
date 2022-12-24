<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use App\Invoice\Entity\TaxRate;
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
     * @psalm-return DataReaderInterface<int, TaxRate>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }
            
    /**
     * @throws Throwable
     */
    public function save(TaxRate $taxrate): void
    {
        $this->entityWriter->write([$taxrate]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(TaxRate $taxrate): void
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
    
    public function repoTaxRatequery(string $tax_rate_id): object|null
    {
        $query = $this
            ->select()
            ->where(['id' => $tax_rate_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    public function withName(string $tax_rate_name): ?TaxRate
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

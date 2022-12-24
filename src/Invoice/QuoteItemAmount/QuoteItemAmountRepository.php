<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteItemAmount;

use App\Invoice\Entity\QuoteItemAmount;
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
final class QuoteItemAmountRepository extends Select\Repository
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
     * Get quoteitemamounts  without filter
     *
     * @psalm-return DataReaderInterface<int,QuoteItemAmount>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select()->load('quote_item');
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, QuoteItemAmount>
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
    public function save(QuoteItemAmount $quoteitemamount): void
    {
        $this->entityWriter->write([$quoteitemamount]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(QuoteItemAmount $quoteitemamount): void
    {
        $this->entityWriter->delete([$quoteitemamount]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    } 
    
    public function repoQuoteItemAmountquery(string $quote_item_id): object|null {
        $query = $this->select()->load(['quote_item'])->where(['quote_item_id' => $quote_item_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $quote_item_id
     * @return int
     */
    public function repoCount(string $quote_item_id): int {
        $query = $this->select()
                      ->where(['quote_item_id'=>$quote_item_id]);
        return $query->count(); 
    }
}
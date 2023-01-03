<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteTaxRate;

use App\Invoice\Entity\QuoteTaxRate;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class QuoteTaxRateRepository extends Select\Repository
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
     * Get quotetaxrates  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('quote')->load('tax_rate');
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
     * @param array|object|null $quotetaxrate
     * @throws Throwable 
     * @return void
     */
    public function save(array|object|null $quotetaxrate): void
    {
        $this->entityWriter->write([$quotetaxrate]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $quotetaxrate
     * @throws Throwable 
     * @return void
     */
    public function delete(array|object|null $quotetaxrate): void
    {
        $this->entityWriter->delete([$quotetaxrate]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    //find all quote tax rates assigned to specific quote. Normally only one but just in case more than one assigned
    //used in quote/view to determine if a 'one-off'  quote tax rate acquired from tax rates is to be applied to the quote 
    //quote tax rates are children of their parent tax rate and are normally used when all products use the same tax rate ie. no item tax
    /**
     * @param null|string $quote_id
     */
    public function repoCount(string|null $quote_id): int {
        $count = $this->select()
                      ->where(['quote_id' => $quote_id])
                      ->count();
        return $count;   
    }
    
    //find a specific quotes tax rate, normally to delete
    /**
     * @return null|object
     *
     * @psalm-return TEntity|null
     */
    public function repoQuoteTaxRatequery(string $id):object|null    {
        $query = $this->select()
                      ->load('quote')
                      ->load('tax_rate')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    // find all quote tax rates used for a specific quote normally to apply include_item_tax 
    // (see function calculate_quote_taxes in NumberHelper
    // load 'tax rate' so that we can use tax_rate_id through the BelongTo relation in the Entity
    // to access the parent tax rate table's percent name and percentage 
    // which we will use in quote/view
    public function repoQuotequery(string $quote_id): EntityReader    {
        $query = $this->select()
                      //->load('tax_rate')
                      ->where(['quote_id' => $quote_id]);
        return $this->prepareDataReader($query);   
    }
    
    /**
     * @return null|object
     *
     * @psalm-return TEntity|null
     */
    public function repoTaxRatequery(string $tax_rate_id):object|null    {
        $query = $this->select()
                      ->load('tax_rate')
                      ->where(['tax_rate_id' => $tax_rate_id]);
        return  $query->fetchOne() ?: null;        
    }
        
    public function repoGetQuoteTaxRateAmounts(string $quote_id): EntityReader  {
        $query = $this->select()
                      ->where(['quote_id'=>$quote_id]);
        return $this->prepareDataReader($query);   
    }
        
    public function repoUpdateQuoteTaxTotal(string $quote_id): float {
        $getTaxRateAmounts = $this->repoGetQuoteTaxRateAmounts($quote_id);        
        $total = 0.00;
        foreach ($getTaxRateAmounts as $item) {
            foreach ($item as $key=>$value) {
               if ($key === 'quote_tax_rate_amount') {             
                  $total += $value;  
               } 
            }    
        }
        return $total;
    }
}
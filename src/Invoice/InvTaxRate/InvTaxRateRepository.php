<?php

declare(strict_types=1); 

namespace App\Invoice\InvTaxRate;

use App\Invoice\Entity\InvTaxRate;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class InvTaxRateRepository extends Select\Repository
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
     * Get invtaxrates  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('inv')->load('tax_rate');
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
     * @param array|object|null $invtaxrate
     * @throwable 
     * @return void
     */
    public function save(array|object|null $invtaxrate): void
    {
        $this->entityWriter->write([$invtaxrate]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $invtaxrate
     * @throwable 
     * @return void
     */
    public function delete(array|object|null $invtaxrate): void
    {
        $this->entityWriter->delete([$invtaxrate]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    //find all inv tax rates assigned to specific inv. Normally only one but just in case more than one assigned
    //used in inv/view to determine if a 'one-off'  inv tax rate acquired from tax rates is to be applied to the inv 
    //inv tax rates are children of their parent tax rate and are normally used when all products use the same tax rate ie. no item tax
    
    /**
     * @param null|string $inv_id
     */
    public function repoCount(string|null $inv_id): int {
        $count = $this->select()
                      ->where(['inv_id' => $inv_id])
                      ->count();
        return $count;   
    }
    
    //find a specific invs tax rate, normally to delete
    public function repoInvTaxRatequery(string $id): null|InvTaxRate    {
        $query = $this->select()->load('inv')->load('tax_rate')->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    // find all inv tax rates used for a specific inv normally to apply include_item_tax 
    // (see function calculate_inv_taxes in NumberHelper
    // load 'tax rate' so that we can use tax_rate_id through the BelongTo relation in the Entity
    // to access the parent tax rate table's percent name and percentage 
    // which we will use in inv/view
    public function repoInvquery(string $inv_id): EntityReader    {
        $query = $this->select()->load('tax_rate')->where(['inv_id' => $inv_id]);
        return $this->prepareDataReader($query);   
    }
    
    public function repoTaxRatequery(string $tax_rate_id): null|InvTaxRate    {
        $query = $this->select()->load('tax_rate')->where(['tax_rate_id' => $tax_rate_id]);
        return  $query->fetchOne() ?: null;        
    }
        
    public function repoGetInvTaxRateAmounts(string $inv_id): EntityReader  {
        $query = $this->select()
                      ->where(['inv_id'=>$inv_id]);
        return $this->prepareDataReader($query);   
    }
        
    public function repoUpdateInvTaxTotal(string $inv_id): float {
        $getTaxRateAmounts = $this->repoGetInvTaxRateAmounts($inv_id);        
        $total = 0.00;
        foreach ($getTaxRateAmounts as $item) {
            foreach ($item as $key=>$value) {
               if ($key === 'inv_tax_rate_amount') {             
                  $total += $value;  
               } 
            }    
        }
        return $total;
    }
}
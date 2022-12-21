<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\Entity\Product;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\DataReaderInterface;
use Cycle\Database\Injection\Parameter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

final class ProductRepository extends Select\Repository
{
    private EntityWriter $entityWriter;

    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }

    /**
     * Get products without filter
     *
     * @psalm-return DataReaderInterface<int, Product>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit');
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, Product>
     */
    public function getReader(): DataReaderInterface
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }
    
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'desc']);
    }

    /**
     * @throws Throwable
     */
    public function save(Product $product): void
    {
        $this->entityWriter->write([$product]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(Product $product): void
    {
        $this->entityWriter->delete([$product]);
    }
    

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'product_description'])
                ->withOrder([
                             'id'=>'desc',
                             'product_description' => 'desc'
                            ])
        );
    }
    
    /**
     * @param null|string $product_id
     *
     * @return null|object
     */
    public function repoProductquery(string|null $product_id): object|null
    {
        $query = $this
            ->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit')
            ->where(['id' => $product_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * @psalm-param 'Cleen Screans'|'Tuch Padd' $product_name
     */
    public function withName(string $product_name) : ?Product 
    {
        $query = $this
            ->select()
            ->where(['product_name' => $product_name]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * Get products with filter
     *
     * @psalm-return DataReaderInterface<int, Product>
     */
    
    public function repoProductwithfamilyquery(string $product_name, string $family_id): DataReaderInterface
    {
        $query = $this
            ->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit');

        //lookup without filters eg. product/lookup
        if (empty($product_name)&&(empty($family_id))) {}
                
        //eg. product/lookup?fp=Cleaning%20Services
        if ((!empty($product_name))&&(empty($family_id))) {      
            $query = $query->where(['product_name' => ltrim(rtrim($product_name))]);
        }
        
        //eg. product/lookup?Cleaning%20Services&ff=4
        if (!empty($product_name)&&($family_id>(string)0)) {      
            $query = $query->where(['family_id'=>$family_id])->andWhere(['product_name' => ltrim(rtrim($product_name))]);
        }
        
        //eg. product/lookup?ff=4
        if (empty($product_name)&&($family_id>(string)0)) {                  
            $query = $query->where(['family_id'=>$family_id]);
        }
        
        return $this->prepareDataReader($query);
    } 
    
     /**
     * Get selection of products from all products
     *
     * @psalm-return DataReaderInterface<int, Product>
     */
    
    public function findinProducts($product_ids) : DataReaderInterface {
        $query = $this
        ->select()
        ->where(['id'=>['in'=> new Parameter($product_ids)]]);
        return $this->prepareDataReader($query);    
    } 
    
    /**
     * @param int|string $product_id
     *
     * @psalm-param ''|int $product_id
     */
    public function repoCount(string|int $product_id): int {
        $count = $this->select()
                      ->where(['id' => $product_id])
                      ->count();
        return $count;   
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

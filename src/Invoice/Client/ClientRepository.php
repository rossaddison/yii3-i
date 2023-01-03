<?php

declare(strict_types=1); 

namespace App\Invoice\Client;

use App\Invoice\Entity\Client;
use Cycle\ORM\Select;
use Cycle\Database\Injection\Parameter;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class ClientRepository extends Select\Repository
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
    
    public function count() : int {
        $count = $this->select()                                
                      ->count();
        return $count; 
    }
    
    /**
     * Get Client with filter active
     *
     * @psalm-return EntityReader
     */
    public function findAllWithActive(int $active) : EntityReader
    {
        if (($active) < 2) {
         $query = $this->select()
                ->where(['client_active' => $active]);  
         return $this->prepareDataReader($query);
       } else {
         return $this->findAllPreloaded();  
       }       
    }

    /**
     * Get clients  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
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
        return Sort::only(['id'])->withOrder(['id' => 'desc']);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $client
     * @throws Throwable 
     * @return void
     */
    public function save(array|object|null $client): void
    {
        $this->entityWriter->write([$client]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $client
     * @throws Throwable 
     * @return void
     */
    public function delete(array|object|null $client): void
    {
        $this->entityWriter->delete([$client]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    public function repoClientCount(string $id): int {
        $count = $this->select()
                      ->where(['id'=>$id])  
                      ->count();
        return $count; 
    }
    
    /**
     * @return null|object
     *
     * @psalm-return TEntity|null
     */
    public function repoClientquery(string $id) : null|object {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    public function repoUserClient(array $available_client_id_list) : EntityReader {
        $query = $this
        ->select()
        ->where(['id'=>['in'=> new Parameter($available_client_id_list)]]);
        return $this->prepareDataReader($query);    
    } 
    
    /**
     * Get clients  without filter
     *
     * @psalm-return EntityReader
     */
    public function  repoActivequery(bool $client_active): EntityReader
    {
        $query = $this->select()->where(['client_active' => $client_active]);
        return $this->prepareDataReader($query);
    }
    
    /**
     * @return null|object
     *
     * @psalm-return TEntity|null
     */
    public function withName(string $client_name): null|object
    {
        $query = $this
            ->select()
            ->where(['client_name' => $client_name]);
        return  $query->fetchOne() ?: null;
    }    
}
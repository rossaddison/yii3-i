<?php

declare(strict_types=1); 

namespace App\Invoice\Client;

use App\Invoice\Entity\Client;
use Cycle\ORM\Select;
use Cycle\Database\Injection\Parameter;
use Throwable;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

final class ClientRepository extends Select\Repository
{
private EntityWriter $entityWriter;

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
     * @psalm-return DataReaderInterface<int, Client>
     */
    public function findAllWithActive(int $active) : DataReaderInterface
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
     * @psalm-return DataReaderInterface<int,Client>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, Client>
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
    public function save(Client $client): void
    {
        $this->entityWriter->write([$client]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(Client $client): void
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
    
    public function repoClientquery(string $id): null|object {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    public function repoUserClient(array $available_client_id_list) : DataReaderInterface {
        $query = $this
        ->select()
        ->where(['id'=>['in'=> new Parameter($available_client_id_list)]]);
        return $this->prepareDataReader($query);    
    } 
    
    /**
     * Get clients  without filter
     *
     * @psalm-return DataReaderInterface<int,Client>
     */
    public function  repoActivequery(bool $client_active): DataReaderInterface
    {
        $query = $this->select()->where(['client_active' => $client_active]);
        return $this->prepareDataReader($query);
    }
    
    public function withName(string $client_name): null|object
    {
        $query = $this
            ->select()
            ->where(['client_name' => $client_name]);
        return  $query->fetchOne() ?: null;
    }    
}
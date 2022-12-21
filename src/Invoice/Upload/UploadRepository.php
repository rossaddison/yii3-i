<?php

declare(strict_types=1); 

namespace App\Invoice\Upload;

use App\Invoice\Entity\Upload;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

final class UploadRepository extends Select\Repository
{
private EntityWriter $entityWriter;

    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }
    
    public $ctype_default = "application/octet-stream";
    
    public $content_types = [
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'txt' => 'text/plain',
        'xml' => 'application/xml',
    ];
    
    /**
     * @return array
     */
    public function getContentTypes() : array {
        return $this->content_types;
    }
    
    /**
     * @return array
     */
    public function getContentTypeDefaultOctetStream() : array {
        return $this->ctype_default;
    }

    /**
     * Get uploads  without filter
     *
     * @psalm-return DataReaderInterface<int,Upload>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select()
                      ->load('client');
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, Upload>
     */
    public function getReader(): DataReaderInterface
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }
    
    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }
    
    /**
     * @throws Throwable
     */
    public function save(Upload $upload): void
    {
        $this->entityWriter->write([$upload]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(Upload $upload): void
    {
        $this->entityWriter->delete([$upload]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * 
     * @param string $id
     * @return Upload|null
     */
    public function repoUploadquery(string $id) : Upload|null {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }
    
    /**
     * Get uploads
     *
     * @psalm-return DataReaderInterface<int, Upload>
     */
    public function repoUploadUrlClientquery(string $url_key, int $client_id): DataReaderInterface {
        $query = $this->select()
                      ->where(['url_key'=>$url_key])
                      ->andWhere(['client_id'=>$client_id]);        
        return $this->prepareDataReader($query);
    }
    
    /**
     * 
     * @param string $url_key
     * @param int $client_id
     * @return int
     */
    public function repoCount(string $url_key, int $client_id) : int {
        $query = $this->select()
                      ->where(['url_key'=>$url_key])
                      ->andWhere(['client_id'=>$client_id]); 
        return $query->count();
    }   
}
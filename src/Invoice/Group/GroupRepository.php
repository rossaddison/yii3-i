<?php

declare(strict_types=1); 

namespace App\Invoice\Group;

use App\Invoice\Entity\Group;
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
final class GroupRepository extends Select\Repository
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
     * Get groups  without filter
     *
     * @psalm-return DataReaderInterface<int,Group>
     */
    public function findAllPreloaded(): DataReaderInterface
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return DataReaderInterface<int, Group>
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
    public function save(Group $group): void
    {
        $this->entityWriter->write([$group]);
    }
    
    /**
     * @throws Throwable
     */
    public function delete(Group $group): void
    {
        $this->entityWriter->delete([$group]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
  /**
   * @param int $id
   * @param bool $set_next
   * @return mixed
   */
    public function generate_number(int $id, bool $set_next = true) : mixed
    {
        $group = $this->repoGroupquery((string)$id) ?: null;
        $my_result = $this->parse_identifier_format(
            (string)$group->getIdentifier_format(),
            (int)$group->getNext_id(),
            (int)$group->getLeft_pad()) ?? null ;
        if ($set_next) {
            $this->set_next_number($id);
        }
        if (!empty($my_result) && gettype($my_result)){
            return $my_result;        
        } 
        return '';
    }
    
   /**
    * @param string $identifier_format
    * @param int $next_id
    * @param int $left_pad
    * @return string|null
    */
    private function parse_identifier_format(string $identifier_format = '', int $next_id = 1, int  $left_pad = 1): string|null
    {
        $template_vars = [];
        $var = '';
        if (preg_match_all('/{{{([^{|}]*)}}}/', $identifier_format, $template_vars)) {
            foreach ($template_vars[1] as $var) {
                switch ($var) {
                    case 'year':
                        $replace = date('Y');
                        break;
                    case 'yy':
                        $replace = date('y');
                        break;
                    case 'month':
                        $replace = date('m');
                        break;
                    case 'day':
                        $replace = date('d');
                        break;
                    case 'id':
                        $replace = str_pad((string)$next_id, $left_pad, '0', STR_PAD_LEFT);
                        break;
                    default:
                        $replace = '';
                }
                $identifier_format = str_replace('{{{' . $var . '}}}', $replace, $identifier_format);
            }
        }
        return $identifier_format;
    }
    
    public function repoGroupquery(string $id): object|null    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
    
    public function repoGroupcount(string $id): int {
        $count = $this->select()
                      ->where(['id' => $id])
                      ->count();  
        return  $count;
    }
    
    public function repoCountAll(): int {
        $count = $this->select()
                      ->count();  
        return  $count;
    }
    
    /**
     * @param $id
     */
    public function set_next_number(int $id) : int
    {
        $result = $this->repoGroupquery((string)$id) ?: null;
        if (null!==$result) {
            $incremented_next_id = $result->getNext_id() + 1;
            $result->setNext_id($incremented_next_id); 
            $this->save($result);
            return (int)$result->getNext_id();
        } else {
            return 0;
        }     
    }
}
<?php

declare(strict_types=1); 

namespace App\Invoice\UserClient;

use App\Invoice\Entity\UserClient;
use App\Invoice\Entity\Client;
use App\Invoice\Entity\UserInv;
use App\Invoice\UserClient\UserClientForm;
use App\Invoice\UserClient\UserClientService as UCS;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Invoice\Client\ClientRepository as CR;

use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;

/**
 * @template TEntity of object
 * @extends Select\Repository<TEntity>
 */
final class UserClientRepository extends Select\Repository
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
     * Get userclients  without filter
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
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $userclient
     * @throws Throwable 
     * @return void
     */
    public function save(array|object|null $userclient): void
    {
        $this->entityWriter->write([$userclient]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|object|null $userclient
     * @throws Throwable 
     * @return void
     */
    public function delete(array|object|null $userclient): void
    {
        $this->entityWriter->delete([$userclient]);
    }
    
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }
    
    /**
     * @return null|object
     *
     * @psalm-return TEntity|null
     */
    public function repoUserClientquery(string $id):object|null    {
        $query = $this->select()
                      ->load('user')
                      ->load('client')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;        
    }
        
     /**
     * Get clients  with filter user_id
     *
     * @psalm-return EntityReader
     */
    public function repoClientquery(string $user_id): EntityReader    {
        $query = $this->select()
                      ->load('client')
                      ->where(['user_id' => $user_id]);
        return $this->prepareDataReader($query);     
    }
    
    public function repoClientCountquery(string $user_id): int {
        $query = $this->select()
                      ->where(['user_id' => $user_id]);                      
        return $query->count();     
    }
    
    public function repoUserClientqueryCount(string $user_id, string $client_id): int {
        $query = $this->select()
                      ->where(['user_id' => $user_id])
                      ->andWhere(['client_id'=>$client_id]);
        return $query->count();     
    }
    
    /**
     * @param string $user_id
     * @return array
     */
    public function get_assigned_to_user(string $user_id) : array {
        // Get all clients assigned to this user
        $count_user_clients = $this->repoClientCountquery($user_id);   
        $assigned_client_ids = [];
        if ($count_user_clients> 0) {
            $user_clients = $this->repoClientquery($user_id);        
            foreach ($user_clients as $user_client) {
                if ($user_client instanceof UserClient) {
                    // Include Non-active clients as well since these might be reactivated later 
                    $assigned_client_ids[] = $user_client->getClient_id();                 
                }
            }
        }
        return $assigned_client_ids;
    }
    
   /**
     * @param string $user_id
     * @param CR $cR
     *
     * @return (int|null)[]
     *
     * @psalm-return array<int<0, max>, int|null>
     */
    public function get_not_assigned_to_user(string $user_id, CR $cR) : array
    {
        // Get an array of client ids that have been assigned to this user
        $assigned_client_ids = $this->get_assigned_to_user($user_id);
        
        // Get all existing clients including non-active ones
        $all_clients = $cR->findAllPreloaded();
        $every_client_ids = [];
        foreach ($all_clients as $client) {
            if ($client instanceof Client) {
                $client_id = $client->getClient_id();
                $every_client_ids[] = $client_id;
            }
        }
        
        // Create unassigned client list for dropdown
        $possible_client_ids = array_diff($every_client_ids, $assigned_client_ids);
        
        return $possible_client_ids;
    }
    
    /**
     * 
     * @param UIR $uiR
     * @param CR $cR
     * @param UCS $ucS
     * @param ValidatorInterface $validator
     * @return void
     */
    public function reset_users_all_clients(UIR $uiR, CR $cR, UCS $ucS, ValidatorInterface $validator) : void
    {
        // Users that have their all_clients setting active
        if ($uiR->countAllWithAllClients()>0) {
            $users = $uiR->findAllWithAllClients();
            foreach ($users as $user) {
                if ($user instanceof UserInv) {
                    $user_id = $user->getUser_id();
                    $available_client_ids = $this->get_not_assigned_to_user($user_id, $cR); 
                    $this->assign_to_user_client($available_client_ids, $user_id, $validator, $ucS);
                }
            }
        }            
    }
    
    /**
     * 
     * @param array $available_client_ids
     * @param string $user_id
     * @param ValidatorInterface $validator
     * @param UCS $ucS
     * @return void
     */
    public function assign_to_user_client(array $available_client_ids, string $user_id, ValidatorInterface $validator, UCS $ucS): void{
        foreach ($available_client_ids as $key => $value) {
                   $user_client = [
                        'user_id' => $user_id,
                        'client_id' => $value,
                    ]; 
                    $form = new UserClientForm();
                    ($form->load($user_client) && $validator->validate($form)->isValid()) ? $ucS->saveUserClient(new UserClient(), $form) : '';
        }
    }
    
    /**
     * @param string $user_id
     */
    public function unassign_to_user_client(string $user_id) : void {
        $clients = $this->repoClientquery($user_id);        
        foreach ($clients as $client) {
            $this->delete($client);
        }
    }
}
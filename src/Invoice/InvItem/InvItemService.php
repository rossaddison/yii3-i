<?php
declare(strict_types=1); 

namespace App\Invoice\InvItem;

use App\Invoice\Entity\InvItem;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Entity\InvItemAmount;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Task\TaskRepository as taskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;

final class InvItemService
{
    private InvItemRepository $repository;
 
    public function __construct(InvItemRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param InvItem $model
     * @param InvItemForm $form
     * @param string $inv_id
     * @param PR $pr
     * @param TRR $trr
     * @param IIAS $iias
     * @param IIAR $iiar
     * @param SR $s
     * @param UNR $unR
     * @return void
     */
    public function addInvItem_product(InvItem $model, InvItemForm $form, string $inv_id,PR $pr, TRR $trr , IIAS $iias, IIAR $iiar, SR $s, UNR $unR): void
    {        
       // This function is used in product/save_product_lookup_item_product when adding a product using the modal 
       $tax_rate_id = ((null !==($form->getTax_rate_id())) ? $form->getTax_rate_id() : '');
       // The form is required to have a tax value even if it is a zero rate
       $model->setTax_rate_id((int)$tax_rate_id);
       $model->setInv_id((int)$inv_id);       
       $product_id = ((null !==($form->getProduct_id())) ? $form->getProduct_id() : '');
       $model->setProduct_id((int)$product_id);       
       $name = (( (null !==($form->getProduct_id())) && ($pr->repoCount($product_id)> 0) ) ? $pr->repoProductquery($form->getProduct_id())->getProduct_name() : '');  
       $model->setName($name);
       
       // If the user has changed the description on the form => override default product description
       $description = ((null !==($form->getDescription())) ? 
                                 $form->getDescription() : 
                                 $pr->repoProductquery((string)$form->getProduct_id())->getProduct_description());
       
       $model->setDescription($description);
       
       $model->setQuantity($form->getQuantity());
       
       $model->setPrice($form->getPrice());
       $model->setDiscount_amount($form->getDiscount_amount());
       $model->setOrder($form->getOrder());
       
       $datehelper = new DateHelper($s);
       $datetime = $datehelper->get_or_set_with_style($form->getDate());
       $datetimeimmutable = new \DateTimeImmutable(!empty($datetime) ? $datetime->format('Y-m-d H:i:s') : 'now');
       $model->setDate($datetimeimmutable);
       
       // Product_unit is a string which we get from unit's name field using the unit_id
       $model->setProduct_unit($unR->repoUnitquery((string)$form->getProduct_unit_id())->getUnit_name());
       $model->setProduct_unit_id($form->getProduct_unit_id());
       $tax_rate_percentage = $this->taxrate_percentage((int)$tax_rate_id, $trr);
       // Users are required to enter a tax rate even if it is zero percent.
       $product_id ? $this->repository->save($model) : '';  
       $product_id ? $this->saveInvItemAmount((int)$model->getId(), $form->getQuantity(), $form->getPrice(), $form->getDiscount_amount(), $tax_rate_percentage, $iias, $iiar) : '';
    }
    
    /**
     * 
     * @param InvItem $model
     * @param InvItemForm $form
     * @param string $inv_id
     * @param PR $pr
     * @param SR $s
     * @param UNR $unR
     * @return int
     */
    public function saveInvItem_product(InvItem $model, InvItemForm $form, string $inv_id,PR $pr, SR $s, UNR $unR): int
    {        
       // This function is used in invitem/edit_product when editing an item on the inv view
       // see https://github.com/cycle/orm/issues/348
       null!==$form->getTax_rate_id() ? $model->setTaxRate($model->getTaxRate()->getTax_rate_id() == $form->getTax_rate_id() ? $model->getTaxRate() : null): '';
       $tax_rate_id = ((null !==($form->getTax_rate_id())) ? $form->getTax_rate_id() : '');
       $model->setTax_rate_id((int)$tax_rate_id);
       null!==$form->getProduct_id() ? $model->setProduct($model->getProduct()->getProduct_id() == $form->getProduct_id() ? $model->getProduct() : null): '';
       $product_id = ((null !==($form->getProduct_id())) ? $form->getProduct_id() : '');
       $model->setProduct_id((int)$product_id);
       
       $model->setInv_id((int)$inv_id);
       $name = (( (null !==($form->getProduct_id())) && ($pr->repoCount($product_id)> 0) ) ? $pr->repoProductquery($form->getProduct_id())->getProduct_name() : '');  
       $model->setName($name);
       
       // If the user has changed the description on the form => override default product description
       $description = ((null !==($form->getDescription())) ? 
                                 $form->getDescription() : 
                                 $pr->repoProductquery((string)$form->getProduct_id())->getProduct_description());
       
       $model->setDescription($description);
       
       $model->setQuantity($form->getQuantity());
       
       $model->setPrice($form->getPrice());
       $model->setDiscount_amount($form->getDiscount_amount());
       $model->setOrder($form->getOrder());
       
       $datehelper = new DateHelper($s);
       $datetime = $datehelper->get_or_set_with_style($form->getDate());
       $datetimeimmutable = new \DateTimeImmutable(!empty($datetime) ? $datetime->format('Y-m-d H:i:s') : 'now');
       $model->setDate($datetimeimmutable);
       
       // Product_unit is a string which we get from unit's name field using the unit_id
       $model->setProduct_unit($unR->repoUnitquery((string)$form->getProduct_unit_id())->getUnit_name());
       $model->setProduct_unit_id((int)$form->getProduct_unit_id());         
       $product_id ? $this->repository->save($model) : '';  
       return (int)$tax_rate_id;
    }
    
    /**
     * 
     * @param InvItem $model
     * @param InvItemForm $form
     * @param string $inv_id
     * @param taskR $taskR
     * @param TRR $trr
     * @param IIAS $iias
     * @param IIAR $iiar
     * @param SR $s
     * @return void
     */
    public function addInvItem_task(InvItem $model, InvItemForm $form, string $inv_id, taskR $taskR, TRR $trr , IIAS $iias, IIAR $iiar, SR $s): void
    {        
       // This function is used in task/selection_inv when adding a new task from the modal
       // see https://github.com/cycle/orm/issues/348
       $tax_rate_id = ((null !==($form->getTax_rate_id())) ? $form->getTax_rate_id() : '');
       $model->setTax_rate_id((int)$tax_rate_id);      
       $task_id = ((null !==($form->getTask_id())) ? $form->getTask_id() : '');
       // Product id and task id are mutually exclusive
       $model->setTask_id((int)$task_id);
       
       $model->setInv_id((int)$inv_id);
       
       $name = (( (null !==($form->getTask_id())) && ($taskR->repoCount((int)$form->getTask_id())> 0) ) ? $taskR->repoTaskquery($form->getTask_id())->getName() : '');  
       $model->setName($name);
       
       // If the user has changed the description on the form => override default task description
       $description = ((null !==($form->getDescription())) ? 
                                 $form->getDescription() : 
                                 $taskR->repoTaskquery((string)$form->getTask_id())->getDescription());
       
       $model->setDescription($description);
       
       $model->setQuantity($form->getQuantity());
       $model->setProduct_unit('');
       $model->setPrice($form->getPrice());
       $model->setDiscount_amount($form->getDiscount_amount());
       $model->setOrder($form->getOrder());
       
       $datehelper = new DateHelper($s);
       $datetime = $datehelper->get_or_set_with_style($form->getDate());
       $datetimeimmutable = new \DateTimeImmutable(!empty($datetime) ? $datetime->format('Y-m-d H:i:s') : 'now');
       $model->setDate($datetimeimmutable);              
       $tax_rate_percentage = $this->taxrate_percentage((int)$tax_rate_id, $trr);
       $task_id ? $this->repository->save($model) : '';                
       $task_id ? $this->saveInvItemAmount((int)$model->getId(), $form->getQuantity(), $form->getPrice(), $form->getDiscount_amount(), $tax_rate_percentage, $iias, $iiar) : '';
    }
    
    /**
     * 
     * @param InvItem $model
     * @param InvItemForm $form
     * @param string $inv_id
     * @param taskR $taskR
     * @param SR $s
     * @return int
     */
    public function saveInvItem_task(InvItem $model, InvItemForm $form, string $inv_id, taskR $taskR, SR $s): int
    {        
       // This function is used in invitem/edit_task when editing an item on the inv view
       // see https://github.com/cycle/orm/issues/348
       null!==$form->getTax_rate_id() ? $model->setTaxRate($model->getTaxRate()->getTax_rate_id() == $form->getTax_rate_id() ? $model->getTaxRate() : null): '';
       $tax_rate_id = ((null !==($form->getTax_rate_id())) ? $form->getTax_rate_id() : '');
       $model->setTax_rate_id((int)$tax_rate_id);
       null!==$form->getTask_id() ? $model->setTask($model->getTask()->getId() == $form->getTask_id() ? $model->getTask() : null): '';
       $task_id = ((null !==($form->getTask_id())) ? $form->getTask_id() : '');
       // Product id and task id are mutually exclusive
       $model->setTask_id((int)$task_id);
       
       $model->setInv_id((int)$inv_id);
       
       $name = (( (null !==($form->getTask_id())) && ($taskR->repoCount((int)$form->getTask_id())> 0) ) ? $taskR->repoTaskquery($form->getTask_id())->getName() : '');  
       $model->setName($name);
       
       // If the user has changed the description on the form => override default task description
       $description = ((null !==($form->getDescription())) ? 
                                 $form->getDescription() : 
                                 $taskR->repoTaskquery((string)$form->getTask_id())->getDescription());
       
       $model->setDescription($description);
       
       $model->setQuantity($form->getQuantity());
       $model->setProduct_unit('');
       $model->setPrice($form->getPrice());
       $model->setDiscount_amount($form->getDiscount_amount());
       $model->setOrder($form->getOrder());
       
       $datehelper = new DateHelper($s);
       $datetime = $datehelper->get_or_set_with_style($form->getDate());
       $datetimeimmutable = new \DateTimeImmutable(!empty($datetime) ? $datetime->format('Y-m-d H:i:s') : 'now');
       $model->setDate($datetimeimmutable);              
       $task_id ? $this->repository->save($model) : '';                
       return (int)$tax_rate_id;
    }
    
    /**
     * 
     * @param int $inv_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float $tax_rate_percentage
     * @param IIAS $iias
     * @param IIAR $iiar
     * @return void
     */
    public function saveInvItemAmount(int $inv_item_id, float $quantity, float $price, float $discount, float $tax_rate_percentage, IIAS $iias, IIAR $iiar): void
    {       
       $iias_array['inv_item_id'] = $inv_item_id;       
       $sub_total = $quantity * $price;
       $tax_total = (($sub_total * ($tax_rate_percentage/100)));
       $discount_total = ($quantity*$discount);
       
       $iias_array['discount'] = $discount_total;
       $iias_array['subtotal'] = $sub_total;
       $iias_array['taxtotal'] = $tax_total;
       $iias_array['total'] = ($sub_total - $discount_total + $tax_total);       
       
       if ($iiar->repoCount((string)$inv_item_id) === 0) {
         $iias->saveInvItemAmountNoForm(new InvItemAmount(), $iias_array);} else {
         $iias->saveInvItemAmountNoForm($iiar->repoInvItemAmountquery((string)$inv_item_id), $iias_array);     
       }                      
    }        
    
    /**
     * 
     * @param array|object|null $model
     * @return void
     */
    public function deleteInvItem(array|object|null $model): void 
    {
        $this->repository->delete($model);
    }
    
    /**
     * 
     * @param int $id
     * @param TRR $trr
     * @return float|null
     */
    public function taxrate_percentage(int $id, TRR $trr): float|null
    {
        $taxrate = $trr->repoTaxRatequery((string)$id);
        $percentage = $taxrate->getTax_rate_percent();        
        return $percentage;
    }
    
    /**
     * 
     * @param int $basis_inv_id
     * @param string $new_inv_id
     * @param InvItemRepository $iiR
     * @param IIAR $iiaR
     * @param SR $sR
     * @return void
     */
    public function initializeCreditInvItems(int $basis_inv_id, string $new_inv_id, InvItemRepository $iiR, IIAR $iiaR, SR $sR): void {        
        // Get the basis invoice's items and balance with a negative quantity
        $items = $iiR->repoInvquery((string)$basis_inv_id);
        foreach ($items as $item){
            $new_item = new InvItem();
            $new_item->setInv_id((int)$new_inv_id);
            $new_item->setTax_rate_id((int)$item->getTax_rate_id());
            $item->getProduct_id() && null!==$item->getProduct_id() 
            ? $new_item->setProduct_id((int)$item->getProduct_id()) 
            : $new_item->setTask_id((int)$item->getTask_id()); 
            $new_item->setName($item->getName() ?? '');
            $new_item->setDescription($item->getDescription() ?? '');
            $new_item->setQuantity($item->getQuantity()*-1);
            $new_item->setPrice($item->getPrice() ?? 0.00);
            $new_item->setDiscount_amount($item->getDiscount_amount() ?? 0.00);
            $new_item->setOrder($item->getOrder());
            // Even if an invoice is balanced with a credit invoice it will remain recurring ... unless stopped.
            $new_item->setIs_recurring($item->getIs_recurring());
            $new_item->setProduct_unit($item->getProduct_unit() ?? '');
            $new_item->setProduct_unit_id((int)$item->getProduct_unit_id());
            $new_item->setDate($item->getDate_added());
            $iiR->save($new_item);
                       
            // Create an item amount for this item; reversing the items amounts to negative
            $basis_item_amount = $iiaR->repoInvItemAmountquery((string)$item->getId());
            $new_item_amount = new InvItemAmount();
            $new_item_amount->setInv_item_id((int)$new_item->getId());
            $new_item_amount->setSubtotal($basis_item_amount->getSubtotal()*-1);
            $new_item_amount->setTax_total($basis_item_amount->getTax_total()*-1);
            $new_item_amount->setDiscount($basis_item_amount->getDiscount()*-1);
            $new_item_amount->setTotal($basis_item_amount->getTotal()*-1);
            $iiaR->save($new_item_amount);        
        }
    }    
}
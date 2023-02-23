<?php
declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\QuoteAmount;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Entity\InvItem;
use App\Invoice\Helpers\MpdfHelper;
use App\Invoice\Helpers\CustomValuesHelper as CVH;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Session\SessionInterface as Session;

Class PdfHelper
{
    private SR $s;
    
    private Session $session;

    public function __construct(SR $s, Session $session) {
        $this->s = $s;
        $this->session = $session;
    }
    
    /**
     * 
     * @return string|null
     */
    private function locale_to_language() : string|null {
        $dropdown_locale = (string)$this->session->get('_language');
        /** @var array $session_list */
        $session_list = $this->s->locale_language_array();
        /** @var string $session_list[$dropdown_locale] */
        return $session_list[$dropdown_locale] ?? null;
    }
        
    // Used on line 104 to determine the cldr 
    /**
     * 
     * @param array|object $quote_or_inv
     * @return mixed
     */
    private function get_print_language(array|object $quote_or_inv) : mixed {
        $locale_lang = $this->locale_to_language();
        // Get the client language if set : otherwise use the locale as basis
        if ($quote_or_inv instanceof \App\Invoice\Entity\Quote || 
            $quote_or_inv instanceof \App\Invoice\Entity\Inv) {     
            $print_language = (!empty($quote_or_inv->getClient()?->getClient_language()) ?  $quote_or_inv->getClient()?->getClient_language() : $locale_lang);              
            $this->session->set('print_language', $print_language); 
            return  $print_language;
        }
        return '';
    }
    
    /**
     * @param string|null $quote_id
     * @param string $user_id
     * @param bool $stream
     * @param bool $custom
     * @param QuoteAmount|null $quote_amount
     * @param array $quote_custom_values
     * @param \App\Invoice\Client\ClientRepository $cR
     * @param \App\Invoice\CustomValue\CustomValueRepository $cvR
     * @param \App\Invoice\CustomField\CustomFieldRepository $cfR
     * @param \App\Invoice\QuoteItem\QuoteItemRepository $qiR
     * @param \App\Invoice\QuoteItemAmount\QuoteItemAmountRepository $qiaR
     * @param \App\Invoice\Quote\QuoteRepository $qR
     * @param \App\Invoice\QuoteTaxRate\QuoteTaxRateRepository $qtrR
     * @param \App\Invoice\UserInv\UserInvRepository $uiR
     * @param \Yiisoft\Yii\View\ViewRenderer $viewrenderer
     * @psalm-suppress MissingReturnType
     */
    public function generate_quote_pdf(string|null $quote_id, string $user_id, bool $stream, bool $custom, object|null $quote_amount, array $quote_custom_values,\App\Invoice\Client\ClientRepository $cR, \App\Invoice\CustomValue\CustomValueRepository $cvR, \App\Invoice\CustomField\CustomFieldRepository $cfR, \App\Invoice\QuoteItem\QuoteItemRepository $qiR, \App\Invoice\QuoteItemAmount\QuoteItemAmountRepository $qiaR, \App\Invoice\Quote\QuoteRepository $qR, \App\Invoice\QuoteTaxRate\QuoteTaxRateRepository $qtrR, \App\Invoice\UserInv\UserInvRepository $uiR,
                                \Yiisoft\Yii\View\ViewRenderer $viewrenderer)
    {       
            if ($quote_id) {
            
            $quote = $qR->repoCount($quote_id) > 0 ? $qR->repoQuoteLoadedquery($quote_id) : null;
            
            if (null!==$quote){
                // If userinv details have been filled, use these details
                $userinv = ($uiR->repoUserInvcount($user_id)>0 ? $uiR->repoUserInvquery($user_id) : null);
                // If a template has been selected in the dropdown use it otherwise use the default 'quote' template under
                // views/invoice/template/quote/pdf/quote.pdf
                $quote_template = (!empty($this->s->get_setting('pdf_quote_template')) ? $this->s->get_setting('pdf_quote_template') : 'quote');            

                // Determine if discounts should be displayed if there are items on the quote     
                $items = ($qiR->repoCount($quote_id) > 0 ? $qiR->repoQuoteItemIdquery($quote_id) : null);

                $show_item_discounts = false;
                // Determine if any of the items have a discount, if so then the discount amount row will have to be shown.
                if (null!==$items) {
                    /** @var QuoteItem $item */
                    foreach ($items as $item) {
                       if ($item->getDiscount_amount() !== 0.00) {
                            $show_item_discounts = true;
                       }
                    }
                }
                // Get all data related to building the quote including custom fields
                $data = [
                    'quote' => $quote,
                    'quote_tax_rates' => (($qtrR->repoCount((string)$this->session->get('quote_id')) > 0) ? $qtrR->repoQuotequery((string)$this->session->get('quote_id')) : null), 
                    'items' => $items,
                    'qiaR'=>$qiaR,
                    'output_type' => 'pdf',
                    'show_item_discounts' => $show_item_discounts,
                    // Show the custom fields if the user has answered yes on the modal ie $custom = true
                    'show_custom_fields' => $custom,
                    // Custom fields appearing near the top of the quote
                    'custom_fields'=>$cfR->repoTablequery('quote_custom'),
                    'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('quote_custom')),
                    'cvH'=> new CVH($this->s),
                    'quote_custom_values' => $quote_custom_values,
                    'top_custom_fields' =>$viewrenderer->renderPartialAsString('/invoice/template/quote/pdf/top_custom_fields', [
                        'custom_fields'=>$cfR->repoTablequery('quote_custom'),
                        'cvR'=>$cvR, 
                        'quote_custom_values'=> $quote_custom_values,  
                        'cvH'=> new CVH($this->s),
                        's'=>$this->s,   
                    ]),    
                    // Custom fields appearing at the bottom of the quote
                    'view_custom_fields'=>$viewrenderer->renderPartialAsString('/invoice/template/quote/pdf/view_custom_fields', [
                        'custom_fields'=>$cfR->repoTablequery('quote_custom'),
                        'cvR'=>$cvR,
                        'quote_custom_values'=> $quote_custom_values,  
                        'cvH'=> new CVH($this->s),
                        's'=>$this->s,   
                    ]),        
                    's'=>$this->s,
                    'countryhelper'=>new CountryHelper(),
                    'userinv'=>$userinv,
                    'client'=>$cR->repoClientquery((string)$quote->getClient()?->getClient_id()),
                    'quote_amount'=>$quote_amount,            
                    // Use the temporary print language to define cldr            
                    'cldr'=> array_keys($this->s->locale_language_array(), $this->get_print_language($quote)),
                ];        
                // Quote Template will be either 'quote' or a custom designed quote in the folder.
                $html = $viewrenderer->renderPartialAsString('/invoice/template/quote/pdf/'.$quote_template,$data);
                // Set the print language to null for future use
                $this->session->set('print_language','');
                $mpdfhelper = new MpdfHelper(); 
                $filename = $this->s->trans('quote') . '_' . str_replace(['\\', '/'], '_', ($quote->getNumber() ?? (string)rand(0, 10)));
                return $mpdfhelper->pdf_create($html, $filename, $stream, $quote->getPassword(), $this->s, $isInvoice = false, $quote);
            }    
        } 
    }   //generate_quote_pdf
    
    /**
     * @param string|null $inv_id
     * @param string $user_id
     * @param bool $stream
     * @param bool $custom
     * @param InvAmount|null $inv_amount
     * @param array $inv_custom_values
     * @param \App\Invoice\Client\ClientRepository $cR
     * @param \App\Invoice\CustomValue\CustomValueRepository $cvR
     * @param \App\Invoice\CustomField\CustomFieldRepository $cfR
     * @param \App\Invoice\InvItem\InvItemRepository $iiR
     * @param \App\Invoice\InvItemAmount\InvItemAmountRepository $iiaR
     * @param \App\Invoice\Inv\InvRepository $iR
     * @param \App\Invoice\InvTaxRate\InvTaxRateRepository $itrR
     * @param \App\Invoice\UserInv\UserInvRepository $uiR
     * @param \Yiisoft\Yii\View\ViewRenderer $viewrenderer
     * @psalm-suppress MissingReturnType
     */
    public function generate_inv_pdf(string|null $inv_id, string $user_id, bool $stream, bool $custom, InvAmount|null $inv_amount, array $inv_custom_values,\App\Invoice\Client\ClientRepository $cR, \App\Invoice\CustomValue\CustomValueRepository $cvR, \App\Invoice\CustomField\CustomFieldRepository $cfR, \App\Invoice\InvItem\InvItemRepository $iiR, \App\Invoice\InvItemAmount\InvItemAmountRepository $iiaR, \App\Invoice\Inv\InvRepository $iR, \App\Invoice\InvTaxRate\InvTaxRateRepository $itrR, \App\Invoice\UserInv\UserInvRepository $uiR,
                                \Yiisoft\Yii\View\ViewRenderer $viewrenderer)
    {       
       if ($inv_id) { 
            $inv = $iR->repoCount($inv_id) > 0 ? $iR->repoInvLoadedquery($inv_id) : null;
            if ($inv) {
                // If userinv details have been filled, use these details
                $userinv = ($uiR->repoUserInvcount($user_id)>0 ? $uiR->repoUserInvquery($user_id) : null);
                // 'draft' => status_id => 1 
                $inv_template = $this->generate_inv_pdf_template_normal_paid_overdue_watermark($inv->getStatus_id() ?? 1);
                      
                // Determine if discounts should be displayed if there are items on the invoice      
                $items = ($iiR->repoCount($inv_id) > 0 ? $iiR->repoInvItemIdquery($inv_id) : null);

                $show_item_discounts = false;
                // Determine if any of the items have a discount, if so then the discount amount row will have to be shown.
                if (null!==$items) {
                    /** @var InvItem $item */ 
                    foreach ($items as $item) {  
                        if ($item->getDiscount_amount() !== 0.00) {
                            $show_item_discounts = true;
                        }
                    }
                }

                // Get all data related to building the inv including custom fields
                $data = [
                    'inv' => $inv,
                    'inv_tax_rates' => (($itrR->repoCount((string)$this->session->get('inv_id')) > 0) ? $itrR->repoInvquery((string)$this->session->get('inv_id')) : null), 
                    'items' => $items,
                    'iiaR'=>$iiaR,
                    'output_type' => 'pdf',
                    'show_item_discounts' => $show_item_discounts,
                    // Show the custom fields if the user has answered yes on the modal ie $custom = true
                    'show_custom_fields' => $custom,
                    // Custom fields appearing near the top of the quote
                    'custom_fields'=>$cfR->repoTablequery('inv_custom'),
                    'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('inv_custom')),
                    'cvH'=> new CVH($this->s),
                    'inv_custom_values' => $inv_custom_values,
                    'top_custom_fields' =>$viewrenderer->renderPartialAsString('/invoice/template/invoice/pdf/top_custom_fields', [
                        'custom_fields'=>$cfR->repoTablequery('inv_custom'),
                        'cvR'=>$cvR, 
                        'inv_custom_values'=> $inv_custom_values,  
                        'cvH'=> new CVH($this->s),
                        's'=>$this->s,   
                    ]),    
                    // Custom fields appearing at the bottom of the quote
                    'view_custom_fields'=>$viewrenderer->renderPartialAsString('/invoice/template/invoice/pdf/view_custom_fields', [
                        'custom_fields'=>$cfR->repoTablequery('inv_custom'),
                        'cvR'=>$cvR,
                        'inv_custom_values'=> $inv_custom_values,  
                        'cvH'=> new CVH($this->s),
                        's'=>$this->s,   
                    ]),        
                    's'=>$this->s,
                    'countryhelper'=>new CountryHelper(),
                    'userinv'=>$userinv,
                    'client'=>$cR->repoClientquery((string)$inv->getClient()?->getClient_id()),
                    'inv_amount'=>$inv_amount,            
                    // Use the temporary print language to define cldr            
                    'cldr'=> array_keys($this->s->locale_language_array(), $this->get_print_language($inv)),
                ];        
                // Inv Template will be either 'inv' or a custom designed inv in the folder.
                $html = $viewrenderer->renderPartialAsString('/invoice/template/invoice/pdf/'.$inv_template,$data);
                // Set the print language to null for future use
                $this->session->set('print_language','');
                $mpdfhelper = new MpdfHelper(); 
                $filename = $this->s->trans('invoice') . '_' . str_replace(['\\', '/'], '_', ($inv->getNumber() ?? (string)rand(0, 10)));
                //$isInvoice is assigned to true as it is an invoice
                // If stream is true return the pdf as a string using mpdf otherwise save to local file and 
                // return the filename inclusive target_path to be used to attach to email attachments
                return $mpdfhelper->pdf_create($html, $filename, $stream, $inv->getPassword(), $this->s, true, $inv);
                
            }
       }
    } //generate_inv_pdf
    
    /**
     * Determines what watermark words eg. paid, and overdue that will be shown diagonally across an invoice
     * @param int $status_id
     * @return string
     */
    public function generate_inv_pdf_template_normal_paid_overdue_watermark(int $status_id) : string {
        switch ($status_id) {
            case ($status_id == 4 && !empty($this->s->get_setting('pdf_invoice_template_paid'))):
                $return =  $this->s->get_setting('pdf_invoice_template_paid');
                break;
            case ($status_id == 4 && empty($this->s->get_setting('pdf_invoice_template_paid'))):                
                $return = 'paid';
                break;
            case ($status_id == 5 && !empty($this->s->get_setting('pdf_invoice_template_overdue'))):
                $return =  $this->s->get_setting('pdf_invoice_template_overdue');
                break;
            case ($status_id == 5 && empty($this->s->get_setting('pdf_invoice_template_overdue'))):                
                $return = 'overdue';
                break;
            default: 
                $return =  'invoice';
                break;
        }
        return $return;
    }
    
    public function generate_inv_sumex(): void {
        
    }
} 
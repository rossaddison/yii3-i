<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use DateTime;
use InvalidArgumentException;

class Invoice implements XmlSerializable {
    public string $xmlTagName = 'Invoice';
    private ?string $UBLVersionID = '2.1';
    private ?string $customizationID = '1.0';    
    private string $documentCurrencyCode = 'EUR';    
    private ?ContractDocumentReference $contractDocumentReference;            
    private ?Delivery $delivery;        
    private ?InvoicePeriod $invoicePeriod;
    private ?OrderReference $orderReference;    
    private ?LegalMonetaryTotal $legalMonetaryTotal;
    private ?Party $accountingSupplierParty;
    private ?Party $accountingCustomerParty;    
    private ?PaymentMeans $paymentMeans;            
    private ?PaymentTerms $paymentTerms;    
    private ?TaxTotal $taxTotal;    
       
    private DateTime $issueDate;
    private ?DateTime $taxPointDate;
    private ?DateTime $dueDate;        
    
    private ?string $accountingCostCode;
    /** @var AdditionalDocumentReference[] $additionalDocumentReferences */
    private array $additionalDocumentReferences = [];
    private array $allowanceCharges;
    private ?string $buyerReference;
    private ?bool $isCopyIndicator; 
    private ?string $id;
    protected array $invoiceLines;
    
    protected ?int $invoiceTypeCode = InvoiceTypeCode::INVOICE;
    
    private ?string $note;
    private ?string $profileID;
    private ?string $supplierAssignedAccountID;
    
    public function __construct(            
            ContractDocumentReference $contractDocumentReference,                                      
            ?Delivery $delivery,          
            ?InvoicePeriod $invoicePeriod,           
            ?LegalMonetaryTotal  $legalMonetaryTotal,
            ?OrderReference $orderReference,
            ?Party $accountingSupplierParty,
            ?Party $accountingCustomerParty,
            PaymentMeans $paymentMeans,
            PaymentTerms $paymentTerms,
            TaxTotal $taxTotal,    
            
            DateTime $issueDate,
            DateTime $taxPointDate,
            DateTime $dueDate,
            
            ?string $accountingCostCode,
            array  $additionalDocumentReferences,            
            array  $allowanceCharges,
            ?string $buyerReference,
            ?bool $isCopyIndicator,
            ?string $id,
            array  $invoiceLines,
            ?string $note,
            ?string $profileID,
            ?string $supplierAssignedAccountID,
    ) {
            
            $this->contractDocumentReference = $contractDocumentReference;
            $this->delivery = $delivery;            
            $this->invoicePeriod = $invoicePeriod;
            $this->orderReference = $orderReference;
            $this->accountingSupplierParty = $accountingSupplierParty;
            $this->accountingCustomerParty = $accountingCustomerParty;
            $this->paymentMeans = $paymentMeans;
            $this->paymentTerms = $paymentTerms;
            $this->taxTotal = $taxTotal;          
            
            $this->issueDate = $issueDate;            
            $this->taxPointDate = $taxPointDate;
            $this->dueDate = $dueDate;
            
            $this->accountingCostCode = $accountingCostCode;            
            
            /** @var AdditionalDocumentReference[] $additionalDocumentReferences */
            $this->additionalDocumentReferences = $additionalDocumentReferences;
            $this->allowanceCharges = $allowanceCharges;
            $this->buyerReference = $buyerReference;
            $this->isCopyIndicator = $isCopyIndicator;
            $this->id = $id;            
            $this->invoiceLines = $invoiceLines;            
            $this->legalMonetaryTotal = $legalMonetaryTotal;
            $this->note = $note;
            $this->paymentTerms = $paymentTerms;
            $this->profileID = $profileID;
            $this->supplierAssignedAccountID = $supplierAssignedAccountID;
    }

    /**
     * @return null|string
     */
    public function getUBLVersionID(): ?string {
        return $this->UBLVersionID;
    }

    /**
     * @param null|string $UBLVersionID
     * eg. '2.0', '2.1', '2.2', ...
     * @return Invoice
     */
    public function setUBLVersionID(?string $UBLVersionID): Invoice {
        $this->UBLVersionID = $UBLVersionID;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getId(): ?string {
        return $this->id;
    }

    /**
     * @param null|string $id
     * @return Invoice
     */
    public function setId(?string $id): Invoice {
        $this->id = $id;
        return $this;
    }

    /**
     * @param null|string $customizationID
     * @return Invoice
     */
    public function setCustomizationID(?string $customizationID): Invoice {
        $this->customizationID = $customizationID;
        return $this;
    }

    /**
     * @param null|string $profileID
     * @return Invoice
     */
    public function setProfileID(?string $profileID): Invoice {
        $this->profileID = $profileID;
        return $this;
    }

    /**
     * @return null|bool
     */
    public function isCopyIndicator(): null|bool {
        return $this->isCopyIndicator;
    }

    /**
     * @param bool $isCopyIndicator
     * @return Invoice
     */
    public function setCopyIndicator(bool $isCopyIndicator): Invoice {
        $this->isCopyIndicator = $isCopyIndicator;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getIssueDate(): ?DateTime {
        return $this->issueDate;
    }

    /**
     * @param DateTime $issueDate
     * @return Invoice
     */
    public function setIssueDate(DateTime $issueDate): Invoice {
        $this->issueDate = $issueDate;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDueDate(): ?DateTime {
        return $this->dueDate;
    }

    /**
     * @param DateTime $dueDate
     * @return Invoice
     */
    public function setDueDate(DateTime $dueDate): Invoice {
        $this->dueDate = $dueDate;
        return $this;
    }

    /**
     * @param string $currencyCode
     * @return Invoice
     */
    public function setDocumentCurrencyCode(string $currencyCode = 'EUR'): Invoice {
        $this->documentCurrencyCode = $currencyCode;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getInvoiceTypeCode(): ?int {
        return $this->invoiceTypeCode;
    }

    /**
     * @param int $invoiceTypeCode
     * @return Invoice
     */
    public function setInvoiceTypeCode(int $invoiceTypeCode): Invoice {
        $this->invoiceTypeCode = $invoiceTypeCode;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getNote() {
        return $this->note;
    }

    /**
     * @param null|string $note
     * @return Invoice
     */
    public function setNote(?string $note) : Invoice {
        $this->note = $note;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getTaxPointDate(): ?DateTime {
        return $this->taxPointDate;
    }

    /**
     * @param DateTime $taxPointDate
     * @return Invoice
     */
    public function setTaxPointDate(DateTime $taxPointDate): Invoice {
        $this->taxPointDate = $taxPointDate;
        return $this;
    }

    /**
     * @return null|PaymentTerms
     */
    public function getPaymentTerms(): ?PaymentTerms {
        return $this->paymentTerms;
    }

    /**
     * @param PaymentTerms $paymentTerms
     * @return Invoice
     */
    public function setPaymentTerms(PaymentTerms $paymentTerms): Invoice {
        $this->paymentTerms = $paymentTerms;
        return $this;
    }

    /**
     * @return null|Party
     */
    public function getAccountingSupplierParty(): ?Party {
        return $this->accountingSupplierParty;
    }

    /**
     * @param Party $accountingSupplierParty
     * @return Invoice
     */
    public function setAccountingSupplierParty(Party $accountingSupplierParty): Invoice {
        $this->accountingSupplierParty = $accountingSupplierParty;
        return $this;
    }

    /**
     * @return string
     */
    public function getSupplierAssignedAccountID(): ?string {
        return $this->supplierAssignedAccountID;
    }

    /**
     * @param string $supplierAssignedAccountID
     * @return Invoice
     */
    public function setSupplierAssignedAccountID(string $supplierAssignedAccountID): Invoice {
        $this->supplierAssignedAccountID = $supplierAssignedAccountID;
        return $this;
    }

    /**
     * @return null|Party
     */
    public function getAccountingCustomerParty(): ?Party {
        return $this->accountingCustomerParty;
    }

    /**
     * @param Party $accountingCustomerParty
     * @return Invoice
     */
    public function setAccountingCustomerParty(Party $accountingCustomerParty): Invoice {
        $this->accountingCustomerParty = $accountingCustomerParty;
        return $this;
    }

    /**
     * @return null|PaymentMeans
     */
    public function getPaymentMeans(): ?PaymentMeans {
        return $this->paymentMeans;
    }

    /**
     * @param PaymentMeans $paymentMeans
     * @return Invoice
     */
    public function setPaymentMeans(PaymentMeans $paymentMeans): Invoice {
        $this->paymentMeans = $paymentMeans;
        return $this;
    }

    /**
     * @return null|TaxTotal
     */
    public function getTaxTotal(): ?TaxTotal {
        return $this->taxTotal;
    }

    /**
     * @param TaxTotal $taxTotal
     * @return Invoice
     */
    public function setTaxTotal(TaxTotal $taxTotal): Invoice {
        $this->taxTotal = $taxTotal;
        return $this;
    }

    /**
     * @return null|LegalMonetaryTotal
     */
    public function getLegalMonetaryTotal(): ?LegalMonetaryTotal {
        return $this->legalMonetaryTotal;
    }

    /**
     * @param LegalMonetaryTotal $legalMonetaryTotal
     * @return Invoice
     */
    public function setLegalMonetaryTotal(LegalMonetaryTotal $legalMonetaryTotal): Invoice {
        $this->legalMonetaryTotal = $legalMonetaryTotal;
        return $this;
    }

    /**
     * @return null|array
     */
    public function getInvoiceLines(): ?array {
        return $this->invoiceLines;
    }

    /**
     * @param InvoiceLine[] $invoiceLines
     * @return Invoice
     */
    public function setInvoiceLines(array $invoiceLines): Invoice {
        $this->invoiceLines = $invoiceLines;
        return $this;
    }

    /**
     * @return null|array
     */
    public function getAllowanceCharges(): ?array {
        return $this->allowanceCharges;
    }

    /**
     * @param AllowanceCharge[] $allowanceCharges
     * @return Invoice
     */
    public function setAllowanceCharges(array $allowanceCharges): Invoice {
        $this->allowanceCharges = $allowanceCharges;
        return $this;
    }

    /**
     * @return null|AdditionalDocumentReference
     */
    public function getAdditionalDocumentReference(): ?AdditionalDocumentReference {
        return $this->additionalDocumentReferences[0] ?? null;
    }

    /**
     * @param AdditionalDocumentReference $additionalDocumentReference
     * @return Invoice
     */
    public function setAdditionalDocumentReference(AdditionalDocumentReference $additionalDocumentReference): Invoice {
        $this->additionalDocumentReferences = [$additionalDocumentReference];
        return $this;
    }

    /**
     * @param AdditionalDocumentReference $additionalDocumentReference
     * @return Invoice
     */
    public function addAdditionalDocumentReference(AdditionalDocumentReference $additionalDocumentReference): Invoice {
        $this->additionalDocumentReferences[] = $additionalDocumentReference;
        return $this;
    }

    /**
     * @param string $buyerReference
     * @return Invoice
     */
    public function setBuyerReference(string $buyerReference): Invoice {
        $this->buyerReference = $buyerReference;
        return $this;
    }

    /**
     * @return string buyerReference
     */
    public function getBuyerReference(): ?string {
        return $this->buyerReference;
    }

    /**
     * @return null|string
     */
    public function getAccountingCostCode(): ?string {
        return $this->accountingCostCode;
    }

    /**
     * @param null|string $accountingCostCode
     * @return Invoice
     */
    public function setAccountingCostCode(?string $accountingCostCode): Invoice {
        $this->accountingCostCode = $accountingCostCode;
        return $this;
    }

    /**
     * @return null|InvoicePeriod
     */
    public function getInvoicePeriod(): ?InvoicePeriod {
        return $this->invoicePeriod;
    }

    /**
     * @param InvoicePeriod $invoicePeriod
     * @return Invoice
     */
    public function setInvoicePeriod(InvoicePeriod $invoicePeriod): Invoice {
        $this->invoicePeriod = $invoicePeriod;
        return $this;
    }

    /**
     * @return Delivery
     */
    public function getDelivery(): ?Delivery {
        return $this->delivery;
    }

    /**
     * @param Delivery $delivery
     * @return Invoice
     */
    public function setDelivery(Delivery $delivery): Invoice {
        $this->delivery = $delivery;
        return $this;
    }

    /**
     * @return OrderReference
     */
    public function getOrderReference(): ?OrderReference {
        return $this->orderReference;
    }

    /**
     * @param OrderReference $orderReference
     * @return Invoice
     */
    public function setOrderReference(OrderReference $orderReference): Invoice {
        $this->orderReference = $orderReference;
        return $this;
    }

    /**
     * @return ContractDocumentReference
     */
    public function getContractDocumentReference(): ?ContractDocumentReference {
        return $this->contractDocumentReference;
    }

    /**
     * @param ContractDocumentReference $contractDocumentReference
     * @return Invoice
     */
    public function setContractDocumentReference(ContractDocumentReference $contractDocumentReference): Invoice {
        $this->contractDocumentReference = $contractDocumentReference;
        return $this;
    }

    /**
     * The validate function that is called during xml writing to valid the data of the object.
     *
     * @return void
     * @throws InvalidArgumentException An error with information about required data that is missing to write the XML
     */
    public function validate() {
        if ($this->id === null) {
            throw new InvalidArgumentException('Missing invoice id');
        }

        if (!$this->issueDate instanceof DateTime) {
            throw new InvalidArgumentException('Invalid invoice issueDate');
        }

        if ($this->invoiceTypeCode === null) {
            throw new InvalidArgumentException('Missing invoice invoiceTypeCode');
        }

        if ($this->accountingSupplierParty === null) {
            throw new InvalidArgumentException('Missing invoice accountingSupplierParty');
        }

        if ($this->accountingCustomerParty === null) {
            throw new InvalidArgumentException('Missing invoice accountingCustomerParty');
        }

        if (!empty($this->invoiceLines)) {
            throw new InvalidArgumentException('Missing invoice lines');
        }

        if ($this->legalMonetaryTotal === null) {
            throw new InvalidArgumentException('Missing invoice LegalMonetaryTotal');
        }
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void {
        $this->validate();

        $writer->write([
            Schema::CBC . 'UBLVersionID' => $this->UBLVersionID,
            Schema::CBC . 'CustomizationID' => $this->customizationID,
        ]);

        if ($this->profileID !== null) {
            $writer->write([
                Schema::CBC . 'ProfileID' => $this->profileID
            ]);
        }

        $writer->write([
            Schema::CBC . 'ID' => $this->id
        ]);
        
        if ($this->isCopyIndicator !== null) {
            $writer->write([
                Schema::CBC . 'CopyIndicator' => $this->isCopyIndicator ? 'true' : 'false'
            ]);
        }

        $writer->write([
            Schema::CBC . 'IssueDate' => $this->issueDate->format('Y-m-d'),
        ]);

        if ($this->dueDate !== null && $this->xmlTagName === 'Invoice') {
            $writer->write([
                Schema::CBC . 'DueDate' => $this->dueDate->format('Y-m-d')
            ]);
        }

        $writer->write([
            Schema::CBC . $this->xmlTagName . 'TypeCode' => $this->invoiceTypeCode
        ]);
      

        if ($this->note !== null) {
            $writer->write([
                Schema::CBC . 'Note' => $this->note
            ]);
        }

        if ($this->taxPointDate !== null) {
            $writer->write([
                Schema::CBC . 'TaxPointDate' => $this->taxPointDate->format('Y-m-d')
            ]);
        }

        $writer->write([
            Schema::CBC . 'DocumentCurrencyCode' => $this->documentCurrencyCode,
        ]);

        if ($this->accountingCostCode !== null) {
            $writer->write([
                Schema::CBC . 'AccountingCostCode' => $this->accountingCostCode
            ]);
        }

        if ($this->buyerReference !== null) {
            $writer->write([
                Schema::CBC . 'BuyerReference' => $this->buyerReference
            ]);
        }

        if ($this->contractDocumentReference !== null) {
            $writer->write([
                Schema::CAC . 'ContractDocumentReference' => $this->contractDocumentReference,
            ]);
        }

        if ($this->invoicePeriod !== null) {
            $writer->write([
                Schema::CAC . 'InvoicePeriod' => $this->invoicePeriod
            ]);
        }

        if ($this->orderReference !== null) {
            $writer->write([
                Schema::CAC . 'OrderReference' => $this->orderReference
            ]);
        }

        if (!empty($this->additionalDocumentReferences)) {
            /** @var AdditionalDocumentReference $additionalDocumentReference */
            foreach ($this->additionalDocumentReferences as $additionalDocumentReference) {
                $writer->write([
                    Schema::CAC . 'AdditionalDocumentReference' => $additionalDocumentReference
                ]);
            }
        }

        if ($this->supplierAssignedAccountID !== null) {
            $customerParty = [
                Schema::CBC . 'SupplierAssignedAccountID' => $this->supplierAssignedAccountID,
                Schema::CAC . "Party" => $this->accountingCustomerParty
            ];
        } else {
            $customerParty = [
                Schema::CAC . "Party" => $this->accountingCustomerParty
            ];
        }

        $writer->write([
            Schema::CAC . 'AccountingSupplierParty' => [Schema::CAC . "Party" => $this->accountingSupplierParty],
            Schema::CAC . 'AccountingCustomerParty' => $customerParty,
        ]);

        if ($this->delivery !== null) {
            $writer->write([
                Schema::CAC . 'Delivery' => $this->delivery
            ]);
        }

        if ($this->paymentMeans !== null) {
            $writer->write([
                Schema::CAC . 'PaymentMeans' => $this->paymentMeans
            ]);
        }

        if ($this->paymentTerms !== null) {
            $writer->write([
                Schema::CAC . 'PaymentTerms' => $this->paymentTerms
            ]);
        }

        
        if (!empty($this->allowanceCharges)) {
            /** @var AllowanceCharge $allowanceCharge */
            foreach ($this->allowanceCharges as $allowanceCharge) {
                $writer->write([
                    Schema::CAC . 'AllowanceCharge' => $allowanceCharge
                ]);
            }
        }

        if ($this->taxTotal !== null) {
            $writer->write([
                Schema::CAC . 'TaxTotal' => $this->taxTotal
            ]);
        }

        $writer->write([
            Schema::CAC . 'LegalMonetaryTotal' => $this->legalMonetaryTotal
        ]);
        
        /** @var InvoiceLine $invoiceLine */
        foreach ($this->invoiceLines as $invoiceLine) {
            $writer->write([
                Schema::CAC . $invoiceLine->xmlTagName => $invoiceLine
            ]);
        }
    }
}

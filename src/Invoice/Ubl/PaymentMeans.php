<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use DateTime;

class PaymentMeans implements XmlSerializable
{
    private ?int $paymentMeansCode = 1;
    private array $paymentMeansCodeAttributes = [
        'listID' => 'UN/ECE 4461',
        'listName' => 'Payment Means',
        'listURI' => 'http://docs.oasis-open.org/ubl/os-UBL-2.0-update/cl/gc/default/PaymentMeansCode-2.0.gc'];
    private ?DateTime $paymentDueDate;
    private ?string $instructionId;
    private ?string $instructionNote;
    private ?string $paymentId;
    private ?PayeeFinancialAccount $payeeFinancialAccount;
    
    public function __construct(
            ?PayeeFinancialAccount $payeeFinancialAccount,
            string $paymentId = '',
            string $instructionNote = '',
            string $instructionId = '',
            DateTime $paymentDueDate = new DateTime 
    ) {
            $this->payeeFinancialAccount = $payeeFinancialAccount;
            $this->paymentId = $paymentId;
            $this->instructionNote = $instructionNote;
            $this->instructionId = $instructionId;
            $this->paymentDueDate = $paymentDueDate;
    }

    /**
     * @return null|int
     */
    public function getPaymentMeansCode(): ?int
    {
        return $this->paymentMeansCode;
    }

    /**
     * @param null|int $paymentMeansCode
     * @return PaymentMeans
     */
    public function setPaymentMeansCode(?int $paymentMeansCode, array $attributes = null): PaymentMeans
    {
        $this->paymentMeansCode = $paymentMeansCode;
        if (is_array($attributes) && !empty($attributes)) {
            $this->paymentMeansCodeAttributes = $attributes;
        }
        return $this;
    }

    /**
     * @return null|DateTime
     */
    public function getPaymentDueDate(): ?DateTime
    {
        return $this->paymentDueDate;
    }

    /**
     * @param null|DateTime $paymentDueDate
     * @return PaymentMeans
     */
    public function setPaymentDueDate(?DateTime $paymentDueDate): PaymentMeans
    {
        $this->paymentDueDate = $paymentDueDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getInstructionId(): ?string
    {
        return $this->instructionId;
    }

    /**
     * @param string $instructionId
     * @return PaymentMeans
     */
    public function setInstructionId(?string $instructionId): PaymentMeans
    {
        $this->instructionId = $instructionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getInstructionNote(): ?string
    {
        return $this->instructionNote;
    }

    /**
     * @param string $instructionNote
     * @return PaymentMeans
     */
    public function setInstructionNote(?string $instructionNote): PaymentMeans
    {
        $this->instructionNote = $instructionNote;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    /**
     * @param string $paymentId
     * @return PaymentMeans
     */
    public function setPaymentId(?string $paymentId): PaymentMeans
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    /**
     * @return null|PayeeFinancialAccount
     */
    public function getPayeeFinancialAccount(): ?PayeeFinancialAccount
    {
        return $this->payeeFinancialAccount;
    }

    /**
     * @param PayeeFinancialAccount $payeeFinancialAccount
     * @return PaymentMeans
     */
    public function setPayeeFinancialAccount(?PayeeFinancialAccount $payeeFinancialAccount): PaymentMeans
    {
        $this->payeeFinancialAccount = $payeeFinancialAccount;
        return $this;
    }
    
    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?p=3&q=PaymentMeans
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            'name' => Schema::CBC . 'PaymentMeansCode',
            'value' => $this->paymentMeansCode,
            'attributes' => $this->paymentMeansCodeAttributes
        ]);

        if ($this->getPaymentDueDate() !== null) {
            $writer->write([
                Schema::CBC . 'PaymentDueDate' => null!==$this->getPaymentDueDate() ? $this->getPaymentDueDate()?->format('Y-m-d') : (new DateTime())->format('Y-m-d')
            ]);
        }

        if ($this->getInstructionId() !== null) {
            $writer->write([
                Schema::CBC . 'InstructionID' => $this->getInstructionId()
            ]);
        }

        if ($this->getInstructionNote() !== null) {
            $writer->write([
                Schema::CBC . 'InstructionNote' => $this->getInstructionNote()
            ]);
        }

        if ($this->getPaymentId() !== null) {
            $writer->write([
                Schema::CBC . 'PaymentID' => $this->getPaymentId()
            ]);
        }

        if ($this->getPayeeFinancialAccount() !== null) {
            $writer->write([
                Schema::CAC . 'PayeeFinancialAccount' => $this->getPayeeFinancialAccount()
            ]);
        }
    }
}

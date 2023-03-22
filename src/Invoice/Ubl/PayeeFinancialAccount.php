<?php
declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class PayeeFinancialAccount implements XmlSerializable
{
    private ?string $id;
    private ?string $name;
    private ?FinancialInstitutionBranch $financialInstitutionBranch;

    public function __construct(
            ?FinancialInstitutionBranch $financialInstitutionBranch,            
            ?string $id,
            ?string $name
    ) {
            $this->financialInstitutionBranch = $financialInstitutionBranch;            
            $this->id = $id;
            $this->name = $name;
    }
    
    /**
     * @return null|string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param null|string $id
     * @return PayeeFinancialAccount
     */
    public function setId(?string $id): PayeeFinancialAccount
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     * @return PayeeFinancialAccount
     */
    public function setName(?string $name): PayeeFinancialAccount
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return null|FinancialInstitutionBranch
     */
    public function getFinancialInstitutionBranch(): ?FinancialInstitutionBranch
    {
        return $this->financialInstitutionBranch;
    }

    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?p=3&q=PayeeFinancialAccount
     * @param null|FinancialInstitutionBranch $financialInstitutionBranch
     * @return PayeeFinancialAccount
     */
    public function setFinancialInstitutionBranch(?FinancialInstitutionBranch $financialInstitutionBranch): PayeeFinancialAccount
    {
        $this->financialInstitutionBranch = $financialInstitutionBranch;
        return $this;
    }

    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            'name' => Schema::CBC . 'ID',
            'value' => $this->id,
            'attributes' => [
                //'schemeID' => 'IBAN'
            ]
        ]);

        if ($this->getName() !== null) {
            $writer->write([
                Schema::CBC . 'Name' => $this->getName()
            ]);
        }

        if ($this->getFinancialInstitutionBranch() !== null) {
            $writer->write([
                Schema::CAC . 'FinancialInstitutionBranch' => $this->getFinancialInstitutionBranch()
            ]);
        }
    }
}

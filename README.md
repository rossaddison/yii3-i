# yii3-i
Yii3 Invoice

**Features**

* Cycle ORM Interface using Invoiceplane type database schema. 
* Generate VAT invoices using Mpdf. 
* Code Generator - Controller to views. 
* PCI Compliant Payment Gateway Interfaces - Braintree Sandbox, Stripe Sandbox, and Amazon Pay Integration Tested. 
* Generate openPeppol Ubl 2.1 Invoice 3.0.15 XML Invoices - Validate with Ecosio. 
* StoreCove API Connector with Json Invoice. 
* Invoice Cycle - Quote to Sales Order (with Client's Purchase Order details) to Invoice.     
* Multiple Language Compliant - Steps to Generate new language files included. 
* Separate Client Console and Company Console. 
* Install with Composer.

**Installing with Composer in Windows**
*````composer update````*

**Installing npm_modules folder containing bootstrap as mentioned in package.json**
* Step 1: Download node.js at https://nodejs.org/en/download
* Step 2: Ensure C:\ProgramFiles\nodejs is in environment variable path. Search ... edit the system environment variables
* Step 3: Run ````npm i```` in ````c:\wamp64\yii3-i\invoice```` folder.

Adjust c:\wamp64\yii3-i\invoice\config\common\params.php file line approx. 193 to **MODE_WRITE_ONLY** for installation.
This will automatically build up the tables under database yii3-i.

````'mode' => PhpFileSchemaProvider::MODE_WRITE_ONLY,````

After installing, ensure mode is on **MODE_READ_AND_WRITE** for faster performance.

Signup your first user using **Create User Account**

Signup your second user as your Client/Customer.

**To enable your signed-up Client to make payments:** 
* Step 1: Make sure you have created a client ie. Client ... View ... New
* Step 2: Create a Settings...User Account
* Step 3: Use the Assigned Client ... Burger Button ... and assign the New User Account to an existing Client.
* Step 4: Make sure they are active.
* Step 5: Make sure the relevant invoice has the status 'sent' either by manualy editing the status of the invoice under Invoice ... View ... Options or by actually sending the invoice to the client by email under Invoice ... View ... Options.

**To install at least a service and a product, and a foreign and a non-foreign client automatically follow these steps please:**

* Step 1: Settings ... View ... General ... Install Test Data ... Yes  AND   Use Test Date ... Yes
* Step 2: In the main Url type: invoice and press enter. The Invoice Controller will create 2 clients and products automatically.

**The package by default will not use VAT and will use the traditional Invoiceplane type installation providing both line item tax and invoice tax** 

**If you require VAT based invoices, ensure VAT is setup by going to  Settings ... Views ... Value Added Tax and use a separate database for this purpose. Only line item tax will be available.**

**Steps to translate into another language:** 

GeneratorController includes a function google_translate_lang ...            
This function takes the English ip_lang array or gateway_lang located in 

````src/Invoice/Language/English```` 

and translates it into the chosen locale (Settings...View...Google Translate) 

outputting it to ````resources/views/generator/output_overwrite.```` 

* Step 1: Download https://curl.haxx.se/ca/cacert.pem into active c:\wamp64\bin\php\php8.1.12 folder.
* Step 2: Select your project that you created under https://console.cloud.google.com/projectselector2/iam-admin/serviceaccounts?pportedpurview=project
* Step 3: Click on Actions icon and select Manage Keys. 
* Step 4: Add Key.
* Step 5: Choose the Json File option and Download the file to src/Invoice/Google_translate_unique_folder.
* Step 6: You will have to enable the Cloud Translation API and provide your billing details. You will be charged 0 currency.
* Step 7: Move the file from views/generator/output_overwrite to eg. src/Invoice/Language/{your language}

**Xml electronic invoices - Can be output if the following sequence is followed:**

* a: A logged in Client sets up their Peppol details on their side via Client...View...Options...Edit Peppol Details for e-invoicing.

* b: A quote is created and sent by the Administrator to the Client.

* c: A logged in Client creates a sales order from the quote with their purchase order number, purchase order line number, and their contact person in the modal.

* d: A logged in Client, on each of the sales order line items, inputs their line item purchase order reference number, and their purchase order line number. (Mandatory or else exception will be raised).

* e: A logged in Administrator, requests that terms and conditions be accepted.

* f: A logged in Client accepts the terms and conditions.

* g: A logged in Administrator, updates the status of the sales order from assembled, approved, confirmed, to generate.

* h: A logged in Administrator can generate an invoice if the sales order status is on 'generate'

* i: A logged in Administrator can now generate a Peppol Xml Invoice using today's exchange rates setup on Settings...View...Peppol Electronic Invoicing...One of From Currency and One of To Currency

* j: Peppol exceptions will be raised.

[![Yii2](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/) [![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT) ![stable](https://img.shields.io/static/v1?label=No%20Release&message=0.0.0&color=9cf)  ![Downloads](https://img.shields.io/static/v1?label=Downloads/week&message=185&color=9cf)  ![Build](https://img.shields.io/static/v1?label=Build&message=Passing&color=66ff00)
![Dependency Checker](https://img.shields.io/static/v1?label=Dependency%20Checker&message=Passing&color=66ff00) ![Static Analysis](https://img.shields.io/static/v1?label=Static%20Analysis&message=Passing&color=66ff00)
![Psalm Level](https://img.shields.io/static/v1?label=Psalm%20Level&message=1&color=66ff00)



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
* Step 1: c:\windows\system32>cd\
* Step 2: c:\>**md** wamp64\www\yii3-i
* Step 3: c:\wamp64\yii3-i>**composer update**

Adjust c:\wamp64\yii3-i\config\common\params.php file line approx. 193 to **MODE_WRITE_ONLY** for installation.
This will automatically build up the tables under database yii3-i.

````'mode' => PhpFileSchemaProvider::MODE_WRITE_ONLY,````

After installing, ensure mode is on **MODE_READ_AND_WRITE** for faster performance.

Signup your first user using **Create User Account**
Signup your second user as your Client/Customer.

**To enable your signed-up Client to make payments:** 
* Step 1: Make sure you have created a client ie. Client ... View ... New
* Step 1: Create a Settings...User Account
* Step 2: Use the Assigned Client ... Burger Button ... and assign the New User Account to an existing Client.
* Step 4: Make sure they are active.

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
* Step 7: Move the file from views/generator/output_overwrite to eg. src/Invoice/Language/{your language}'

[![Yii2](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/) [![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT) ![stable](https://img.shields.io/static/v1?label=No%20Release&message=0.0.0&color=9cf)  ![Downloads](https://img.shields.io/static/v1?label=Downloads/week&message=185&color=9cf)  ![Build](https://img.shields.io/static/v1?label=Build&message=Passing&color=66ff00)
![Dependency Checker](https://img.shields.io/static/v1?label=Dependency%20Checker&message=Passing&color=66ff00) ![Static Analysis](https://img.shields.io/static/v1?label=Static%20Analysis&message=Passing&color=66ff00)
![Psalm Level](https://img.shields.io/static/v1?label=Psalm%20Level&message=1&color=66ff00)


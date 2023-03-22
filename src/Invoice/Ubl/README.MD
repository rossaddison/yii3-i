# Acknowledgements to github.com/num-num/ubl-invoice 
Aim: To implement UBL openPeppol electronic invoicing
[https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?q=Invoice]
1. declare(strict_types=1) included in all classes
2. public function constructs included in all classes as dictated by Psalm Level 1 testing.
3. Basic recoding presumption: if a field is validated against null
   in the XmlSerialize function, allow for null in the initialization 
   and also in the construct. 
4. Getters and Setters BOTH allow for null if point 3. satisfied allowing
   for a worst case scenario of a Setter(with a null getter) as a param.
   All null setter values are prevented from being sent to the Writer 
   by means of null!== or !==null 
5. Allow Psalm Level 1 testing to dictate the coding especially the 
   insistance on a construct.
6. The ClassifiedTaxCategory will have a default percent of 0.00
   since yii3-i requires at least a zero percent tax category.
   
#  Preparation for Testing
1. There are 3 psalm-suppressed areas of concern namely:
   [Generator.php at line 15, column 9] @psalm-suppress MissingReturnType 
   [Generator.php at line 31, column 14] @psalm-suppress InvalidArgument 
   [Generator.php at line 35, column 10] @psalm-suppress MissingReturnType
   
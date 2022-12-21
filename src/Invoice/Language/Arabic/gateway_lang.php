<?php
declare(strict_types=1);
$lang = array(
    // General strings
    'online_payment'                     => 'الد�?ع الالكتروني',
    'online_payments'                    => 'الد�?ع الالكتروني',
    'online_payment_for'                 => 'الد�?ع الالكتروني لـ',
    'online_payment_for_invoice'         => 'الد�?ع الإلكتروني لل�?اتورة',
    'online_payment_method'              => 'طريقة الد�?ع عبر الإنترنت',
    'online_payment_creditcard_hint'     => 'إذا كنت تود الد�?ع عبر بطاقة الإئتمان, الرجاء إدخال المعلومات المدرجة.<br/> بيانات بطاقة الإئتمان لن تكون مح�?وظة �?ي سير�?راتنا وسيتم تحويلك لبوابة الد�?ع بإستخدام إتصال آمن ومش�?ر.',
    'enable_online_payments'             => 'تمكين الد�?ع عبر الإنترنت',
    'payment_provider'                   => 'مزود خدمة الد�?ع',
    'add_payment_provider'               => 'إضا�?ة مو�?ر الد�?ع',
    'transaction_reference'              => 'رقم قيد الحركة',
    'payment_description'                => 'الد�?ع لل�?اتورة %s',

    // Credit card strings
    'creditcard_cvv'                     => 'الرقم CVV',
    'creditcard_details'                 => 'ت�?اصيل بطاقة الائتمان',
    'creditcard_expiry_month'            => 'شهر الانتهاء',
    'creditcard_expiry_year'             => 'سنة الإنتهاء',
    'creditcard_number'                  => 'رقم بطاقة الائتمان',
    'online_payment_card_invalid'        => 'هذه البطاقة غير صالحة. الرجاء التحقق من المعلومات المقدمة.',

    // Payment Gateway Fields
    'online_payment_apiLoginId'          => 'معر�? تسجيل الدخول Api', // Field for AuthorizeNet_AIM
    'online_payment_transactionKey'      => 'رقم العملية', // Field for AuthorizeNet_AIM
    'online_payment_testMode'            => 'وضع الاختبار', // Field for AuthorizeNet_AIM
    'online_payment_developerMode'       => 'وضع المطور', // Field for AuthorizeNet_AIM
    'online_payment_websiteKey'          => 'م�?تاح الموقع', // Field for Buckaroo_Ideal
    'online_payment_secretKey'           => 'الم�?تاح السري', // Field for Buckaroo_Ideal
    'online_payment_merchantId'          => 'معر�? التاجر', // Field for CardSave
    'online_payment_password'            => 'كلمة المرور', // Field for CardSave
    'online_payment_apiKey'              => 'Api', // Field for Coinbase
    'online_payment_secret'              => 'الرمز السري', // Field for Coinbase
    'online_payment_accountId'           => 'معر�? الحساب', // Field for Coinbase
    'online_payment_storeId'             => 'معر�? المخزن', // Field for FirstData_Connect
    'online_payment_sharedSecret'        => 'الرمز السري المشترك', // Field for FirstData_Connect
    'online_payment_appId'               => 'معر�? التطبيق', // Field for GoCardless
    'online_payment_appSecret'           => 'كلمة سر التطبيق', // Field for GoCardless
    'online_payment_accessToken'         => 'رمزالوصول المميز', // Field for GoCardless
    'online_payment_merchantAccessCode'  => 'رمز وصول التاجر', // Field for Migs_ThreeParty
    'online_payment_secureHash'          => 'تجزئة آمنة', // Field for Migs_ThreeParty
    'online_payment_siteId'              => 'معر�? الموقع', // Field for MultiSafepay
    'online_payment_siteCode'            => 'موقع التعليمات البرمجية', // Field for MultiSafepay
    'online_payment_accountNumber'       => 'رقم الحساب', // Field for NetBanx
    'online_payment_storePassword'       => 'تخزين كلمات المرور', // Field for NetBanx
    'online_payment_merchantKey'         => 'م�?تاح التاجر', // Field for PayFast
    'online_payment_pdtKey'              => 'م�?تاح Pdt', // Field for PayFast
    'online_payment_username'            => 'اسم المستخدم', // Field for Payflow_Pro
    'online_payment_vendor'              => 'المورد', // Field for Payflow_Pro
    'online_payment_partner'             => 'الشريك', // Field for Payflow_Pro
    'online_payment_pxPostUsername'      => 'اسم المستخدم ل Px Post', // Field for PaymentExpress_PxPay
    'online_payment_pxPostPassword'      => 'كلمة المرور ل Px Post', // Field for PaymentExpress_PxPay
    'online_payment_signature'           => 'التوقيع', // Field for PayPal_Express
    'online_payment_referrerId'          => 'معر�? المرجع', // Field for SagePay_Direct
    'online_payment_transactionPassword' => 'كلمة مرور المعاملة', // Field for SecurePay_DirectPost
    'online_payment_subAccountId'        => 'معر�? الحساب ال�?رعي', // Field for TargetPay_Directebanking
    'online_payment_secretWord'          => 'كلمة سر', // Field for TwoCheckout
    'online_payment_installationId'      => 'رمز التثبيت', // Field for WorldPay
    'online_payment_callbackPassword'    => 'كلمة المرور Callback', // Field for WorldPay

    // Status / Error Messages
    'online_payment_payment_cancelled'   => 'تم إلغاء الد�?ع.',
    'online_payment_payment_failed'      => '�?شل الد�?ع. الرجاء المحاولة مرة أخرى.',
    'online_payment_payment_successful'  => 'نجح الد�?ع لل�?اتورة %s!',
    'online_payment_payment_redirect'    => 'الرجاء الانتظار بينما يتم توجيهك إلى ص�?حة الد�?ع...',
    'online_payment_3dauth_redirect'     => 'الرجاء الانتظار بينما يتم إعادة توجيهك إلى المتحقق من البطاقة للمصادقة...'
);
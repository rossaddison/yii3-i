<?php
declare(strict_types=1);
$lang = array(
	// payment gateways
	'merchant_2checkout'					=> '2Checkout',
	'merchant_authorize_net'				=> 'Authorize.Net AIM',
	'merchant_authorize_net_sim'			=> 'Authorize.Net SIM',
	'merchant_buckaroo'						=> 'Buckaroo',
	'merchant_cardsave'						=> 'Cardsave',
	'merchant_dps_pxpay'					=> 'DPS PaymentExpress PxPay',
	'merchant_dps_pxpost'					=> 'DPS PaymentExpress PxPost',
	'merchant_dummy'						=> 'Dummy',
	'merchant_eway'							=> 'eWay Hosted',
	'merchant_eway_shared'					=> 'eWay Shared',
	'merchant_eway_shared_uk'				=> 'eWay Shared (UK)',
	'merchant_ideal'						=> 'iDEAL',
	'merchant_inipay'						=> 'INIpay',
	'merchant_gocardless'					=> 'GoCardless',
	'merchant_manual'						=> 'マニュアル',
	'merchant_mollie'						=> 'Mollie',
	'merchant_netaxept'						=> 'Nets Netaxept',
	'merchant_ogone_directlink'				=> 'Ogone DirectLink',
	'merchant_payflow_pro'					=> 'Payflow Pro',
	'merchant_paymate'						=> 'Paymate',
	'merchant_paypal_express'				=> 'PayPal Express',
	'merchant_paypal_pro'					=> 'PayPal Pro',
	'merchant_rabo_omnikassa'				=> 'Rabo OmniKassa',
	'merchant_sagepay_direct'				=> 'Sagepay Direct',
	'merchant_sagepay_server'				=> 'Sagepay Server',
	'merchant_stripe'						=> 'Stripe',
	'merchant_webteh_direct'				=> 'Webteh Direct',
	'merchant_worldpay'						=> 'WorldPay',

	// payment gateway settings
	'merchant_api_login_id'					=> 'API ログイン ID',
	'merchant_transaction_key'				=> '取引用キー',
	'merchant_test_mode'					=> 'テストモード',
	'merchant_developer_mode'				=> '開発者モード',
	'merchant_simulator_mode'				=> 'シミュレータ モード',
	'merchant_user_id'						=> 'ユーザー ID',
	'merchant_app_id'						=> 'アプリID',
	'merchant_psp_id'						=> 'PSP ID',
	'merchant_api_key'						=> 'APIキー',
	'merchant_key'							=> 'キー',
	'merchant_key_version'					=> '鍵バージョン',
	'merchant_username'						=> 'ユーザー名',
	'merchant_vendor'						=> '外注先',
	'merchant_partner_id'					=> '取引先コード',
	'merchant_password'						=> 'パスワード',
	'merchant_signature'					=> '署名',
	'merchant_customer_id'					=> '顧客 ID',
	'merchant_merchant_id'					=> '業者ID',
	'merchant_account_no'					=> 'アカウントなし',
	'merchant_installation_id'				=> '設備ID',
	'merchant_website_key'					=> 'ウェブサイトキー',
	'merchant_secret_word'					=> '秘密の単語',
	'merchant_secret'						=> '秘密',
	'merchant_app_secret'					=> 'アプリケーション認証',
	'merchant_secret_key'					=> '認証キー',
	'merchant_token'						=> 'Token',
	'merchant_access_token'					=> 'アクセストークン',
	'merchant_payment_response_password'	=> 'お支払応答パスワード',
	'merchant_company_name'					=> '会社名',
	'merchant_company_logo'					=> '会社のロゴ',
	'merchant_page_title'					=> 'ページのタイトル',
	'merchant_page_banner'					=> 'ページのバナー',
	'merchant_page_description'				=> 'ページの説明',
	'merchant_page_footer'					=> 'ページフッダー',
	'merchant_enable_token_billing'			=> 'トークン請求のためにカード詳細を保管します。',
	'merchant_paypal_email'					=> 'PayPal のアカウントのメール アドレス',
	'merchant_acquirer_url'					=> '取得URL',
	'merchant_public_key_path'				=> '公開鍵サーバーパス',
	'merchant_private_key_path'				=> '秘密鍵サーバーパス',
	'merchant_private_key_password'			=> '公開鍵パスワード',
	'merchant_solution_type'				=> 'PayPal アカウントが必要です。',
	'merchant_landing_page'					=> '選択した「支払」タブ',
	'merchant_solution_type_mark'			=> 'PayPal アカウントが必要です。',
	'merchant_solution_type_sole'			=> 'PayPal アカウント オプション',
	'merchant_landing_page_billing'			=> 'ゲスト閲覧 / アカウント作成',
	'merchant_landing_page_login'			=> 'PayPal アカウントにログイン',

	// payment gateway fields
	'merchant_card_type'					=> 'カード種類',
	'merchant_card_no'						=> 'カード番号',
	'merchant_name'							=> '名前',
	'merchant_first_name'					=> '名',
	'merchant_last_name'					=> '姓',
	'merchant_card_issue'					=> 'カードIssue番号',
	'merchant_exp_month'					=> '有効月',
	'merchant_exp_year'						=> '有効年',
	'merchant_start_month'					=> '開始月',
	'merchant_start_year'					=> '開始年',
	'merchant_csc'							=> 'CSC',
	'merchant_issuer'						=> '発行者',

	// status/error messages
	'merchant_insecure_connection'			=> 'カードの詳細は、もっと安全な接続を介して提出しなければなりません。',
	'merchant_required'						=> '%s 項目は必要入力です.',
	'merchant_invalid_card_no'				=> '無効なカード番号です。',
	'merchant_card_expired'					=> 'カードの有効期限が切れています。',
	'merchant_invalid_status'				=> '無効な支払状態です。',
	'merchant_invalid_method'				=> 'このゲートウェイは指定した方法をサポートしません。',
	'merchant_invalid_response'				=> '支払ゲートウェイで無効な応答を受信しました。',
	'merchant_payment_failed'				=> 'お支払いできませんでした。もう一度やり直してください。',
	'merchant_payment_redirect'				=> 'お支払いページにリダイレクトします。しばらくお待ちください。',
	'merchant_3dauth_redirect'				=> 'カード発行会社の認証ページにリダイレクトします。しばらくお待ちください。'
);

/* End of file ./language/english/merchant_lang.php */
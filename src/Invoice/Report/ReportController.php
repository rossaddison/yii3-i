<?php

declare(strict_types=1);

namespace App\Invoice\Report;

// Repositories
use App\Invoice\Client\ClientRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\Payment\PaymentRepository;
use App\Invoice\Setting\SettingRepository;

// Helpers
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\MpdfHelper;
use App\Invoice\Helpers\NumberHelper;

// Services and forms
use App\Service\WebControllerService;
use App\User\UserService;

// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Yiisoft
use Yiisoft\Http\Method;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

class ReportController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService; 
    private TranslatorInterface $translator;
        
    public function __construct(ViewRenderer $viewRenderer, 
                                WebControllerService $webService,
                                UserService $userService,     
                                TranslatorInterface $translator
            )
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/report')
                                           ->withLayout('@views/layout/invoice.php');
                                           
        $this->webService = $webService;
        $this->userService = $userService;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @return string
     */
    private function alert(SessionInterface $session) : string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash'=>$this->flash($session, '', ''),
            'errors' => [],
        ]);
    }
    
    /**
     * 
     * @param string $drop_down_locale
     * @param SettingRepository $sR
     * @return void
     */
    private function cldr(string $drop_down_locale, SettingRepository $sR) : void {
        $cldr = $sR->withKey('cldr');
        $cldr->setSetting_value($drop_down_locale);
        $sR->save($cldr);        
    }
    
    /**
     * 
     * @param SessionInterface $session
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash(SessionInterface $session, string $level, string $message): Flash{
        $flash = new Flash($session);
        $flash->set($level, $message); 
        return $flash;
    }
    
    /**
     * 
     * @param Request $request
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param ClientRepository $cR
     * @param InvAmountRepository $iaR
     * @param SettingRepository $sR
     * @return Response
     */
    public function invoice_aging_index(Request $request, 
                                        ViewRenderer $head, 
                                        SessionInterface $session,
                                        ClientRepository $cR,
                                        InvAmountRepository $iaR,
                                        SettingRepository $sR) : Response
    {
        $parameters = [
            'head'=> $head,
            'alerts' => $this->alert($session),
            'action' => ['report/invoice_aging_index'],
        ];
        if ($request->getMethod() === Method::POST) { 
            $data = [
                'results' => $this->invoice_aging_report($cR, $iaR, $sR) ?: [],
                'numberhelper' => new NumberHelper($sR),
                'due_invoices' => $this->invoice_aging_due_invoices($iaR, $sR) ?: [],
            ];
            $mpdfhelper = new MpdfHelper(); 
            // Forth parameter $password is empty because these reports are intended for management only
            // Sixth parameter $isInvoice is false because reports and not Invoices are being generated            
            // Last parameter $quote_or_invoice is false because reports are being generated which are not meant for clients
            return $mpdfhelper->pdf_create($this->viewRenderer->renderPartialAsString('/invoice/report/invoice_aging', $data), 
                   $sR->trans('invoice_aging'), true, '', $sR, false);             
        }
        return $this->viewRenderer->render('invoice_aging_index', $parameters);
    }
    
    /**
     * @param ClientRepository $cR
     * @param InvAmountRepository $iaR
     * @param SettingRepository $sR
     *
     * @return (float|null|string)[][]
     *
     * @psalm-return list{0?: array{client: string, range_1: float|null|string, range_2: float|null|string, range_3: float|null|string, total_balance: float|null|string},...}
     */
    private function invoice_aging_report($cR, $iaR, $sR) : array {
        $clienthelper = new ClientHelper($sR);
        $numberhelper = new NumberHelper($sR);
        $clients = $cR->findAllPreloaded();
        $fifteens = $iaR->AgingCount(1,15)>0 ? $iaR->Aging(1,15): null;
        $thirties = $iaR->AgingCount(16,30)>0 ? $iaR->Aging(16,30): null;
        $overthirties = $iaR->AgingCount(31,365)>0 ? $iaR->Aging(31,365): null;
        $one_to_year = $iaR->AgingCount(1,365)>0 ? $iaR->Aging(1,365): null;
        $results = [];
        $row = [
            'client'=>'',
            'range_1'=>0.00,
            'range_2'=>0.00,
            'range_3'=>0.00,
            'total_balance'=>0.00,
        ];
        foreach ($clients as $client) {
            // Client Name and Surname
            $row['client']=$clienthelper->format_client($client);
            $row['range_1']=null!== $fifteens ? $numberhelper->format_amount($this->invoice_aging_sum($fifteens, $client->getClient_id())):0.00;
            $row['range_2']=null!== $thirties ? $numberhelper->format_amount($this->invoice_aging_sum($thirties, $client->getClient_id())):0.00;
            $row['range_3']=null!== $overthirties ? $numberhelper->format_amount($this->invoice_aging_sum($overthirties, $client->getClient_id())):0.00;
            $row['total_balance']=null!==$one_to_year ? $numberhelper->format_amount($this->invoice_aging_sum($one_to_year, $client->getClient_id())):0.00;
            array_push($results,$row); 
        }        
        return $results;
    }
    
    /**
     * 
     * @param InvAmountRepository $iaR
     * @param SettingRepository $sR
     * @return array
     */
    private function invoice_aging_due_invoices(InvAmountRepository $iaR, SettingRepository $sR): array {
      
        $numberhelper = new NumberHelper($sR);
        $fifteens = $iaR->AgingCount(1,15)>0 ? $iaR->Aging(1,15): null;
        $thirties = $iaR->AgingCount(16,30)>0 ? $iaR->Aging(16,30): null;
        $overthirties = $iaR->AgingCount(31,365)>0 ? $iaR->Aging(31,365): null;
        $results = [];
        $row = [            
            'range_index' => 0,
            'invoice_number' => '',
            'invoice_balance' => 0.00
        ];
        if (null!== $fifteens) {  
            foreach ($fifteens as $fifteen) {
                if ($fifteen->getBalance() > 0) {
                    $row = [
                        'range_index' => 1,
                        'invoice_number' => $fifteen->getInv()->getNumber(),
                        'invoice_balance' => $numberhelper->format_amount($fifteen->getBalance())
                    ];
                } 
                array_push($results, $row);
            }
        }
        if (null!== $thirties) {  
            foreach ($thirties as $thirty) {
                if ($thirty->getBalance() > 0) {
                    $row = [
                        'range_index' => 2,
                        'invoice_number' => $thirty->getInv()->getNumber(),
                        'invoice_balance' => $numberhelper->format_amount($thirty->getBalance())
                    ];
                } 
                array_push($results, $row);
            }
        }
        if (null!== $overthirties) {  
            foreach ($overthirties as $overthirty) {
                if ($overthirty->getBalance() > 0) {
                    $row = [
                        'range_index' => 3,
                        'invoice_number' => $overthirty->getInv()->getNumber(),
                        'invoice_balance' => $numberhelper->format_amount($overthirty->getBalance())
                    ];
                } 
                array_push($results, $row);
            }
        }    
        return $results;
    }
    
    /**
     * @psalm-param \Yiisoft\Data\Reader\DataReaderInterface<array-key, array|object> $invamounts
     */
    private function invoice_aging_sum(\Yiisoft\Data\Reader\DataReaderInterface $invamounts, int|null $client_id) : float {
        $sum = 0.00;
        foreach ($invamounts as $invamount) {
            $sum += ($client_id == $invamount->getInv()->getClient_id()) ? $invamount->getBalance() : 0.00; 
        } 
        return $sum;
    }
    
    /**
     * 
     * @param Request $request
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param PaymentRepository $pymtR
     * @param SettingRepository $sR
     * @return Response
     */
    public function payment_history_index(Request $request, ViewRenderer $head, SessionInterface $session,                                         
                                    PaymentRepository $pymtR,
                                    SettingRepository $sR) : Response
    {        
        $datehelper = new DateHelper($sR);
        $parameters = [
            'head'=> $head,
            'alerts' => $this->alert($session),
            'action' => ['report/payment_history_index'],            
            'datehelper' => $datehelper,
            'start_tax_year' => $datehelper->tax_year_to_immutable(),
        ];
        if ($request->getMethod() === Method::POST) { 
            $body = $request->getParsedBody();
            $from_date = $body['from_date'];
            $to_date = $body['to_date'];
            $data = [
                'from_date' => $from_date,
                'to_date' => $to_date,
                //Date Invoice Client Payment Method Note Amount
                'results' => $this->payment_history_report($pymtR, $datehelper->date_to_mysql($from_date), $datehelper->date_to_mysql($to_date), $sR) 
                         ?: [],
                'datehelper'=>$datehelper,
                'numberhelper' => new NumberHelper($sR),
            ];
            $mpdfhelper = new MpdfHelper(); 
            return $mpdfhelper->pdf_create($this->viewRenderer->renderPartialAsString('/invoice/report/payment_history', $data), 
                                           $sR->trans('payment_history'), true, '', $sR, false);             
        }
        return $this->viewRenderer->render('payment_history_index', $parameters);
    }
    
    /**
     * @param PaymentRepository $pymtR 
     * @param string $from
     * @param string $to
     * @param SettingRepository $sR
     *
     * @return (mixed|string)[][]
     *
     * @psalm-return list{0?: array{payment_date: mixed, payment_invoice: mixed, payment_client: string, payment_method: mixed, payment_note: mixed, payment_amount: mixed},...}
     */
    private function payment_history_report($pymtR, $from, $to, $sR) : array {
        $clienthelper = new ClientHelper($sR);
        $payments = $pymtR->repoPaymentLoaded_from_to_count($from,$to) > 0 ? $pymtR->repoPaymentLoaded_from_to($from,$to) : [];
        //Report Headings: Date, Invoice, Client, Payment Method, Note, Amount
        $results = [];
        $row = [
            'payment_date'=>'',
            'payment_invoice'=>'',
            'payment_client'=>'',
            'payment_method'=>'',
            'payment_note'=>'',
            'payment_amount'=>''
        ];
        foreach ($payments as $payment) {
            $row['payment_date']=$payment->getPayment_date();
            $row['payment_invoice']=$payment->getInv()->getNumber();
            // Client Name and Surname
            $row['payment_client']=$clienthelper->format_client($payment->getInv()->getClient());
            $row['payment_method']=$payment->getPaymentMethod()->getName();
            $row['payment_note']=$payment->getNote();
            $row['payment_amount']=$payment->getAmount();
            array_push($results,$row); 
        }        
        return $results;
    }
    
    /**
     * 
     * @param Request $request
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param ClientRepository $cR
     * @param InvRepository $iR
     * @param InvAmountRepository $iaR
     * @param SettingRepository $sR
     * @return Response
     */
    public function sales_by_client_index(Request $request, ViewRenderer $head, SessionInterface $session,                                         
                                    ClientRepository $cR,
                                    InvRepository $iR,
                                    InvAmountRepository $iaR,
                                    SettingRepository $sR) : Response
    {
        $datehelper = new DateHelper($sR);
        $parameters = [
            'head'=> $head,
            'alerts' => $this->alert($session),
            'action' => ['report/sales_by_client_index'],
            'datehelper'=> $datehelper,
            'start_tax_year' => $datehelper->tax_year_to_immutable(),
        ];
        if ($request->getMethod() === Method::POST) { 
            $body = $request->getParsedBody();
            $from_date = $body['from_date'];
            $to_date = $body['to_date'];
            $data = [
                'from_date' => $from_date,
                'to_date' => $to_date,
                'results' => $this->sales_by_client_report($cR, $iR, $datehelper->date_to_mysql($from_date), $datehelper->date_to_mysql($to_date), $iaR, $sR),
                'numberhelper' => new NumberHelper($sR),
                'clienthelper' => new ClientHelper($sR),
            ];
            $mpdfhelper = new MpdfHelper(); 
            return $mpdfhelper->pdf_create(
                     $this->viewRenderer->renderPartialAsString('/invoice/report/sales_by_client', $data), 
                     $sR->trans('sales_by_client'), true, '', $sR, false
            );             
        }
        return $this->viewRenderer->render('sales_by_client_index', $parameters);
    }
    
    /**
     * @param ClientRepository $cR
     * @param InvRepository $iR 
     * @param string $from
     * @param string $to
     * @param InvAmountRepository $iaR
     * @param SettingRepository $sR
     *
     * @return (float|int|string)[][]
     *
     * @psalm-return list{array{client_name_surname: string, inv_count: int, sales_no_tax: float, item_tax_total: float, tax_total: float, sales_with_tax: float},...}
     */
    private function sales_by_client_report($cR, $iR, $from, $to, $iaR, $sR) : array {
        // Report Heading:  Sales by Client
        // Report Heading2: From To Date
        // Horizontal heading: Client Name and Surname, Inv Count, Sales Total, Item Tax, Tax, Sales With Tax
        $results = [];
        $row = [
            'client_name_surname' => '',
            'inv_count'=> 0.00,            
            'sales_no_tax'=> 0.00,
            // plus (before/after item tax)
            'item_tax_total' => 0.00,
            // plus
            'tax_total' => 0.00,
            // equals
            'sales_with_tax'=> 0.00            
        ];
        $clienthelper = new ClientHelper($sR);
        $clients = $cR->count() > 0 ? $cR->findAllPreloaded() : null;
        foreach ($clients as $client) {
                // Client Name and Surname
                $row['client_name_surname'] = $clienthelper->format_client($client);
                $row['inv_count'] = $iR->repoCountByClient($client->getClient_id());
                $row['sales_no_tax'] = $iR->repoCountByClient($client->getClient_id()) > 0 
                              ? $iR->with_item_subtotal_from_to($client->getClient_id(), $from, $to, $iaR) 
                              : 0.00;
                // plus
                $row['item_tax_total'] = $iR->repoCountByClient($client->getClient_id()) > 0 
                              ? $iR->with_item_tax_total_from_to($client->getClient_id(), $from, $to, $iaR) 
                              : 0.00;
                // plus
                $row['tax_total'] = $iR->repoCountByClient($client->getClient_id()) > 0 
                              ? $iR->with_tax_total_from_to($client->getClient_id(), $from, $to, $iaR) 
                              : 0.00;
                // equals
                $row['sales_with_tax'] = $iR->repoCountByClient($client->getClient_id()) > 0 
                              ? $iR->with_total_from_to($client->getClient_id(), $from, $to, $iaR) 
                              : 0.00;                
                array_push($results,$row); 
        }        
        return $results;
    }
    
    /**
     * 
     * @param Request $request
     * @param ViewRenderer $head
     * @param SessionInterface $session
     * @param ClientRepository $cR
     * @param InvRepository $iR
     * @param InvAmountRepository $iaR
     * @param SettingRepository $sR
     * @return Response
     */
    public function sales_by_year_index(Request $request, ViewRenderer $head, SessionInterface $session,                                         
                                    ClientRepository $cR,
                                    InvRepository $iR,
                                    InvAmountRepository $iaR,
                                    SettingRepository $sR) : Response
    {       
        $datehelper = new DateHelper($sR);
        $parameters = [
            'head'=> $head,
            'alerts' => $this->alert($session),
            'action' => ['report/sales_by_year_index'],            
            'datehelper' => $datehelper,
            'start_tax_year' => $datehelper->tax_year_to_immutable(),
        ];
        if ($request->getMethod() === Method::POST) { 
            $body = $request->getParsedBody();
            $from_date = $body['from_date'];
            $to_date = $body['to_date'];
            $data = [
                'from_date' => $from_date,
                'to_date' => $to_date,
                'results' => $this->sales_by_year_report($cR, $iR, $datehelper->date_to_mysql($from_date), $datehelper->date_to_mysql($to_date), $iaR, $sR) 
                         ?: [],
                'n' => new NumberHelper($sR),
                'clienthelper' => new ClientHelper($sR),
            ];
            $mpdfhelper = new MpdfHelper(); 
            // Forth parameter $password is empty because these reports are intended for management only
            // Sixth parameter $isInvoice is false because reports and not Invoices are being generated            
            // Last parameter $quote_or_invoice is false because reports are being generated which are not meant for clients
            return $mpdfhelper->pdf_create(
                                           $this->viewRenderer->renderPartialAsString('/invoice/report/sales_by_year', $data),
                                           $sR->trans('sales_by_date'), true, '', $sR, false
            );
        }
        return $this->viewRenderer->render('sales_by_year_index', $parameters);
    }
    
    /**
     * @param ClientRepository $cR
     * @param InvRepository $iR 
     * @param string $from
     * @param string $to
     * @param InvAmountRepository $iaR
     * @param SettingRepository $sR
     *
     * @return array[]
     *
     * @psalm-return list{0?: array,...}
     */
    private function sales_by_year_report($cR, $iR, $from, $to, $iaR, $sR) : array {
        $results = [];
        $year = [
            'year' => '',
            'Name' => '',
            'VAT_ID' => '',
            'period_sales_no_tax'=> 0.00,
            // plus (before/after item tax)
            'period_item_tax_total' => 0.00,
            // plus
            'period_tax_total' => 0.00,
            // equals
            'period_sales_with_tax'=> 0.00,
            // total of customer payments
            'period_total_paid'=> 0.00,
            'quarters' => [
                'first' => [
                    'beginning' => '',
                    'end' => '',
                    'sales_no_tax' => 0.00,
                    'item_tax_total' => 0.00,
                    'tax_total' => 0.00,
                    'sales_with_tax' => 0.00,
                    'paid' => 0.00
                ],
                'second' => [                    
                    'beginning' => '',
                    'end' => '',
                    'sales_no_tax' => 0.00,
                    'item_tax_total' => 0.00,
                    'tax_total' => 0.00,
                    'sales_with_tax' => 0.00,                    
                    'paid' => 0.00
                ],
                'third'=>[                    
                    'beginning' => '',
                    'end' => '',
                    'sales_no_tax' => 0.00,
                    'item_tax_total' => 0.00,
                    'tax_total' => 0.00,
                    'sales_with_tax' => 0.00,                    
                    'paid' => 0.00
                ],
                'fourth'=>[                    
                    'beginning' => '',
                    'end' => '',
                    'sales_no_tax' => 0.00,
                    'item_tax_total' => 0.00,
                    'tax_total' => 0.00,
                    'sales_with_tax' => 0.00,                    
                    'paid' => 0.00
                ]
            ],
        ];
        
        $clienthelper = new ClientHelper($sR);
        $datehelper = new DateHelper($sR);
        $clients = $cR->count() > 0 ? $cR->findAllPreloaded() : null;
        foreach ($clients as $client) {
                                              
                // Convert the mysql $from which is a string into an immutable so that we can use the add function 
                // associated with immutable dates
                
                $immutable_from = $datehelper->ymd_to_immutable($from);
                $immutable_to = $datehelper->ymd_to_immutable($to);
                
                $interval = new \DateInterval('P1Y');                
                
                $daterange = new \DatePeriod($immutable_from, $interval, $immutable_to);
                $client_id = (int)$client->getClient_id();
                foreach($daterange as $current_year){
                    
                    $additional_year = $this->quarters($year,  $immutable_from, $current_year, $client,  $clienthelper, $client_id, $iR, $iaR);   
                    
                    array_push($results, $additional_year);
                    $immutable_from = $immutable_from->add(new \DateInterval('P1Y'));
                }    
        }        
        return $results;
    }
    
    /**
     * @param ((float|string)[][]|float|string)[] $year
     *
     * @psalm-param array{year: '', Name: '', VAT_ID: '', period_sales_no_tax: float, period_item_tax_total: float, period_tax_total: float, period_sales_with_tax: float, period_total_paid: float, quarters: array{first: array{beginning: '', end: '', sales_no_tax: float, item_tax_total: float, tax_total: float, sales_with_tax: float, paid: float}, second: array{beginning: '', end: '', sales_no_tax: float, item_tax_total: float, tax_total: float, sales_with_tax: float, paid: float}, third: array{beginning: '', end: '', sales_no_tax: float, item_tax_total: float, tax_total: float, sales_with_tax: float, paid: float}, fourth: array{beginning: '', end: '', sales_no_tax: float, item_tax_total: float, tax_total: float, sales_with_tax: float, paid: float}}} $year
     * @psalm-param InvRepository<object> $iR
     * @psalm-param InvAmountRepository<object> $iaR
     */
    private function quarters(array $year, \DateTimeImmutable $immutable_from, \DateTimeImmutable $current_year, $client, 
                              ClientHelper $clienthelper, int $client_id, InvRepository $iR, InvAmountRepository $iaR) : array 
    {
        $quarters = ['first' => 3, 'second' => 6, 'third' => 9, 'fourth' => 12];
        // Develop all the quarters from ONE immutable (unchangeable) start date
        // Each immutable date is presented in the mysql Y-m-d format for comparison with the mysql dates
        $immutable_from_start_date = $immutable_from;
        
        foreach ($quarters as $quarter => $month_ending) {
            $quarter_from = $immutable_from_start_date->add(new \DateInterval('P'.(string)$month_ending.'M'))
                                                      ->sub(new \DateInterval('P3M'))
                                                      ->add(new \DateInterval('P1D'))
                                                      ->format('Y-m-d');
            
            $quarter_to  =  $immutable_from_start_date->add(new \DateInterval('P'.(string)$month_ending.'M'))
                                                      ->format('Y-m-d');

            $year['quarters'][$quarter]['beginning'] = $quarter_from;
            $year['quarters'][$quarter]['end'] = $quarter_to;
            
            $sales_no_tax = $iR->repoCountByClient($client_id) > 0 
                          ? $iR->with_item_subtotal_from_to($client_id, 
                                $quarter_from, 
                                $quarter_to, $iaR) 
                          : 0.00;    
            $year['quarters'][$quarter]['sales_no_tax'] = $sales_no_tax;
            
            $item_tax_total = $iR->repoCountByClient($client_id) > 0 
                            ? $iR->with_item_tax_total_from_to($client_id, 
                                $quarter_from, 
                                $quarter_to, $iaR) 
                            : 0.00;
            $year['quarters'][$quarter]['item_tax_total'] = $item_tax_total;
            
            $tax_total = $iR->repoCountByClient($client_id) > 0 
                          ? $iR->with_tax_total_from_to($client_id, 
                                $quarter_from, 
                                $quarter_to, $iaR) 
                          : 0.00;
            $year['quarters'][$quarter]['tax_total'] = $tax_total;
            
            $sales_with_tax = $iR->repoCountByClient($client_id) > 0 
                          ? $iR->with_total_from_to($client_id, 
                                $quarter_from, 
                                $quarter_to, $iaR) 
                          : 0.00;           
            $year['quarters'][$quarter]['sales_with_tax'] = $sales_with_tax;
            
            $paid = $iR->repoCountByClient($client_id) > 0 
                          ? $iR->with_paid_from_to($client_id, 
                                $quarter_from, 
                                $quarter_to, $iaR) 
                          : 0.00;           
            $year['quarters'][$quarter]['paid'] = $paid;
        }
        $from = $year['quarters']['first']['beginning'];
        $to = $year['quarters']['fourth']['end'];
        $year['year'] = $current_year->format('Y');
        // Client Name and Surname
        $year['Name'] = $clienthelper->format_client($client);
        // Item subtotal = Sales without taxes
        $year['VAT_ID'] = $client->getClient_vat_id();
        $year['period_sales_no_tax'] = $iR->repoCountByClient($client->getClient_id()) > 0 
                      ? $iR->with_item_subtotal_from_to($client->getClient_id(), $from, $to, $iaR) 
                      : 0.00;
        // plus
        $year['period_item_tax_total'] = $iR->repoCountByClient($client->getClient_id()) > 0 
                      ? $iR->with_item_tax_total_from_to($client->getClient_id(), $from, $to, $iaR) 
                      : 0.00;
        // plus
        $year['period_tax_total'] = $iR->repoCountByClient($client->getClient_id()) > 0 
                      ? $iR->with_tax_total_from_to($client->getClient_id(), $from, $to, $iaR) 
                      : 0.00;
        // equals
        $year['period_sales_with_tax'] = $iR->repoCountByClient($client->getClient_id()) > 0 
                      ? $iR->with_total_from_to($client->getClient_id(), $from, $to, $iaR) 
                      : 0.00;
        // what the customer has actually paid towards the annual sales with tax
        $year['period_total_paid'] = $iR->repoCountByClient($client->getClient_id()) > 0 
                      ? $iR->with_paid_from_to($client->getClient_id(), $from, $to, $iaR) 
                      : 0.00;        
        return $year;
    }
}
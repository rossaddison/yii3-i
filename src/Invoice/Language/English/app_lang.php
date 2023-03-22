<?php
// Do not delete this file. It is the template for app.php
declare(strict_types=1);
$lang = [
    'invoice.add'=>'Add',    
    'invoice.archive'=>'Invoice Archive',
    'invoice.client.add'=>'Client Add',
    'invoice.client.custom.add'=>'Client Custom Add',
    'invoice.client.note.add'=>'Client Note Add',    
    'invoice.client'=>'Client',
    'invoice.client.custom'=>'Client Custom',
    'invoice.client.note'=>'Client Note',
    'invoice.clients'=>'Clients',
    'invoice.company.public'=>'Company Public',   
    'invoice.company.private'=>'Company Private',
    'invoice.company.private.logo'=>'Company Logo',
    'invoice.create'=>'Create',
    'invoice.custom.invoice.add'=>'Custom Invoice Add',    
    'invoice.custom.field'=>'Custom Field',
    'invoice.custom.field.add'=>'Custom Field Add',     
    'invoice.custom.field.number'=>'Number',
    'invoice.custom.value'=>'Custom Value',    
    'invoice.custom.value.new'=>'Custom Value New',    
    'invoice.custom.value.delete'=>'Delete Custom Value First',
    'invoice.debug'=>'Debug Mode On',
    'invoice.default'=>'Default',
    'invoice.deleted'=>'Deleted',    
    'invoice.development.progress'=>'Development Progress',
    'invoice.development.schema'=>'Schema',
    'invoice.draft.guest'=>'Draft invoices are not viewable by Clients.',
    'invoice.email.template.add'=>'Email Template Add',
    'invoice.enter'=>'Enter',
    'invoice.email.template'=>'Email Template',
    'invoice.family'=>'Family',
    'invoice.family.add'=>'Family Add',
    'invoice.first.reset'=> 'First delete the test quotes and invoices that you created for testing. Then the test data can be deleted.',      
    'invoice.generator'=> 'Generator',
    'invoice.generator.add'=>'Generator Add',
    'invoice.generators.relation'=> 'Generators Relation',    
    'invoice.generator.relations.add'=>'Generators Relation Add',    
    'invoice.generator.google.translate.ip' => 'Translate English\ip_lang.php',
    'invoice.generator.google.translate.gateway' => 'Translate English\gateway_lang.php',
    'invoice.generator.google.translate.app' => 'Translate messages\en\app.php',
    'invoice.group'=>'Group',
    'invoice.group.add'=>'Group Add',    
    'invoice.home' => 'Home',    
    'invoice.install.test.data' => 'Settings...General...Install Test Data',
    'invoice.invalid.amount'=> 'Invalid Amount',
    'invoice.invalid.subscriber.number'=> 'Invalid Subscriber Number',    
    'invoice.invoice' => 'Invoice',
    'invoice.invoice.custom'=>'Invoice Custom',    
    'invoice.invoice.html.sumex.yes' => 'Html with Sumex',
    'invoice.invoice.html.sumex.no' => 'Html without Sumex',
    'invoice.invoice.item'=>'Invoice Item',
    'invoice.invoice.amount'=>'Invoice Item Amount',
    'invoice.invoice.tax.rate'=>'Invoice Tax Rate',
    'invoice.invoice.recurring'=>'Invoice Recurring',
    'invoice.invoice.item.lookup'=>'Item Lookup',
    'invoice.invoice.add'=>'Invoice Add',
    'invoice.invoice.item.add'=>'Invoice Item Add',
    'invoice.invoice.amount.add'=>'Invoice Item Amount Add',
    'invoice.invoice.tax.rate.add'=>'Invoice Tax Rate',
    'invoice.invoice.pdf.archived.no'=>'Pdf NOT Archived at Uploads/Archive/Invoice',
    'invoice.invoice.pdf.archived.yes'=>'Pdf Archived at Uploads/Archive/Invoice',
    'invoice.item.lookup'=>'Invoice Item Lookup',
    'invoice.invoice.recurring.add'=>'Recurring Add',
    'invoice.merchant'=>'Merchant',
    'invoice.merchant.add'=>'Merchant Add',
    'invoice.online.log'=>'Online Log',    
    'invoice.orm' => 'Orm',
    'invoice.payment.method.add'=>'Payment Method Add',
    'invoice.payment.custom.add'=>'Payment Custom Add',
    'invoice.payment.add'=>'Payment Add',        
    'invoice.payment'=>'Payment',
    'invoice.payment.method'=>'Payment Method',
    'invoice.payment.custom'=>'Payment Custom',
    'invoice.payments'=>'Payments',
    'invoice.performance'=>'Performance',    
    'invoice.permission'=>'You do not have the required permission.',
    'invoice.permission.unauthorised'=>'Ask your administator to create a User Account so that you can view Quotes and Invoices. '.
                                       'Your administrator will have to assign the observor role to you as well. '.
                                       'Your adminstrator will have to make sure your account is Actve as well.' ,
    'invoice.permission.authorised.view'=>'You have been authorised to view quotes and invoices. '.
                                       'If you cannot see them it is likely that your Administrator has NOT made your user account Active or marked the invoice as Sent.',
    'invoice.permission.authorised.edit'=>'You have been given Administator permissions to create, edit, and update quotes and invoices.',
    'invoice.platform'=>'Platform',
    'invoice.platform.editor'=>'Editor',
    'invoice.platform.netbeans.UTF-8'=>'Netbeans UTF-8 encoding', 
    'invoice.platform.server'=>'Server',
    'invoice.platform.sqlPath'=>'Sql Path',
    'invoice.platform.mySqlVersion'=>'mySql Version',
    'invoice.platform.PhpVersion'=>'Php Version',
    'invoice.platform.PhpSupport'=>'Php Support',
    'invoice.platform.update'=> 'WampServer Files and Addons',
    'invoice.platform.PhpMyAdmin'=>'PhpMyAdmin Version',
    'invoice.platform.windowsVersion'=>'Windows 10 Home Edition',
    'invoice.platform.xdebug'=>'Xdebug Extension',
    'invoice.platform.csrf'=>'Cross Site Forgery Protection',
    'invoice.product'=>'Product',    
    'invoice.product.add'=>'Product Add',    
    'invoice.products'=>'Products',
    'invoice.project' => 'Project',
    'invoice.project.add'=>'Project Add',
    'invoice.quote'=>'Quote',
    'invoice.quote.item'=>'Quote Item',
    'invoice.quote.custom'=>'Quote Custom',
    'invoice.quote.custom.add'=>'Quote Custom Add',
    'invoice.quote.amount'=>'Quote Amount',
    'invoice.quote.item.amount'=>'Quote Item Amount',
    'invoice.quote.tax.rate'=>'Quote Tax Rate',
    'invoice.quote.add'=>'Quote Add',
    'invoice.quote.item.add'=>'Quote Item Add',
    'invoice.quote.item.amount.add'=>'Quote Item Amount Add',
    'invoice.quote.amount.add'=>'Quote Amount Add',
    'invoice.quote.tax.rate.add'=>'Quote Tax Rate Add',
    'invoice.quotes' =>'Quotes',    
    'invoice.recurring'=>'Recurring',
    'invoice.report'=>'Report',
    'invoice.setting'=>'Setting',
    'invoice.setting.company'=>'Company Public Details',
    'invoice.setting.company.private'=>'Company Private Details',
    'invoice.setting.company.profile'=>'Changing Profile eg. mobile and email address',
    'invoice.setting.translator.key'=>'Translator Key',
    'invoice.setting.section'=>'Section',
    'invoice.setting.subsection'=>'Subsection',    
    'invoice.sumex'=>'Sumex',
    'invoice.setting.add'=>'Setting Add',
    'invoice.sumex.add'=>'Sumex Add',
    'invoice.sumex.edit'=>'Sumex Edit',
    'invoice.task'=>'Task',
    'invoice.task.add'=>'Task Add',
    'invoice.tax.rate'=>'Tax Rate',
    'invoice.tax.rate.add'=>'Tax Rate Add',     
    'invoice.test.data.install'=>'Install Test Data',
    'invoice.test.data.use'=>'Use Test Data',
    'invoice.test.remove'=>'Remove Test Data',       
    'invoice.test.remove.tooltip'=>'View..Settings..General..Install Test Data..No and View..Settings..General..Use Test Data..No',
    'invoice.test.reset'=>'Reset Test Data',
    'invoice.test.reset.tooltip'=>'View..Settings..General..Install Test Data..Yes and View..Settings..General..Use Test Data..Yes',
    'invoice.test.reset.setting'=>'Settings Reinstall',
    'invoice.test.reset.setting.tooltip'=>'This will remove all current settings and reinstall the default settings in InvoiceController/install_default_settings_on_first_run',
    'invoice.time.zone'=>'Time Zone',
    'invoice.unit'=>'Unit',
    'invoice.unit.add'=>'Unit Add',
    'invoice.user.account'=>'User Account',
    'invoice.utility.assets.clear'=>'Clear Assets Cache',
    'invoice.vendor.nikic.fast-route'=>'Building Faster Routes',
    'invoice.view'=>'View',
    'gridview.api' => 'API',
    'gridview.create.at' => 'Create at',
    'gridview.login' => 'Login',
    'gridview.profile' => 'Profile',
    'gridview.title' => 'List of users',
    'home.caption.slide1' => '<h5>Step 1:</h5><p>Signup and Login</p>',
    'home.caption.slide2' => '<h5>Step 2:</h5><p>Open the Windows Command Prompt and goto the yii3-i directory.</p>',
    'home.caption.slide3' => '<h5>Step 3:</h5><p>At the command prompt: c:\wamp64\www\yii3-i>yii user/assignRole admin 1  (This will assign the admin role to user with user_id 1)</p>',
    'home.caption.slide4' => '<h5>step 4:</h5><p>Signup a client</p>',
    'home.caption.slide5' => '<h5>step 5:</h5><p>At the command prompt: c:\wamp64\www\yii3-i>yii user/assignRole observer 2  (This will assign the observer role to the paying client with user_id 2)</p>',    
    'home.caption.slide6' => '<h5>step 6:</h5><p>Login with admin details and create invoices.</p>',    
    'layout.add.post' => 'Add post',
    'layout.add.random-content' => 'Add random content',
    'layout.add.tag' => 'Add tag',
    'layout.add' => 'Add',
    'layout.archive.for-year' => 'Archive for {year}',
    'layout.archive' => 'Archive',
    'layout.blog' => 'Blog',
    'layout.change-language' => 'Change language',
    'layout.console' => 'Console',
    'layout.content' => 'Content',
    'layout.create.new-user' => 'Create new user',
    'layout.db.schema' => 'DB Schema',
    'layout.go.home' => 'Go Back Home',
    'layout.login' => 'Login',
    'layout.migrations' => 'Migrations',
    'layout.no-records' => 'No records',
    'layout.not-found' => 'Not found',
    'layout.page.not-found' => 'The page {url} could not be found.',
    'layout.pagination-summary' => 'Showing {pageSize} out of {total} posts',
    'layout.password-verify' => 'Confirm password',
    'layout.password' => 'Password',
    'layout.rbac.assign-role' => 'Assign RBAC role to user',
    'layout.remember' => 'Remember me',
    'layout.reset' => 'Reset',
    'layout.show-more' => 'show more',
    'layout.submit' => 'Submit',
    'layout.title' => 'Title',
    'layout.total.posts' => 'Total {count} posts',
    'menu.blog' => 'Blog',
    'menu.comments-feed' => 'Comments Feed',
    'menu.contact' => 'Contact',
    'menu.language' => 'Language',
    'menu.login' => 'Login',
    'menu.logout' => 'Logout ({login})',
    'menu.signup' => 'Signup',
    'menu.swagger' => 'Swagger',
    'menu.users' => 'Users',
    'signup' => 'Signup',
    'validator.invalid.login.password' => 'Invalid login or password',
    'validator.password.not.match' => 'Passwords do not match',
    'validator.user.exist' => 'User with this login already exists',
];

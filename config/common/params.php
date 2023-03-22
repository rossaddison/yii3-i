<?php

declare(strict_types=1);

use App\ViewInjection\CommonViewInjection;
use App\ViewInjection\LayoutViewInjection;
use App\ViewInjection\LinkTagsViewInjection;
use App\ViewInjection\MetaTagsViewInjection;
use Cycle\Database\Config\SQLite\FileConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Form\Field\SubmitButton;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\Cycle\Schema\Conveyor\AttributedSchemaConveyor;
use Yiisoft\Yii\Cycle\Schema\Provider\FromConveyorSchemaProvider;
use Yiisoft\Yii\Cycle\Schema\Provider\PhpFileSchemaProvider;
use Yiisoft\Yii\View\CsrfViewInjection;

// yii3-i
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Session\SessionInterface;

return [
    'mailer' => [
        'adminEmail' => 'admin@example.com',
        'senderEmail' => 'sender@example.com',
    ],

    'yiisoft/aliases' => [
        'aliases' => [
            '@root' => dirname(__DIR__, 2),
            '@assets' => '@root/public/assets',
            '@assetsUrl' => '@baseUrl/assets',
            '@baseUrl' => $_ENV['BASE_URL'],
            '@messages' => '@resources/messages',
            '@npm' => '@root/node_modules',
            '@public' => '@root/public',
            '@resources' => '@root/resources',
            '@runtime' => '@root/runtime',
            '@src' => '@root/src',
            '@vendor' => '@root/vendor',
            '@layout' => '@views/layout',
            '@views' => '@resources/views',
        ],
    ],

    'yiisoft/form' => [
        'configs' => [
            'default' => [
                'containerClass' => 'form-floating mb-3',
                'inputClass' => 'form-control',
                'invalidClass' => 'is-invalid',
                'validClass' => 'is-valid',
                'template' => '{input}{label}{hint}{error}',
                'labelClass' => 'floatingInput',
                'errorClass' => 'fw-bold fst-italic',
                'hintClass' => 'form-text',
                'fieldConfigs' => [
                    SubmitButton::class => [
                        'buttonClass()' => ['btn btn-primary btn-lg mt-3'],
                        'containerClass()' => ['d-grid gap-2 form-floating'],
                    ],
                ],
            ],
        ],
    ],

    'yiisoft/rbac-rules-container' => [
        'rules' => require __DIR__ . '/rbac-rules.php',
    ],

    'yiisoft/router-fastroute' => [
        'enableCache' => false,
    ],

    'yiisoft/translator' => [
        'locale' => 'en',
        'fallbackLocale' => 'en',
        'defaultCategory' => 'app',
        'categorySources' => [
            Reference::to('translation.app'),
        ],
    ],

    'yiisoft/view' => [
        'basePath' => '@views',
        'parameters' => [
            'assetManager' => Reference::to(AssetManager::class),
            'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
            'currentRoute' => Reference::to(CurrentRoute::class),
            'translator' => Reference::to(TranslatorInterface::class),
            // yii-invoice - Below parameters are specifically used in views/layout/invoice
            's' => Reference::to(SettingRepository::class),
            'session' => Reference::to(SessionInterface::class),
            'datehelper' => Reference::to(DateHelper::class),
        ],
    ],

    'yiisoft/cookies' => [
        'secretKey' => '53136271c432a1af377c3806c3112ddf',
    ],

    'yiisoft/yii-view' => [
        'viewPath' => '@views',
        'layout' => '@views/layout/main',
        'injections' => [
            Reference::to(CommonViewInjection::class),
            Reference::to(CsrfViewInjection::class),
            Reference::to(LayoutViewInjection::class),
            Reference::to(LinkTagsViewInjection::class),
            Reference::to(MetaTagsViewInjection::class),
            Reference::to(SettingRepository::class)
        ],
    ],

    'yiisoft/yii-cycle' => [
        // DBAL config
        'dbal' => [
            // SQL query logger. Definition of Psr\Log\LoggerInterface
            // For example, \Yiisoft\Yii\Cycle\Logger\StdoutQueryLogger::class
            'query-logger' => null,
            // Default database
            'default' => 'default',
            'aliases' => [],
            'databases' => [
                //'default' => ['connection' => 'sqlite'],
                
                // yii-invoice
                'default' => ['connection' => 'mysql'],
            ],
            'connections' => [
                // 'sqlite' => new SQLiteDriverConfig(
                //     connection: new FileConnectionConfig(
                //        database: 'runtime/database.db'
                //    )
                //),
                
                // yii-invoice
                'mysql'=> new \Cycle\Database\Config\MySQLDriverConfig(
                    connection: 
                          new \Cycle\Database\Config\MySQL\DsnConnectionConfig('mysql:host=localhost;dbname=yii3-i', 
                                                                               'root', 
                                                                               ''),
                          driver: \Cycle\Database\Driver\MySQL\MySQLDriver::class,
                ), 
            ],
        ],

        // Cycle migration config
        'migrations' => [
            'directory' => '@root/migrations',
            'namespace' => 'App\\Migration',
            'table' => 'migration',
            'safe' => false,
        ],

        /**
         * SchemaProvider list for {@see \Yiisoft\Yii\Cycle\Schema\Provider\Support\SchemaProviderPipeline}
         * Array of classname and {@see SchemaProviderInterface} object.
         * You can configure providers if you pass classname as key and parameters as array:
         * [
         *     SimpleCacheSchemaProvider::class => [
         *         'key' => 'my-custom-cache-key'
         *     ],
         *     FromFilesSchemaProvider::class => [
         *         'files' => ['@runtime/cycle-schema.php']
         *     ],
         *     FromConveyorSchemaProvider::class => [
         *         'generators' => [
         *              Generator\SyncTables::class, // sync table changes to database
         *          ]
         *     ],
         * ]
         */
        'schema-providers' => [
            // Uncomment next line to enable a Schema caching in the common cache
            // \Yiisoft\Yii\Cycle\Schema\Provider\SimpleCacheSchemaProvider::class => ['key' => 'cycle-orm-cache-key'],

            // Store generated Schema in the file
            PhpFileSchemaProvider::class => [
                'mode' => PhpFileSchemaProvider::MODE_WRITE_ONLY,
                // yii-invoice
            //    'mode' => PhpFileSchemaProvider::MODE_READ_AND_WRITE,
                'file' => 'runtime/schema.php',
            ],

            //PhpFileSchemaProvider::class => [
            //    'mode' => PhpFileSchemaProvider::MODE_WRITE_ONLY,
            //    'file' => 'runtime/schema.php',
            //],
            FromConveyorSchemaProvider::class => [
                'generators' => [
                    Cycle\Schema\Generator\SyncTables::class, // sync table changes to database
                ],
            ],
        ],

        /**
         * Config for {@see \Yiisoft\Yii\Cycle\Schema\Conveyor\AnnotatedSchemaConveyor}
         * Annotated entity directories list.
         * {@see \Yiisoft\Aliases\Aliases} are also supported.
         */
        'entity-paths' => [
            '@src',
        ],
        'conveyor' => AttributedSchemaConveyor::class,
    ],
    'yiisoft/yii-swagger' => [
        'annotation-paths' => [
            '@src/Controller',
            '@src/User/Controller',
        ],
    ],
    'yiisoft/yii-sentry' => [
        'handleConsoleErrors' => false, // Add to disable console errors.
        'options' => [
            // Set to `null` to disable error sending (note that in case of web application errors it only prevents
            // sending them via HTTP). To disable interactions with Sentry SDK completely, remove middleware and the
            // rest of the config.
            'dsn' => $_ENV['SENTRY_DSN'] ?? null,
            'environment' => $_ENV['YII_ENV'] ?? null, // Add to separate "production" / "staging" environment errors.
        ],
    ],
    
    'yiisoft/mailer' => [
        'messageBodyTemplate' => [
            'viewPath' => '@src/Contact/mail',
        ],
        'fileMailer' => [
            'fileMailerStorage' => '@runtime/mail',
        ],
        'useSendmail' => false,
        'writeToFiles' => false,
    ],
    
    'symfony/mailer' => [
        'esmtpTransport' => [
            'scheme' => 'smtp', // "smtps": using TLS, "smtp": without using TLS.
            'host' => 'mail.yourinternet.com',
            'port' => 25,
            'username' => 'your.name@yourinternet.com',
            'password' => 'yourpassword',
            'options' => [], // See: https://symfony.com/doc/current/mailer.html#tls-peer-verification
        ],
    ], 
    
    // These parameters appear on ZugFerdXml produced invoice
    // and also Sumex1 semi-compatible invoice and is used in App/Invoice/Libraries/Sumex class 
    // see settingRepository->get_config_company_details() function    
    'company' => [
        'name' => 'MyCompanyName',
        'address_1' => '1 MyCompany Street',
        'address_2' => 'MyCompany Area',
        'city' => 'MyCompanyCity',
        'country' => 'MyCompanyCountry',        
        'zip' => 'A11 1AA',
        'state' => 'My State',
        'vat_id' => 'GB123456789',
        'tax_code' => 'Tax Code',
        'phone' => '02000000000',
        'fax' => '0200000000',
    ]
];

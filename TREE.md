├── CONTINUITY_MAP.md
├── NOTES.MD
├── PROJECT_CONTEXT.md
├── README.md
├── TREE.md
├── app
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── Api
│   │   │   └── Controller.php
│   │   └── Middleware
│   │       └── EnsureWarehouseAccess.php
│   ├── Models
│   │   ├── Business.php
│   │   ├── BusinessLocation.php
│   │   ├── Module.php
│   │   ├── OfflineFulfillmentPending.php
│   │   ├── Permission.php
│   │   ├── Product.php
│   │   ├── Purchase.php
│   │   ├── Role.php
│   │   ├── Sale.php
│   │   ├── SaleItem.php
│   │   ├── StockMovement.php
│   │   ├── SupervisorOverride.php
│   │   ├── User.php
│   │   ├── Warehouse.php
│   │   ├── WarehouseFulfillment.php
│   │   ├── WarehouseStock.php
│   │   └── policyfiles
│   │       └── Supervisor_Override_Semantics.md
│   ├── Providers
│   │   └── AppServiceProvider.php
│   └── Services
│       ├── AuditService.php
│       ├── FulfillmentService.php
│       ├── FulfillmentStateMachine.php
│       ├── FulfillmentTokenService.php
│       ├── OfflineFulfillmentStateMachine.php
│       ├── OfflineQrSigner.php
│       ├── OfflineQrVerifier.php
│       ├── OfflineReconciliationService.php
│       ├── PurchaseReceiptService.php
│       ├── PurchaseService.php
│       ├── SaleService.php
│       ├── StockService.php
│       ├── SupervisorOverrideAuthService.php
│       ├── SupervisorOverrideService.php
│       └── TransferService.php
├── artisan
├── bootstrap
│   ├── app.php
│   ├── cache
│   │   ├── packages.php
│   │   └── services.php
│   └── providers.php
├── composer.json
├── composer.lock
├── config
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── offline_qr.php
│   ├── permission.php
│   ├── queue.php
│   ├── sanctum.php
│   ├── services.php
│   └── session.php
├── database
│   ├── database.sqlite
│   ├── factories
│   │   ├── BusinessFactory.php
│   │   ├── BusinessLocationFactory.php
│   │   ├── OfflineFulfillmentPendingFactory.php
│   │   ├── ProductFactory.php
│   │   ├── SaleFactory.php
│   │   ├── SupervisorOverrideFactory.php
│   │   ├── UserFactory.php
│   │   └── WarehouseFactory.php
│   ├── migrations
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_01_04_000433_create_modules_table.php
│   │   ├── 2026_01_04_001602_create_business_modules_table.php
│   │   ├── 2026_01_04_002741_create_permission_tables.php
│   │   ├── 2026_01_04_003259_add_module_id_to_permissions_table.php
│   │   ├── 2026_01_04_004535_create_businesses_table.php
│   │   ├── 2026_01_04_004829_add_business_id_to_users_table.php
│   │   ├── 2026_01_04_005424_create_business_locations_table.php
│   │   ├── 2026_01_04_005630_create_warehouses_table.php
│   │   ├── 2026_01_04_005859_create_business_location_warehouse_table.php
│   │   ├── 2026_01_04_011844_create_products_table.php
│   │   ├── 2026_01_04_012113_create_warehouse_stock_table.php
│   │   ├── 2026_01_04_012424_create_stock_movements_table.php
│   │   ├── 2026_01_04_012803_create_sales_table.php
│   │   ├── 2026_01_04_013804_create_sale_items_table.php
│   │   ├── 2026_01_04_014017_create_fulfillment_tokens_table.php
│   │   ├── 2026_01_04_014250_create_warehouse_fulfillments_table.php
│   │   ├── 2026_01_04_014510_create_audit_logs_table.php
│   │   ├── 2026_01_04_015047_create_parties_table.php
│   │   ├── 2026_01_04_015312_create_party_accounts_table.php
│   │   ├── 2026_01_04_015956_create_purchases_table.php
│   │   ├── 2026_01_04_020232_create_purchase_items_table.php
│   │   ├── 2026_01_04_020536_create_stock_transfers_table.php
│   │   ├── 2026_01_04_020818_create_stock_transfer_items_table.php
│   │   ├── 2026_01_04_195147_create_personal_access_tokens_table.php
│   │   ├── 2026_01_04_204628_add_details_to_businesses_table.php
│   │   ├── 2026_01_04_221055_add_business_location_id_to_users_table.php
│   │   ├── 2026_01_04_230322_harden_fulfillment_tokens_table.php
│   │   ├── 2026_01_05_212204_drop_plaintext_token_from_fulfillment_tokens.php
│   │   ├── 2026_01_06_184310_create_purchase_receipts_table.php
│   │   ├── 2026_01_06_184826_create_purchase_receipt_items_table.php
│   │   ├── 2026_01_08_021819_create_offline_fulfillment_pendings_table.php
│   │   ├── 2026_01_09_090344_add_state_machine_to_warehouse_fulfillments_table.php
│   │   ├── 2026_01_09_091317_add_idempotency_guard_to_stock_movements.php
│   │   ├── 2026_01_09_102332_add_state_machine_to_offline_fulfillment_pendings_table.php
│   │   ├── 2026_01_10_230942_create_supervisor_overrides_table.php
│   │   └── 2026_01_10_235454_add_requires_override_to_offline_fulfillment_pendings_table.php
│   ├── schema
│   │   └── mysql-schema.sql
│   └── seeders
│       └── DatabaseSeeder.php
├── package.json
├── phpunit.xml
├── public
│   ├── favicon.ico
│   ├── index.php
│   └── robots.txt
├── resources
│   ├── css
│   │   └── app.css
│   ├── js
│   │   ├── app.js
│   │   └── bootstrap.js
│   └── views
│       └── welcome.blade.php
├── routes
│   ├── console.php
│   └── web.php
├── storage
│   ├── app
│   │   ├── private
│   │   └── public
│   ├── framework
│   │   ├── cache
│   │   │   └── data
│   │   ├── sessions
│   │   │   ├── 4XnIAfZpipCxr3SEJQ7QOluUkk8C2sMqnFe9r3t6
│   │   │   ├── 5ugEJZVEdDxoG7tJ0AAtKjCOhHdWAj6iys9LBqEe
│   │   │   ├── 6r2yBYOYlxbRVD0Uusmj5081uqouTRoguiLOg6lV
│   │   │   ├── HTTvpjfFTTX8RxuIicPL0EuwJMODid8WaQp9W2tz
│   │   │   ├── Hx5Z1g2FSkWxqi8g7KQq6RCsUHpv3QpQZYlIZXIp
│   │   │   ├── IEiI1pzVso1IkFttSrhCKfy0Uqor7zCrOJtIG9qd
│   │   │   ├── J9dHMWWhNjYh14iCvXOdUfTx60t0efbIpKVe3aJh
│   │   │   ├── Jmp7tML4ZF4wAd7PLyjtPNQpZbJQqPK8uWZgsyIQ
│   │   │   ├── O78PRgWaWSrseVr0razy27644J9UYLszVJ8cqGjQ
│   │   │   ├── S0y5PQEbkqBjoorZwv1lMTiD4JP307DMZfXvT2VZ
│   │   │   ├── Sith3UjACj4v3MttbCMQgSb1pRLYQTIXztxwn94U
│   │   │   ├── VUZkUJi964EuEsogg9YnNVWJWBBHrT8eclpun2bn
│   │   │   ├── dElE8hm7cUPKszmC4hKQhfGqygycXLrRc8j553Zi
│   │   │   ├── dPprDUMdRlGA3vCcKgCLPSWv5aSDn8kypoVoX8BL
│   │   │   ├── dXsS9tSMJI7Bc3x1sQ6luvBOTvuuPQcqQVoBJI9D
│   │   │   ├── eYmhMCUHbKq3vo4liiGeWTTnkZ2fyfZYkKPYjbBw
│   │   │   ├── gs5j05k1KyTBn2srzEWDwK7koaqdWwH91aqUFaFl
│   │   │   ├── jrfNoAcapHrmCy4rmFS5Y2m2P1Hr2QQWvGgFWi9a
│   │   │   ├── kEb04X1lKqyf75GpkCXHf27ZwARmrtx1epNkQjD5
│   │   │   ├── kRGsMSlOgCk0iG8nH9Zff2smrRO40cgjjGkGcEeC
│   │   │   ├── lQ5gD7eA3OyUYG7jqaSRbh67Nw5mbvtel93TjlYx
│   │   │   ├── oeZy1nds6L2YTqyNZAMqbDsP2cLyKtkd2hNsH5o4
│   │   │   ├── paE17JCIEM9S8ACVExLVUKXlZAtZAfRkWTr591IB
│   │   │   ├── qR2rHWCAhQDMRvjTA0MwMUPUne9Wg9fWLQehI002
│   │   │   ├── tFg3YyhnGOjjgym0Qq7lB57vXFQeNZHx3F4Ab8Zv
│   │   │   ├── woGNA9wghbwAuZK8ZEy3qUIhksTLB4z0GfWJAoFD
│   │   │   ├── y3K6qF9yPizkQP13m4JI9GtMg4droJ28pNQo1Two
│   │   │   └── zYYS1iG46ppD8XVccznC0Tjv7rWg2xEsePCoS4th
│   │   ├── testing
│   │   └── views
│   ├── keys
│   │   ├── offline-qr-2026-q1
│   │   └── offline-qr-2026-q1.pub
│   └── logs
│       └── laravel.log
├── tests
│   ├── CreatesApplication.php
│   ├── Feature
│   │   ├── ExampleTest.php
│   │   └── Fulfillment
│   │       ├── OfflineReconciliationTest.php
│   │       └── QrScanTest.php
│   ├── Helpers
│   │   └── FulfillmentTestHelper.php
│   ├── TestCase.php
│   └── Unit
│       └── ExampleTest.php
├── vendor
│   ├── autoload.php
│   ├── bin
│   │   ├── carbon
│   │   ├── carbon.bat
│   │   ├── patch-type-declarations
│   │   ├── patch-type-declarations.bat
│   │   ├── php-parse
│   │   ├── php-parse.bat
│   │   ├── phpunit
│   │   ├── phpunit.bat
│   │   ├── pint
│   │   ├── pint.bat
│   │   ├── psysh
│   │   ├── psysh.bat
│   │   ├── sail
│   │   ├── sail.bat
│   │   ├── var-dump-server
│   │   ├── var-dump-server.bat
│   │   ├── yaml-lint
│   │   └── yaml-lint.bat
│   ├── brick
│   │   └── math
│   │       ├── CHANGELOG.md
│   │       ├── LICENSE
│   │       ├── composer.json
│   │       ├── phpstan.neon
│   │       ├── src
│   │       └── tools
│   ├── carbonphp
│   │   └── carbon-doctrine-types
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       └── src
│   ├── composer
│   │   ├── ClassLoader.php
│   │   ├── InstalledVersions.php
│   │   ├── LICENSE
│   │   ├── autoload_classmap.php
│   │   ├── autoload_files.php
│   │   ├── autoload_namespaces.php
│   │   ├── autoload_psr4.php
│   │   ├── autoload_real.php
│   │   ├── autoload_static.php
│   │   ├── installed.json
│   │   ├── installed.php
│   │   └── platform_check.php
│   ├── dflydev
│   │   └── dot-access-data
│   │       ├── CHANGELOG.md
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       └── src
│   ├── doctrine
│   │   ├── inflector
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   ├── docs
│   │   │   └── src
│   │   └── lexer
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── UPGRADE.md
│   │       ├── composer.json
│   │       └── src
│   ├── dragonmantank
│   │   └── cron-expression
│   │       ├── CHANGELOG.md
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       └── src
│   ├── egulias
│   │   └── email-validator
│   │       ├── CONTRIBUTING.md
│   │       ├── LICENSE
│   │       ├── composer.json
│   │       └── src
│   ├── fakerphp
│   │   └── faker
│   │       ├── CHANGELOG.md
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       ├── rector-migrate.php
│   │       └── src
│   ├── filp
│   │   └── whoops
│   │       ├── CHANGELOG.md
│   │       ├── LICENSE.md
│   │       ├── SECURITY.md
│   │       ├── composer.json
│   │       └── src
│   ├── fruitcake
│   │   └── php-cors
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       └── src
│   ├── graham-campbell
│   │   └── result-type
│   │       ├── LICENSE
│   │       ├── composer.json
│   │       └── src
│   ├── guzzlehttp
│   │   ├── guzzle
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── UPGRADING.md
│   │   │   ├── composer.json
│   │   │   ├── package-lock.json
│   │   │   └── src
│   │   ├── promises
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── psr7
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   └── uri-template
│   │       ├── CHANGELOG.md
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       └── src
│   ├── hamcrest
│   │   └── hamcrest-php
│   │       ├── CHANGES.txt
│   │       ├── CONTRIBUTING.md
│   │       ├── LICENSE.txt
│   │       ├── README.md
│   │       ├── composer.json
│   │       ├── generator
│   │       └── hamcrest
│   ├── laravel
│   │   ├── framework
│   │   │   ├── LICENSE.md
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   ├── config
│   │   │   ├── config-stubs
│   │   │   ├── pint.json
│   │   │   └── src
│   │   ├── pail
│   │   │   ├── LICENSE.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── pint
│   │   │   ├── LICENSE.md
│   │   │   ├── builds
│   │   │   ├── composer.json
│   │   │   └── overrides
│   │   ├── prompts
│   │   │   ├── LICENSE.md
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── sail
│   │   │   ├── LICENSE.md
│   │   │   ├── README.md
│   │   │   ├── bin
│   │   │   ├── composer.json
│   │   │   ├── database
│   │   │   ├── runtimes
│   │   │   ├── src
│   │   │   └── stubs
│   │   ├── sanctum
│   │   │   ├── LICENSE.md
│   │   │   ├── README.md
│   │   │   ├── UPGRADE.md
│   │   │   ├── composer.json
│   │   │   ├── config
│   │   │   ├── database
│   │   │   ├── src
│   │   │   └── testbench.yaml
│   │   ├── serializable-closure
│   │   │   ├── LICENSE.md
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   └── tinker
│   │       ├── LICENSE.md
│   │       ├── README.md
│   │       ├── composer.json
│   │       ├── config
│   │       └── src
│   ├── league
│   │   ├── commonmark
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── config
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE.md
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── flysystem
│   │   │   ├── INFO.md
│   │   │   ├── LICENSE
│   │   │   ├── composer.json
│   │   │   ├── readme.md
│   │   │   └── src
│   │   ├── flysystem-local
│   │   │   ├── FallbackMimeTypeDetector.php
│   │   │   ├── LICENSE
│   │   │   ├── LocalFilesystemAdapter.php
│   │   │   └── composer.json
│   │   ├── mime-type-detection
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── uri
│   │   │   ├── BaseUri.php
│   │   │   ├── Http.php
│   │   │   ├── HttpFactory.php
│   │   │   ├── LICENSE
│   │   │   ├── SchemeType.php
│   │   │   ├── Uri.php
│   │   │   ├── UriInfo.php
│   │   │   ├── UriResolver.php
│   │   │   ├── UriScheme.php
│   │   │   ├── UriTemplate
│   │   │   ├── UriTemplate.php
│   │   │   ├── Urn.php
│   │   │   └── composer.json
│   │   └── uri-interfaces
│   │       ├── Contracts
│   │       ├── Encoder.php
│   │       ├── Exceptions
│   │       ├── FeatureDetection.php
│   │       ├── HostFormat.php
│   │       ├── HostRecord.php
│   │       ├── HostType.php
│   │       ├── IPv4
│   │       ├── IPv6
│   │       ├── Idna
│   │       ├── KeyValuePair
│   │       ├── LICENSE
│   │       ├── QueryString.php
│   │       ├── UriComparisonMode.php
│   │       ├── UriString.php
│   │       ├── UrnComparisonMode.php
│   │       └── composer.json
│   ├── mockery
│   │   └── mockery
│   │       ├── CHANGELOG.md
│   │       ├── CONTRIBUTING.md
│   │       ├── COPYRIGHT.md
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── SECURITY.md
│   │       ├── composer.json
│   │       ├── composer.lock
│   │       ├── docs
│   │       └── library
│   ├── monolog
│   │   └── monolog
│   │       ├── CHANGELOG.md
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       └── src
│   ├── myclabs
│   │   └── deep-copy
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       └── src
│   ├── nesbot
│   │   └── carbon
│   │       ├── LICENSE
│   │       ├── bin
│   │       ├── composer.json
│   │       ├── extension.neon
│   │       ├── lazy
│   │       ├── readme.md
│   │       └── src
│   ├── nette
│   │   ├── schema
│   │   │   ├── composer.json
│   │   │   ├── license.md
│   │   │   ├── readme.md
│   │   │   └── src
│   │   └── utils
│   │       ├── composer.json
│   │       ├── license.md
│   │       ├── readme.md
│   │       └── src
│   ├── nikic
│   │   └── php-parser
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── bin
│   │       ├── composer.json
│   │       └── lib
│   ├── nunomaduro
│   │   ├── collision
│   │   │   ├── LICENSE.md
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   ├── phpstan-baseline.neon
│   │   │   └── src
│   │   └── termwind
│   │       ├── LICENSE.md
│   │       ├── composer.json
│   │       ├── playground.php
│   │       └── src
│   ├── phar-io
│   │   ├── manifest
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   ├── composer.lock
│   │   │   ├── manifest.xsd
│   │   │   ├── src
│   │   │   └── tools
│   │   └── version
│   │       ├── CHANGELOG.md
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       └── src
│   ├── phpoption
│   │   └── phpoption
│   │       ├── LICENSE
│   │       ├── composer.json
│   │       └── src
│   ├── phpunit
│   │   ├── php-code-coverage
│   │   │   ├── ChangeLog-11.0.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── php-file-iterator
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── php-invoker
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── php-text-template
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── php-timer
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   └── phpunit
│   │       ├── ChangeLog-11.5.md
│   │       ├── DEPRECATIONS.md
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── SECURITY.md
│   │       ├── composer.json
│   │       ├── composer.lock
│   │       ├── phpunit
│   │       ├── phpunit.xsd
│   │       ├── schema
│   │       └── src
│   ├── psr
│   │   ├── clock
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── container
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── event-dispatcher
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── http-client
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── http-factory
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── http-message
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   ├── docs
│   │   │   └── src
│   │   ├── log
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   └── simple-cache
│   │       ├── LICENSE.md
│   │       ├── README.md
│   │       ├── composer.json
│   │       └── src
│   ├── psy
│   │   └── psysh
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── bin
│   │       ├── build
│   │       ├── composer.json
│   │       ├── src
│   │       └── test-logger-demo.php
│   ├── ralouphie
│   │   └── getallheaders
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       └── src
│   ├── ramsey
│   │   ├── collection
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   └── uuid
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       └── src
│   ├── sebastian
│   │   ├── cli-parser
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── code-unit
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── code-unit-reverse-lookup
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── comparator
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── complexity
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── diff
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── environment
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── exporter
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── global-state
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── lines-of-code
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── object-enumerator
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── object-reflector
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── recursion-context
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   ├── type
│   │   │   ├── ChangeLog.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SECURITY.md
│   │   │   ├── composer.json
│   │   │   └── src
│   │   └── version
│   │       ├── ChangeLog.md
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── SECURITY.md
│   │       ├── composer.json
│   │       └── src
│   ├── spatie
│   │   └── laravel-permission
│   │       ├── LICENSE.md
│   │       ├── README.md
│   │       ├── composer.json
│   │       ├── config
│   │       ├── database
│   │       ├── ide.json
│   │       ├── pint.json
│   │       └── src
│   ├── staabm
│   │   └── side-effects-detector
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       └── lib
│   ├── symfony
│   │   ├── clock
│   │   │   ├── CHANGELOG.md
│   │   │   ├── Clock.php
│   │   │   ├── ClockAwareTrait.php
│   │   │   ├── ClockInterface.php
│   │   │   ├── DatePoint.php
│   │   │   ├── LICENSE
│   │   │   ├── MockClock.php
│   │   │   ├── MonotonicClock.php
│   │   │   ├── NativeClock.php
│   │   │   ├── README.md
│   │   │   ├── Resources
│   │   │   ├── Test
│   │   │   └── composer.json
│   │   ├── console
│   │   │   ├── Application.php
│   │   │   ├── Attribute
│   │   │   ├── CHANGELOG.md
│   │   │   ├── CI
│   │   │   ├── Color.php
│   │   │   ├── Command
│   │   │   ├── CommandLoader
│   │   │   ├── Completion
│   │   │   ├── ConsoleEvents.php
│   │   │   ├── Cursor.php
│   │   │   ├── DataCollector
│   │   │   ├── Debug
│   │   │   ├── DependencyInjection
│   │   │   ├── Descriptor
│   │   │   ├── Event
│   │   │   ├── EventListener
│   │   │   ├── Exception
│   │   │   ├── Formatter
│   │   │   ├── Helper
│   │   │   ├── Input
│   │   │   ├── Interaction
│   │   │   ├── LICENSE
│   │   │   ├── Logger
│   │   │   ├── Messenger
│   │   │   ├── Output
│   │   │   ├── Question
│   │   │   ├── README.md
│   │   │   ├── Resources
│   │   │   ├── SignalRegistry
│   │   │   ├── SingleCommandApplication.php
│   │   │   ├── Style
│   │   │   ├── Terminal.php
│   │   │   ├── Tester
│   │   │   └── composer.json
│   │   ├── css-selector
│   │   │   ├── CHANGELOG.md
│   │   │   ├── CssSelectorConverter.php
│   │   │   ├── Exception
│   │   │   ├── LICENSE
│   │   │   ├── Node
│   │   │   ├── Parser
│   │   │   ├── README.md
│   │   │   ├── XPath
│   │   │   └── composer.json
│   │   ├── deprecation-contracts
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── composer.json
│   │   │   └── function.php
│   │   ├── error-handler
│   │   │   ├── BufferingLogger.php
│   │   │   ├── CHANGELOG.md
│   │   │   ├── Command
│   │   │   ├── Debug.php
│   │   │   ├── DebugClassLoader.php
│   │   │   ├── Error
│   │   │   ├── ErrorEnhancer
│   │   │   ├── ErrorHandler.php
│   │   │   ├── ErrorRenderer
│   │   │   ├── Exception
│   │   │   ├── Internal
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── Resources
│   │   │   ├── ThrowableUtils.php
│   │   │   └── composer.json
│   │   ├── event-dispatcher
│   │   │   ├── Attribute
│   │   │   ├── CHANGELOG.md
│   │   │   ├── Debug
│   │   │   ├── DependencyInjection
│   │   │   ├── EventDispatcher.php
│   │   │   ├── EventDispatcherInterface.php
│   │   │   ├── EventSubscriberInterface.php
│   │   │   ├── GenericEvent.php
│   │   │   ├── ImmutableEventDispatcher.php
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   └── composer.json
│   │   ├── event-dispatcher-contracts
│   │   │   ├── CHANGELOG.md
│   │   │   ├── Event.php
│   │   │   ├── EventDispatcherInterface.php
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   └── composer.json
│   │   ├── finder
│   │   │   ├── CHANGELOG.md
│   │   │   ├── Comparator
│   │   │   ├── Exception
│   │   │   ├── Finder.php
│   │   │   ├── Gitignore.php
│   │   │   ├── Glob.php
│   │   │   ├── Iterator
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── SplFileInfo.php
│   │   │   └── composer.json
│   │   ├── http-foundation
│   │   │   ├── AcceptHeader.php
│   │   │   ├── AcceptHeaderItem.php
│   │   │   ├── BinaryFileResponse.php
│   │   │   ├── CHANGELOG.md
│   │   │   ├── ChainRequestMatcher.php
│   │   │   ├── Cookie.php
│   │   │   ├── EventStreamResponse.php
│   │   │   ├── Exception
│   │   │   ├── File
│   │   │   ├── FileBag.php
│   │   │   ├── HeaderBag.php
│   │   │   ├── HeaderUtils.php
│   │   │   ├── InputBag.php
│   │   │   ├── IpUtils.php
│   │   │   ├── JsonResponse.php
│   │   │   ├── LICENSE
│   │   │   ├── ParameterBag.php
│   │   │   ├── README.md
│   │   │   ├── RateLimiter
│   │   │   ├── RedirectResponse.php
│   │   │   ├── Request.php
│   │   │   ├── RequestMatcher
│   │   │   ├── RequestMatcherInterface.php
│   │   │   ├── RequestStack.php
│   │   │   ├── Response.php
│   │   │   ├── ResponseHeaderBag.php
│   │   │   ├── ServerBag.php
│   │   │   ├── ServerEvent.php
│   │   │   ├── Session
│   │   │   ├── StreamedJsonResponse.php
│   │   │   ├── StreamedResponse.php
│   │   │   ├── Test
│   │   │   ├── UriSigner.php
│   │   │   ├── UrlHelper.php
│   │   │   └── composer.json
│   │   ├── http-kernel
│   │   │   ├── Attribute
│   │   │   ├── Bundle
│   │   │   ├── CHANGELOG.md
│   │   │   ├── CacheClearer
│   │   │   ├── CacheWarmer
│   │   │   ├── Config
│   │   │   ├── Controller
│   │   │   ├── ControllerMetadata
│   │   │   ├── DataCollector
│   │   │   ├── Debug
│   │   │   ├── DependencyInjection
│   │   │   ├── Event
│   │   │   ├── EventListener
│   │   │   ├── Exception
│   │   │   ├── Fragment
│   │   │   ├── HttpCache
│   │   │   ├── HttpClientKernel.php
│   │   │   ├── HttpKernel.php
│   │   │   ├── HttpKernelBrowser.php
│   │   │   ├── HttpKernelInterface.php
│   │   │   ├── Kernel.php
│   │   │   ├── KernelEvents.php
│   │   │   ├── KernelInterface.php
│   │   │   ├── LICENSE
│   │   │   ├── Log
│   │   │   ├── Profiler
│   │   │   ├── README.md
│   │   │   ├── RebootableInterface.php
│   │   │   ├── Resources
│   │   │   ├── TerminableInterface.php
│   │   │   └── composer.json
│   │   ├── mailer
│   │   │   ├── CHANGELOG.md
│   │   │   ├── Command
│   │   │   ├── DataCollector
│   │   │   ├── DelayedEnvelope.php
│   │   │   ├── Envelope.php
│   │   │   ├── Event
│   │   │   ├── EventListener
│   │   │   ├── Exception
│   │   │   ├── Header
│   │   │   ├── LICENSE
│   │   │   ├── Mailer.php
│   │   │   ├── MailerInterface.php
│   │   │   ├── Messenger
│   │   │   ├── README.md
│   │   │   ├── SentMessage.php
│   │   │   ├── Test
│   │   │   ├── Transport
│   │   │   ├── Transport.php
│   │   │   └── composer.json
│   │   ├── mime
│   │   │   ├── Address.php
│   │   │   ├── BodyRendererInterface.php
│   │   │   ├── CHANGELOG.md
│   │   │   ├── CharacterStream.php
│   │   │   ├── Crypto
│   │   │   ├── DependencyInjection
│   │   │   ├── DraftEmail.php
│   │   │   ├── Email.php
│   │   │   ├── Encoder
│   │   │   ├── Exception
│   │   │   ├── FileBinaryMimeTypeGuesser.php
│   │   │   ├── FileinfoMimeTypeGuesser.php
│   │   │   ├── Header
│   │   │   ├── HtmlToTextConverter
│   │   │   ├── LICENSE
│   │   │   ├── Message.php
│   │   │   ├── MessageConverter.php
│   │   │   ├── MimeTypeGuesserInterface.php
│   │   │   ├── MimeTypes.php
│   │   │   ├── MimeTypesInterface.php
│   │   │   ├── Part
│   │   │   ├── README.md
│   │   │   ├── RawMessage.php
│   │   │   ├── Resources
│   │   │   ├── Test
│   │   │   └── composer.json
│   │   ├── polyfill-ctype
│   │   │   ├── Ctype.php
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── bootstrap.php
│   │   │   ├── bootstrap80.php
│   │   │   └── composer.json
│   │   ├── polyfill-intl-grapheme
│   │   │   ├── Grapheme.php
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── bootstrap.php
│   │   │   ├── bootstrap80.php
│   │   │   └── composer.json
│   │   ├── polyfill-intl-idn
│   │   │   ├── Idn.php
│   │   │   ├── Info.php
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── Resources
│   │   │   ├── bootstrap.php
│   │   │   ├── bootstrap80.php
│   │   │   └── composer.json
│   │   ├── polyfill-intl-normalizer
│   │   │   ├── LICENSE
│   │   │   ├── Normalizer.php
│   │   │   ├── README.md
│   │   │   ├── Resources
│   │   │   ├── bootstrap.php
│   │   │   ├── bootstrap80.php
│   │   │   └── composer.json
│   │   ├── polyfill-mbstring
│   │   │   ├── LICENSE
│   │   │   ├── Mbstring.php
│   │   │   ├── README.md
│   │   │   ├── Resources
│   │   │   ├── bootstrap.php
│   │   │   ├── bootstrap80.php
│   │   │   └── composer.json
│   │   ├── polyfill-php80
│   │   │   ├── LICENSE
│   │   │   ├── Php80.php
│   │   │   ├── PhpToken.php
│   │   │   ├── README.md
│   │   │   ├── Resources
│   │   │   ├── bootstrap.php
│   │   │   └── composer.json
│   │   ├── polyfill-php83
│   │   │   ├── LICENSE
│   │   │   ├── Php83.php
│   │   │   ├── README.md
│   │   │   ├── Resources
│   │   │   ├── bootstrap.php
│   │   │   ├── bootstrap81.php
│   │   │   └── composer.json
│   │   ├── polyfill-php84
│   │   │   ├── LICENSE
│   │   │   ├── Php84.php
│   │   │   ├── README.md
│   │   │   ├── Resources
│   │   │   ├── bootstrap.php
│   │   │   ├── bootstrap82.php
│   │   │   └── composer.json
│   │   ├── polyfill-php85
│   │   │   ├── LICENSE
│   │   │   ├── Php85.php
│   │   │   ├── README.md
│   │   │   ├── Resources
│   │   │   ├── bootstrap.php
│   │   │   └── composer.json
│   │   ├── polyfill-uuid
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── Uuid.php
│   │   │   ├── bootstrap.php
│   │   │   ├── bootstrap80.php
│   │   │   └── composer.json
│   │   ├── process
│   │   │   ├── CHANGELOG.md
│   │   │   ├── Exception
│   │   │   ├── ExecutableFinder.php
│   │   │   ├── InputStream.php
│   │   │   ├── LICENSE
│   │   │   ├── Messenger
│   │   │   ├── PhpExecutableFinder.php
│   │   │   ├── PhpProcess.php
│   │   │   ├── PhpSubprocess.php
│   │   │   ├── Pipes
│   │   │   ├── Process.php
│   │   │   ├── ProcessUtils.php
│   │   │   ├── README.md
│   │   │   └── composer.json
│   │   ├── routing
│   │   │   ├── Alias.php
│   │   │   ├── Annotation
│   │   │   ├── Attribute
│   │   │   ├── CHANGELOG.md
│   │   │   ├── CompiledRoute.php
│   │   │   ├── DependencyInjection
│   │   │   ├── Exception
│   │   │   ├── Generator
│   │   │   ├── LICENSE
│   │   │   ├── Loader
│   │   │   ├── Matcher
│   │   │   ├── README.md
│   │   │   ├── RequestContext.php
│   │   │   ├── RequestContextAwareInterface.php
│   │   │   ├── Requirement
│   │   │   ├── Route.php
│   │   │   ├── RouteCollection.php
│   │   │   ├── RouteCompiler.php
│   │   │   ├── RouteCompilerInterface.php
│   │   │   ├── Router.php
│   │   │   ├── RouterInterface.php
│   │   │   └── composer.json
│   │   ├── service-contracts
│   │   │   ├── Attribute
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── ResetInterface.php
│   │   │   ├── ServiceCollectionInterface.php
│   │   │   ├── ServiceLocatorTrait.php
│   │   │   ├── ServiceMethodsSubscriberTrait.php
│   │   │   ├── ServiceProviderInterface.php
│   │   │   ├── ServiceSubscriberInterface.php
│   │   │   ├── ServiceSubscriberTrait.php
│   │   │   ├── Test
│   │   │   └── composer.json
│   │   ├── string
│   │   │   ├── AbstractString.php
│   │   │   ├── AbstractUnicodeString.php
│   │   │   ├── ByteString.php
│   │   │   ├── CHANGELOG.md
│   │   │   ├── CodePointString.php
│   │   │   ├── Exception
│   │   │   ├── Inflector
│   │   │   ├── LICENSE
│   │   │   ├── LazyString.php
│   │   │   ├── README.md
│   │   │   ├── Resources
│   │   │   ├── Slugger
│   │   │   ├── TruncateMode.php
│   │   │   ├── UnicodeString.php
│   │   │   └── composer.json
│   │   ├── translation
│   │   │   ├── CHANGELOG.md
│   │   │   ├── Catalogue
│   │   │   ├── CatalogueMetadataAwareInterface.php
│   │   │   ├── Command
│   │   │   ├── DataCollector
│   │   │   ├── DataCollectorTranslator.php
│   │   │   ├── DependencyInjection
│   │   │   ├── Dumper
│   │   │   ├── Exception
│   │   │   ├── Extractor
│   │   │   ├── Formatter
│   │   │   ├── IdentityTranslator.php
│   │   │   ├── LICENSE
│   │   │   ├── Loader
│   │   │   ├── LocaleSwitcher.php
│   │   │   ├── LoggingTranslator.php
│   │   │   ├── MessageCatalogue.php
│   │   │   ├── MessageCatalogueInterface.php
│   │   │   ├── MetadataAwareInterface.php
│   │   │   ├── Provider
│   │   │   ├── PseudoLocalizationTranslator.php
│   │   │   ├── README.md
│   │   │   ├── Reader
│   │   │   ├── Resources
│   │   │   ├── StaticMessage.php
│   │   │   ├── Test
│   │   │   ├── TranslatableMessage.php
│   │   │   ├── Translator.php
│   │   │   ├── TranslatorBag.php
│   │   │   ├── TranslatorBagInterface.php
│   │   │   ├── Util
│   │   │   ├── Writer
│   │   │   └── composer.json
│   │   ├── translation-contracts
│   │   │   ├── CHANGELOG.md
│   │   │   ├── LICENSE
│   │   │   ├── LocaleAwareInterface.php
│   │   │   ├── README.md
│   │   │   ├── Test
│   │   │   ├── TranslatableInterface.php
│   │   │   ├── TranslatorInterface.php
│   │   │   ├── TranslatorTrait.php
│   │   │   └── composer.json
│   │   ├── uid
│   │   │   ├── AbstractUid.php
│   │   │   ├── BinaryUtil.php
│   │   │   ├── CHANGELOG.md
│   │   │   ├── Command
│   │   │   ├── Exception
│   │   │   ├── Factory
│   │   │   ├── HashableInterface.php
│   │   │   ├── LICENSE
│   │   │   ├── MaxUlid.php
│   │   │   ├── MaxUuid.php
│   │   │   ├── NilUlid.php
│   │   │   ├── NilUuid.php
│   │   │   ├── README.md
│   │   │   ├── TimeBasedUidInterface.php
│   │   │   ├── Ulid.php
│   │   │   ├── Uuid.php
│   │   │   ├── UuidV1.php
│   │   │   ├── UuidV3.php
│   │   │   ├── UuidV4.php
│   │   │   ├── UuidV5.php
│   │   │   ├── UuidV6.php
│   │   │   ├── UuidV7.php
│   │   │   ├── UuidV8.php
│   │   │   └── composer.json
│   │   ├── var-dumper
│   │   │   ├── CHANGELOG.md
│   │   │   ├── Caster
│   │   │   ├── Cloner
│   │   │   ├── Command
│   │   │   ├── Dumper
│   │   │   ├── Exception
│   │   │   ├── LICENSE
│   │   │   ├── README.md
│   │   │   ├── Resources
│   │   │   ├── Server
│   │   │   ├── Test
│   │   │   ├── VarDumper.php
│   │   │   └── composer.json
│   │   └── yaml
│   │       ├── CHANGELOG.md
│   │       ├── Command
│   │       ├── Dumper.php
│   │       ├── Escaper.php
│   │       ├── Exception
│   │       ├── Inline.php
│   │       ├── LICENSE
│   │       ├── Parser.php
│   │       ├── README.md
│   │       ├── Resources
│   │       ├── Tag
│   │       ├── Unescaper.php
│   │       ├── Yaml.php
│   │       └── composer.json
│   ├── theseer
│   │   └── tokenizer
│   │       ├── CHANGELOG.md
│   │       ├── LICENSE
│   │       ├── README.md
│   │       ├── composer.json
│   │       ├── composer.lock
│   │       └── src
│   ├── tijsverkoyen
│   │   └── css-to-inline-styles
│   │       ├── LICENSE.md
│   │       ├── composer.json
│   │       └── src
│   ├── vlucas
│   │   └── phpdotenv
│   │       ├── LICENSE
│   │       ├── composer.json
│   │       └── src
│   └── voku
│       └── portable-ascii
│           ├── CHANGELOG.md
│           ├── LICENSE.txt
│           ├── README.md
│           ├── composer.json
│           └── src
└── vite.config.js
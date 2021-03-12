<?php
require 'vendor/autoload.php';

use App\Pim\PimOrchestrator;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '.env');
$dotenv->load();

putenv('GOOGLE_APPLICATION_CREDENTIALS='.dirname(__FILE__).'/'.$_SERVER['GOOGLE_TRANSLATE_CREDENTIALS_FILENAME']);

$pimOrchestrator = new PimOrchestrator();

$pimOrchestrator->translateProductsTypeForAttributes(PimOrchestrator::TYPE_PRODUCT_MODELS);
$pimOrchestrator->translateProductsTypeForAttributes(PimOrchestrator::TYPE_PRODUCTS);



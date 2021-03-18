<?php
require 'vendor/autoload.php';

use App\Pim\PimOrchestrator;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '.env');
$dotenv->load();

$pimOrchestrator = new PimOrchestrator();

$pimOrchestrator->translateProductsTypeForAttributes(PimOrchestrator::TYPE_PRODUCT_MODELS);
$pimOrchestrator->translateProductsTypeForAttributes(PimOrchestrator::TYPE_PRODUCTS);



<?php
require 'vendor/autoload.php';

use App\Pim\PimOrchestrator;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '.env');
$dotenv->load();

putenv('GOOGLE_APPLICATION_CREDENTIALS='.dirname(__FILE__).'/akecld-akeneo-presales-team-454cfe6d737b.json');

$sourceLocale = $_SERVER['LOCALE_SOURCE'];
$targetLocale = $_SERVER['LOCALE_DESTINATION'];
$targetAttributes = explode(',', $_SERVER['TARGET_ATTRIBUTES']);
$scope = $_SERVER['SCOPE_DESTINATION'];

$pimOrchestrator = new PimOrchestrator();

$pimOrchestrator->translateProductsTypeForAttributes(PimOrchestrator::TYPE_PRODUCT_MODELS, $targetAttributes, $scope, $sourceLocale, $targetLocale);
$pimOrchestrator->translateProductsTypeForAttributes(PimOrchestrator::TYPE_PRODUCTS, $targetAttributes, $scope, $sourceLocale, $targetLocale);



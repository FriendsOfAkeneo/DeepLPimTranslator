<?php


namespace App\Pim;


use Akeneo\Pim\ApiClient\Exception\RuntimeException;
use Akeneo\Pim\ApiClient\Exception\UnprocessableEntityHttpException;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Akeneo\Pim\ApiClient\Stream\UpsertResourceListResponse;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use App\Exception\UpsertException;
use App\Translator\DeeplTranslator;

class PimOrchestrator
{
    const TYPE_PRODUCT_MODELS = 'ProductModel';
    const TYPE_PRODUCTS = 'Product';
    const PARAM_ATTRIBUTES = 'attributes';
    const PARAM_SCOPE_SOURCE = 'scope_source';
    const PARAM_SCOPE_DESTINATION = 'scope_destination';
    const PARAM_LOCALE_SOURCE = 'locale_source';
    const PARAM_LOCALE_DESTINATION = 'locale_destination';
    const PARAM_CATEGORIES_SOURCE = 'categories_source';

    private $client;

    private $translator;

    private $pimAttributes = [];

    private $params = [];

    /**
     * PimOrchestrator constructor.
     */
    public function __construct()
    {
        $this->params = [
            static::PARAM_LOCALE_SOURCE => $_SERVER['LOCALE_SOURCE'],
            static::PARAM_LOCALE_DESTINATION => $_SERVER['LOCALE_DESTINATION'],
            static::PARAM_ATTRIBUTES => explode(',', $_SERVER['TARGET_ATTRIBUTES']),
            static::PARAM_SCOPE_SOURCE => $_SERVER['SCOPE_SOURCE'],
            static::PARAM_CATEGORIES_SOURCE => explode(',', $_SERVER['CATEGORIES_SOURCE']),
            static::PARAM_SCOPE_DESTINATION => $_SERVER['SCOPE_DESTINATION'],
        ];

        $clientBuilder = new AkeneoPimEnterpriseClientBuilder($_SERVER['PIM_URL']);
        $this->client = $clientBuilder->buildAuthenticatedByPassword(
            $_SERVER['PIM_API_CLIENT_ID'],
            $_SERVER['PIM_API_CLIENT_SECRET'],
            $_SERVER['PIM_API_USER'],
            $_SERVER['PIM_API_PASSWORD']);

        $this->translator = new DeeplTranslator();
    }

    /**
     * @param $type
     * @param $attributes
     * @param $scope
     * @param $sourceLocale
     * @param $targetLocale
     * @return array
     */
    public function retrieveProductTypeTotranslateForAttributes($type)
    {
        $products = [];
        foreach ($this->params[static::PARAM_ATTRIBUTES] as $attribute) {
            echo "Search For attribute $attribute...\n";
            // put attribute informations in cache
            $this->pimAttributes[$attribute] = $this->client->getAttributeApi()->get($attribute);

            $searchBuilder = new SearchBuilder();

            $searchBuilder->addFilter($attribute, "NOT EMPTY", null, ['scope' => $this->pimAttributes[$attribute]['scopable'] ? $this->params[static::PARAM_SCOPE_SOURCE] : null, 'locale' => $this->params[static::PARAM_LOCALE_SOURCE]]);
            $searchBuilder->addFilter($attribute, "EMPTY", null, ['scope' => $this->pimAttributes[$attribute]['scopable'] ? $this->params[static::PARAM_SCOPE_SOURCE] : null, 'locale' => $this->params[static::PARAM_LOCALE_DESTINATION]]);

            if ($type == static::TYPE_PRODUCTS) {
                $searchBuilder->addFilter('enabled', "=", true);
            }
            if ('' !== $this->params[self::PARAM_CATEGORIES_SOURCE]) {
                $searchBuilder->addFilter('categories', "IN", $this->params[self::PARAM_CATEGORIES_SOURCE]);
            }
            $searchFilters = $searchBuilder->getFilters();

            try {

                $response = $this->client->{'get' . $type . 'Api'}()->all(
                    "100",
                    [
                        "search" => $searchFilters,
                        'scope' => $this->params[static::PARAM_SCOPE_SOURCE],
                        'attributes' => implode(',', $this->params[static::PARAM_ATTRIBUTES])
                    ]
                );


                $idColumn = $type == static::TYPE_PRODUCT_MODELS ? 'code' : 'identifier';

                foreach ($response as $product) {
                    $products[$product[$idColumn]]['values'][$attribute] = $product['values'][$attribute];
                }
            } catch (UnprocessableEntityHttpException $e) {
                echo "\033[7;31m    Error : " . $e->getMessage() . "\033[0m\n";
            }
        }

        return $products;
    }

    /**
     * @param $type
     * @param $attributes
     * @param $scope
     * @param $sourceLocale
     * @param $targetLocale
     */
    public function translateProductsTypeForAttributes($type)
    {
        $fromLanguage = substr($this->params[static::PARAM_LOCALE_SOURCE], 0, strpos($this->params[static::PARAM_LOCALE_SOURCE], '_'));
        $targetLanguage = substr($this->params[static::PARAM_LOCALE_DESTINATION], 0, strpos($this->params[static::PARAM_LOCALE_DESTINATION], '_'));
        $updatedProducts = [];

        echo "Search for $type...";

        $list = $this->retrieveProductTypeTotranslateForAttributes($type);

        echo count($list) . " $type to process\n";

        foreach ($list as $productIdentifier => $product) {

            $toTranslate = [];

            foreach ($this->params[static::PARAM_ATTRIBUTES] as $keyAttr => $attribute) {
                if (isset($product['values'][$attribute])) {
                    foreach ($product['values'][$attribute] as $val) {
                        if ($val['locale'] == $this->params[static::PARAM_LOCALE_SOURCE]) {
                            $toTranslate[] = $val['data'];
                            break;
                        }
                    }
                } else {
                    unset($this->params[static::PARAM_ATTRIBUTES][$keyAttr]);
                }
            }

            if (count($toTranslate) > 0) {
                echo $productIdentifier . ":BEFORE:" . implode(';', $toTranslate) . "\n";

                $translated = $this->translator->translate($toTranslate, $fromLanguage, $targetLanguage);

                echo $productIdentifier . ":AFTER:" . implode(';', $translated) . "\n";


                $updatedProducts[$productIdentifier] = [];

                $indice = 0;
                foreach ($this->params[static::PARAM_ATTRIBUTES] as $attribute) {
                    $updatedProducts[$productIdentifier]['values'][$attribute][] = [
                        'data' => $translated[$indice],
                        'locale' => $this->params[static::PARAM_LOCALE_DESTINATION],
                        'scope' => $this->pimAttributes[$attribute]['scopable'] ? $this->params[static::PARAM_SCOPE_DESTINATION] : null,
                    ];
                    $indice++;
                }
            }
        }

        $this->updateProductType($type, $updatedProducts);
    }

    /**
     * @param $type
     * @param $products
     */
    public function updateProductType($type, $products)
    {
        try {
            $toUpdate = [];
            $batchNum = 0;
            $idColumn = $type == static::TYPE_PRODUCT_MODELS ? 'code' : 'identifier';
            foreach ($products as $productIdentifier => $values) {
                $toUpdate[] = array_merge([$idColumn => (string)$productIdentifier], $values);
                if (count($toUpdate) == 100) {
                    $batchNum++;
                    echo "Upsert $type batch $batchNum...";
                    $response = $this->client->{'get' . $type . 'Api'}()->upsertList($toUpdate);
                    $this->checkUpsertResponse($response);
                    echo "OK\n";
                    $toUpdate = [];
                }
            }

            if (count($toUpdate) > 0) {
                $batchNum++;
                echo "Upsert $type batch $batchNum...";
                $response = $this->client->{'get' . $type . 'Api'}()->upsertList($toUpdate);
                $this->checkUpsertResponse($response);
                echo "OK\n";
            }
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            echo 'Error: ' . $errorMessage . "\n";
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }

    protected function checkUpsertResponse($response)
    {
        if (is_int($response) && $response < 400) {
            return;
        }

        if ($response instanceof UpsertResourceListResponse && $response->valid()) {
            return;
        }

        $errors = $success = [];
        foreach ($response as $row) {
            if (!is_null($row) && (int)$row['status_code'] < 400) {
                $success[] = $row;
                continue;
            }
            $errors[] = $row;
        }

        if ($errors) {
            throw new RuntimeException('The response is invalid: ' . json_encode($errors, JSON_PRETTY_PRINT));
        }

        return $success;
    }
}

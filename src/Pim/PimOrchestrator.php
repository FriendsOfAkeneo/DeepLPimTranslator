<?php


namespace App\Pim;


use Akeneo\Pim\ApiClient\Exception\UnprocessableEntityHttpException;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use App\Translator\GoogleTranslator;

class PimOrchestrator
{
    const TYPE_PRODUCT_MODELS = 'ProductModel';
    const TYPE_PRODUCTS = 'Product';

    private $client;

    private $translator;

    private $pimAttributes = [];

    /**
     * PimOrchestrator constructor.
     */
    public function __construct()
    {
        $clientBuilder = new AkeneoPimEnterpriseClientBuilder($_SERVER['PIM_URL']);
        $this->client = $clientBuilder->buildAuthenticatedByPassword(
            $_SERVER['PIM_API_CLIENT_ID'],
            $_SERVER['PIM_API_CLIENT_SECRET'],
            $_SERVER['PIM_API_USER'],
            $_SERVER['PIM_API_PASSWORD']);

        $this->translator = new GoogleTranslator();
    }

    /**
     * @param $type
     * @param $attributes
     * @param $scope
     * @param $sourceLocale
     * @param $targetLocale
     * @return array
     */
    public function retrieveProductTypeTotranslateForAttributes($type, $attributes, $scope, $sourceLocale, $targetLocale)
    {
        $products = [];
        foreach($attributes as $attribute) {
            echo "Search For attribute $attribute...\n";
            $searchBuilder = new SearchBuilder();
            $this->pimAttributes[$attribute] = $this->client->getAttributeApi()->get($attribute);
            $searchBuilder->addFilter($attribute, "NOT EMPTY", null, ['scope' => $this->pimAttributes[$attribute]['scopable'] ? $scope : null, 'locale' => $sourceLocale]);
            $searchBuilder->addFilter($attribute, "EMPTY", null, ['scope' => $this->pimAttributes[$attribute]['scopable'] ? $scope : null , 'locale' => $targetLocale]);
            if($type == static::TYPE_PRODUCTS) {
                $searchBuilder->addFilter('enabled', "=", true);
            }

            $searchFilters = $searchBuilder->getFilters();

            $response = $this->client->{'get'.$type.'Api'}()->all(
                "100",
                [
                    "search" => $searchFilters,
                    'scope' => $scope,
                    'attributes' => implode(',', $attributes)
                ]
            );

            $idColumn = $type == static::TYPE_PRODUCT_MODELS ? 'code' : 'identifier';

            foreach ($response as $product) {
                $products[$product[$idColumn]]['values'][$attribute] = $product['values'][$attribute];
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
    public function translateProductsTypeForAttributes($type, $attributes, $scope, $sourceLocale, $targetLocale)
    {
        $targetLanguage = substr($targetLocale,0, strpos($targetLocale, '_'));
        $updatedProducts = [];

        echo "Search for $type...";

        $list = $this->retrieveProductTypeTotranslateForAttributes($type, $attributes, $scope, $sourceLocale, $targetLocale);

        echo count($list)." $type to process\n";

        foreach ($list as $productIdentifier => $product) {

            $toTranslate = [];

            foreach($attributes as $keyAttr => $attribute) {
                if(isset($product['values'][$attribute])) {
                    foreach ($product['values'][$attribute] as $val) {
                        if ($val['locale'] == $sourceLocale) {
                            $toTranslate[] = $val['data'];
                            break;
                        }
                    }
                } else {
                    unset($attributes[$keyAttr]);
                }
            }

            if(count($toTranslate) > 0) {
                echo $productIdentifier . ":BEFORE:". implode(';',$toTranslate) ."\n" ;

                $translated = $this->translator->translate($toTranslate, $targetLanguage);

                echo $productIdentifier . ":AFTER:". implode(';',$translated) ."\n" ;


                $updatedProducts[$productIdentifier] = [];

                $indice = 0;
                foreach($attributes as $attribute) {
                    $updatedProducts[$productIdentifier]['values'][$attribute][] = [
                        'data' => $translated[$indice],
                        'locale' => $targetLocale,
                        'scope' => $this->pimAttributes[$attribute]['scopable'] ? $scope : null,
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
            foreach($products as $productIdentifier => $values) {
                $toUpdate[]=array_merge([$idColumn=>$productIdentifier], $values);
                if(count($toUpdate) == 100){
                    $batchNum++;
                    echo "Upsert $type batch $batchNum...";
                    $this->client->{'get'.$type.'Api'}()->upsertList($toUpdate);
                    echo "OK\n";
                    $toUpdate = [];
                }
            }

            if(count($toUpdate) > 0){
                $batchNum++;
                echo "Upsert $type batch $batchNum...";
                $this->client->{'get'.$type.'Api'}()->upsertList($toUpdate);
                echo "OK\n";
            }
        } catch (UnprocessableEntityHttpException $e) {
            $httpCode = $e->getCode();
            $errorMessage = $e->getMessage();
            echo 'Error '.$httpCode.' : '.$errorMessage;
            foreach ($e->getResponseErrors() as $error) {
                // do your stuff with the error
                echo $error['property'];
                echo $error['message']."\n";
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

    }
}

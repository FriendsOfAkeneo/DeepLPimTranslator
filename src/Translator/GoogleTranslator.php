<?php


namespace App\Translator;


use Google\Cloud\Translate\V3\TranslationServiceClient;

class GoogleTranslator
{
    private $client;

    public function __construct()
    {
        $this->client = new TranslationServiceClient();
    }

    public function translate($values = [], $localeTo)
    {
        $response = $this->client->translateText(
            $values,
            $localeTo,
            TranslationServiceClient::locationName('akecld-akeneo-presales-team', 'global')
        );

        $translated=[];
        foreach ($response->getTranslations() as $key => $translation) {
            $translated[] = $translation->getTranslatedText();
        }

        return $translated;
    }
}

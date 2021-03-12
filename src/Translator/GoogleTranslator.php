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
        $credentialfile = file_get_contents(getenv('GOOGLE_APPLICATION_CREDENTIALS'));
        $jsonCredentials = json_decode($credentialfile);
        $response = $this->client->translateText(
            $values,
            $localeTo,
            TranslationServiceClient::locationName($jsonCredentials->project_id, 'global')
        );

        $translated=[];
        foreach ($response->getTranslations() as $key => $translation) {
            $translated[] = $translation->getTranslatedText();
        }

        return $translated;
    }
}

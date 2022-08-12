<?php


namespace App\Translator;



use DeepL\Translator;

class DeeplTranslator
{
    private $client;

    public function __construct()
    {
        $this->client = new Translator($_SERVER['DEEPL_API_KEY']);
    }

    public function translate(array $values, $localeFrom, $localeTo)
    {
        $translations = $this->client->translateText(
            $values,
            $localeFrom,
            $localeTo
        );

        $translated=[];
        foreach ($translations as $translation) {
            $translated[] = $translation->text;
        }

        return $translated;
    }
}

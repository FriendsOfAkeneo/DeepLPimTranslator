<?php


namespace App\Translator;


use BabyMarkt\DeepL\DeepL;

class DeeplTranslator
{
    private $client;

    public function __construct()
    {
        $this->client = new DeepL($_SERVER['DEEPL_API_KEY']);
    }

    public function translate($values = [], $localeFrom, $localeTo)
    {
        $translations = $this->client->translate(
            $values,
            $localeFrom,
            $localeTo
        );

        $translated=[];
        foreach ($translations as $translation) {
            $translated[] = $translation['text'];
        }

        return $translated;
    }
}

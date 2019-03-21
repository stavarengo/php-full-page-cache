<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 19/03/19
 * Time: 13:11
 */

namespace Sta\FullPageCache\ContentNormalizerOfHeadersThatVary;

class AcceptLanguage implements ContentNormalizerOfHeadersThatVaryInterface
{
    /**
     * @var string[]
     */
    protected $listOfLanguagesYouSupport = [];

    public function __construct(array $listOfLanguagesYouSupport)
    {
        $this->listOfLanguagesYouSupport = $listOfLanguagesYouSupport;
    }

    public function canNormalizeContentsFrom(string $lowerCaseHeaderName): bool
    {
        return $lowerCaseHeaderName == 'accept-language';
    }

    public function normalize(string $headerValue, string $lowerCaseHeaderName): string
    {
        $acceptLanguageList = array_filter(
            array_map(
                function (string $acceptLanguageItem) {
                    $acceptLanguageItem = trim($acceptLanguageItem);
                    $acceptLanguageItem = preg_replace('~;.*$~', '', $acceptLanguageItem);
                    $acceptLanguageItem = preg_replace('~(..)_(..)~', '$1-$2', $acceptLanguageItem);

                    return $acceptLanguageItem;
                },
                explode(',', strtolower($headerValue))
            )
        );

        $resultList = [];
        foreach ($acceptLanguageList as $key => $acceptLanguage) {
            foreach ($this->listOfLanguagesYouSupport as $supportedLanguage) {
                if (strtolower($supportedLanguage) == $acceptLanguage) {
                    $resultList[] = $acceptLanguage;
                    unset($acceptLanguage[$key]);
                    continue;
                }
            }
        }

        $resultList = array_unique($resultList);
        sort($resultList);

        return implode(',', $resultList);
    }
}
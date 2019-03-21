<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 19/03/19
 * Time: 13:11
 */

namespace Sta\FullPageCache\ContentNormalizerOfHeadersThatVary;


class AcceptEncoding implements ContentNormalizerOfHeadersThatVaryInterface
{
    /**
     * @var string[]
     */
    private $listOfEncodesYouSupport = [];

    public function __construct(array $listOfEncodesYouSupport)
    {
        $this->listOfEncodesYouSupport = $listOfEncodesYouSupport;
    }

    public function canNormalizeContentsFrom(string $lowerCaseHeaderName): bool
    {
        return $lowerCaseHeaderName == 'accept-encoding';
    }

    public function normalize(string $headerValue, string $lowerCaseHeaderName): string
    {
        $acceptEncodingList = array_filter(array_map('trim', explode(',', strtolower($headerValue))));
        $resultList = [];

        foreach ($acceptEncodingList as $key => $acceptEncoding) {
            foreach ($this->listOfEncodesYouSupport as $supportedEncode) {
                if (strtolower($supportedEncode) == $acceptEncoding) {
                    $resultList[] = $acceptEncoding;
                    unset($acceptEncodingList[$key]);
                    continue;
                }
            }
        }

        $resultList = array_unique($resultList);
        sort($resultList);

        return implode(',', $resultList);
    }
}
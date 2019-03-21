<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 19/03/19
 * Time: 13:08
 */

namespace Sta\FullPageCache\ContentNormalizerOfHeadersThatVary;

interface ContentNormalizerOfHeadersThatVaryInterface
{
    public function canNormalizeContentsFrom(string $lowerCaseHeaderName): bool;

    public function normalize(string $headerValue, string $lowerCaseHeaderName): string;
}
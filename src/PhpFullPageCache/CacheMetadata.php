<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 18/03/19
 * Time: 15:12
 */

namespace Sta\FullPageCache;


class CacheMetadata
{
    /**
     * @var string[]
     */
    protected $vary;

    /**
     * CacheMetadata constructor.
     * @param string[] $vary
     */
    public function __construct(array $vary)
    {
        $this->vary = $vary;
    }

    /**
     * @return string[]
     */
    public function getVary(): array
    {
        return $this->vary;
    }

    /**
     * @param string[] $vary
     * @return CacheMetadata
     */
    public function setVary(array $vary): CacheMetadata
    {
        $this->vary = $vary;
        return $this;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 18/03/19
 * Time: 15:12
 */

namespace Sta\FullPageCache;


class RequestMetadata implements \Serializable
{
    const SERIALIZE_VERSION_ENTRY_NAME = ':v:';
    /**
     * @var string[]
     */
    protected $vary = [];

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
     * @return RequestMetadata
     */
    public function setVary(array $vary): RequestMetadata
    {
        $this->vary = $vary;
        return $this;
    }

    /**
     * String representation of object
     * @link https://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        $me = get_object_vars($this);
        $me[self::SERIALIZE_VERSION_ENTRY_NAME] = 1;

        return serialize($me);
    }

    /**
     * Constructs the object
     * @link https://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        $me = unserialize($serialized);

        foreach ($me as $attr => $value) {
            if ($attr == self::SERIALIZE_VERSION_ENTRY_NAME) {
                continue;
            }
            $this->$attr = $value;
        }
    }
}
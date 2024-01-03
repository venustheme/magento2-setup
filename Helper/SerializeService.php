<?php
namespace Ves\Setup\Helper;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ObjectManager;

class SerializeService
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private static $serializer;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private static $jsoner;

    private static $isOldVersion = null;

    public static function init()
    {
        if (self::isOldVersion()) {
            self::$serializer = \Zend\Serializer\Serializer::factory('PhpSerialize');
            self::$jsoner     = \Zend\Serializer\Serializer::factory('Json');
        } else {
            /** @var \Magento\Framework\Serialize\Serializer\Serialize $serializer */
            self::$serializer = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Serialize\Serializer\Serialize::class
            );
            self::$jsoner     = self::getObjectManager()
                ->get(\Magento\Framework\Serialize\Serializer\Json::class);
        }
    }

    /**
     * @return ObjectManager
     */
    public static function getObjectManager()
    {
        return ObjectManager::getInstance();
    }

    /**
     * @return bool
     */
    public static function isOldVersion()
    {
        if (self::$isOldVersion === null) {
            self::$isOldVersion = false;
        }

        return self::$isOldVersion;
    }

    /**
     * @param array|string $data
     *
     * @return string|null
     */
    public static function encode($data)
    {
        self::init();

        $serialized = true;

        try {
            $result = self::$jsoner->serialize($data);
        } catch (\Exception $e) {
            $serialized = false;
        }

        if (!$serialized) {
            try {
                $result = self::$serializer->serialize($data);
            } catch (\Exception $e) {
                $result = null;
            }
        }

        return $result;
    }

    /**
     * @param string $string
     *
     * @return array

     */
    public static function decode($string)
    {
        if (!is_string($string)) {
            return null;
        }

        self::init();

        $unserialized = true;

        try {
            new \ReflectionClass('Zend\Json\Json');
        } catch (\Exception $e) {}

        // we use this because json_decode does not work correct for php5
        if (class_exists('Zend\Json\Json', false)) {
            $useDecoder                                = \Zend\Json\Json::$useBuiltinEncoderDecoder;
            \Zend\Json\Json::$useBuiltinEncoderDecoder = true;
        }

        try {
            $result = self::$jsoner->unserialize($string);
        } catch (\Exception $e) {
            $unserialized = false;
        }

        if (!$unserialized) {
            try {
                $result = self::$serializer->unserialize($string);
            } catch (\Exception $e) {
                $result = null;
            }
        }

        if (class_exists('Zend\Json\Json', false)) {
            \Zend\Json\Json::$useBuiltinEncoderDecoder = $useDecoder;
        }

        return $result;
    }

    /**
     * @param string|array $data
     *
     * @return string|null
     */
    public static function encodeWithNewMagento($data)
    {
        self::$isOldVersion = false;

        return self::encode($data);
    }

    /**
     * @param string|array $data
     *
     * @return string|null
     */
    public static function encodeWithOldMagento($data)
    {
        self::$isOldVersion = true;

        return self::encode($data);
    }

    /**
     * @param string|array $data
     *
     * @return array

     */
    public static function decodeWithNewMagento($data)
    {
        self::$isOldVersion = false;

        return self::decode($data);
    }

    /**
     * @param string|array $data
     *
     * @return array

     */
    public static function decodeWithOldMagento($data)
    {
        self::$isOldVersion = true;

        return self::decode($data);
    }
}

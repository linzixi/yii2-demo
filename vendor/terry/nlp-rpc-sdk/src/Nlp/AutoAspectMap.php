<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: nlp.proto

namespace Nlp;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>nlp.AutoAspectMap</code>
 */
class AutoAspectMap extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string aspect = 1;</code>
     */
    private $aspect = '';
    /**
     * Generated from protobuf field <code>string tag = 2;</code>
     */
    private $tag = '';
    /**
     * Generated from protobuf field <code>float prob = 3;</code>
     */
    private $prob = 0.0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $aspect
     *     @type string $tag
     *     @type float $prob
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Nlp::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string aspect = 1;</code>
     * @return string
     */
    public function getAspect()
    {
        return $this->aspect;
    }

    /**
     * Generated from protobuf field <code>string aspect = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setAspect($var)
    {
        GPBUtil::checkString($var, True);
        $this->aspect = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string tag = 2;</code>
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Generated from protobuf field <code>string tag = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setTag($var)
    {
        GPBUtil::checkString($var, True);
        $this->tag = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>float prob = 3;</code>
     * @return float
     */
    public function getProb()
    {
        return $this->prob;
    }

    /**
     * Generated from protobuf field <code>float prob = 3;</code>
     * @param float $var
     * @return $this
     */
    public function setProb($var)
    {
        GPBUtil::checkFloat($var);
        $this->prob = $var;

        return $this;
    }

}

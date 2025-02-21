<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: nlp.proto

namespace Nlp;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>nlp.SimRequest</code>
 */
class SimRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string query = 1;</code>
     */
    private $query = '';
    /**
     * Generated from protobuf field <code>.nlp.NlpListMapRequest haystack = 2;</code>
     */
    private $haystack = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $query
     *     @type \Nlp\NlpListMapRequest $haystack
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Nlp::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string query = 1;</code>
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Generated from protobuf field <code>string query = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setQuery($var)
    {
        GPBUtil::checkString($var, True);
        $this->query = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.nlp.NlpListMapRequest haystack = 2;</code>
     * @return \Nlp\NlpListMapRequest
     */
    public function getHaystack()
    {
        return $this->haystack;
    }

    /**
     * Generated from protobuf field <code>.nlp.NlpListMapRequest haystack = 2;</code>
     * @param \Nlp\NlpListMapRequest $var
     * @return $this
     */
    public function setHaystack($var)
    {
        GPBUtil::checkMessage($var, \Nlp\NlpListMapRequest::class);
        $this->haystack = $var;

        return $this;
    }

}


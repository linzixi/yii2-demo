<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: nlp.proto

namespace Nlp;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>nlp.NlpMapListStringReply</code>
 */
class NlpMapListStringReply extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>map<string, .nlp.NLpListStringReply> res = 1;</code>
     */
    private $res;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type array|\Google\Protobuf\Internal\MapField $res
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Nlp::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>map<string, .nlp.NLpListStringReply> res = 1;</code>
     * @return \Google\Protobuf\Internal\MapField
     */
    public function getRes()
    {
        return $this->res;
    }

    /**
     * Generated from protobuf field <code>map<string, .nlp.NLpListStringReply> res = 1;</code>
     * @param array|\Google\Protobuf\Internal\MapField $var
     * @return $this
     */
    public function setRes($var)
    {
        $arr = GPBUtil::checkMapField($var, \Google\Protobuf\Internal\GPBType::STRING, \Google\Protobuf\Internal\GPBType::MESSAGE, \Nlp\NLpListStringReply::class);
        $this->res = $arr;

        return $this;
    }

}


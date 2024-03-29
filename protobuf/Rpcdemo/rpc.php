<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: rpcdata.proto

namespace Rpcdemo;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>rpcdemo.rpc</code>
 */
class rpc extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string req_class = 2;</code>
     */
    private $req_class = '';
    /**
     * Generated from protobuf field <code>string req_action = 3;</code>
     */
    private $req_action = '';
    /**
     * Generated from protobuf field <code>string req_params = 4;</code>
     */
    private $req_params = '';
    /**
     * Generated from protobuf field <code>string res_data = 5;</code>
     */
    private $res_data = '';
    /**
     * Generated from protobuf field <code>int32 res_code = 6;</code>
     */
    private $res_code = 0;
    /**
     * Generated from protobuf field <code>int32 res_time = 7;</code>
     */
    private $res_time = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $req_class
     *     @type string $req_action
     *     @type string $req_params
     *     @type string $res_data
     *     @type int $res_code
     *     @type int $res_time
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Rpcdata::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string req_class = 2;</code>
     * @return string
     */
    public function getReqClass()
    {
        return $this->req_class;
    }

    /**
     * Generated from protobuf field <code>string req_class = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setReqClass($var)
    {
        GPBUtil::checkString($var, True);
        $this->req_class = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string req_action = 3;</code>
     * @return string
     */
    public function getReqAction()
    {
        return $this->req_action;
    }

    /**
     * Generated from protobuf field <code>string req_action = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setReqAction($var)
    {
        GPBUtil::checkString($var, True);
        $this->req_action = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string req_params = 4;</code>
     * @return string
     */
    public function getReqParams()
    {
        return $this->req_params;
    }

    /**
     * Generated from protobuf field <code>string req_params = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setReqParams($var)
    {
        GPBUtil::checkString($var, True);
        $this->req_params = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string res_data = 5;</code>
     * @return string
     */
    public function getResData()
    {
        return $this->res_data;
    }

    /**
     * Generated from protobuf field <code>string res_data = 5;</code>
     * @param string $var
     * @return $this
     */
    public function setResData($var)
    {
        GPBUtil::checkString($var, True);
        $this->res_data = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>int32 res_code = 6;</code>
     * @return int
     */
    public function getResCode()
    {
        return $this->res_code;
    }

    /**
     * Generated from protobuf field <code>int32 res_code = 6;</code>
     * @param int $var
     * @return $this
     */
    public function setResCode($var)
    {
        GPBUtil::checkInt32($var);
        $this->res_code = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>int32 res_time = 7;</code>
     * @return int
     */
    public function getResTime()
    {
        return $this->res_time;
    }

    /**
     * Generated from protobuf field <code>int32 res_time = 7;</code>
     * @param int $var
     * @return $this
     */
    public function setResTime($var)
    {
        GPBUtil::checkInt32($var);
        $this->res_time = $var;

        return $this;
    }

}


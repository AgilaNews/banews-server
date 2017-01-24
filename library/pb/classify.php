<?php
// DO NOT EDIT! Generated by Protobuf-PHP protoc plugin 1.0
// Source: classify.proto
//   Date: 2017-01-23 07:44:50

namespace iface {

  class Feature extends \DrSlump\Protobuf\Message {

    /**  @var int */
    public $Index = null;
    
    /**  @var float */
    public $Value = null;
    

    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
      $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'iface.Feature');

      // OPTIONAL INT32 Index = 1
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 1;
      $f->name      = "Index";
      $f->type      = \DrSlump\Protobuf::TYPE_INT32;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      // OPTIONAL DOUBLE Value = 2
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 2;
      $f->name      = "Value";
      $f->type      = \DrSlump\Protobuf::TYPE_DOUBLE;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      foreach (self::$__extensions as $cb) {
        $descriptor->addField($cb(), true);
      }

      return $descriptor;
    }

    /**
     * Check if <Index> has a value
     *
     * @return boolean
     */
    public function hasIndex(){
      return $this->_has(1);
    }
    
    /**
     * Clear <Index> value
     *
     * @return \iface\Feature
     */
    public function clearIndex(){
      return $this->_clear(1);
    }
    
    /**
     * Get <Index> value
     *
     * @return int
     */
    public function getIndex(){
      return $this->_get(1);
    }
    
    /**
     * Set <Index> value
     *
     * @param int $value
     * @return \iface\Feature
     */
    public function setIndex( $value){
      return $this->_set(1, $value);
    }
    
    /**
     * Check if <Value> has a value
     *
     * @return boolean
     */
    public function hasValue(){
      return $this->_has(2);
    }
    
    /**
     * Clear <Value> value
     *
     * @return \iface\Feature
     */
    public function clearValue(){
      return $this->_clear(2);
    }
    
    /**
     * Get <Value> value
     *
     * @return float
     */
    public function getValue(){
      return $this->_get(2);
    }
    
    /**
     * Set <Value> value
     *
     * @param float $value
     * @return \iface\Feature
     */
    public function setValue( $value){
      return $this->_set(2, $value);
    }
  }
}

namespace iface {

  class Sample extends \DrSlump\Protobuf\Message {

    /**  @var \iface\Feature[]  */
    public $Features = array();
    

    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
      $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'iface.Sample');

      // REPEATED MESSAGE Features = 1
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 1;
      $f->name      = "Features";
      $f->type      = \DrSlump\Protobuf::TYPE_MESSAGE;
      $f->rule      = \DrSlump\Protobuf::RULE_REPEATED;
      $f->reference = '\iface\Feature';
      $descriptor->addField($f);

      foreach (self::$__extensions as $cb) {
        $descriptor->addField($cb(), true);
      }

      return $descriptor;
    }

    /**
     * Check if <Features> has a value
     *
     * @return boolean
     */
    public function hasFeatures(){
      return $this->_has(1);
    }
    
    /**
     * Clear <Features> value
     *
     * @return \iface\Sample
     */
    public function clearFeatures(){
      return $this->_clear(1);
    }
    
    /**
     * Get <Features> value
     *
     * @param int $idx
     * @return \iface\Feature
     */
    public function getFeatures($idx = NULL){
      return $this->_get(1, $idx);
    }
    
    /**
     * Set <Features> value
     *
     * @param \iface\Feature $value
     * @return \iface\Sample
     */
    public function setFeatures(\iface\Feature $value, $idx = NULL){
      return $this->_set(1, $value, $idx);
    }
    
    /**
     * Get all elements of <Features>
     *
     * @return \iface\Feature[]
     */
    public function getFeaturesList(){
     return $this->_get(1);
    }
    
    /**
     * Add a new element to <Features>
     *
     * @param \iface\Feature $value
     * @return \iface\Sample
     */
    public function addFeatures(\iface\Feature $value){
     return $this->_add(1, $value);
    }
  }
}

namespace iface {

  class PredictRequest extends \DrSlump\Protobuf\Message {

    /**  @var \iface\Sample[]  */
    public $Samples = array();
    

    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
      $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'iface.PredictRequest');

      // REPEATED MESSAGE Samples = 1
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 1;
      $f->name      = "Samples";
      $f->type      = \DrSlump\Protobuf::TYPE_MESSAGE;
      $f->rule      = \DrSlump\Protobuf::RULE_REPEATED;
      $f->reference = '\iface\Sample';
      $descriptor->addField($f);

      foreach (self::$__extensions as $cb) {
        $descriptor->addField($cb(), true);
      }

      return $descriptor;
    }

    /**
     * Check if <Samples> has a value
     *
     * @return boolean
     */
    public function hasSamples(){
      return $this->_has(1);
    }
    
    /**
     * Clear <Samples> value
     *
     * @return \iface\PredictRequest
     */
    public function clearSamples(){
      return $this->_clear(1);
    }
    
    /**
     * Get <Samples> value
     *
     * @param int $idx
     * @return \iface\Sample
     */
    public function getSamples($idx = NULL){
      return $this->_get(1, $idx);
    }
    
    /**
     * Set <Samples> value
     *
     * @param \iface\Sample $value
     * @return \iface\PredictRequest
     */
    public function setSamples(\iface\Sample $value, $idx = NULL){
      return $this->_set(1, $value, $idx);
    }
    
    /**
     * Get all elements of <Samples>
     *
     * @return \iface\Sample[]
     */
    public function getSamplesList(){
     return $this->_get(1);
    }
    
    /**
     * Add a new element to <Samples>
     *
     * @param \iface\Sample $value
     * @return \iface\PredictRequest
     */
    public function addSamples(\iface\Sample $value){
     return $this->_add(1, $value);
    }
  }
}

namespace iface {

  class ProbabilityOfSample extends \DrSlump\Protobuf\Message {

    /**  @var float */
    public $Probs = null;
    

    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
      $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'iface.ProbabilityOfSample');

      // OPTIONAL DOUBLE Probs = 1
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 1;
      $f->name      = "Probs";
      $f->type      = \DrSlump\Protobuf::TYPE_DOUBLE;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      foreach (self::$__extensions as $cb) {
        $descriptor->addField($cb(), true);
      }

      return $descriptor;
    }

    /**
     * Check if <Probs> has a value
     *
     * @return boolean
     */
    public function hasProbs(){
      return $this->_has(1);
    }
    
    /**
     * Clear <Probs> value
     *
     * @return \iface\ProbabilityOfSample
     */
    public function clearProbs(){
      return $this->_clear(1);
    }
    
    /**
     * Get <Probs> value
     *
     * @return float
     */
    public function getProbs(){
      return $this->_get(1);
    }
    
    /**
     * Set <Probs> value
     *
     * @param float $value
     * @return \iface\ProbabilityOfSample
     */
    public function setProbs( $value){
      return $this->_set(1, $value);
    }
  }
}

namespace iface {

  class PredictResponse extends \DrSlump\Protobuf\Message {

    /**  @var \iface\ProbabilityOfSample[]  */
    public $Samples = array();
    

    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
      $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'iface.PredictResponse');

      // REPEATED MESSAGE Samples = 1
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 1;
      $f->name      = "Samples";
      $f->type      = \DrSlump\Protobuf::TYPE_MESSAGE;
      $f->rule      = \DrSlump\Protobuf::RULE_REPEATED;
      $f->reference = '\iface\ProbabilityOfSample';
      $descriptor->addField($f);

      foreach (self::$__extensions as $cb) {
        $descriptor->addField($cb(), true);
      }

      return $descriptor;
    }

    /**
     * Check if <Samples> has a value
     *
     * @return boolean
     */
    public function hasSamples(){
      return $this->_has(1);
    }
    
    /**
     * Clear <Samples> value
     *
     * @return \iface\PredictResponse
     */
    public function clearSamples(){
      return $this->_clear(1);
    }
    
    /**
     * Get <Samples> value
     *
     * @param int $idx
     * @return \iface\ProbabilityOfSample
     */
    public function getSamples($idx = NULL){
      return $this->_get(1, $idx);
    }
    
    /**
     * Set <Samples> value
     *
     * @param \iface\ProbabilityOfSample $value
     * @return \iface\PredictResponse
     */
    public function setSamples(\iface\ProbabilityOfSample $value, $idx = NULL){
      return $this->_set(1, $value, $idx);
    }
    
    /**
     * Get all elements of <Samples>
     *
     * @return \iface\ProbabilityOfSample[]
     */
    public function getSamplesList(){
     return $this->_get(1);
    }
    
    /**
     * Add a new element to <Samples>
     *
     * @param \iface\ProbabilityOfSample $value
     * @return \iface\PredictResponse
     */
    public function addSamples(\iface\ProbabilityOfSample $value){
     return $this->_add(1, $value);
    }
  }
}

namespace iface {

  class ClassificationServiceClient extends \Grpc\BaseStub {

    public function __construct($hostname, $opts, $channel = null) {
      parent::__construct($hostname, $opts, $channel);
    }
    /**
     * @param iface\PredictRequest $input
     */
    public function Predict(\iface\PredictRequest $argument, $metadata = array(), $options = array()) {
      return $this->_simpleRequest('/iface.ClassificationService/Predict', $argument, '\iface\PredictResponse::deserialize', $metadata, $options);
    }
  }
}

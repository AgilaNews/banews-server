<?php
// DO NOT EDIT! Generated by Protobuf-PHP protoc plugin 1.0
// Source: github.com/AgilaNews/goshare/general/general.proto
//   Date: 2016-12-22 07:36:46

namespace ipeninsula {

  class EmptyMessage extends \DrSlump\Protobuf\Message {


    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
      $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'ipeninsula.EmptyMessage');

      foreach (self::$__extensions as $cb) {
        $descriptor->addField($cb(), true);
      }

      return $descriptor;
    }
  }
}


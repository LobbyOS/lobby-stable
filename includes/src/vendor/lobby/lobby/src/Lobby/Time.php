<?php
/**
 * Time is stored in Lobby DB as UTC
 * This file has the class that handles the timezone conversion etc.
 */
namespace Lobby;

use Lobby\DB;

/**
 * Handle time conversions and date outputs
 */
class Time {

  /**
   * The user's timezone
   */
  private static $tz = "UTC";

  /**
   * Sets the PHP & database timezone as UTC
   */
  public static function __constructStatic(){
    /**
     * Default timezone of Lobby is UTC
     */
    date_default_timezone_set("UTC");
    if(DB::getType() === "mysql"){
      $sql = DB::getDBH()->prepare("SET time_zone = ?;");
      $sql->execute(array('+00:00'));
    }
    self::loadConfig();
  }

  /**
   * Load the user's timezone config
   */
  public static function loadConfig(){
    $tz = DB::getOption("lobby_timezone");
    if($tz !== null){
      self::$tz = $tz;
    }
  }

  /**
   * Return the current instant's timestamp
   * @param string $format The format of timestamp to be returned
   * @return string Timestamp
   */
  public static function now($format = "Y-m-d H:i:s"){
    $date = new \DateTime("now", new \DateTimeZone(self::$tz));
    return $date->format($format);
  }

  /**
   * Convert a UTC date to user's timezone
   * @param string $date UTC date that is compatible with \DateTime()
   * @param string $format The format of timestamp to be returned
   * @return Timestamp
   */
  public static function date($date, $format = "Y-m-d H:i:s"){
    $date = new \DateTime($date, new \DateTimeZone("UTC"));
    $date->setTimeZone(new \DateTimeZone(self::$tz));
    return $date->format($format);
  }

  /**
   * Convert offsets to timezone
   * @param string $offset Offset like "GMT+5:30" etc.
   * @return string Timezone of offest
   */
  public static function getTimezone($offset){
    foreach (\DateTimeZone::listIdentifiers(\DateTimeZone::ALL) as $timezone) {
      $datetime = new \DateTime("now", new \DateTimeZone($timezone));

      // find a timezone matching the offset and abbreviation
      if ($offset == $datetime->format('P')) {
        return $timezone;
      }
    }
  }

  /**
   * Get timeago value from timestamp
   * @param int $time Timestamp
   * @return string Values like "about 2 seconds ago"
   */
  public static function getTimeago($time) {
    $estimate_time = time() - $time;
    if( $estimate_time < 1 ){
        return 'less than 1 second ago';
    }

    $condition = array(
      12 * 30 * 24 * 60 * 60  =>  'year',
      30 * 24 * 60 * 60       =>  'month',
      24 * 60 * 60            =>  'day',
      60 * 60                 =>  'hour',
      60                      =>  'minute',
      1                       =>  'second'
    );

    foreach( $condition as $secs => $str ){
      $d = $estimate_time / $secs;
      if( $d >= 1 ){
        $r = round( $d );
        return 'about ' . $r . ' ' . $str . ( $r > 1 ? 's' : '' ) . ' ago';
      }
    }
  }

}

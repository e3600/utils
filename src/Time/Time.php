<?php

namespace JyUtils\Time;

/**
 * Class Time 时间
 */
class Time
{
  /**
   * 取时间范围 - 年
   *
   * @param int $last      -1表示往上一年，-2上两年，以此类推
   * @param int $timestamp 留空为当时间戳
   * @return array
   */
  public static function getRangeYear($last = 0, $timestamp = 0)
  {
    $timestamp = $timestamp ? $timestamp : time();
    if ($last < 0) {
      $timestamp = strtotime("{$last} year", $timestamp);
    }
    return [
      'number' => date('Y', $timestamp),
      'start'  => strtotime(date('Y-01-01 00:00:00', $timestamp)),
      'end'    => strtotime(date("Y-12-31 23:59:59", $timestamp)),
    ];
  }
  
  /**
   * 取时间范围 - 某年所有季度的时间范围
   *
   * @param int $timestamp 留空为当时间戳
   * @return array[]
   */
  public static function getRangeYearAllQuarter($timestamp = 0)
  {
    $timestamp = $timestamp ? $timestamp : time();
    return [
      self::get_appoint_quarter_by_time(strtotime(date('Y-01-01', $timestamp))),
      self::get_appoint_quarter_by_time(strtotime(date('Y-04-01', $timestamp))),
      self::get_appoint_quarter_by_time(strtotime(date('Y-07-01', $timestamp))),
      self::get_appoint_quarter_by_time(strtotime(date('Y-10-01', $timestamp))),
    ];
  }
  
  /**
   * 取指定季度的时间范围
   *
   * @param int $timestamp 留空为当时间戳
   * @param int $quarter   留空为第1季度，1=第一季度，2=第二季度，以此类推
   * @return array
   */
  public static function getAssignRangeQuarter($timestamp = 0, $quarter = 1)
  {
    if (!in_array($quarter, [1, 2, 3, 4])) {
      $quarter = 1;
    }
    $res = self::getRangeYearAllQuarter($timestamp);
    return $res[($quarter - 1)];
  }
  
  /**
   * 取指定月份的时间范围
   *
   * @param int $timestamp 留空为当时间戳
   * @param int $month     留空为第1个月，1=第1个月，2=第2个月，以此类推
   * @return array
   */
  public static function getAssignRangeMonth($timestamp = 0, $month = 1)
  {
    if (!in_array($month, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12])) {
      $month = 1;
    }
    $res = self::getRangeYearAllMonth($timestamp);
    return $res[$month];
  }
  
  /**
   * 取时间范围 - 某年所有月份的时间范围
   *
   * @param int $timestamp
   * @return array
   */
  public static function getRangeYearAllMonth($timestamp = 0)
  {
    $timestamp = $timestamp ? $timestamp : time();
    $months    = [];
    $start     = strtotime(date('Y-01-01 00:00:00', $timestamp));
    for ($i = 0; $i < 12; $i++) {
      $number          = $i + 1;
      $months[$number] = [
        'number' => $number,
        'start'  => strtotime("+$i month", $start),
      ];
      
      $days                   = idate('t', $months[$number]['start']);
      $months[$number]['end'] = strtotime(date("Y-m-{$days} 23:59:59", $months[$number]['start']));
    }
    return $months;
  }
  
  /**
   * 取时间范围 - 季度
   *
   * @param int $last      -1表示往上一季度，-2上两季度，以此类推
   * @param int $timestamp 留空为当时间戳
   * @return array
   */
  public static function getRangeQuarter($last = 0, $timestamp = 0)
  {
    $timestamp = $timestamp ? $timestamp : time();
    if ($last < 0) {
      $number    = -$last * 3;
      $timestamp = strtotime("-{$number} month", $timestamp);
    }
    $res = self::get_appoint_quarter_by_time($timestamp);
    return [
      'number' => $res['number'],
      'start'  => $res['start'],
      'end'    => $res['end'],
    ];
  }
  
  /**
   * 取时间范围 - 月
   *
   * @param int $last      -1表示往上一月，-2上两月，以此类推
   * @param int $timestamp 留空为当时间戳
   * @return array
   */
  public static function getRangeMonth($last = 0, $timestamp = 0)
  {
    $timestamp = $timestamp ? $timestamp : time();
    if ($last < 0) {
      $timestamp = strtotime("{$last} month", $timestamp);
    }
    $days = idate('t', $timestamp);
    return [
      'number' => date('m', $timestamp),
      'start'  => strtotime(date('Y-m-01 00:00:00', $timestamp)),
      'end'    => strtotime(date("Y-m-{$days} 23:59:59", $timestamp)),
    ];
  }
  
  /**
   * 取时间范围 - 周
   *
   * @param int $last      -1表示往上一周，-2上两周，以此类推
   * @param int $timestamp 留空为当时间戳
   * @return array
   */
  public static function getRangeWeek($last = 0, $timestamp = 0)
  {
    $timestamp = $timestamp ? $timestamp : time();
    // 星期中的第几天（星期天是 0）
    $w = $number = idate('w', $timestamp);
    $w = $w == 0 ? 6 : $w - 1;
    
    if ($last < 0) {
      $timestamp = strtotime("{$last} week", $timestamp);
    }
    $data = [
      'number' => $number,
      'start'  => strtotime(date('Y-m-d 00:00:00', $timestamp - $w * 86400)),
    ];
    
    $data['end'] = strtotime(date('Y-m-d 23:59:59', $data['start'] + 6 * 86400));
    return $data;
  }
  
  /**
   * 取时间范围 - 天
   *
   * @param int $last      -1表示往上一天，-2上两天，以此类推
   * @param int $timestamp 留空为当时间戳
   * @return array
   */
  public static function getRangeDay($last = 0, $timestamp = 0)
  {
    $timestamp = $timestamp ? $timestamp : time();
    if ($last < 0) {
      $timestamp = strtotime("{$last} day", $timestamp);
    }
    return [
      'number' => date('d', $timestamp),
      'start'  => strtotime(date('Y-m-d 00:00:00', $timestamp)),
      'end'    => strtotime(date("Y-m-d 23:59:59", $timestamp)),
    ];
  }
  
  /**
   * 以当前时间为准，增加N年，返回时间戳
   *
   * @param int $value
   * @return false|int
   */
  public static function addYear($value = 1)
  {
    return strtotime("+{$value} year");
  }
  
  public static function subYear($value = 1)
  {
    return strtotime("-{$value} year");
  }
  
  public static function addMonth($value = 1)
  {
    return strtotime("+{$value} month");
  }
  
  public static function subMonth($value = 1)
  {
    return strtotime("-{$value} month");
  }
  
  public static function addDay($value = 1)
  {
    return strtotime("+{$value} day");
  }
  
  public static function subDay($value = 1)
  {
    return strtotime("-{$value} day");
  }
  
  public static function addHour($value = 1)
  {
    return strtotime("+{$value} hour");
  }
  
  public static function subHour($value = 1)
  {
    return strtotime("-{$value} hour");
  }
  
  public static function addMinute($value = 1)
  {
    return strtotime("+{$value} minute");
  }
  
  public static function subMinute($value = 1)
  {
    return strtotime("-{$value} minute");
  }
  
  public static function addSecond($value = 1)
  {
    return strtotime("+{$value} second");
  }
  
  public static function subSecond($value = 1)
  {
    return strtotime("-{$value} second");
  }
  
  /**
   * 计算两个日期相隔多少年，多少月，多少天
   *
   * @param   $date1        格式如：2011-11-5，或时间戳
   * @param   $date2        格式如：2012-12-01，或时间戳
   * @param   $isGetTime    返回是否取出时分秒
   * @return  array array('年','月','日');
   */
  public static function diffDate($date1, $date2, $isGetTime = false)
  {
    if (is_numeric($date1)) {
      $date1 = date_create(date("Y-m-d", $date1));
      $date2 = date_create(date("Y-m-d", $date2));
    } else {
      $date1 = date_create($date1);
      $date2 = date_create($date2);
    }
    
    // 取到2个时间的间隔
    $diff = date_diff($date1, $date2);
    return [
      'year'   => $diff->y,
      'month'  => $diff->m,
      'day'    => $diff->d,
      'hour'   => $diff->h,
      'minute' => $diff->i,
      'second' => $diff->s,
      'days'   => $diff->days,
    ];
    return $diff;
  }
  
  /**
   * 取日/周/月/季/年的时间范围
   *
   * @param int  $type      1 = 今天/昨天
   *                        7 = 本周/上周
   *                        30 = 本月/上月
   *                        90 = 本季/上季
   *                        365 = 今年/去年
   * @param int  $timestamp 指定时间戳
   * @param bool $returnStr 是否返回字符格式的时间
   * @return array
   */
  public static function getRange($type = 1, $timestamp = 0, $returnStr = false)
  {
    $timestamp = $timestamp ? $timestamp : time();
    
    /**
     * 获取「今天」「昨天」的时间戳
     *
     * @param $timestamp
     * @return array
     */
    $get_day = function ($timestamp) {
      $data = [];
      // 今天
      $data['start'] = strtotime(date('Y-m-d 00:00:00', $timestamp));
      $data['end']   = strtotime(date('Y-m-d 23:59:59', $timestamp));
      
      // 昨天
      $time                  = $data['start'] - 1;
      $data['last']['start'] = strtotime(date('Y-m-d 00:00:00', $time));
      $data['last']['end']   = $data['start'] - 1;
      return $data;
    };
    
    /**
     * 获取「本周」「上周」的时间戳
     *
     * @param $timestamp
     * @return array
     */
    $get_week = function ($timestamp) {
      // 星期中的第几天（星期天是 0）
      $w = idate('w', $timestamp);
      if ($w == 0) {
        $w = 6;
      } else {
        $w--;
      }
      $data = [];
      // 本周开始 - 结束
      $data['start'] = strtotime(date('Y-m-d 00:00:00', $timestamp - $w * 86400));
      $data['end']   = strtotime(date('Y-m-d 23:59:59', $timestamp));
      
      // 上周开始 - 结束
      $time                  = $data['start'] - 86400 * 7;
      $data['last']['start'] = strtotime(date('Y-m-d 00:00:00', $time));
      $data['last']['end']   = $data['start'] - 1;
      return $data;
    };
    
    /**
     * 获取「本月」「上月」的时间戳
     *
     * @param $timestamp
     * @return array
     */
    $get_month = function ($timestamp) {
      $data = [];
      // 本月开始 - 结束
      $data['month'] = date('m', $timestamp);
      $data['start'] = strtotime(date('Y-m-01 00:00:00', $timestamp));
      $data['end']   = strtotime(date("Y-m-d 23:59:59", $timestamp));
      
      // 上月开始 - 结束
      $time                  = strtotime('-1 month', $timestamp);
      $data['last']['month'] = date('m', $time);
      $data['last']['start'] = strtotime(date('Y-m-01 00:00:00', $time));
      $data['last']['end']   = $data['start'] - 1;
      return $data;
    };
    
    /**
     * 获取「本季」「上季」的时间戳
     *
     * @param $timestamp
     * @return array
     */
    $get_quarter = function ($timestamp) {
      $data = [];
      
      // 本季开始 - 结束
      $res             = self::get_appoint_quarter_by_time($timestamp);
      $data['quarter'] = $res['number'];
      $data['start']   = $res['start'];
      $data['end']     = $res['end'];
      
      // 上季开始 - 结束
      $res                     = self::get_appoint_quarter_by_time(strtotime('-3 month', $timestamp));
      $data['last']['quarter'] = $res['number'];
      $data['last']['start']   = $res['start'];
      $data['last']['end']     = $res['end'];
      return $data;
    };
    
    /**
     * 获取「今年」「去年」的时间戳
     *
     * @param $timestamp
     * @return array
     */
    $get_year = function ($timestamp) {
      $data = [];
      // 本年开始 - 结束
      $data['year']  = date('Y', $timestamp);
      $data['start'] = strtotime(date('Y-01-01 00:00:00', $timestamp));
      $data['end']   = strtotime(date("Y-m-d 23:59:59", $timestamp));
      
      // 上年开始 - 结束
      $time                  = $data['start'] - 1;
      $Y                     = date('Y', $time);  // 取年份
      $data['last']['year']  = $Y;
      $data['last']['start'] = strtotime(date("$Y-01-01 00:00:00", $time));
      $data['last']['end']   = $data['start'] - 1;
      return $data;
    };
    
    if ($type == 1) {
      $res = $get_day($timestamp);
    } else if ($type == 7) {
      $res = $get_week($timestamp);
    } else if ($type == 30) {
      $res = $get_month($timestamp);
    } else if ($type == 90) {
      $res = $get_quarter($timestamp);
    } else if ($type == 365) {
      $res = $get_year($timestamp);
    } else {
      $res = [];
    }
    
    if ($res && $returnStr) {
      $res['start_str'] = date('Y-m-d', $res['start']);
      $res['end_str']   = date('Y-m-d', $res['end']);
      
      $temp = $res['last'];
      unset($res['last']);
      $res['last']              = $temp;
      $res['last']['start_str'] = date('Y-m-d', $res['last']['start']);
      $res['last']['end_str']   = date('Y-m-d', $res['last']['end']);
    }
    return $res;
  }
  
  /**
   * 取最近时间范围
   *
   * @return array[]
   */
  public static function getRangeLately()
  {
    $timestamp = time();
    $time      = strtotime(date('Y-m-d', $timestamp));
    return [
      'day'     => [
        'start' => strtotime('-1 day', $timestamp),
        'end'   => $timestamp,
      ],
      'week'    => [
        'start' => strtotime('-7 day', $timestamp),
        'end'   => $timestamp,
      ],
      'month'   => [
        'start' => strtotime('-30 day', $time),
        'end'   => $timestamp,
      ],
      'quarter' => [
        'start' => strtotime('-90 day', $time),
        'end'   => $timestamp,
      ],
      'year'    => [
        'start' => strtotime('-364 day', $time),
        'end'   => $timestamp,
      ],
    ];
  }
  
  /**
   * 取指定时间，24小时中每个小时的范围
   *
   * @param int $typeTime 0=今天，1=昨天，或指定时间戳
   * @return array
   */
  public static function get12Hour($typeTime = 0)
  {
    $list = [];
    if ($typeTime == 1) {
      $day = date('Y-m-d', strtotime('-1 day', time()));
    } else if (strlen(trim($typeTime)) == 10) {
      $day = date('Y-m-d', $typeTime);
    } else {
      $day = date('Y-m-d', time());
    }
    for ($i = 0; $i < 24; $i++) {
      $list[] = [
        'hour'  => $i,
        'start' => strtotime($day . ' ' . $i . ':00:00'),
        'end'   => strtotime($day . ' ' . $i . ':59:59'),
      ];
    }
    return $list;
  }
  
  /**
   * 取指定时间戳，当前隶属的季度时间范围
   *
   * @param int $dateline 指定时间戳，留空默认为当前时间
   * @return array
   */
  private static function get_appoint_quarter_by_time($dateline = 0)
  {
    $dateline = $dateline ? $dateline : time();
    $quarter  = ceil((date('n', $dateline)) / 3);   // 当月是第几季度
    return [
      'number' => $quarter,
      'start'  => strtotime(date('Y-m-d H:i:s', mktime(0, 0, 0, $quarter * 3 - 3 + 1, 1, date('Y', $dateline)))),
      'end'    => strtotime(date('Y-m-d H:i:s', mktime(23, 59, 59, $quarter * 3, date('t', mktime(0, 0, 0, $quarter * 3, 1, date("Y", $dateline))), date('Y', $dateline)))),
    ];
  }
}



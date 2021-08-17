<?php

namespace JyUtils\Check;

class Check
{
  /**
   * 检查一个数值范围
   *
   * @param int   $value 被检查的值
   * @param array $range 数值范围数组(2个成员)
   * @return mixed 成功返回当前被检查的值，失败返回false
   */
  public static function range($value, $range)
  {
    $int_options = [
      "options" => [
        "min_range" => $range[0],
        "max_range" => $range[1],
      ],
    ];
    return filter_var($value, FILTER_VALIDATE_INT, $int_options);
  }
  
  /**
   * 验证email
   *
   * @param array $value 被检查的值
   * @return mixed 成功返回当前被检查的值，失败返回false
   */
  public static function email($value)
  {
    return filter_var($value, FILTER_VALIDATE_EMAIL);
  }
  
  /**
   * 验证ip
   *
   * @param array $value  被检查的值
   * @param array $ipType 被检查的IP类型，默认兼容ipv4和ipv6，4=只要ipv4，6=只要ipv6
   * @return mixed 成功返回当前被检查的值，失败返回false
   */
  public static function ip($value, $ipType = 0)
  {
    if ($ipType == 4) {
      return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    } else if ($ipType == 6) {
      return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    } else {
      return filter_var($value, FILTER_VALIDATE_IP);
    }
  }
  
  /**
   * 验证mobile
   *
   * @param array $value 被检查的值
   * @return mixed 成功返回当前被检查的值，失败返回false
   */
  public static function mobile($value)
  {
    if (!preg_match("/^1[345789]\d{9}$/", $value, $res)) {
      return false;
    }
    return $res[0];
  }
  
    /**
   * 验证url
   *
   * @param array $value 被检查的值
   * @return mixed 成功返回当前被检查的值，失败返回false
   */
  public static function url($value)
  {
    return filter_var($value, FILTER_VALIDATE_URL);
  }
}

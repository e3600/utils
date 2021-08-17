<?php

namespace JyUtils\Request;

use DB;
use JyUtils\Str\Str;

/**
 * 提供给Function/Security.php文件使用
 */
class Request
{
  /**
   * 取请求参数
   *
   * @param string|array $name    如：tid(强转整数，tid:int)
   * @param null         $default 如果值不存在，返回默认值(name非数组时有效)
   * @return array|int|mixed|string
   */
  public static function get($name, $default = null)
  {
    $args      = array_merge($_GET, $_POST);
    $is_filter = defined('IS_FILTER'); // 避免重复过虑
    if (is_array($name)) {
      $res = [];
      foreach ($name as $v) {
        [$key, $type] = explode(":", $v);
        if (isset($args[$key])) {
          if ($type == 'int') {
            $res[$key] = intval($args[$key]);
          } else if ($is_filter) {
            $res[$key] = $args[$key];
          } else {
            $res[$key] = self::quote($args[$key]);
          }
        }
      }
      return self::trim($res, "'");
      
    } else {
      if (strpos($name, ':')){
        [$key, $type] = explode(":", $name);
      }else{
        $type = '';
        $key = $name;
      }
      if ($type == 'int') {
        return isset($args[$key]) ? intval($args[$key]) : $default;
      } else if ($is_filter) {
        return isset($args[$key]) ? self::trim($args[$key], "'") : $default;
      } else {
        return isset($args[$key]) ? self::quote($args[$key]) : $default;
      }
    }
  }
  
  /**
   * 兼容非Dsicuz环境
   *
   * @param mixed $str
   * @param false $noarray
   * @return array|float|int|string
   */
  private static function quote($str, $noarray = false)
  {
    // Discuz环境
    if (method_exists('DB', 'quote')) {
      return self::trim(self::quote($args[$key], $noarray), "'");
    }
    
    if (is_string($str)) {
      return addslashes($str);
    }
    
    if (is_int($str) or is_float($str)) {
      return $str;
    }
    
    if (is_array($str)) {
      if ($noarray === false) {
        foreach ($str as &$v) {
          $v = self::quote($v, true);
        }
        return $str;
      } else {
        return '\'\'';
      }
    }
    
    if (is_bool($str)) {
      return $str ? '1' : '0';
    }
    
    return '\'\'';
  }
  
  /**
   * 获取全部字段
   *
   * @param null|array|string $except 排除字段(排除不获取的字段)
   * @return array|string
   */
  public static function all($except = null)
  {
    // 处理排除字段
    $args = self::except(array_merge($_GET, $_POST), $except);
    
    // 避免重复过虑
    if (defined('IS_FILTER')) {
      return self::trim($args, "'");
    } else {
      // 过虑字段安全
      return self::trim(self::quote($args), "'");
    }
  }
  
  /**
   * 过滤数组字段
   *
   * @param array $Array
   * @param null  $except
   * @param bool  $isTrim
   * @return mixed
   */
  public static function filter(array $Array, $except = null, $isTrim = true)
  {
    if (is_null($except)) {
      foreach ($Array as $key => &$v) {
        if (is_array($v)) {
          $v = self::filter($Array[$key], $except, $isTrim);
        } else {
          $v = $isTrim ? self::quote(trim($v)) : self::quote($v);
          $v = str_replace('\n', "\n", $v);
        }
      }
    } else {
      foreach ($Array as $key => &$v) {
        if (!in_array($key, $except)) {
          $v = $isTrim ? self::quote(trim($v)) : self::quote($v);
        }
        $v = str_replace('\n', "\n", $v);
      }
    }
    return self::trim($Array, "'");
  }
  
  /**
   * 从请求中取回头部信息
   *
   * @param $key
   * @return mixed
   */
  public static function head($key)
  {
    $key = str_replace('-', '_', $key);
    $key = Str::start(strtoupper($key), 'HTTP_');
    return filter_input(INPUT_SERVER, $key, FILTER_SANITIZE_MAGIC_QUOTES);
  }
  
  /**
   * 从`$_SERVER`中取信息
   *
   * @param $key
   * @return mixed
   */
  public static function server($key)
  {
    $key = strtoupper(str_replace('-', '_', $key));
    return filter_input(INPUT_SERVER, $key, FILTER_SANITIZE_MAGIC_QUOTES);
  }
  
  /**
   * 取当前访问的完整URL
   *
   * @return string
   */
  public static function getUrl()
  {
    return self::server('REQUEST_SCHEME') . "://" . self::server('SERVER_NAME') . self::server('REQUEST_URI');
  }
  
  /**
   * 取当前请求的域名
   *
   * @return mixed
   */
  public static function getHost()
  {
    return self::server('HTTP_HOST');
  }
  
  /**
   * 取当前请求的 user-agent
   *
   * @return mixed
   */
  public static function getUa()
  {
    return self::server('HTTP_USER_AGENT');
  }
  
  /**
   * 取当前请求来源
   *
   * @return mixed
   */
  public static function getReferer()
  {
    return self::server('HTTP_REFERER');
  }
  
  /**
   * 取客户端希望接受的数据类型
   *
   * @return mixed
   */
  public static function getAccept()
  {
    return self::server('HTTP_ACCEPT');
  }
  
  public static function getPort()
  {
    return self::server('SERVER_PORT');
  }
  
  /**
   * /demo-utils.php
   *
   * @return mixed
   */
  public static function getUri()
  {
    return self::server('DOCUMENT_URI');
  }
  
  /**
   * /demo-utils.php?mod=depot
   *
   * @return mixed
   */
  public static function getRequestUri()
  {
    return self::server('REQUEST_URI');
  }
  
  /**
   * 获取查询字符串，如：mod=depot
   *
   * @return mixed
   */
  public static function getQueryString()
  {
    return self::server('QUERY_STRING');
  }
  
  /**
   * 取当前请求的用户IP
   *
   * @return mixed
   */
  public static function getIp()
  {
    return self::server('REMOTE_ADDR');
  }
  
  /**
   * 取当前请求的请求方法，如POST
   *
   * @return mixed
   */
  public static function getMethod()
  {
    return self::server('REQUEST_METHOD');
  }
  
  /**
   * 获取原始的 POST 数据
   *
   * @return false|string
   */
  public static function getContent()
  {
    return file_get_contents('php://input');
  }
  
  /**
   * 当前请求是否为https
   *
   * @return bool
   */
  public static function isHttps()
  {
    return self::server('REQUEST_SCHEME') == 'https';
  }
  
  /**
   * 判断当前的请求方法
   *
   * @param $method
   * @return bool
   */
  public static function isMethod($method)
  {
    return self::getMethod() == strtoupper($method);
  }
  
  /**
   * 是否为Ajax请求
   *
   * @return bool
   */
  public static function isAjax()
  {
    return self::head('X-Requested-With') == 'XMLHttpRequest';
  }
  
  /**
   * 是否是微信内置浏览器的请求
   *
   * @return bool
   */
  public static function isWeixin()
  {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
      return true;
    } else {
      return false;
    }
  }
  
  /**
   * 是否手机端访问
   *
   * @return bool
   */
  public static function isMobile()
  {
    $mobile            = [];
    $touchbrowser_list = [
      'iphone',
      'android',
      'phone',
      'mobile',
      'wap',
      'netfront',
      'java',
      'opera mobi',
      'opera mini',
      'ucweb',
      'windows ce',
      'symbian',
      'series',
      'webos',
      'sony',
      'blackberry',
      'dopod',
      'nokia',
      'samsung',
      'palmsource',
      'xda',
      'pieplus',
      'meizu',
      'midp',
      'cldc',
      'motorola',
      'foma',
      'docomo',
      'up.browser',
      'up.link',
      'blazer',
      'helio',
      'hosin',
      'huawei',
      'novarra',
      'coolpad',
      'webos',
      'techfaith',
      'palmsource',
      'alcatel',
      'amoi',
      'ktouch',
      'nexian',
      'ericsson',
      'philips',
      'sagem',
      'wellcom',
      'bunjalloo',
      'maui',
      'smartphone',
      'iemobile',
      'spice',
      'bird',
      'zte-',
      'longcos',
      'pantech',
      'gionee',
      'portalmmm',
      'jig browser',
      'hiptop',
      'benq',
      'haier',
      '^lct',
      '320x320',
      '240x320',
      '176x220',
      'windows phone',
    ];
    $wmlbrowser_list   = [
      'cect',
      'compal',
      'ctl',
      'lg',
      'nec',
      'tcl',
      'alcatel',
      'ericsson',
      'bird',
      'daxian',
      'dbtel',
      'eastcom',
      'pantech',
      'dopod',
      'philips',
      'haier',
      'konka',
      'kejian',
      'lenovo',
      'benq',
      'mot',
      'soutec',
      'nokia',
      'sagem',
      'sgh',
      'sed',
      'capitel',
      'panasonic',
      'sonyericsson',
      'sharp',
      'amoi',
      'panda',
      'zte',
    ];
    $pad_list          = ['ipad'];
    $useragent         = strtolower($_SERVER['HTTP_USER_AGENT']);
    
    if (Str::dstrpos($useragent, $pad_list)) {
      return false;
    }
    if (Str::dstrpos($useragent, $touchbrowser_list, true)) {
      return true;
    }
    if (Str::dstrpos($useragent, $wmlbrowser_list)) {
      return true; // wml版
    }
    $brower = ['mozilla', 'chrome', 'safari', 'opera', 'm3gate', 'winwap', 'openwave', 'myop'];
    if (Str::dstrpos($useragent, $brower)) {
      return false;
    }
    return true;
  }
  
  private static function except($args, $key = null)
  {
    if (!is_null($key)) {
      if (is_array($key)) {
        foreach ($key as $v) {
          if (isset($args[$v])) {
            unset($args[$v]);
          }
        }
      } elseif (isset($args[$key])) {
        unset($args[$key]);
      }
    }
    return $args;
  }
  
  /**
   * 删首尾空
   *
   * @param string|array $Arr
   * @param string       $str
   * @return array|string
   */
  private static function trim($Arr, $str = null)
  {
    if (!is_array($Arr)) {
      return trim($Arr, $str) . (substr($v, -3) == "\''" ? "'" : '');
    }
    foreach ($Arr as $k => $v) {
      if (is_array($v)) {
        $Arr[$k] = self::trim($v, $str);
      } else {
        $Arr[$k] = trim($v, $str) . (substr($v, -3) == "\''" ? "'" : '');
      }
    }
    return $Arr;
  }
}

<?php

namespace JyUtils\Str;

class Str
{
  /**
   * 取文本左边，[首次]出现的字符开始，失败返回整个字符
   *
   * @param string $subject
   * @param string $search
   * @return string
   */
  public static function before($subject, $search)
  {
    return $search === '' ? $subject : explode($search, $subject)[0];
  }
  
  /**
   * 取文本左边，[最后]出现的字符开始，失败返回整个字符
   *
   * @param string $subject
   * @param string $search
   * @return string
   */
  public static function beforeLast($subject, $search)
  {
    if ($search === '') {
      return $subject;
    }
    
    $pos = mb_strrpos($subject, $search);
    
    if ($pos === false) {
      return $subject;
    }
    
    return static::substr($subject, 0, $pos);
  }
  
  /**
   * 取文本右边，[首次]出现的字符开始，失败返回整个字符
   *
   * @param string $subject
   * @param string $search
   * @return string
   */
  public static function after($subject, $search)
  {
    return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
  }
  
  /**
   * 取文本右边，[最后]出现的字符开始，失败返回整个字符
   *
   * @param string $subject
   * @param string $search
   * @return string
   */
  public static function afterLast($subject, $search)
  {
    if ($search === '') {
      return $subject;
    }
    
    $position = strrpos($subject, (string)$search);
    
    if ($position === false) {
      return $subject;
    }
    
    return substr($subject, $position + strlen($search));
  }
  
  /**
   * 取由start和length参数指定的字符串部分。
   *
   * @param string   $string
   * @param int      $start
   * @param int|null $length
   * @return string
   */
  public static function substr($string, $start, $length = null)
  {
    return mb_substr($string, $start, $length, 'UTF-8');
  }
  
  /**
   * 取子字符串出现的次数。
   *
   * @param string   $haystack
   * @param string   $needle
   * @param int      $offset
   * @param int|null $length
   * @return int
   */
  public static function substrCount($haystack, $needle, $offset = 0, $length = null)
  {
    if (!is_null($length)) {
      return substr_count($haystack, $needle, $offset, $length);
    } else {
      return substr_count($haystack, $needle, $offset);
    }
  }
  
  /**
   * 给字符开始位置加前缀
   *
   * @param string $value
   * @param string $prefix
   * @return string
   */
  public static function start($value, $prefix)
  {
    $quoted = preg_quote($prefix, '/');
    
    return $prefix . preg_replace('/^(?:' . $quoted . ')+/u', '', $value);
  }
  
  /**
   * 给字符开始位置加后缀
   *
   * @param string $value
   * @param string $cap
   * @return string
   */
  public static function finish($value, $cap)
  {
    $quoted = preg_quote($cap, '/');
    
    return preg_replace('/(?:' . $quoted . ')+$/u', '', $value) . $cap;
  }
  
  /**
   * 使用数组顺序替换字符串中的给定值
   * https://learnku.com/docs/laravel/7.x/helpers/7486#81218a
   *
   * @param string                    $search
   * @param array<int|string, string> $replace
   * @param string                    $subject
   * @return string
   */
  public static function replaceArray($search, array $replace, $subject)
  {
    $segments = explode($search, $subject);
    
    $result = array_shift($segments);
    
    foreach ($segments as $segment) {
      $result .= (array_shift($replace) ?? $search) . $segment;
    }
    
    return $result;
  }
  
  /**
   * 替换字符串中给定值的第一个匹配项
   *
   * @param string $search
   * @param string $replace
   * @param string $subject
   * @return string
   */
  public static function replaceFirst($search, $replace, $subject)
  {
    if ($search == '') {
      return $subject;
    }
    
    $position = strpos($subject, $search);
    
    if ($position !== false) {
      return substr_replace($subject, $replace, $position, strlen($search));
    }
    
    return $subject;
  }
  
  /**
   * 替换字符串中给定值的最后一次出现
   *
   * @param string $search
   * @param string $replace
   * @param string $subject
   * @return string
   */
  public static function replaceLast($search, $replace, $subject)
  {
    $position = strrpos($subject, $search);
    
    if ($position !== false) {
      return substr_replace($subject, $replace, $position, strlen($search));
    }
    
    return $subject;
  }
  
  /**
   * 生成一个指定长度的随机字符串
   *
   * @param int $length
   * @return string
   */
  public static function random($length = 16)
  {
    $string = '';
    
    while (($len = strlen($string)) < $length) {
      $size = $length - $len;
      
      $bytes = random_bytes($size);
      
      $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
    }
    
    return $string;
  }
  
  /**
   * 判断给定的字符串是否包含给定的子字符串(第二个参数可以为数组，如果为数组，只要包含一个就返回true)。
   * https://learnku.com/docs/laravel/7.x/helpers/7486#74302b
   *
   * @param string          $haystack
   * @param string|string[] $needles
   * @return bool
   */
  public static function contains($haystack, $needles)
  {
    foreach ((array)$needles as $needle) {
      if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
        return true;
      }
    }
    
    return false;
  }
  
  /**
   * 判断给定的字符串是否包含[所有]数组值。
   *
   * @param string   $haystack
   * @param string[] $needles
   * @return bool
   */
  public static function containsAll($haystack, array $needles)
  {
    foreach ($needles as $needle) {
      if (!static::contains($haystack, $needle)) {
        return false;
      }
    }
    
    return true;
  }
  
  /**
   * 判断给定的字符串是否是有效的 uuid。
   *
   * @param string $value
   * @return bool
   */
  public static function isUuid($value)
  {
    if (!is_string($value)) {
      return false;
    }
    
    return preg_match('/^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/iD', $value) > 0;
  }
  
  /**
   * 限制字符串中的字符数。
   *
   * @param string $value
   * @param int    $limit
   * @param string $end
   * @return string
   */
  public static function limit($value, $limit = 100, $end = '...')
  {
    if (mb_strwidth($value, 'UTF-8') <= $limit) {
      return $value;
    }
    
    return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
  }
  
  /**
   * 限制字符串中的单词数。
   *
   * @param string $value
   * @param int    $words
   * @param string $end
   * @return string
   */
  public static function words($value, $words = 100, $end = '...')
  {
    preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches);
    
    if (!isset($matches[0]) || static::length($value) === static::length($matches[0])) {
      return $value;
    }
    
    return rtrim($matches[0]) . $end;
  }
  
  /**
   * 返回给定字符串的长度。
   *
   * @param string      $value
   * @param string|null $encoding
   * @return int
   */
  public static function length($value, $encoding = null)
  {
    if ($encoding) {
      return mb_strlen($value, $encoding);
    }
    
    return mb_strlen($value);
  }
  
  /**
   * 将给定的字符串转换为小写。
   *
   * @param string $value
   * @return string
   */
  public static function lower($value)
  {
    return mb_strtolower($value, 'UTF-8');
  }
  
  /**
   * 将给定的字符串转换为大写。
   *
   * @param string $value
   * @return string
   */
  public static function upper($value)
  {
    return mb_strtoupper($value, 'UTF-8');
  }
  
  public static function u2g($value)
  {
    return iconv('utf-8', 'gbk', $value);
  }
  
  public static function g2u($value)
  {
    return iconv('gbk', 'utf-8', $value);
  }
}

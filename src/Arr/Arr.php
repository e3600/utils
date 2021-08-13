<?php

namespace JyUtils\Arr;
// 使用：https://learnku.com/docs/laravel/7.x/helpers/7486#cf0e31

class Arr
{
  /**
   * 把一个数组分成两个数组，一个有键，另一个有值。
   *
   *
   * @param array $array
   * @return array
   */
  public static function divide($array)
  {
    return [array_keys($array), array_values($array)];
  }
  
  /**
   * 除了键数组外，返回其他所有的数组
   *
   * @param array        $array
   * @param array|string $keys
   * @return array
   */
  public static function except($array, $keys)
  {
    static::forget($array, $keys);
    
    return $array;
  }
  
  /**
   * 取的数组中指定键的子集
   *
   * @param array        $array
   * @param array|string $keys
   * @return array
   */
  public static function only($array, $keys)
  {
    return array_intersect_key($array, array_flip((array)$keys));
  }
  
  /**
   * 通过给定的真值测试返回数组中的第一个元素。
   *
   * @param iterable      $array
   * @param callable|null $callback
   * @param mixed         $default
   * @return mixed
   */
  public static function first($array, callable $callback = null, $default = null)
  {
    if (is_null($callback)) {
      if (empty($array)) {
        return $default;
      }
      
      foreach ($array as $item) {
        return $item;
      }
    }
    
    foreach ($array as $key => $value) {
      if ($callback($value, $key)) {
        return $value;
      }
    }
    
    return $default;
  }
  
  /**
   * 通过给定的真值测试返回数组中的最后一个元素。
   *
   * @param array         $array
   * @param callable|null $callback
   * @param mixed         $default
   * @return mixed
   */
  public static function last($array, callable $callback = null, $default = null)
  {
    if (is_null($callback)) {
      return empty($array) ? $default : end($array);
    }
    
    return static::first(array_reverse($array, true), $callback, $default);
  }
  
  /**
   * 使用“点”符号从给定的数组中移除一个或多个数组项。
   *
   * @param array        $array
   * @param array|string $keys
   * @return void
   */
  public static function forget(&$array, $keys)
  {
    $original = &$array;
    
    $keys = (array)$keys;
    
    if (count($keys) === 0) {
      return;
    }
    
    foreach ($keys as $key) {
      // 如果确切的密钥存在于顶层，删除它
      if (static::exists($array, $key)) {
        unset($array[$key]);
        
        continue;
      }
      
      $parts = explode('.', $key);
      
      // 每次传球前都要清理干净
      $array = &$original;
      
      while (count($parts) > 1) {
        $part = array_shift($parts);
        
        if (isset($array[$part]) && is_array($array[$part])) {
          $array = &$array[$part];
        } else {
          continue 2;
        }
      }
      
      unset($array[array_shift($parts)]);
    }
  }
  
  /**
   * 加入数组成员，在头部
   *
   * @param array $array
   * @param mixed $value
   * @param mixed $key
   * @return array
   */
  public static function prepend($array, $value, $key = null)
  {
    if (is_null($key)) {
      array_unshift($array, $value);
    } else {
      $array = [$key => $value] + $array;
    }
    
    return $array;
  }
  
  /**
   * Get an item from an array using "dot" notation.
   *
   * @param \ArrayAccess|array $array
   * @param string|int|null    $key
   * @param mixed              $default
   * @return mixed
   */
  public static function get($array, $key, $default = null)
  {
    if (!is_array($array)) {
      return $default;
    }
    
    if (is_null($key)) {
      return $array;
    }
    
    if (static::exists($array, $key)) {
      return $array[$key];
    }
    
    if (strpos($key, '.') === false) {
      return $array[$key] ?? value($default);
    }
    
    foreach (explode('.', $key) as $segment) {
      if (is_array($array) && static::exists($array, $segment)) {
        $array = $array[$segment];
      } else {
        return $default;
      }
    }
    
    return $array;
  }
  
  /**
   * 确定所提供的数组中是否存在给定的键。
   *
   * @param \ArrayAccess|array $array
   * @param string|int         $key
   * @return bool
   */
  public static function exists($array, $key)
  {
    return array_key_exists($key, $array);
  }
  
  /**
   * 从数组中获取一个值，然后删除它。
   *
   * @param array  $array
   * @param string $key
   * @param mixed  $default
   * @return mixed
   */
  public static function pull(&$array, $key, $default = null)
  {
    $value = static::get($array, $key, $default);
    
    static::forget($array, $key);
    
    return $value;
  }
  
  /**
   * 从数组中获取一个或一定数量的随机值。
   *
   * @param array    $array
   * @param int|null $number
   * @return mixed
   *
   * @throws \InvalidArgumentException
   */
  public static function random($array, $number = null)
  {
    $requested = is_null($number) ? 1 : $number;
    
    $count = count($array);
    
    if ($requested > $count) {
      return $array;
    }
    
    if (is_null($number)) {
      return $array[array_rand($array)];
    }
    
    if ((int)$number === 0) {
      return [];
    }
    
    $keys = array_rand($array, $number);
    
    $results = [];
    
    foreach ((array)$keys as $key) {
      $results[] = $array[$key];
    }
    
    return $results;
  }
  
  /**
   * 将数组随机排序，并返回结果。
   *
   * @param array    $array
   * @param int|null $seed
   * @return array
   */
  public static function shuffle($array, $seed = null)
  {
    if (is_null($seed)) {
      shuffle($array);
    } else {
      mt_srand($seed);
      shuffle($array);
      mt_srand();
    }
    return $array;
  }
  
  /**
   * 将数组转换为查询字符串。
   *
   * @param array $array
   * @return string
   */
  public static function query($array)
  {
    return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
  }
  
  /**
   * 使用给定的回调过滤数组。
   *
   * @param array    $array
   * @param callable $callback
   * @return array
   */
  public static function where($array, callable $callback)
  {
    return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
  }
  
  /**
   * 如果给定的值不是数组而且不是 null，则将其包装为一个。
   *
   * @param mixed $value
   * @return array
   */
  public static function wrap($value)
  {
    if (is_null($value)) {
      return [];
    }
    
    return is_array($value) ? $value : [$value];
  }
  
  /**
   * 分裂数据中的 ID
   *
   * @param int|string|array $data
   * @param string           $field
   * @return array
   */
  public static function splitbyid($data, $field = 'id')
  {
    return array_filter(splitbyname($data, $field), 'is_numeric');
  }
  
  /**
   * 分裂数据中的 NAME
   *
   * @param int|string|array $data
   * @param string           $field
   * @return array
   */
  public static function splitbyname($data, $field = 'name')
  {
    if (is_int($data)) {
      $data = [$data];
    } else if (is_string($data)) {
      $data = explode(',', $data);
    } else if (is_array($data) && self::array_depth($data) > 1) {
      $data = array_column($data, $field);
    } else if (is_array($data) && self::is_assoc($data)) {
      $data = [$data[$field]];
    }
    
    return array_values(array_filter(array_unique($data), 'is_value'));
  }
  
  /**
   * 是否为关联数组
   *
   * @param array $var
   * @return bool
   */
  public static function is_assoc($var)
  {
    return is_array($var) and (array_values($var) !== $var);
  }
  
  /**
   * 取得数组深度
   *
   * @param $array
   * @return int
   */
  public static function array_depth($array)
  {
    $max_depth = 1;
    
    foreach ($array as $value) {
      if (is_array($value)) {
        $depth = self::array_depth($value) + 1;
        
        if ($depth > $max_depth) {
          $max_depth = $depth;
        }
      }
    }
    return $max_depth;
  }
  
  public static function groupby($data, Closure $fn)
  {
    $ret = [];
    foreach ($data as $v) {
      @$ret[$fn($v)] = $v;
    }
    
    return $ret;
  }
  
  public static function groupbyid($data, $field = 'id')
  {
    $ret = [];
    foreach ($data as $v) {
      @$ret[$v[$field]] = $v;
    }
    
    return $ret;
  }
}

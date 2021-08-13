<?php

namespace JyUtils\Request;

/**
 * 过虑全部GET|POST
 *
 * @param array $exception 例外，不过虑的参数
 * @param bool  $isTrim    是否删首尾家，调用trim
 */
function filter(array $exception = null, bool $isTrim = true)
{
    $_GET  = _Container::filter($_GET, $exception, $isTrim);
    $_POST = _Container::filter($_POST, $exception, $isTrim);
}

/**
 * 单独过虑全部GET
 *
 * @param array $exception 例外，不过虑的参数
 * @return array 返回处理完后的数组
 */
function filter_get(array $exception = null)
{
    return _Container::filter($_GET, $exception);
}

/**
 * 单独过虑全部POST
 *
 * @param array $exception 例外，不过虑的参数
 * @return array 返回处理完后的数组
 */
function filter_post(array $exception = null)
{
    return _Container::filter($_POST, $exception);
}

/**
 * 获取请求参数
 *
 * @param null $key     字符串或数组
 * @param null $default 当key为数组时，此参数无效
 * @param null $except  只有当key和default为空时(获取全部时有效)
 * @return array|mixed|string 当key为数组时，返回数组，否则返回字符串，字段不存在时，返回空字符串
 */
function request($key = null, $default = null, $except = null)
{
    if (is_null($key)) {
        return _Container::gets($except);
    }
    return _Container::get($key, $default);
}

/**
 * 当前类依赖于Discuz的DB类里的DB::quote
 */
class _Container
{
    /**
     * @param string|array $name    如：tid(强转整数，tid:int)
     * @param null         $default 如果值不存在，返回默认值(name为字符串时有效)
     * @return array|int|mixed|string
     */
    public static function get($name, $default = null)
    {
        $args = array_merge($_GET, $_POST);
        if (is_array($name)) {
            $res = [];
            foreach ($name as $v) {
                list($key, $type) = explode(":", $v);
                if (isset($args[$key])) {
                    if ($type == 'int') {
                        $res[$key] = intval($args[$key]);
                    } else {
                        $res[$key] = DB::quote($args[$key]);
                    }
                }
            }
            return self::trim($res, "'");
            
        } else {
            list($key, $type) = explode(":", $name);
            if ($type == 'int') {
                return isset($args[$key]) ? intval($args[$key]) : $default;
            } else {
                return $args[$key] ? self::trim(DB::quote($args[$key]), "'") : $default;
            }
        }
    }
    
    public static function gets($except = null)
    {
        // 处理排除字段
        $args = self::except(array_merge($_GET, $_POST), $except);
        
        // 过虑字段安全
        return self::trim(DB::quote($args), "'");
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
                    $v = $isTrim ? DB::quote(trim($v)) : DB::quote($v);
                    $v = str_replace('\n', "\n", $v);
                }
            }
        } else {
            foreach ($Array as $key => &$v) {
                if (!in_array($key, $except)) {
                    $v = $isTrim ? DB::quote(trim($v)) : DB::quote($v);
                }
                $v = str_replace('\n', "\n", $v);
            }
        }
        return self::trim($Array, "'");
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

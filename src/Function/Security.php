<?php

use JyUtils\Request\Request;
use JyUtils\Request\uploadFilter;

/**
 * 过虑全部GET|POST
 *
 * @param array $exception 例外，不过虑的参数
 * @param bool  $isTrim    是否删首尾空，调用trim
 */
function filter(array $exception = null, bool $isTrim = true)
{
  define('IS_FILTER', true);
  $_GET  = Request::filter($_GET, $exception, $isTrim);
  $_POST = Request::filter($_POST, $exception, $isTrim);
}

/**
 * 单独过虑全部GET
 *
 * @param array $exception 例外，不过虑的参数
 * @param bool  $isTrim    是否删首尾空，调用trim
 * @return array 返回处理完后的数组
 */
function filter_get(array $exception = null, bool $isTrim = true)
{
  return Request::filter($_GET, $exception, $isTrim);
}

/**
 * 单独过虑全部POST
 *
 * @param array $exception 例外，不过虑的参数
 * @param bool  $isTrim    是否删首尾空，调用trim
 * @return array 返回处理完后的数组
 */
function filter_post(array $exception = null, bool $isTrim = true)
{
  return Request::filter($_POST, $exception, $isTrim);
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
    return Request::all($except);
  }
  return Request::get($key, $default);
}

/**
 * 上传文件安全过虑，当文件存在木马嫌疑时，将会回调
 *
 * @param $dangerCall
 */
function uploadFilter($dangerCall)
{
  uploadFilter::start($dangerCall);
}



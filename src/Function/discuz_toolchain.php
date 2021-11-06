<?php
// 哪都达的不使用此文件
if (stripos($_SERVER['HTTP_HOST'], 'jieyang.la') !== false) {
  return;
}

/**
 * @yazi 封装的 Discuz 工具链，
 * 之所以单独放一个文件是为了避免被乱改
 */
$_GG['__LAST_DEFERS__']      = [];
$_GG['__LAST_QUERY_SQL__']   = [];
$_GG['__EXECUTE_DURATION__'] = microtime(true);

/**
 * Dump and Die (打印并死掉)
 */
function dd()
{
  foreach (func_get_args() as $v) {
    echo '<yz-dd>';
    highlight_string("<?php\n" . var_export($v, true));
    echo '</yz-dd>';
  }
  echo <<<HTML
	<style>yz-dd{display:block;unicode-bidi:embed;font-family:monospace;background:#f1f1f1;padding:1em;margin-bottom:20px}</style>
	<script>!function(){for(var e=document.querySelectorAll("yz-dd"),r=0;r<e.length;r++){e[r].querySelector("code")&&(e[r].innerHTML=e[r].querySelector("code").innerHTML);for(var l=e[r].querySelectorAll("span:first-child, span > span:first-child"),n=0;n<l.length;n++)"<?php"==l[n].innerText.replace(/\\n$/,"")&&l[n].remove();var o=e[r].querySelectorAll("br:first-of-type");for(n=0;n<o.length;n++){var t=o[n].previousSibling;t&&"<?php"==t.nodeValue&&(t.remove(),o[n].remove())}}}();</script>
HTML;
  die;
}

function dda(...$value)
{
  global $_G;
  // 示例，在程序入口定义IS_DEBUG，define('IS_DEBUG', getcookie('saltkey') == 'kAu7W8Ww');
  if ((isset($_G['uid']) && $_G['uid'] == 1) || (defined('IS_DEBUG') && IS_DEBUG == true)) {
    dd(...$value);
  }
}

function start_microtime()
{
  global $_GG;
  $_GG['__EXECUTE_DURATION__'] = microtime(true);
}

function input($data)
{
  if (is_array($data)) {
    $data = print_r($data, true);
  }
  if ($data == NULL . NULL) {
    file_put_contents('d.txt', '');
  } else {
    file_put_contents('d.txt', $data, FILE_APPEND);
  }
}

function inc($name, $increase)
{
  static $_vars = [];
  
  if (!isset($_vars[$name])) {
    $_vars[$name] = 0;
  }
  return $_vars[$name] += $increase;
}

function null()
{
  return new NullValue;
}

/**
 * 一次（适用于请求生命周期）
 *
 * @param $var
 * @param $val
 * @return mixed
 */
function once($var, $val)
{
  static $history = [];
  
  $hash = crc32(serialize($var));
  if (is_nil($val)) {
    unset($history[$hash]);
  } else if (!array_key_exists($hash, $history)) {
    $history[$hash] = value($val);
  }
  
  return @$history[$hash];
}

/**
 * 以 JSON 格式丢出数据
 *
 * @param mixed $data
 */
function json($data)
{
  global $_GG;
  if ($_GG['__DO_NOT_RUSH_BACK_JSON_COUNTER__'] > 0) {
    $_GG['__DO_NOT_RUSH_BACK_JSON__'][] = $data;
    throw new DoNotRushBackJSONException;
  }
  
  if (!headers_sent()) {
    header('Content-Type: application/json; charset=UTF-8');
  }
  
  if (is_array($data)) {
    unset($data['__RETURN_BY_LAYDATA__']);
  }
  
  if (isset($_GG['__EXECUTE_DURATION__'])) {
    $data['execute_duration'] = round(microtime(true) - $_GG['__EXECUTE_DURATION__'], 3);
  }
  exit(json_encode(windup($data)));
}

/**
 * 成功
 *
 * @param mixed $data
 */
function succ($data = null)
{
  if (is_null($data)) {
    json(['status' => 1]);
  }
  json(['status' => 1, 'data' => $data]);
}

/**
 * 失败
 *
 * @param string $msg
 * @param int    $status
 */
function fail($msg, $status = 0)
{
  if (is_array($msg)) {
    json($msg + ['status' => $status]);
  }
  json(['status' => $status, 'msg' => $msg]);
}

/**
 * 调用某个函数
 *
 * @param Closure|string $fn
 * @param mixed          ...$args
 * @return mixed|null
 */
function call($fn = null, ...$args)
{
  switch (true) {
    case $fn instanceof Closure:
    case is_string($fn) && function_exists($fn):
      return $fn(...$args);
  }
  
  return null;
}

/**
 * 代换请求参数
 *
 * @param Closure $fn
 * @param mixed   $vars
 * @return mixed
 */
function swap($fn, $vars = [])
{
  if (is_string($vars)) {
    parse_str($vars, $vars);
  }
  
  $oldValues = [];
  foreach ($vars as $k => $v) {
    $oldValues[$k] = $_GET[$k];
    $_GET[$k]      = $v;
  }
  
  try {
    $result = $fn();
  } catch (DoNotRushBackJSONException $e) {
    foreach (array_keys($vars) as $k) {
      $_GET[$k] = $oldValues[$k];
    }
    
    throw $e;
  }
  
  foreach (array_keys($vars) as $k) {
    $_GET[$k] = $oldValues[$k];
  }
  
  return $result;
}

/**
 * 平面化数组
 *
 * @param array $array
 * @param int   $depth
 * @return mixed
 */
function flat($array, $depth = PHP_INT_MAX)
{
  $depth = max(0, $depth);
  
  return $depth ? array_reduce($array, function ($carry, $item) use ($depth) {
    if (is_array($item)) {
      $carry = array_merge((array)$carry, flat($item, $depth - 1));
    } else {
      $carry[] = $item;
    }
    
    return $carry;
  }) : $array;
}

/**
 * 使用全局锁
 * 注意，需要使用succ或fail才可以解锁
 */
function uselock($key, $tableName = 'pre_wukong_locks')
{
  lock($key, $tableName);
  defer(function () use ($key, $tableName) {
    unlock($key, $tableName);
  });
}

/**
 * 全局事务锁
 *
 * @param string $key 钥匙，30 字符内
 */
function lock($key, $tableName = 'pre_wukong_locks')
{
  DB::query("insert into {$tableName} (`key`, created_at) values ('" . daddslashes($key) . "', " . TIMESTAMP . ")");
}

/**
 * 解除全局事务锁
 *
 * @param string $key 钥匙，30 字符内
 */
function unlock($key, $tableName = 'pre_wukong_locks')
{
  DB::query("delete from {$tableName} where `key`='" . daddslashes($key) . "'");
}

/**
 * 等待
 *
 * @param Closure $fn
 * @param array   $vars
 * @return mixed
 */
function wait($fn, $vars = [])
{
  global $_GG;
  if (!$_GG['__DO_NOT_RUSH_BACK_JSON__']) {
    $_GG['__DO_NOT_RUSH_BACK_JSON__'] = [];
  }
  
  if ($_GG['__DO_NOT_RUSH_BACK_JSON_COUNTER__'] > 0) {
    $_GG['__DO_NOT_RUSH_BACK_JSON_COUNTER__']++;
  } else {
    $_GG['__DO_NOT_RUSH_BACK_JSON_COUNTER__'] = 1;
  }
  
  try {
    $result = swap($fn, $vars);
  } catch (DoNotRushBackJSONException $e) {
    $result = array_shift($_GG['__DO_NOT_RUSH_BACK_JSON__']);
  }
  
  if (--$_GG['__DO_NOT_RUSH_BACK_JSON_COUNTER__'] < 1) {
    unset($_GG['__DO_NOT_RUSH_BACK_JSON_COUNTER__']);
  }
  
  return $result;
}

/**
 * 等待数据
 *
 * @param Closure $fn
 * @param array   $vars
 * @return mixed
 */
function waitd($fn, $vars = [])
{
  $result = wait($fn, $vars);
  return $result['data'];
}

/**
 * 只等待成功数据
 *
 * @param Closure $fn
 * @param array   $vars
 * @return mixed
 */
function waitf($fn, $vars = [])
{
  $result = wait($fn, $vars);
  if (is_null($result)) {
    return null;
  } else if ($result['status'] === 1) {
    return $result['data'];
  }
  fail($result);
}

/**
 * 延迟函数
 *
 * @param Closure|string $fn
 */
function defer($fn)
{
  global $_GG;
  $_GG['__LAST_DEFERS__'][] = $fn;
}

/**
 * 调用succ时，会执行 $fn 函数
 *
 * @param   $fn 要执行的函数
 */
function defers(Closure $fn)
{
  defer(function ($data) use ($fn) {
    if ($data['status'] === 1) {
      call($fn, $data['data']);
    }
  });
}

/**
 * 调用fail时，会执行 $fn 函数
 *
 * @param Closure $fn 要执行的函数
 * @return void
 */
function deferf(Closure $fn)
{
  defer(function ($data) use ($fn) {
    if ($data['status'] !== 1) {
      call($fn, $data);
    }
  });
}

function windup($data = null)
{
  global $_GG;
  if ($_GG['__IN_TRANSACTION__'] > 0) {
    query('rollback');
    query('set autocommit=1');
    unset($_GG['__IN_TRANSACTION__']);
  }
  
  while (count($_GG['__LAST_DEFERS__']) > 0) {
    call(array_pop($_GG['__LAST_DEFERS__']), $data);
  }
  return $data;
}

/**
 * 取值
 *
 * @param $var
 * @return mixed
 */
function value($var)
{
  return $var instanceof Closure ? $var() : $var;
}

/**
 * 数组第一个
 *
 * @param array $array
 * @param mixed $default
 * @return mixed
 */
function first($array, $default = null)
{
  return reset($array) === false ? $default : reset($array);
}

/**
 * 数组最后一个
 *
 * @param array $array
 * @return mixed
 */
function last($array)
{
  return end($array);
}

/**
 * 正则匹配
 *
 * @param $pattern
 * @param $subject
 * @return mixed
 */
function match($pattern, $subject)
{
  preg_match($pattern, $subject, $matches);
  return $matches[1];
}

function streg($string)
{
  return '#' . preg_quote($string) . '#';
}

/**
 * 多元组合
 *
 * @param mixed ...$args
 * @return mixed
 */
function combine(...$args)
{
  return last($args);
}

/**
 * 集合中是否包含项目
 *
 * @param array|string $items
 * @param array|string $collections
 * @return bool
 */
function contains($items, $collections)
{
  return !array_diff(splitbyname($items), splitbyname($collections));
}

/**
 * 集合中是否包含且只包含项目
 *
 * @param array|string $collection
 * @param array|string $items
 * @return bool
 */
function intersect($collection, $items)
{
  return $collection === array_intersect(splitbyname($collection), splitbyname($items));
}

/**
 * 转换单词复数
 *
 * @param string $word
 * @return string
 */
function plural($word)
{
  static $_rules = [
    'plural' => [
      '/(matr|vert|ind)(ix|ex)$/i' => '\1ices',    # matrix, vertex, index
      '/(ss|sh|ch|x|z)$/i'         => '\1es',      # sibilant rule (no ending e)
      '/([^aeiou])o$/i'            => '\1oes',     # -oes rule
      '/([^aeiou]|qu)y$/i'         => '\1ies',     # -ies rule
      '/sis$/i'                    => 'ses',       # synopsis, diagnosis
      '/(m|l)ouse$/i'              => '\1ice',     # mouse, louse
      '/(t|i)um$/i'                => '\1a',       # datum, medium
      '/([li])fe?$/i'              => '\1ves',     # knife, life, shelf
      '/(octop|vir|syllab)us$/i'   => '\1i',       # octopus, virus, syllabus
      '/(ax|test)is$/i'            => '\1es',      # axis, testis
      '/([a-rt-z])$/i'             => '\1s'        # not ending in s
    ],
    
    'irregular' => [
      'bus'         => 'busses',
      'child'       => 'children',
      'man'         => 'men',
      'person'      => 'people',
      'quiz'        => 'quizzes',
      # words whose singular and plural forms are the same
      'equipment'   => 'equipment',
      'fish'        => 'fish',
      'information' => 'information',
      'money'       => 'money',
      'moose'       => 'moose',
      'news'        => 'news',
      'rice'        => 'rice',
      'series'      => 'series',
      'sheep'       => 'sheep',
      'species'     => 'species',
    ],
  ];
  
  if (isset($_rules['irregular'][$word])) {
    return $_rules['irregular'][$word];
  }
  
  foreach ($_rules['plural'] as $regex => $replace) {
    $word = preg_replace($regex, $replace, $word, 1, $count);
    
    if ($count) {
      return $word;
    }
  }
  
  return $word;
}

function is_nil($var)
{
  return $var instanceof NullValue;
}

/**
 * 是否为关联数组
 *
 * @param array $var
 * @return bool
 */
function is_assoc($var)
{
  return is_array($var) and $var !== array_values($var) and ($keys = array_keys($var)) !== array_filter($keys, 'is_int');
}

function is_value($var)
{
  return $var !== '' and $var !== null;
}

function is_number($var)
{
  return strlen(intval($var)) == strlen($var);
}

/**
 * 人类友好的文件尺寸
 *
 * @param $size
 * @return string
 */
function file_size($size)
{
  $unit = [
    'B'  => 2 ** 0,
    'KB' => 2 ** 10,
    'MB' => 2 ** 20,
    'GB' => 2 ** 30,
    'TB' => 2 ** 40,
    'PB' => 2 ** 50,
    'EB' => 2 ** 60,
    'ZB' => 2 ** 70,
    'YB' => 2 ** 80,
  ];
  
  foreach ($unit as $k => $v) {
    if ($size <= $v * 1024) {
      return round($size / $v, 2) . ' ' . $k;
    }
  }
  
  return 'unknown';
}

/**
 * 自主分页函数
 *
 * @param string|array   $sql
 * @param Closure|string $cb
 * @param int            $cacheTime 缓存数据，0=不缓存，大于0时，表示缓存时长(秒)
 * @throws DoNotRushBackJSONException
 */
function laydata($sql, $cb = null, $cacheTime = 0)
{
  $cacheName = md5($sql . $_GET['page'] . $_GET['limit']);
  if ($cacheTime > 0 and ($data = C::memory()->get($cacheName))) {
    json($data);
  }
  
  $var = ['code' => 0, 'status' => 1, 'msg' => ''];
  if (is_array($sql) && isset($sql['__RETURN_BY_LAYDATA__'])) {
    $var = $sql;
  } else if (is_array($sql)) {
    $var['per']  = $var['count'] = count($var['data']);
    $var['data'] = paginate(function ($_, $limit) use ($sql, &$var) {
      return first([$sql, $var['per'] = $limit ?: $var['count']]);
    });
  } elseif (is_string($sql)) {
    $var['data']  = paginate(function ($start, $limit) use ($sql, &$var) {
      $var['per'] = $limit;
      return fetch_all("$sql limit $start, $limit");
    });
    $var['count'] = fetch_first(preg_replace('#^select\s+([\s\S]+?)\s+from\s+([a-zA-Z0-9_]+\.)?pre_#i', 'select $1,count(*) from $2pre_', $sql))['count(*)'];
  }
  
  if ($cb) {
    $cb($var['data'], $var['count'], $var['per']);
  }
  
  global $_GG;
  if ($_GG['__DO_NOT_RUSH_BACK_JSON_COUNTER__'] > 0) {
    $_GG['__DO_NOT_RUSH_BACK_JSON__'][] = $var + ['__RETURN_BY_LAYDATA__' => true];
    throw new DoNotRushBackJSONException;
  }
  
  if ($cacheTime > 0) {
    redis_set($cacheName, $var, $cacheTime);
  }
  json($var);
}

/**
 * 分页函数
 *
 * @param Closure $cb
 * @return mixed
 */
function paginate(Closure $cb)
{
  $page  = $_GET['page'] > 0 ? intval($_GET['page']) : 1;
  $limit = hasParam('limit') && $_GET['limit'] >= 0 ? intval($_GET['limit']) : 20;
  
  return $cb(($page - 1) * $limit, $limit, $page);
}

/**
 * 联结数据中的 ID
 *
 * @param int|string|array $data
 * @param string           $field
 * @return string
 */
function joinbyid($data, $field = 'id')
{
  return implode(',', splitbyid($data, $field));
}

/**
 * 联结数据中的 NAME
 *
 * @param int|string|array $data
 * @param string           $field
 * @return string
 */
function joinbyname($data, $field = 'name')
{
  return implode(',', DB::quote(splitbyname($data, $field)));
}

/**
 * 分裂数据中的 ID
 *
 * @param int|string|array $data
 * @param string           $field
 * @return array
 */
function splitbyid($data, $field = 'id')
{
  return array_values(array_filter(splitbyname($data, $field), 'is_numeric'));
}

/**
 * 分裂数据中的 NAME
 *
 * @param int|string|array $data
 * @param string           $field
 * @return array
 */
function splitbyname($data, $field = 'name')
{
  if (is_int($data)) {
    $data = [$data];
  } else if (is_string($data)) {
    $data = explode(',', $data);
  } else if (is_array($data) && array_depth($data) > 1) {
    $data = array_column($data, $field);
  } else if (is_array($data) && is_assoc($data)) {
    $data = [$data[$field]];
  }
  
  return array_values(array_filter(array_unique($data), 'is_value'));
}

function groupby($data, Closure $fn)
{
  $ret = [];
  foreach ($data as $v) {
    @$ret[$fn($v)] = $v;
  }
  
  return $ret;
}

function groupbyid($data, $field = 'id')
{
  $ret = [];
  foreach ($data as $v) {
    @$ret[$v[$field]] = $v;
  }
  
  return $ret;
}

function groupsby($data, Closure $fn)
{
  $ret = [];
  foreach ($data as $v) {
    @$ret[$fn($v)][] = $v;
  }
  
  return $ret;
}

function groupsbyid($data, $field = 'id')
{
  $ret = [];
  foreach ($data as $v) {
    @$ret[$v[$field]][] = $v;
  }
  
  return $ret;
}

function groupbyprefix(&$data)
{
  if (!is_assoc($data)) {
    foreach ($data as &$v) {
      groupbyprefix($v);
    }
    return $data;
  }
  
  $result = [];
  foreach ($data as $k => $v) {
    if ($k[0] == '_') {
      [, $group, $name] = explode('_', $k, 3);
      if (!$group || !$name) {
        continue;
      }
      
      $result[$group][$name] = $v;
      unset($data[$k]);
    }
  }
  
  return $data += $result;
}

function idornull($field, $id = null)
{
  if (func_num_args() == 1) {
    return is_nil($field) ? 'null' : intval($field);
  }
  
  if (is_nil($id)) {
    return "isnull({$field})";
  } else {
    return "{$field}=" . intval($id);
  }
}

function findone($array, $value, $column = 'id')
{
  $result = filterby($array, $value, $column);
  return count($result) ? $result[0] : null;
}

function sumby($array, $column, $scale = 2)
{
  $total = 0;
  foreach ($array as $item) {
    $total = bcadd($total, $item[$column], $scale);
  }
  
  return $total;
}

function countby($array, $value, $column = 'id')
{
  return count(filterby($array, $value, $column));
}

function sortby(&$array, $orderby)
{
  $args = [];
  foreach (explode(',', orderby($orderby)) as $v) {
    [$name, $sort] = explode(' ', $v);
    
    $args[] = array_column($array, trim($name, '`'));
    $args[] = $sort == 'asc' ? SORT_ASC : SORT_DESC;
  }
  
  $args[] = &$array;
  array_multisort(...$args);
}

function linkby(&$source, $target, $source_column, $target_column)
{
  foreach ($source as &$v) {
    $v[$target_column] = $target[$v[$source_column]];
  }
  
  return $source;
}

function filterby($array, $value, $column = 'id')
{
  return array_values(array_filter($array, function ($v) use ($column, $value) {
    return $v[$column] == $value;
  }));
}

/**
 * 迷糊查询
 * fuzzysearch($zones, streg('镇'), 'name')
 *
 * @param array      $array
 * @param string     $regex
 * @param null|mixed $column
 * @return array
 */
function fuzzysearch($array, $regex, $column = null)
{
  if (is_null($column)) {
    return preg_grep($regex, $array) ?: [];
  }
  
  return array_intersect_key($array, preg_grep($regex, array_column($array, $column))) ?: [];
}

function rearrange($array, $orderby)
{
  foreach ($orderby as $field => $value) {
    $value = array_flip($value);
    
    foreach ($array as &$_) {
      $_["_sort_{$field}"] = $value[$_[$field]];
    }
  }
  
  $sorter = [];
  foreach (array_keys($orderby) as $v) {
    $sorter[] = "_sort_{$v}";
  }
  
  sortby($array, implode(',', $sorter));
  exceptfields($array, $sorter);
  return $array;
}

/**
 * 开启一个事务
 *
 * @param Closure $fn
 * @return mixed
 */
function transaction($fn)
{
  global $_GG;
  if (isset($_GET['_transaction']) && $_GET['_transaction'] == 'NONE') {
    return;
  }
  
  if ($_GG['__IN_TRANSACTION__'] > 0) {
    $_GG['__IN_TRANSACTION__']++;
  } else {
    $_GG['__IN_TRANSACTION__'] = 1;
    query('set autocommit=0');
    query('begin');
  }
  
  $res = $fn();
  if (--$_GG['__IN_TRANSACTION__'] < 1) {
    query('commit');
    query('set autocommit=1');
    unset($_GG['__IN_TRANSACTION__']);
  }
  
  return $res;
}

/**
 * 开始事务，如果失败时调用了fail，事务将被回滚
 *
 * @param Closure $fn 函数
 * @return mixed
 */
function transaction_back(Closure $fn)
{
  deferf(function () {
    query('rollback');
    query('set autocommit=1');
  });
  return transaction($fn);
}

/**
 * 以锁的方式开启一个事务
 *
 * @param string  $key
 * @param Closure $fn
 * @return mixed
 */
function ltransaction($key, $fn)
{
  return transaction(function () use ($key, $fn) {
    lock($key);
    $data = $fn();
    unlock($key);
    
    return $data;
  });
}

/**
 * 从数组中筛选特定字段
 *
 * @param array $data   源数组
 * @param mixed $fields 筛选字段
 * @param int   $level  处理级别
 * @return array
 */
function onlyfields(&$data, $fields, $level = 1)
{
  if ($level > 2) {
    return $data;
  }
  if ($fields == '*') {
    return $data;
  }
  if (!is_array($data)) {
    return $data;
  }
  if (is_string($fields)) {
    $fields = explode(',', $fields);
  }
  if (!is_array($fields)) {
    $fields = (array)$fields;
  }
  
  if (!is_assoc($data)) {
    foreach ($data as &$v) {
      onlyfields($v, $fields, $level + 1);
    }
    return $data;
  }
  
  foreach (array_keys($data) as $k) {
    $flag = false;
    foreach ($fields as $f) {
      if (fnmatch($f, $k)) {
        $flag = true;
        break;
      }
    }
    
    if (!$flag) {
      unset($data[$k]);
    }
  }
  return $data;
}

/**
 * 从数组中筛出特定字段
 *
 * @param array $data   源数组
 * @param mixed $fields 筛选字段
 * @param int   $level  处理级别
 * @return array
 */
function exceptfields(&$data, $fields, $level = 1)
{
  if ($level > 2) {
    return $data;
  }
  if (!is_array($data)) {
    return $data;
  }
  if (is_string($fields)) {
    $fields = explode(',', $fields);
  }
  if (!is_array($fields)) {
    $fields = (array)$fields;
  }
  
  if (!is_assoc($data)) {
    foreach ($data as &$v) {
      exceptfields($v, $fields, $level + 1);
    }
    return $data;
  }
  
  foreach (array_keys($data) as $k) {
    foreach ($fields as $f) {
      if (fnmatch($f, $k)) {
        unset($data[$k]);
        break;
      }
    }
  }
  return $data;
}

function last_sql($n = 0)
{
  global $_GG;
  if (!$n && $n !== 0) {
    return $_GG['__LAST_QUERY_SQL__'];
  }
  return $_GG['__LAST_QUERY_SQL__'][$n];
}

/**
 * 创建 SQL 拼接
 *
 * @param $sql
 * @return Closure
 */
function create_sql($sql)
{
  return function ($s) use ($sql) {
    return "$s $sql";
  };
}

/**
 * 更安全的 SQL
 *
 * @param $sql
 * @return string
 */
function safety_sql($sql)
{
  $sql = trim($sql);
  $sql = str_replace('{TIMESTAMP}', time(), $sql);
  $sql = preg_replace('/\s+in\s*\(\)/i', " in ('')", $sql);
  $sql = preg_replace('/([a-zA-Z_\.]+)=($|\s)/', " '' ", $sql);
  $sql = preg_replace('/select\s+\*,\s*count\(\*\)\s+from\s+([a-zA-Z0-9_]+\.)?pre_/i', 'select count(*) from $1pre_', $sql);
  
  // 组合集
  $sql = preg_replace_callback('/([a-zA-Z]+\.)?{([a-zA-Z0-9,_]+)\|([0-9a-zA-Z_]+)}/', function ($matches) {
    [$lead, $group, $prefix] = array_slice($matches, 1);
    $prefix = rtrim($prefix, '_') ? "{$prefix}_" : '';
    
    return implode(',', array_map(function ($a) use ($lead, $prefix) {
      return "{$lead}{$a} {$prefix}{$a}";
    }, explode(',', $group)));
  }, $sql);
  
  // 条件检查
  if (strtolower(substr($sql, 0, 7)) == 'update ' && !stripos($sql, ' where ')) {
    fail('不规范的 SQL: update 子句未含有 where 约束');
  } else if (strtolower(substr($sql, 0, 7)) == 'delete ' && !stripos($sql, ' where ')) {
    fail('不规范的 SQL: delete 子句未含有 where 约束');
  }
  
  return $sql;
}

function safety_field($name)
{
  return '`' . implode('`.`', explode('.', str_replace('`', '', $name))) . '`';
}

/**
 * 构建健全的 order by 字段
 *
 * @param $sql
 * @return string
 */
function orderby($sql)
{
  $fields = explode(',', preg_replace('/\s+/', ' ', strtolower($sql)));
  
  $ret = '';
  foreach ($fields as $v) {
    [$name, $order] = explode(' ', trim($v), 2);
    if (!$name)
      continue;
    
    $order = in_array($order, ['asc', 'desc']) ? $order : 'asc';
    $ret   .= safety_field($name) . " {$order},";
  }
  
  return rtrim($ret, ',');
}

function query($sql, $arg = [], $silent = false, $unbuffered = false)
{
  return call(function ($s) use ($arg, $silent, $unbuffered) {
    global $_GG;
    array_unshift($_GG['__LAST_QUERY_SQL__'], $s);
    return DB::query($s, $arg, $silent, $unbuffered);
  }, safety_sql($sql));
}

function fetch_all($sql, $arg = [], $keyfield = '', $silent = false)
{
  return call(function ($s) use ($arg, $keyfield, $silent) {
    global $_GG;
    array_unshift($_GG['__LAST_QUERY_SQL__'], $s);
    return DB::fetch_all($s, $arg, $keyfield, $silent);
  }, safety_sql($sql));
}

function fetch_first($sql, $arg = [], $silent = false)
{
  return call(function ($s) use ($arg, $silent) {
    global $_GG;
    array_unshift($_GG['__LAST_QUERY_SQL__'], $s);
    return DB::fetch_first($s, $arg, $silent);
  }, safety_sql($sql));
}

function result_first($sql, $arg = [], $silent = false)
{
  return call(function ($s) use ($arg, $silent) {
    global $_GG;
    array_unshift($_GG['__LAST_QUERY_SQL__'], $s);
    return DB::result_first($s, $arg, $silent);
  }, safety_sql($sql));
}

/**
 * 取得数组深度
 *
 * @param $array
 * @return int
 */
function array_depth($array)
{
  $max_depth = 1;
  
  foreach ($array as $value) {
    if (is_array($value)) {
      $depth = array_depth($value) + 1;
      
      if ($depth > $max_depth) {
        $max_depth = $depth;
      }
    }
  }
  return $max_depth;
}

/**
 * 生成订单号
 *
 * @return string
 */
function generate_order_id()
{
  $first  = (time() + inc('GOI', 1)) . mt_rand(100, 999);
  $second = substr(sha1(uniqid() . $first . inc('OSH', 10)), -4);
  $third  = substr(sha1(uniqid() . $second . inc('OSH', 100)), 0, 2);
  return strtoupper($first . $third . $second);
}

function can_count($name)
{
  $counter = fetch_first("select * from pre_wukong_counters where name='{$name}'");
  
  // 没有则立即执行
  if (!$counter) {
    return 1;
  }
  
  // 超过有效期，立即执行
  if ($counter['expired_at'] < time()) {
    return 1;
  }
  
  // 未过期，未超限
  if ($counter['expired_at'] > time() && $counter['count'] < $counter['max_count']) {
    return $counter['count'] + 1;
  }
  
  return false;
}

/**
 * 通用计数器
 *
 * @param string  $name       计数器名字
 * @param Closure $fn         执行器
 * @param int     $max_count  范围时间最多执行次数
 * @param int     $expired_at 有效时间范围
 * @return mixed
 */
function counter($name, $fn, $max_count = 1, $expired_at = 604800)
{
  if (!$count = can_count($name)) {
    return false;
  }
  
  query("replace into pre_wukong_counters (name, count, max_count, expired_at)
		   values('{$name}', {$count}, {$max_count}, {TIMESTAMP}+{$expired_at})");
  return call($fn);
}

/**
 * 根据相关业务 ID 删除资源
 *
 * @param $kind       string 可选参数：images|sounds|videos|origins
 * @param $related_id int
 */
function removeAssets($kind, $related_id)
{
  global $mod, $ac;
  if ($related_id < 1 || !is_numeric($related_id)) {
    return;
  }
  
  $assets = fetch_all("select id from pre_wukong_{$kind} where action='$mod/$ac' and related_id={$related_id}");
  foreach ($assets as $v) {
    query("update pre_wukong_{$kind} set used=0 where id={$v['id']}");
  }
}

/**
 * 根据相关业务 ID 删除图片
 *
 * @param $related_id int
 */
function removeImages($related_id)
{
  removeAssets('images', $related_id);
}

/**
 * 根据相关业务 ID 删除声音
 *
 * @param $related_id int
 */
function removeSounds($related_id)
{
  removeAssets('sounds', $related_id);
}

/**
 * 根据相关业务 ID 删除视频
 *
 * @param $related_id int
 */
function removeVideos($related_id)
{
  removeAssets('videos', $related_id);
}

/**
 * 根据相关业务 ID 删除原图
 *
 * @param $related_id int
 */
function removeOrigins($related_id)
{
  removeAssets('origins', $related_id);
}

/**
 * 对比模型间的新旧资源差异
 *
 * @param array $oldModel
 * @param array $newModel
 * @return array
 */
function diffAssets($oldModel, $newModel = null)
{
  $_pick_assets = function ($data) {
    $assets = [];
    foreach ($data as $v) {
      if (!is_string($v)) {
        continue;
      }
      
      preg_match_all('#(data/attachment/(image|sound|video|origin)s(.dev)?/\d+)#m', $v, $matches);
      $assets = array_merge($assets, array_values($matches[1]));
      
      preg_match_all('#(data/attachment/forum/\d+/\d+/[a-z0-9]+(\.[a-z]+)?)#m', $v, $matches);
      $assets = array_merge($assets, array_values($matches[1]));
    }
    
    return $assets;
  };
  
  // 剔除模型中多余的参数
  $newModel = $newModel ?: $_GET;
  foreach (array_keys($oldModel) as $k) {
    if (!array_key_exists($k, $newModel)) {
      unset($newModel[$k]);
    }
  }
  
  // 返回不再被使用到的文件
  return array_diff($_pick_assets($oldModel), $_pick_assets($newModel));
}

function image_file($name = 'foo.jpg')
{
  global $_G;
  $up  = new discuz_upload();
  $dir = $up->get_target_dir('forum', 0);
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
  }
  
  $file = date('His') . strtolower(random(16)) . '.' . $up->fileext($name);
  return "{$_G['setting']['attachurl']}forum/$dir$file";
}

function clone_image($source)
{
  global $_USER;
  
  if (!is_file($source = trim($source, '/'))) {
    fail('目标图片不存在: ' . $source);
  }
  
  $ret_id = DB::insert('wukong_images', ['user_id' => $_USER['uid'], 'created_at' => time()], true);
  $file   = "data/attachment/images/{$ret_id}" . (isInside() ? '.dev' : '');
  copy($source, $file);
  chmod($file, 0755);
  return "/$file";
}

/**
 * 请求中是否含有某有效参数
 *
 * @param $name
 * @param $default
 * @return bool
 */
function hasParam($name, $default = '')
{
  if (is_null($_GET[$name]) || $_GET[$name] === '') {
    return false;
  }
  
  return $default != strval($_GET[$name]);
}

function arrayToXML($arr)
{
  $code = '';
  foreach ($arr as $k => $v) {
    $code .= "<{$k}>{$v}</{$k}>";
  }
  return "<xml>{$code}</xml>";
}

function XMLToArray($xml)
{
  libxml_disable_entity_loader(true);
  $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
  return json_decode(json_encode($obj), true);
}

function noncestr($length = 32)
{
  $strings  = 'abcdefghijklmnopqrstuvwxyz0123456789';
  $noncestr = '';
  for ($i = 0; $i < $length; $i++) {
    $noncestr .= $strings[mt_rand(0, 35)];
  }
  return $noncestr;
}

function field_seed($tables)
{
  $fields = [];
  foreach (splitbyname($tables) as $item) {
    [$table, $alias] = preg_split('/\s+/', trim($item));
    
    foreach (fetch_all("show full columns from {$table}") as $field) {
      if (preg_match('/(int|float)/', $field['Type'])) {
        $fields[] = ['name' => $field['Field'], 'table' => $alias ?: $table];
      }
    }
  }
  
  $i = date('YmdH') * user_signature() % count($fields);
  return "{$fields[$i]['table']}.{$fields[$i]['name']}";
}

function number_compare($a, $b)
{
  return abs($a - $b) < DELTA;
}

function user_signature()
{
  global $_USER;
  return crc32($_USER['uid'] . $_SERVER['HTTP_ED_TOKEN'] . $_SERVER['HTTP_ED_VERSION'] . $_SERVER['HTTP_ED_SIGNATURE'] . $_SERVER['HTTP_USER_AGENT']);
}

function getallheaders()
{
  $headers = [];
  foreach ($_SERVER as $name => $value) {
    if (substr($name, 0, 5) == 'HTTP_') {
      $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
    }
  }
  return $headers;
}

/**
 * UTF-8转GBK
 *
 * @param string $str 要转换的字符串
 * @return false|string
 */
function utf82gbk($str = '')
{
  if (!$str) return "";
  return iconv("utf-8", "gbk", $str);
}

// Discuz专用redis - - - - - Start
function redis_get($name, $fn = null, $prefix = '')
{
  $value = C::memory()->get($name, $prefix);
  if ($value) {
    return $value;
  } else if ($fn) {
    return $fn();
  } else {
    return false;
  }
}

function redis_set($name, $value, $ttl = 0, $prefix = '')
{
  return C::memory()->set($name, $value, $ttl, $prefix);
}

function redis_rm($name, $prefix = '')
{
  return C::memory()->rm($name, $prefix);
}

function redis_clear()
{
  return C::memory()->clear();
}

function redis_inc($key, $step = 1)
{
  return C::memory()->inc($key, $step);
}

function redis_dec($key, $step = 1)
{
  return C::memory()->dec($key, $step);
}

// Discuz专用redis - - - - - End

function u2g($value)
{
  return iconv('utf-8', 'gbk', $value);
}

function g2u($value)
{
  return iconv('gbk', 'utf-8', $value);
}

function db_limit($count = 10, $page = 0)
{
  $count = max(1, $count);
  if ($page < 1) {
    return " LIMIT $count";
  }
  
  $page  = $page > 0 ? ($page - 1) : 0;
  $start = $page * $count;
  return " LIMIT {$start}, {$count}";
}

function now()
{
  return new \JyUtils\Time\Time();
}

if (!class_exists('NullValue')) {
  class NullValue implements JsonSerializable
  {
    public function jsonSerialize()
    {
      return null;
    }
  }
}

if (!class_exists('DoNotRushBackJSONException')) {
  class DoNotRushBackJSONException extends Exception
  {
  }
}

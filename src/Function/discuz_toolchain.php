<?php
/**
 * @yazi 封装的 Discuz 工具链，
 * 之所以单独放一个文件是为了避免被乱改
 */
$_GET['__LAST_DEFERS__']      = [];
$_GET['__LAST_QUERY_SQL__']   = [];
$_GET['__EXECUTE_DURATION__'] = microtime(true);

if (function_exists('dd')) {
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
}

if (function_exists('dda')) {
  function dda($value, $exit = false)
  {
    global $_G;
    if ($_G['uid'] == 1) {
      if (is_array($value)) {
        print_r($value);
      } else {
        echo $value;
      }
      echo "\n";
    }
    if ($exit) {
      exit();
    }
  }
}

if (function_exists('start_microtime')) {
  function start_microtime()
  {
    $_GET['__EXECUTE_DURATION__'] = microtime(true);
  }
}

if (function_exists('input')) {
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
}

if (function_exists('input')) {
  function inc($name, $increase)
  {
    static $_vars = [];
    
    if (!isset($_vars[$name])) {
      $_vars[$name] = 0;
    }
    return $_vars[$name] += $increase;
  }
}

if (function_exists('null')) {
  function null()
  {
    return new NullValue;
  }
}

if (function_exists('once')) {
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
    if (!array_key_exists($hash, $history)) {
      $history[$hash] = value($val);
    }
    return $history[$hash];
  }
}

/**
 * 以 JSON 格式丢出数据
 *
 * @param mixed $data
 */
if (!function_exists('json')) {
  function json($data)
  {
    global $_G;
    if (isset($_GET['__DO_NOT_RUSH_BACK_JSON_COUNTER__']) && $_GET['__DO_NOT_RUSH_BACK_JSON_COUNTER__'] > 0) {
      $_GET['__DO_NOT_RUSH_BACK_JSON__'][] = $data;
      throw new DoNotRushBackJSONException;
    }
    
    if (!headers_sent()) {
      header('Content-Type: application/json; charset=UTF-8');
    }
    
    unset($data['__RETURN_BY_LAYDATA__']);
    if (isset($_GET['__EXECUTE_DURATION__'])) {
      $data['execute_duration'] = round(microtime(true) - $_GET['__EXECUTE_DURATION__'], 3);
      unset($_GET['__EXECUTE_DURATION__']);
    }
    exit(json_encode(windup($data)));
  }
}

if (!function_exists('succ')) {
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
}

if (!function_exists('fail')) {
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
}
if (function_exists('call')) {
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
}

if (function_exists('swap')) {
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
      global $_G;
      $result = array_shift($_GET['__DO_NOT_RUSH_BACK_JSON__']);
    }
    
    foreach (array_keys($vars) as $k) {
      $_GET[$k] = $oldValues[$k];
    }
    return $result;
  }
}


if (function_exists('flat')) {
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
}

if (function_exists('uselock')) {
  /**
   * 使用全局锁
   * 注意，需要使用succ或fail才可以解锁
   */
  function uselock($key)
  {
    lock($key);
    defer(function () use ($key) {
      unlock($key);
    });
  }
}

if (function_exists('lock')) {
  /**
   * 全局事务锁
   *
   * @param string $key 钥匙，30 字符内
   */
  function lock($key, $tableName = 'pre_wukong_locks')
  {
    DB::query("insert into {$tableName} (`key`, created_at) values ('" . daddslashes($key) . "', " . TIMESTAMP . ")");
  }
}

if (function_exists('unlock')) {
  /**
   * 解除全局事务锁
   *
   * @param string $key 钥匙，30 字符内
   */
  function unlock($key, $tableName = 'pre_wukong_locks')
  {
    DB::query("delete from {$tableName} where `key`='" . daddslashes($key) . "'");
  }
}

if (function_exists('wait')) {
  /**
   * 等待
   *
   * @param Closure $fn
   * @param array   $vars
   * @return mixed
   */
  function wait($fn, $vars = [])
  {
    global $_G;
    
    if (!$_GET['__DO_NOT_RUSH_BACK_JSON__']) {
      $_GET['__DO_NOT_RUSH_BACK_JSON__'] = [];
    }
    
    if ($_GET['__DO_NOT_RUSH_BACK_JSON_COUNTER__'] > 0) {
      $_GET['__DO_NOT_RUSH_BACK_JSON_COUNTER__']++;
    } else {
      $_GET['__DO_NOT_RUSH_BACK_JSON_COUNTER__'] = 1;
    }
    
    $result = swap($fn, $vars);
    if (--$_GET['__DO_NOT_RUSH_BACK_JSON_COUNTER__'] < 1) {
      unset($_GET['__DO_NOT_RUSH_BACK_JSON_COUNTER__']);
    }
    
    return $result;
  }
}

if (function_exists('waitd')) {
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
}

if (function_exists('waitf')) {
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
}

if (function_exists('defer')) {
  /**
   * 延迟函数
   *
   * @param Closure|string $fn
   */
  function defer($fn)
  {
    global $_G;
    $_GET['__LAST_DEFERS__'][] = $fn;
  }
}

if (function_exists('defers')) {
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
}

if (function_exists('deferf')) {
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
}

if (function_exists('windup')) {
  function windup($data = null)
  {
    global $_G;
    
    while (count($_GET['__LAST_DEFERS__']) > 0) {
      call(array_pop($_GET['__LAST_DEFERS__']), $data);
    }
    return $data;
  }
}

if (function_exists('value')) {
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
}

if (function_exists('first')) {
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
}

if (function_exists('last')) {
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
}

if (function_exists('match')) {
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
}

if (function_exists('combine')) {
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
}

if (function_exists('contains')) {
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
}

if (function_exists('is_nil')) {
  function is_nil($var)
  {
    return $var instanceof NullValue;
  }
}

if (function_exists('is_assoc')) {
  /**
   * 是否为关联数组
   *
   * @param array $var
   * @return bool
   */
  function is_assoc($var)
  {
    return is_array($var) and (array_values($var) !== $var);
  }
}

if (function_exists('is_value')) {
  function is_value($var)
  {
    return $var !== '' and $var !== null;
  }
}

if (function_exists('is_number')) {
  function is_number($var)
  {
    return strlen(intval($var)) == strlen($var);
  }
}

if (function_exists('file_size')) {
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
}

if (function_exists('laydata')) {
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
      $var['data'] = $sql;
      $var['per']  = $var['count'] = count($var['data']);
    } else {
      $var['data']  = paginate(function ($start, $limit) use ($sql, &$var) {
        $var['per'] = $limit;
        return fetch_all("$sql limit $start, $limit");
      });
      $var['count'] = fetch_first(preg_replace('#^select\s+(.+?)\s+from\s#i', 'select $1, count(*) from ', $sql))['count(*)'];
    }
    
    if ($cb) {
      $cb($var['data'], $var['count']);
    }
    
    global $_G;
    if ($_GET['__DO_NOT_RUSH_BACK_JSON_COUNTER__'] > 0) {
      $_GET['__DO_NOT_RUSH_BACK_JSON__'][] = $var + ['__RETURN_BY_LAYDATA__' => true];
      throw new DoNotRushBackJSONException;
    }
    
    if ($cacheTime > 0) {
      redis_set($cacheName, $var, $cacheTime);
    }
    json($var);
  }
}

if (function_exists('paginate')) {
  /**
   * 分页函数
   *
   * @param Closure $cb
   * @return mixed
   */
  function paginate(Closure $cb)
  {
    $page  = $_GET['page'] > 0 ? intval($_GET['page']) : 1;
    $limit = $_GET['limit'] > 0 ? intval($_GET['limit']) : 20;
    
    return $cb(($page - 1) * $limit, $limit, $page);
  }
}

if (function_exists('joinbyid')) {
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
}

if (function_exists('joinbyname')) {
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
}

if (function_exists('splitbyid')) {
  /**
   * 分裂数据中的 ID
   *
   * @param int|string|array $data
   * @param string           $field
   * @return array
   */
  function splitbyid($data, $field = 'id')
  {
    return array_filter(splitbyname($data, $field), 'is_numeric');
  }
}

if (function_exists('splitbyname')) {
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
}

if (function_exists('groupby')) {
  function groupby($data, Closure $fn)
  {
    $ret = [];
    foreach ($data as $v) {
      @$ret[$fn($v)] = $v;
    }
    
    return $ret;
  }
}

if (function_exists('groupbyid')) {
  function groupbyid($data, $field = 'id')
  {
    $ret = [];
    foreach ($data as $v) {
      @$ret[$v[$field]] = $v;
    }
    
    return $ret;
  }
}

if (function_exists('groupsby')) {
  function groupsby($data, Closure $fn)
  {
    $ret = [];
    foreach ($data as $v) {
      @$ret[$fn($v)][] = $v;
    }
    
    return $ret;
  }
}

if (function_exists('groupsbyid')) {
  function groupsbyid($data, $field = 'id')
  {
    $ret = [];
    foreach ($data as $v) {
      @$ret[$v[$field]][] = $v;
    }
    
    return $ret;
  }
}

if (function_exists('groupbyprefix')) {
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
}

if (function_exists('idornull')) {
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
}

if (function_exists('findone')) {
  function findone($array, $value, $column = 'id')
  {
    $result = filterby($array, $value, $column);
    return count($result) ? $result[0] : null;
  }
}

if (function_exists('countby')) {
  function countby($array, $value, $column = 'id')
  {
    return count(filterby($array, $value, $column));
  }
}

if (function_exists('sortby')) {
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
}

if (function_exists('linkby')) {
  function linkby(&$source, $target, $source_column, $target_column)
  {
    foreach ($source as &$v) {
      $v[$target_column] = $target[$v[$source_column]];
    }
    
    return $source;
  }
}

if (function_exists('filterby')) {
  function filterby($array, $value, $column = 'id')
  {
    return array_values(array_filter($array, function ($v) use ($column, $value) {
      return $v[$column] == $value;
    }));
  }
}

if (function_exists('rearrange')) {
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
}

if (function_exists('transaction')) {
  /**
   * 开启一个事务
   *
   * @param Closure $fn
   * @return mixed
   */
  function transaction($fn)
  {
    global $_G;
    if ($_GET['__IN_TRANSACTION__'] > 0) {
      $_GET['__IN_TRANSACTION__']++;
    } else {
      $_GET['__IN_TRANSACTION__'] = 1;
      query('set autocommit=0');
      query('begin');
    }
    
    $res = $fn();
    if (--$_GET['__IN_TRANSACTION__'] < 1) {
      query('commit');
      query('set autocommit=1');
      unset($_GET['__IN_TRANSACTION__']);
    }
    
    return $res;
  }
}

if (function_exists('transaction_back')) {
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
}

if (function_exists('ltransaction')) {
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
}

if (function_exists('onlyfields')) {
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
      if (!in_array($k, $fields)) {
        unset($data[$k]);
      }
    }
    return $data;
  }
}

if (function_exists('exceptfields')) {
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
      if (in_array($k, $fields)) {
        unset($data[$k]);
      }
    }
    return $data;
  }
}

if (function_exists('last_sql')) {
  function last_sql($n = 0)
  {
    global $_G;
    if (!$n && $n !== 0) {
      return $_GET['__LAST_QUERY_SQL__'];
    }
    
    return $_GET['__LAST_QUERY_SQL__'][$n];
  }
  
}


if (function_exists('create_sql')) {
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
  
}

if (function_exists('safety_sql')) {
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
    $sql = preg_replace('/\s+in\s*\(\)/', ' in (0)', $sql);
    $sql = preg_replace('/([a-zA-Z_\.]+)=($|\s)/', ' 0 ', $sql);
    
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
  
}

if (function_exists('safety_field')) {
  function safety_field($name)
  {
    return '`' . implode('`.`', explode('.', str_replace('`', '', $name))) . '`';
  }
}

if (function_exists('orderby')) {
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
      
      $order = in_array($order, ['asc', 'desc']) ? $order : 'asc';
      $ret   .= safety_field($name) . " {$order},";
    }
    
    return rtrim($ret, ',');
  }
}

if (function_exists('query')) {
  function query($sql, $arg = [], $silent = false, $unbuffered = false)
  {
    return call(function ($s) use ($arg, $silent, $unbuffered) {
      global $_G;
      
      array_unshift($_GET['__LAST_QUERY_SQL__'], $s);
      return DB::query($s, $arg, $silent, $unbuffered);
    }, safety_sql($sql));
  }
}

if (function_exists('fetch_all')) {
  function fetch_all($sql, $arg = [], $keyfield = '', $silent = false)
  {
    return call(function ($s) use ($arg, $keyfield, $silent) {
      global $_G;
      
      array_unshift($_GET['__LAST_QUERY_SQL__'], $s);
      return DB::fetch_all($s, $arg, $keyfield, $silent);
    }, safety_sql($sql));
  }
}

if (function_exists('fetch_first')) {
  function fetch_first($sql, $arg = [], $silent = false)
  {
    return call(function ($s) use ($arg, $silent) {
      global $_G;
      
      array_unshift($_GET['__LAST_QUERY_SQL__'], $s);
      return DB::fetch_first($s, $arg, $silent);
    }, safety_sql($sql));
  }
}

if (function_exists('result_first')) {
  function result_first($sql, $arg = [], $silent = false)
  {
    return call(function ($s) use ($arg, $silent) {
      global $_G;
      array_unshift($_GET['__LAST_QUERY_SQL__'], $s);
      return DB::result_first($s, $arg, $silent);
    }, safety_sql($sql));
  }
}

if (function_exists('array_depth')) {
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
}

if (function_exists('generate_order_id')) {
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
}

if (function_exists('can_count')) {
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
}

if (function_exists('counter')) {
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
}

if (function_exists('removeImages')) {
  /**
   * 根据相关业务 ID 删除图片
   *
   * @param $related_id int
   */
  function removeImages($related_id)
  {
    removeAssets('images', $related_id);
  }
}

if (function_exists('removeSounds')) {
  /**
   * 根据相关业务 ID 删除声音
   *
   * @param $related_id int
   */
  function removeSounds($related_id)
  {
    removeAssets('sounds', $related_id);
  }
}

if (function_exists('removeVideos')) {
  /**
   * 根据相关业务 ID 删除视频
   *
   * @param $related_id int
   */
  function removeVideos($related_id)
  {
    removeAssets('videos', $related_id);
  }
}

if (function_exists('diffAssets')) {
  /**
   * 对比模型间的新旧资源差异
   *
   * @param array $oldModel
   * @param array $newModel
   * @return array
   */
  function diffAssets($oldModel, $newModel = null, &$newIds = null)
  {
    $_pick_assets = function ($data) {
      $assets = [];
      foreach ($data as $v) {
        if (!is_string($v)) {
          continue;
        }
        
        preg_match_all('#(data/attachment/images/\d+)#m', $v, $matches);
        $assets = array_merge($assets, array_values($matches[1]));
        
        preg_match_all('#(data/attachment/sounds/\d+)#m', $v, $matches);
        $assets = array_merge($assets, array_values($matches[1]));
        
        preg_match_all('#(data/attachment/videos/\d+)#m', $v, $matches);
        $assets = array_merge($assets, array_values($matches[1]));
        
        preg_match_all('#(data/attachment/forum/\d+/\d+/[a-z0-9]+(\.[a-z]+)?)#m', $v, $matches);
        $assets = array_merge($assets, array_values($matches[1]));
      }
      
      return $assets;
    };
    
    $_exclude_invalid_files = function ($files) {
      $result = [];
      foreach ($files as $v) {
        if (is_file(trim($v))) {
          $result[] = trim($v);
        }
      }
      
      return $result;
    };
    
    $_get_file_ids = function ($files) {
      $result = [];
      foreach ($files as $v) {
        if (preg_match('#data/attachment/images/(\d+)#', $v, $matches)) {
          $result['images'][] = $matches[1];
          
        } elseif (preg_match('#data/attachment/sounds/(\d+)#', $v, $matches)) {
          $result['sounds'][] = $matches[1];
          
        } elseif (preg_match('#data/attachment/videos/(\d+)#', $v, $matches)) {
          $result['videos'][] = $matches[1];
        }
      }
      return $result;
    };
    
    // 剔除模型中多余的参数
    $newModel = $newModel ?: $_GET;
    foreach (array_keys($oldModel) as $k) {
      if (!array_key_exists($k, $newModel)) {
        unset($newModel[$k]);
      }
    }
    
    // 挑出那些参数中的资源
    $oldAssets = $_exclude_invalid_files($_pick_assets($oldModel));
    $newAssets = $_exclude_invalid_files($_pick_assets($newModel));
    
    // 返回不再被使用到的文件
    if (isset($newIds)) {
      $newIds = $_get_file_ids($newAssets);
    }
    return array_diff($oldAssets, $newAssets);
  }
}

if (function_exists('image_file')) {
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
}

if (function_exists('clone_image')) {
  function clone_image($source)
  {
    $file   = image_file($source);
    $source = trim($source, '/');
    
    copy($source, $file);
    copy("{$source}.mini", "{$file}.mini");
    
    return "/$file";
  }
}

if (function_exists('clone_image')) {
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
}

if (function_exists('arrayToXML')) {
  function arrayToXML($arr)
  {
    $code = '';
    foreach ($arr as $k => $v) {
      $code .= "<{$k}>{$v}</{$k}>";
    }
    return "<xml>{$code}</xml>";
  }
}

if (function_exists('XMLToArray')) {
  function XMLToArray($xml)
  {
    libxml_disable_entity_loader(true);
    $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    return json_decode(json_encode($obj), true);
  }
}

if (function_exists('noncestr')) {
  function noncestr($length = 32)
  {
    $strings  = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $noncestr = '';
    
    for ($i = 0; $i < $length; $i++) {
      $noncestr .= $strings[mt_rand(0, 35)];
    }
    return $noncestr;
  }
}

if (function_exists('field_seed')) {
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
}

if (function_exists('user_signature')) {
  function user_signature()
  {
    global $_USER;
    return crc32($_USER['uid'] . $_SERVER['HTTP_ED_TOKEN'] . $_SERVER['HTTP_ED_VERSION'] . $_SERVER['HTTP_ED_SIGNATURE'] . $_SERVER['HTTP_USER_AGENT']);
  }
}

if (function_exists('utf82gbk')) {
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
}

if (function_exists('redis_get')) {
  // Discuz专用redis - - - - - Start
  function redis_get($name, $fn, $prefix = '')
  {
    $value = C::memory()->get($name, $prefix);
    if ($value) {
      return $value;
    }
    return $fn();
  }
}

if (function_exists('redis_set')) {
  function redis_set($name, $value, $ttl = 0, $prefix = '')
  {
    return C::memory()->set($name, $value, $ttl, $prefix);
  }
}

if (function_exists('redis_rm')) {
  function redis_rm($name, $prefix = '')
  {
    return C::memory()->rm($name, $prefix);
  }
}

if (function_exists('redis_clear')) {
  function redis_clear()
  {
    return C::memory()->clear();
  }
}

if (function_exists('redis_inc')) {
  function redis_inc($key, $step = 1)
  {
    return C::memory()->inc($key, $step);
  }
}

if (function_exists('redis_dec')) {
  function redis_dec($key, $step = 1)
  {
    return C::memory()->dec($key, $step);
  }
}

// Discuz专用redis - - - - - End

if (function_exists('u2g')) {
  function u2g($value)
  {
    return iconv('utf-8', 'gbk', $value);
  }
}

if (function_exists('g2u')) {
  function g2u($value)
  {
    return iconv('gbk', 'utf-8', $value);
  }
}

if (function_exists('db_limit')) {
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
}

if (function_exists('now')) {
  function now()
  {
    return new \JyUtils\Time\Time();
  }
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

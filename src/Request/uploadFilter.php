<?php

namespace JyUtils\Request;

class uploadFilter
{
  /**
   * 上传文件安全过虑，当文件存在木马嫌疑时，将会回调
   *
   * @param callable $fn
   */
  public static function start(callable $fn)
  {
    $safeLevel = [
      '1' => '一级警报，文件中带有PHP头<?php',
      '2' => '二级警报，文件中带有PHP头<?php，并带有非安全函数：',
    ];
    foreach ($_FILES as $file) {
      $bin = file_get_contents($file['tmp_name']);
      if (stripos($bin, '<?php') === false) {
        continue;
      }
      $callData = [];
      if ($error = self::safeSystem($bin)) {
        $callData = [
          'level' => 2,
          'error' => $safeLevel[2] . $error,
        ];
      } else if ($error = self::safeCode($bin)) {
        $callData = [
          'level' => 2,
          'error' => $safeLevel[2] . $error,
        ];
      } else if ($error = self::safeFile($bin)) {
        $callData = [
          'level' => 2,
          'error' => $safeLevel[2] . $error,
        ];
      } else {
        $callData = [
          'level' => 1,
          'error' => $safeLevel[1],
        ];
      }
      $fn && $fn($callData);
    }
  }
  
  // 执行系统命令
  private static function safeSystem($bin)
  {
    if (stripos($bin, 'shell_exec') !== false) {
      return 'shell_exec';
    } elseif (stripos($bin, 'popen') !== false) {
      return 'popen';
    } elseif (stripos($bin, 'proc_open') !== false) {
      return 'proc_open';
    } elseif (stripos($bin, 'system') !== false) {
      return 'system';
    }
    return false;
  }
  
  // 代码执行
  private static function safeCode($bin)
  {
    if (stripos($bin, 'eval') !== false) {
      return 'eval';
    } elseif (stripos($bin, 'assert') !== false) {
      return 'assert';
    } elseif (stripos($bin, 'call_user_func') !== false) {
      return 'call_user_func';
    } elseif (stripos($bin, 'create_function') !== false) {
      return 'create_function';
    } elseif (stripos($bin, 'base64_decode') !== false) {
      return 'base64_decode';
    }
    return false;
  }
  
  // 文件包含与生成
  private static function safeFile($bin)
  {
    if (stripos($bin, 'file_get_contents') !== false) {
      return 'file_get_contents';
    } elseif (stripos($bin, 'file_put_contents') !== false) {
      return 'file_put_contents';
    } elseif (stripos($bin, 'fputs') !== false) {
      return 'fputs';
    } elseif (stripos($bin, 'require') !== false) {
      return 'require';
    } elseif (stripos($bin, 'include') !== false) {
      return 'include';
    }
    return false;
  }
}

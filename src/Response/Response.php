<?php

namespace JyUtils\Response;

/**
 * Class Response 响应类
 * @method static \JyUtils\Response\_response mp4($file)
 * @method static \JyUtils\Response\_response xml($data)
 * @method static \JyUtils\Response\_response json($data)
 * @method static \JyUtils\Response\_response image($image)
 * @method static \JyUtils\Response\_response response($fn, $format = 'html', array $headers = [], $status = 200)
 */
class Response
{
    private static $instance;
    
    public static function __callStatic($method, $args)
    {
        if (is_null(static::$instance)) {
            self::$instance = new _response();
            return call_user_func_array([self::$instance, $method], $args);
        }
        return self::$instance;
    }
}

class _response
{
    private $url = null;
    
    /**
     * 响应
     *
     * @param        $fn
     * @param string $format  响应格式
     * @param array  $headers 额外响应的协议头
     * @param int    $status  响应的状态码
     */
    public function response($fn, $format = 'html', array $headers = [], $status = 200)
    {
        // 图片类
        if (in_array($format, ['gif', 'jpeg', 'png'])) {
            $headers = array_merge($headers, ["Content-type" => "image/{$format}"]);
            
            // application类
        } elseif (in_array($format, [
            'json',
            'xhtml+xml',
            'xml',
            'atom+xml',
            'pdf',
            'msword',
            'octet-stream',
            'x-www-form-urlencoded',
        ])) {
            $headers = array_merge($headers, ["Content-type" => "application/{$format}"]);
            
            // Mp4视频流
        } else if (in_array($format, ['mp4'])) {
            
            // 文本类 html|plain|xml
        } else {
            $headers = array_merge($headers, ["Content-type" => "text/{$format}"]);
        }
        
        // 输出头
        foreach ($headers as $name => $value) {
            if (strtolower($name) == 'content-type') {
                header($name . ': ' . $value, true, $status);
            } else {
                header($name . ': ' . $value);
            }
        }
        exit($fn());
    }
    
    public function json($data)
    {
        $this->response(function () use ($data) {
            echo $data;
        }, 'json');
    }
    
    public function xml($data)
    {
        $this->response(function () use ($data) {
            echo $data;
        }, 'xml');
    }
    
    /**
     * 响应输出图片，自动识别图片类型
     *
     * @param string $image 图片路径|图片二进制数据
     */
    public function image($image)
    {
        $image = file_exists($image) ? file_get_contents($image) : $image;
        header('Content-type: ' . $this->getFileType($image), true);
        exit($image);
    }
    
    /**
     * 播放Mp4视频文件
     *
     * @param string $file mp4的本地绝对路径|mp4二进制数据
     * @return
     */
    public function mp4($file)
    {
        // 二进制数据，直接输出
        if (!file_exists($file)) {
            header('Content-type: video/mp4');
            header("Accept-Ranges: 0-" . strlen($file));
            exit($file);
        }
        
        // 文件路径
        $fp = @fopen($file, 'rb');
        $size = filesize($file);   // File size
        $length = $size;           // Content length
        $start = 0;                // Start byte
        $end = $size - 1;          // End byte
        header('Content-type: video/mp4');
        header("Accept-Ranges: 0-$length");
        if (isset($_SERVER['HTTP_RANGE'])) {
            $c_start = $start;
            $c_end = $end;
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            if ($range == '-') {
                $c_start = $size - substr($range, 1);
            } else {
                $range = explode('-', $range);
                $c_start = $range[0];
                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
            }
            $c_end = ($c_end > $end) ? $end : $c_end;
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            $start = $c_start;
            $end = $c_end;
            $length = $end - $start + 1;
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: " . $length);
        $buffer = 1024 * 8;
        while (!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                $buffer = $end - $p + 1;
            }
            set_time_limit(0);
            echo fread($fp, $buffer);
            flush();
        }
        fclose($fp);
        exit();
    }
    
    
    /**
     * 取文件类型
     *
     * @param string $file 文件路径|文件二进制数据
     * @return string
     */
    private function getFileType($file)
    {
        $bin = file_exists($file) ? file_get_contents($file) : $file;
        $str_info = @unpack("C2chars", $bin);
        $type_code = intval($str_info['chars1'] . $str_info['chars2']);
        switch ($type_code) {
            case 7790:
                $file_type = 'application/octet-stream';
                break;
            case 7784:
                $file_type = 'application/octet-stream';
                break;
            case 8075:
                $file_type = 'application/octet-stream';
                break;
            case 8297:
                $file_type = 'application/octet-stream';
                break;
            case 255216:
                $file_type = 'image/jpeg';
                break;
            case 7173:
                $file_type = 'image/gif';
                break;
            case 6677:
                $file_type = 'image/bmp';
                break;
            case 13780:
                $file_type = 'image/png';
                break;
            default:
                $file_type = 'application/octet-stream';
                break;
        }
        return $file_type;
    }
}

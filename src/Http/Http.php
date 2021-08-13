<?php

namespace JyUtils\Http;

use CURLFile;

/**
 * http 访问类，不依赖任何库
 *
 * @Author  果子
 * @使用示例：https://bd.125.la/?mod=experience&tid=1757&pid=2203
 * @最后更新：2020年9月29日 08:52:23
 *
 * @method static \JyUtils\Http\_http contentType(string $value)
 * @method static \JyUtils\Http\_http accept($value)
 * @method static \JyUtils\Http\_http acceptJson()
 * @method static \JyUtils\Http\_http headers(array $headers = [], $cover = false)
 * @method static \JyUtils\Http\_http getHeaders($name = null)
 * @method static \JyUtils\Http\_http cookies($value)
 * @method static \JyUtils\Http\_http getCookies($name = null)
 * @method static \JyUtils\Http\_http redirect()
 * @method static \JyUtils\Http\_http setUa($value)
 * @method static \JyUtils\Http\_http setTimeout($seconds = 10)
 * @method static \JyUtils\Http\_http openSsl(string $sslkey, string $sslcert)
 * @method static \JyUtils\Http\_http attach(string $name, string $filePath, string $filename = null)
 * @method static \JyUtils\Http\_http asForm()
 * @method static \JyUtils\Http\_http asJson()
 * @method static \JyUtils\Http\_http post(string $url, $data = [])
 * @method static \JyUtils\Http\_http get($url)
 * @method static \JyUtils\Http\_http body()
 * @method static \JyUtils\Http\_http json()
 * @method static \JyUtils\Http\_http object()
 * @method static \JyUtils\Http\_http successful()
 * @method static \JyUtils\Http\_http ok()
 * @method static \JyUtils\Http\_http isRedirect()
 * @method static \JyUtils\Http\_http getRedirectUrl()
 * @method static \JyUtils\Http\_http clientError()
 * @method static \JyUtils\Http\_http serverError()
 * @method static \JyUtils\Http\_http status()
 */
class Http
{
    private static $instance;
    
    public static function __callStatic($method, $args)
    {
        if (is_null(static::$instance)) {
            self::$instance         = new _http();
            self::$instance->opt    = [];
            self::$instance->result = null;
            return call_user_func_array([self::$instance, $method], $args);
        }
        self::$instance->opt    = [];
        self::$instance->result = null;
        return call_user_func_array([self::$instance, $method], $args);
    }
}

class _http
{
    public $bodyFormat;
    public $opt = [];
    public $result;                           // 返回协议头
    private $headers = '';                    // 返回cookies
    private $cookies = '';                    // 返回结果
    private $httpCode;                        // 返回状态码
    private $pendingFiles;                    // 上传文件模式的数据
    private $error = null;                    // 错误
    private $redirect = true;                 // 是否禁止重定向
    
    function __construct()
    {
        // 设置默认访问信息
        $this->setDefault();
        
        $this->error = null;
    }
    
    /**
     * 指定请求的内容类型。
     *
     * @param string $value
     * @return $this
     */
    public function contentType(string $value)
    {
        return $this->addHeader('Content-Type: ' . $value);
    }
    
    /**
     * 指示服务器应返回的内容类型。
     *
     * @param string $value
     * @return $this
     */
    public function accept($value)
    {
        return $this->addHeader('Accept: ' . $value);
    }
    
    /**
     * 指示服务器应返回JSON。
     *
     * @return $this
     */
    public function acceptJson()
    {
        return $this->accept('application/json');
    }
    
    /**
     * 「设置/增加」访问协议头，默认协议头也将覆盖
     *
     * @param array $headers 成员格式，如：Connection: Keep-Alive
     * @param bool  $cover   是否覆盖默认或已设置协作头，默认为false，false=增加，true=覆盖
     * @return http
     */
    public function headers(array $headers = [], $cover = false)
    {
        if (!$headers) {
            return $this;
        }
        if ($cover) {
            $this->opt['headers'] = $headers;
        } else {
            $this->opt['headers'] = array_merge((array)$this->opt['headers'], $headers);
        }
        return $this;
    }
    
    /**
     * 「获取」访问结果的协议头
     *
     * @param string $name 协议头名称，留空将获取全部协议头
     * @return
     */
    public function getHeaders($name = null)
    {
        if (is_null($name)) {
            return $this->headers;
        } else {
            $list = array_filter(explode("\n", $this->headers), function ($v, $k) {
                return strpos($v, ": ");
            }, ARRAY_FILTER_USE_BOTH);
            
            foreach ($list as $v) {
                list($_name, $_value) = explode(": ", $v);
                if ($_name == trim($name)) {
                    return $_value;
                }
            }
            return '';
        }
    }
    
    /**
     * 「设置」Cookie
     *
     * @param string $value
     * @return http
     */
    public function cookies($value)
    {
        $this->opt['cookies'] = $value;
        return $this;
    }
    
    /**
     * 「获取」访问结果的Cookies
     *
     * @param string $name cookie名称，留空将获取全部Cookies
     * @return string
     */
    public function getCookies($name = null)
    {
        $list = array_filter(explode("\n", $this->headers), function ($v, $k) {
            return stripos($v, 'Set-Cookie') !== false;
        }, ARRAY_FILTER_USE_BOTH);
        
        foreach ($list as &$v) {
            list($temp, $_) = explode(';', $v);
            list($_, $v) = explode(':', $temp);
            $v = trim($v);
            unset($v);
        }
        $cookies = implode('; ', $list);
        if (is_null($name)) {
            return $cookies;
        }
        
        // 通过Name取指定Cookie
        foreach ($list as $v) {
            list($_name, $_value) = explode('=', $v);
            if ($name == $_name) {
                return $_value;
            }
        }
        return '';
    }
    
    /**
     * 「设置」访问的来源地址
     *
     * @param string $value
     * @return http
     */
    public function setReferer($value)
    {
        $this->opt['referer'] = $value;
        return $this;
    }
    
    /**
     * 「设置」 UA, user-agent
     *
     * @param  $value
     * @return http
     */
    public function setUa($value)
    {
        $this->opt['ua'] = $value;
        return $this;
    }
    
    /**
     * 禁止自动重定向(未调用禁止时，为自动重定向)
     *
     * @return http
     */
    public function redirect()
    {
        $this->redirect = false;
        return $this;
    }
    
    /**
     * 「设置」访问超时
     *
     * @param int $seconds 超时值，单位：秒
     * @return http
     */
    public function setTimeout($seconds = 10)
    {
        $this->opt['timeout'] = $seconds;
        return $this;
    }
    
    /**
     * 开启SSL证书访问(提交)
     *
     * @param string $sslkey  证书key文件路径
     * @param string $sslcert 证书cert文件路径
     * @return $this
     */
    public function openSsl(string $sslkey, string $sslcert)
    {
        $this->opt['sslkey']  = $sslkey;
        $this->opt['sslcert'] = $sslcert;
        return $this;
    }
    
    /**
     * 文件上传专用
     *
     * @param string      $name     表单名称，如：file
     * @param string      $filePath 要上传的文件路径
     * @param string|null $filename 可空，自定义文件名
     * @return $this
     */
    public function attach(string $name, string $filePath, string $filename = null)
    {
        if (!file_exists($filePath)) {
            $this->error = '要上传的文件不存在：' . $filePath;
            return $this;
        }
        $this->asMultipart();
        $this->pendingFiles = array_filter([
                                               $name => new CURLFILE($filePath, null, $filename),
                                           ]);
        return $this;
    }
    
    /**
     * POST请求
     *
     * @param string       $url
     * @param array|string $data
     * @return $this
     */
    public function post(string $url, $data = [])
    {
        $this->opt['type'] = "POST";
        
        // 文件模式
        if ($this->bodyFormat == 'multipart') {
            $this->opt['data'] = $data ? array_merge($data, $this->pendingFiles) : $this->pendingFiles;
        } else {
            $this->opt['data'] = $data;
        }
        // print_r($this->opt);
        $this->result = $this->curl($url, $this->opt);
        return $this;
    }
    
    /**
     * GET请求
     *
     * @param $url
     * @return $this
     */
    public function get($url)
    {
        $this->opt['type'] = "GET";
        $this->result      = $this->curl($url, $this->opt);
        return $this;
    }
    
    /**
     * 指定请求方式为表单
     *
     * @return $this
     */
    public function asForm()
    {
        $this->bodyFormat('form_params')->addHeader('Content-Type: application/x-www-form-urlencoded');
        return $this;
    }
    
    /**
     * 指定请求方式为Json
     *
     * @return $this
     */
    public function asJson()
    {
        $this->bodyFormat('json')->addHeader('Content-Type: application/json');
        return $this;
    }
    
    /**
     * 取返回数据
     *
     * @return string|null
     */
    public function body()
    {
        if (!is_null($this->error)) {
            return $this->error;
        }
        return (string)$this->result;
    }
    
    /**
     * 将返回的数据进行JSON解码
     *
     * @return false|mixed|string
     */
    public function json()
    {
        if (!is_null($this->error)) {
            return json_encode(['error' => $this->error]);
        }
        return json_decode($this->result, true);
    }
    
    /**
     * 获取响应的JSON解码主体作为对象。
     *
     * @return false|mixed|string
     */
    public function object()
    {
        if (!is_null($this->error)) {
            return json_encode(['error' => $this->error]);
        }
        return json_decode($this->result, false);
    }
    
    /**
     * 判断请求是否成功。
     *
     * @return bool
     */
    public function successful()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }
    
    /**
     * 确定响应代码是否为“ OK”。
     *
     * @return bool
     */
    public function ok()
    {
        return $this->status() === 200;
    }
    
    /**
     * 确定响应是否为重定向。
     *
     * @return bool
     */
    public function isRedirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }
    
    /**
     * 取重定向后的地址(注意：必须禁止重定向才可以取到)
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        if (preg_match('/^Location:\s?(.*?)$/m', $this->getHeaders(), $res)) {
            return $res[1] ?? '';
        }
        return '';
    }
    
    /**
     * 客户端错误
     *
     * @return bool
     */
    public function clientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }
    
    /**
     * 服务器错误
     *
     * @return bool
     */
    public function serverError()
    {
        return $this->status() >= 500;
    }
    
    /**
     * 取请求返回的状态码，如：200
     *
     * @return mixed
     */
    public function status()
    {
        return $this->httpCode;
    }
    
    /**
     * 指定请求的正文格式。
     *
     * @param string $format
     * @return $this
     */
    private function bodyFormat(string $format)
    {
        $this->bodyFormat = $format;
        return $this;
    }
    
    /**
     * 「增加」协作头
     *
     * @param string $value 如：Accept-Language: zh-cn
     * @return $this
     */
    private function addHeader(string $value)
    {
        $this->opt['headers'][] = $value;
        return $this;
    }
    
    /**
     * curl HTTP请求
     *
     * @param string $url 网址
     * @param mixed  $opt 请求参数
     * @return string
     */
    private function curl($url, $opt = 'GET')
    {
        if (!$this->_check($url)) {
            return $this;
        }
        
        $ch = curl_init($url);
        
        // 是否开启SSL证书访问
        $ssl = $opt['sslkey'] or $opt['sslcert'];
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $ssl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->redirect);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        if ($opt == 'POST' || (isset($opt['type']) && $opt['type'] == 'POST')) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, isset($opt['data']) ? $opt['data'] : '');
        }
        
        if (is_array($opt)) {
            // User-Agent
            if (array_key_exists('ua', $opt)) {
                curl_setopt($ch, CURLOPT_USERAGENT, $opt['ua']);
            }
            
            // Headers
            if (array_key_exists('headers', $opt)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, (array)$opt['headers']);
            }
            
            // Cookies
            if (array_key_exists('cookies', $opt)) {
                curl_setopt($ch, CURLOPT_COOKIE, $opt['cookies']);
            }
            
            // Referer
            if (array_key_exists('referer', $opt)) {
                curl_setopt($ch, CURLOPT_REFERER, $opt['referer']);
            }
            
            // Timeout
            if (array_key_exists('timeout', $opt)) {
                curl_setopt($ch, CURLOPT_TIMEOUT, $opt['timeout']);
            }
            
            // SSL
            if (array_key_exists('sslkey', $opt)) {
                curl_setopt($ch, CURLOPT_SSLKEY, $opt['sslkey']);
            }
            if (array_key_exists('sslcert', $opt)) {
                curl_setopt($ch, CURLOPT_SSLCERT, $opt['sslcert']);
            }
        }
        
        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $result = curl_error($ch);
        } else {
            // 取出状态码
            $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // 获取头长度
            $length = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            
            // 取出头信息
            $this->headers = substr($result, 0, $length);
            
            // 去掉头信息
            $result = substr($result, $length);
        }
        
        curl_close($ch);
        return $result;
    }
    
    /**
     * 设置默认访问信息
     *
     * @return $this
     */
    private function setDefault()
    {
        $this->opt = [];
        
        // 默认请求头
        $this->opt['headers'][] = 'Connection: Keep-Alive';
        $this->opt['headers'][] = 'Accept-Language: zh-cn';
        $this->opt['headers'][] = 'Accept: text/javascript, text/html, application/xml, text/xml, */*';
        
        // 默认UA
        $this->opt['ua'] = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36';
        
        // 默认超时值
        $this->opt['timeout'] = 20;
        
        return $this;
    }
    
    /**
     * 设置为multipart提交方式
     *
     * @return http
     */
    private function asMultipart()
    {
        return $this->bodyFormat('multipart')->addHeader('Content-Type: multipart/form-data');
    }
    
    /**
     * 访问前，检查
     *
     * @param string $url
     * @return bool
     */
    private function _check($url)
    {
        if (strtolower(substr($url, 0, 4)) != 'http') {
            $this->error = 'url地址有误';
            return false;
            
        } elseif ($this->opt['sslkey'] and !$this->opt['sslcert']) {
            $this->error = 'ssl证书不能为空';
            return false;
            
        } elseif (!$this->opt['sslkey'] and $this->opt['sslcert']) {
            $this->error = 'ssl证书密钥不能为空';
            return false;
        }
        return true;
    }
}

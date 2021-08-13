<?php

namespace JyUtils\Time;

class Time
{
    /**
     * 取时间范围 - 年
     *
     * @param int $last      -1表示往上一年，-2上两年，以此类推
     * @param int $timestamp 留空为当时间戳
     * @return array
     */
    public function getRangeYear($last = 0, $timestamp = 0)
    {
        $timestamp = $timestamp ? $timestamp : time();
        if ($last < 0) {
            $timestamp = strtotime("{$last} year", $timestamp);
        }
        return [
            'number'     => date('Y', $timestamp),
            'time_start' => strtotime(date('Y-01-01 00:00:00', $timestamp)),
            'time_end'   => strtotime(date("Y-12-31 23:59:59", $timestamp)),
        ];
    }
    
    /**
     * 取时间范围 - 某年所有季度的时间范围
     *
     * @param int $timestamp 留空为当时间戳
     * @return array[]
     */
    public function getRangeYearAllQuarter($timestamp = 0)
    {
        $timestamp = $timestamp ? $timestamp : time();
        return [
            $this->get_appoint_quarter_by_time(strtotime(date('Y-01-01', $timestamp))),
            $this->get_appoint_quarter_by_time(strtotime(date('Y-04-01', $timestamp))),
            $this->get_appoint_quarter_by_time(strtotime(date('Y-07-01', $timestamp))),
            $this->get_appoint_quarter_by_time(strtotime(date('Y-10-01', $timestamp))),
        ];
    }
    
    /**
     * 获取指定年，指定季度的时间范围
     *
     * @param int $timestamp 留空为当时间戳
     * @param int $quarter   留空为第1季度，1=第一季度，2=第二季度，以此类推
     * @return array
     */
    public function getRangeYearQuarter($timestamp = 0, $quarter = 1)
    {
        if (!in_array($quarter, [1, 2, 3, 4])) {
            $quarter = 1;
        }
        $res = $this->getRangeYearAllQuarter($timestamp);
        return $res[($quarter - 1)];
    }
    
    /**
     * 获取指定年，指定月份的时间范围
     *
     * @param int $timestamp 留空为当时间戳
     * @param int $month     留空为第1个月，1=第1个月，2=第2个月，以此类推
     * @return array
     */
    public function getRangeYearMonth($timestamp = 0, $month = 1)
    {
        if (!in_array($month, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12])) {
            $month = 1;
        }
        $res = $this->getRangeYearAllMonth($timestamp);
        return $res[$month];
    }
    
    /**
     * 取时间范围 - 某年所有月份的时间范围
     *
     * @param int $timestamp
     * @return array
     */
    public function getRangeYearAllMonth($timestamp = 0)
    {
        $timestamp  = $timestamp ? $timestamp : time();
        $months     = [];
        $time_start = strtotime(date('Y-01-01 00:00:00', $timestamp));
        for ($i = 0; $i < 12; $i++) {
            $number          = $i + 1;
            $months[$number] = [
                'number'     => $number,
                'time_start' => strtotime("+$i month", $time_start),
            ];
            
            $days                        = idate('t', $months[$number]['time_start']);
            $months[$number]['time_end'] = strtotime(date("Y-m-{$days} 23:59:59", $months[$number]['time_start']));
        }
        return $months;
    }
    
    /**
     * 取时间范围 - 季度
     *
     * @param int $last      -1表示往上一季度，-2上两季度，以此类推
     * @param int $timestamp 留空为当时间戳
     * @return array
     */
    public function getRangeQuarter($last = 0, $timestamp = 0)
    {
        $timestamp = $timestamp ? $timestamp : time();
        if ($last < 0) {
            $number    = -$last * 3;
            $timestamp = strtotime("-{$number} month", $timestamp);
        }
        $res = $this->get_appoint_quarter_by_time($timestamp);
        return [
            'number'     => $res['number'],
            'time_start' => $res['time_start'],
            'time_end'   => $res['time_end'],
        ];
    }
    
    /**
     * 取时间范围 - 月
     *
     * @param int $last      -1表示往上一月，-2上两月，以此类推
     * @param int $timestamp 留空为当时间戳
     * @return array
     */
    public function getRangeMonth($last = 0, $timestamp = 0)
    {
        $timestamp = $timestamp ? $timestamp : time();
        if ($last < 0) {
            $timestamp = strtotime("{$last} month", $timestamp);
        }
        $days = idate('t', $timestamp);
        return [
            'number'     => date('m', $timestamp),
            'time_start' => strtotime(date('Y-m-01 00:00:00', $timestamp)),
            'time_end'   => strtotime(date("Y-m-{$days} 23:59:59", $timestamp)),
        ];
    }
    
    /**
     * 取时间范围 - 周
     *
     * @param int $last      -1表示往上一周，-2上两周，以此类推
     * @param int $timestamp 留空为当时间戳
     * @return array
     */
    public function getRangeWeek($last = 0, $timestamp = 0)
    {
        $timestamp = $timestamp ? $timestamp : time();
        // 星期中的第几天（星期天是 0）
        $w = $number = idate('w', $timestamp);
        $w = $w == 0 ? 6 : $w - 1;
        
        if ($last < 0) {
            $timestamp = strtotime("{$last} week", $timestamp);
        }
        $data = [
            'number'     => $number,
            'time_start' => strtotime(date('Y-m-d 00:00:00', $timestamp - $w * 86400)),
        ];
        
        $data['time_end'] = strtotime(date('Y-m-d 23:59:59', $data['time_start'] + 6 * 86400));
        return $data;
    }
    
    /**
     * 取时间范围 - 天
     *
     * @param int $last      -1表示往上一天，-2上两天，以此类推
     * @param int $timestamp 留空为当时间戳
     * @return array
     */
    public function getRangeDay($last = 0, $timestamp = 0)
    {
        $timestamp = $timestamp ? $timestamp : time();
        if ($last < 0) {
            $timestamp = strtotime("{$last} day", $timestamp);
        }
        return [
            'number'     => date('d', $timestamp),
            'time_start' => strtotime(date('Y-m-d 00:00:00', $timestamp)),
            'time_end'   => strtotime(date("Y-m-d 23:59:59", $timestamp)),
        ];
    }
    
    public function addYear($value = 1)
    {
        return strtotime("+{$value} year");
    }
    
    public function subYear($value = 1)
    {
        return strtotime("-{$value} year");
    }
    
    public function addMonth($value = 1)
    {
        return strtotime("+{$value} month");
    }
    
    public function subMonth($value = 1)
    {
        return strtotime("-{$value} month");
    }
    
    public function addDay($value = 1)
    {
        return strtotime("+{$value} day");
    }
    
    public function subDay($value = 1)
    {
        return strtotime("-{$value} day");
    }
    
    public function addHour($value = 1)
    {
        return strtotime("+{$value} hour");
    }
    
    public function subHour($value = 1)
    {
        return strtotime("-{$value} hour");
    }
    
    public function addMinute($value = 1)
    {
        return strtotime("+{$value} minute");
    }
    
    public function subMinute($value = 1)
    {
        return strtotime("-{$value} minute");
    }
    
    public function addSecond($value = 1)
    {
        return strtotime("+{$value} second");
    }
    
    public function subSecond($value = 1)
    {
        return strtotime("-{$value} second");
    }
    
    /**
     * 计算两个日期相隔多少年，多少月，多少天
     *
     * @param   $date1    格式如：2011-11-5，或时间戳
     * @param   $date2    格式如：2012-12-01，或时间戳
     * @return  array array('年','月','日');
     */
    function diffDate($date1, $date2)
    {
        if (is_numeric($date1)) {
            $date1 = date_create(date("Y-m-d", $date1));
            $date2 = date_create(date("Y-m-d", $date2));
        } else {
            $date1 = date_create($date1);
            $date2 = date_create($date2);
        }
        
        // 取到2个时间的间隔
        $diff = date_diff($date1, $date2);
        $days = intval($diff->format("%a"));
        
        $ret = [];
        
        // 年
        if ($days > 365) {
            $ret['year'] = intval($days / 365);
        } else {
            $ret['year'] = 0;
        }
        $temp = intval($days % 365);
        
        // 月
        if ($temp > 31) {
            $ret['month'] = intval($temp / 31);
        } else {
            $ret['month'] = 0;
        }
        
        // 日
        $ret['day'] = intval($temp % 31);
        return $ret;
    }
    
    /**
     * 取指定时间戳，当前隶属的季度时间范围
     *
     * @param int $dateline 指定时间戳，留空默认为当前时间
     * @return array
     */
    private function get_appoint_quarter_by_time($dateline = 0)
    {
        $dateline = $dateline ? $dateline : time();
        $quarter  = ceil((date('n', $dateline)) / 3);   // 当月是第几季度
        return [
            'number'     => $quarter,
            'time_start' => strtotime(date('Y-m-d H:i:s', mktime(0, 0, 0, $quarter * 3 - 3 + 1, 1, date('Y', $dateline)))),
            'time_end'   => strtotime(date('Y-m-d H:i:s', mktime(23, 59, 59, $quarter * 3, date('t', mktime(0, 0, 0, $quarter * 3, 1, date("Y", $dateline))), date('Y', $dateline)))),
        ];
    }
}



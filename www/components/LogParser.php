<?php

namespace app\components;

    /**
    * Парсер логов Nginx
    */
class LogParser
{
    // Паттерн для разбора логов Nginx
    const LOG_PATTERN = '/^(\S+)\s+\S+\s+\S+\s+\[([^\]]+)\]\s+"([^"]+)"\s+\d+\s+\d+\s+"([^"]*)"\s+"([^"]*)"/';

    /**
    * Парсит строку лога и возвращает массив данных
    *
    * @param string $logLine Строка лога
    * @return array|null Массив данных или null, если строка не соответствует формату
    */

    public static function parseLog(string $logLine): ?array
    {
        if (!preg_match(self::LOG_PATTERN, $logLine, $matches)) {
            return null;
        }

        $ipAddress = $matches[1];
        $dateTime = self::parseDate($matches[2]);
        $requestLine = $matches[3];
        $referrer = $matches[4];
        $userAgent = $matches[5];

        // Извлечение URL из строки запроса
        $url = self::extractUrl($requestLine);

        // Извлечение User-Agent
        $userAgentData = self::parseUserAgent($userAgent);

        return [
            'ip_address' => $ipAddress,
            'request_date' => $dateTime,
            'url' => $url,
            'user_agent' => $userAgent,
            'os' => $userAgentData['os'],
            'architecture' => $userAgentData['architecture'],
            'browser' => $userAgentData['browser'],
        ];
    }

    /**
     * Берем дату из формата логов
     *
     * @param string $dateStr Строка датыВозвращает дату в формате MySQL
     */
    private static function parseDate(string $dateStr): string
    {
        // Формат: 21/Mar/2019:00:20:06 +0300
        $months = [
            'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
            'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08',
            'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'
        ];

        if (preg_match('/(\d{2})\/(\w+)\/(\d{4}):(\d{2}):(\d{2}):(\d{2})/', $dateStr, $matches)) {
            $day = $matches[1];
            $month = $months[$matches[2]] ?? '01';
            $year = $matches[3];
            $hour = $matches[4];
            $minute = $matches[5];
            $second = $matches[6];

            return "$year-$month-$day $hour:$minute:$second";
        }

        return date('Y-m-d H:i:s');
    }

    /**
     * Извлекаем URL из строки запроса
     */
    private static function extractUrl(string $requestLine): string
    {
        if (preg_match('/\s(\S+)\s/', $requestLine, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Распарсиваем User-Agent, ОСь, архитектуру и браузер
     */
    private static function parseUserAgent(string $userAgent): array
    {
        $result = [
            'os' => null,
            'architecture' => null,
            'browser' => null,
        ];

        // Определение архитектуры
        if (preg_match('/x86_64|x64|amd64|Win64|WOW64|\(64-bit\)/', $userAgent)) {
            $result['architecture'] = 'x64';
        } elseif (preg_match('/x86|i386|i586|i686|Intel|\(32-bit\)/', $userAgent)) {
            $result['architecture'] = 'x86';
        }

        // Определение ОСи
        if (preg_match('/Windows NT/i', $userAgent)) {
            $result['os'] = 'Windows';
        } elseif (preg_match('/Linux/i', $userAgent) && !preg_match('/Android/i', $userAgent)) {
            $result['os'] = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $result['os'] = 'Android';
        } elseif (preg_match('/Macintosh|Mac OS|OS X|iPhone|iPad|iPod/i', $userAgent)) {
            $result['os'] = 'Mac OS';
        }

        // Определение браузера
        if (preg_match('/Chrome\/(\d+)/i', $userAgent)) {
            $result['browser'] = 'Chrome';
        } elseif (preg_match('/Safari\/\d+/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $result['browser'] = 'Safari';
        } elseif (preg_match('/Firefox\/(\d+)/i', $userAgent)) {
            $result['browser'] = 'Firefox';
        } elseif (preg_match('/MSIE|Trident.*rv:(\d+)/i', $userAgent)) {
            $result['browser'] = 'Internet Explorer';
        } elseif (preg_match('/Edge\/(\d+)/i', $userAgent)) {
            $result['browser'] = 'Edge';
        } elseif (preg_match('/Opera|OPR\/(\d+)/i', $userAgent)) {
            $result['browser'] = 'Opera';
        }

        return $result;
    }
}

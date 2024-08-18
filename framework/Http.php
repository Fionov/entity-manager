<?php

declare(strict_types=1);

namespace Framework;

use App\Exceptions\HttpRequestException;

class Http
{
    /**
     * @param string $url
     * @return string|null
     * @throws \App\Exceptions\HttpRequestException
     */
    public function get(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $errorMessage = curl_error($ch);
            throw new HttpRequestException($errorMessage);
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode < 200 || $httpCode > 300) {
                throw new HttpRequestException(sprintf(
                    'Unable to fetch data, error code: %s',
                    $httpCode
                ), $httpCode);
            }
        }

        return $result;
    }
}
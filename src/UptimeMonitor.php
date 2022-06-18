<?php

namespace Squareconcepts\UptimeMonitor;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class UptimeMonitor
{
    private bool $isOnline = false;
    private bool $hasSsl = false;
    private ?Carbon $expires_at = null;
    private int $status = 500;
    private string $url = '';
    private array $urls = [];


    public function __construct( string $url)
    {
        $strUrl = str($url);;
        if($strUrl->startsWith(['http://', 'https://'])){
            $strUrl = $strUrl->remove(['http://', 'https://']);
        } else {
            $strUrl = $strUrl->prepend('https://');
        }
        $this->url = $strUrl->toString();
    }

    /**
     * @return bool
     */
    public function isOnline(): bool
    {
        return $this->isOnline;
    }

    /**
     * @param bool $isOnline
     */
    public function setIsOnline(bool $isOnline): void
    {
        $this->isOnline = $isOnline;
    }

    /**
     * @return bool
     */
    public function isHasSsl(): bool
    {
        return $this->hasSsl;
    }

    /**
     * @param bool $hasSsl
     */
    public function setHasSsl(bool $hasSsl): void
    {
        $this->hasSsl = $hasSsl;
    }

    /**
     * @return ?Carbon
     */
    public function getExpiresAt(): ?Carbon
    {
        return $this->expires_at;
    }

    /**
     * @param Carbon $expires_at
     */
    public function setExpiresAt(Carbon $expires_at): void
    {
        $this->expires_at = $expires_at;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        if($this->isHasSsl()) {
            return str($this->url)->replace('http://', 'https://')->toString();
        } else {
            return str($this->url)->replace('https://', 'http://')->toString();
        }

    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }


    /**
     * @throws \Exception
     */
    public static function make(string $url): UptimeMonitor
    {
        $instance =  new self($url);
        $instance->getExpires();
        $instance->isSiteAvailable();
        return $instance->add($url);
    }

    /**
     * @param string $type humans | months | weeks | days | hours | minutes | seconds
     * @return float|int|string|null
     */
    public function expiresIn(string $type = 'humans'): float|int|string|null
    {
        if($this->getExpiresAt() == null) {
            return null;
        }
        if($type == 'humans') {
            return $this->getExpiresAt()->diffForHumans();
        } else if($type == 'months') {
            return $this->getExpiresAt()->diffInMonths();
        } else if($type == 'weeks') {
            return $this->getExpiresAt()->diffInWeeks();
        } else if($type == 'days') {
            return $this->getExpiresAt()->diffInDays();
        } else if($type == 'hours') {
            return $this->getExpiresAt()->diffInHours();
        } else if($type == 'minutes') {
            return $this->getExpiresAt()->diffInMinutes();
        } else if($type == 'seconds') {
            return $this->getExpiresAt()->diffInSeconds();
        } else {
            return $this->getExpiresAt()->diffForHumans();
        }
    }

    /**
     * @throws \Exception
     */
    private function getExpires(): void
    {
        try {
            $parse_url = parse_url($this->getUrl(), PHP_URL_HOST);
            if($parse_url === null) {
                return;
            }

            $this->hasSsl = $this->has_ssl($parse_url);
            if($this->hasSsl) {
                $get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));
                $read = stream_socket_client("ssl://".$parse_url.":443", $errno, $errstr,
                    30, STREAM_CLIENT_CONNECT, $get);


                $cert = stream_context_get_params($read);
                $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);

                $date = Carbon::parse($certinfo['validTo_time_t']);
                $this->expires_at = $date;
            }
        } catch (\Exception $exception) {
            throw  new \Exception($exception->getMessage());
        }

    }

    private function has_ssl( $domain ) {
        $ssl_check = @fsockopen( 'ssl://' . $domain, 443, $errno, $errstr, 30 );
        $res = !! $ssl_check;
        if ( $ssl_check ) { fclose( $ssl_check ); }
        return $res;
    }

    private function isSiteAvailable( $url = null): void
    {
        if($url == null) {
            $url = $this->getUrl();
        }

        // Check, if a valid url is provided
        if(!filter_var($url, FILTER_VALIDATE_URL)){
            return;
        }

        // Initialize cURL
        $curlInit = curl_init($url);

        // Set options
        curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($curlInit,CURLOPT_HEADER,true);
        curl_setopt($curlInit,CURLOPT_NOBODY,true);
        curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

        // Get response
        $response = curl_exec($curlInit);

        $this->status = curl_getinfo($curlInit, CURLINFO_HTTP_CODE );
        // Close a cURL session
        curl_close($curlInit);
        $this->isOnline = (bool)$response;

        if($this->status == 0) {
            $url = str($this->url)->replace('https://', 'http://')->toString();
            $this->isSiteAvailable($url);
        }
    }

    public function add(string $url): self
    {
        $this->urls[] = $url;

        return $this;
    }

    public function check()
    {
        $requests = function () {
            foreach ($this->urls as $url) {
                yield new Request('GET', $url);
            }
        };
        $client = new Client();
        $pool = new Pool($client, $requests(), [
            'concurrency' => 10,
            'fulfilled' => function (ResponseInterface $response, int $index) {
                if (200 === $response->getStatusCode()) {
                    echo $this->urls[$index].' is up ! ✅'.PHP_EOL;
                } else {
                    echo $this->urls[$index].' is down ! ❌ ('.$response->getStatusCode().')'.PHP_EOL;
                }
            },
            'rejected' => function (RequestException $exception, int $index) {
                echo $this->urls[$index].' is down ! ❌ ('.$exception->getMessage().')'.PHP_EOL;
            },
        ]);

        $pool->promise()->wait();
    }
}

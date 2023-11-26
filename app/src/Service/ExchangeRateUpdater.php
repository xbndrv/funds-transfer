<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExchangeRateUpdater
{
    private const TIMEOUT_SECONDS = 8;
    private const API_URL = 'http://api.exchangerate.host/live';
    private readonly string $accessKey;
    private readonly string $filePath;
    private readonly int $lifeTime;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        /** @var string $projectRoot */
        $projectRoot = $parameterBag->get('kernel.project_dir');
        $this->filePath = $projectRoot.'/'.ExchangeRateProvider::FILE_NAME;

        $accessKey = $parameterBag->get('app.exchange_rate_access_key');
        if (!is_string($accessKey) || '' == $accessKey) {
            throw new \Exception('app.exchange_rate_access_key is not correct');
        }
        $this->accessKey = $accessKey;

        $lifeTime = $parameterBag->get('app.exchange_rate_cache_life_time_seconds');
        if (!is_numeric($lifeTime)) {
            throw new \Exception('app.exchange_rate_cache_life_time_seconds is not correct');
        }
        $this->lifeTime = (int) $lifeTime;
    }

    public function updateRatesIfNecessary(): bool
    {
        if (!file_exists($this->filePath)) {
            return $this->update(true);
        }
        if ((int) filemtime($this->filePath) + $this->lifeTime < time()) {
            return $this->update();
        }

        return false;
    }

    private function update(bool $forced = false): bool
    {
        try {
            $rates = $this->load($forced);
            file_put_contents($this->filePath, (string) json_encode($rates, JSON_PRETTY_PRINT));

            return true;
        } catch (\Exception $exception) {
            if ($forced) {
                throw $exception;
            } else {
                return false;
            }
        }
    }

    /**
     * @return array<mixed>
     *
     * @throws \Exception
     */
    private function load(bool $forced = false): array
    {
        $url = self::API_URL.'?access_key='.$this->accessKey;
        if ($forced) {
            $content = file_get_contents($url);
        } else {
            $streamContext = stream_context_create([
                'http' => [
                    'timeout' => self::TIMEOUT_SECONDS,
                ],
            ]);
            $content = file_get_contents($url, false, $streamContext);
        }
        if (!is_string($content)) {
            throw new \Exception('Couldn\'t load '.$url);
        }
        $data = json_decode($content, true);
        if (!is_array($data) || !is_array($data['quotes']) || ($data['success'] ?? false) !== true) {
            throw new \Exception($url.' returned incorrect data');
        }

        return $data['quotes'];
    }
}

<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExchangeRateProvider
{
    private readonly string $intermediateCurrency;
    public const FILE_NAME = 'var/exchange_rates.json';
    private readonly string $filePath;

    public function __construct(
        ParameterBagInterface $parameterBag
    ) {
        /** @var string $projectRoot */
        $projectRoot = $parameterBag->get('kernel.project_dir');
        $this->filePath = $projectRoot.'/'.self::FILE_NAME;

        $intermediateCurrency = $parameterBag->get('app.exchange_rate_intermediate_currency');
        if (!is_string($intermediateCurrency)) {
            throw new \Exception('app.exchange_rate_intermediate_currency is wrong');
        }
        $this->intermediateCurrency = $intermediateCurrency;
    }

    public function getRate(string $from, string $to): ?float
    {
        return $this->getDirectRate($from, $to) ?? $this->getCompoundRate($from, $to);
    }

    private function getDirectRate(string $from, string $to): ?float
    {
        if ($from == $to) {
            return 1;
        }

        $rates = $this->getRates();
        if (isset($rates[$from.$to])) {
            return is_numeric($rates[$from.$to]) ? (float) $rates[$from.$to] : null;
        }
        if (isset($rates[$to.$from])) {
            return is_numeric($rates[$to.$from]) ? (1 / (float) $rates[$to.$from]) : null;
        }

        return null;
    }

    private function getCompoundRate(string $from, string $to): ?float
    {
        if ($from == $this->intermediateCurrency || $to == $this->intermediateCurrency) {
            return null;
        }

        $firstStep = $this->getDirectRate($from, $this->intermediateCurrency);
        $secondStep = $this->getDirectRate($this->intermediateCurrency, $to);
        if (is_null($firstStep) || is_null($secondStep)) {
            return null;
        }

        return $firstStep * $secondStep;
    }

    /**
     * @return array<mixed>
     *
     * @throws \Exception
     */
    public function getRates(): array
    {
        static $rates;
        if (!isset($rates)) {
            if (!file_exists($this->filePath)) {
                throw new \Exception($this->filePath.' doesn\'t exist');
            }
            $rates = json_decode((string) file_get_contents($this->filePath), true);
            if (!is_array($rates)) {
                throw new \Exception($this->filePath.' is broken');
            }
        }

        return $rates;
    }
}

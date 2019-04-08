<?php

declare(strict_types=1);

namespace Sanchescom\WiFi\System\Windows;

use Sanchescom\WiFi\System\AbstractNetworksCollection;
use Sanchescom\WiFi\System\NetworksCollectionTrait;

/**
 * Class NetworksCollection.
 * {@inheritdoc}
 */
class NetworksCollection extends AbstractNetworksCollection
{
    use NetworksCollectionTrait, UtilityTrait;

    /**
     * @var int
     */
    const BSSID_KEY = 4;

    /**
     * @var int
     */
    const EXTRACT_BSSID_KEY = 1;

    /**
     * @var int
     */
    const ZERO_KEY = 0;

    /**
     * @var int
     */
    const NETWORK_DESCRIPTION_ROWS_AMOUNT = 11;

    /**
     * @var int
     */
    const NETWORK_DESCRIPTION_BLOCK_STEP = 1;

    /**
     * @return string
     */
    protected function getCommand(): string
    {
        return implode(' && ', [
            'chcp 65001',
            $this->getUtility().' show networks mode=Bssid',
            'echo '.$this->separator,
            $this->getUtility().' show interfaces',
        ]);
    }

    /**
     * @return string
     */
    protected function getNetwork(): ?string
    {
        return Network::class;
    }

    /**
     * @param string $output
     *
     * @return array
     */
    protected function extractingNetworks(string $output): array
    {
        list($networks, $current) = $this->explodeOutput($output);

        $currentBssid = $this->extractBssid($current, self::EXTRACT_BSSID_KEY);

        $availableNetworks = $this->explodeAvailableNetworks($networks);

        $countAvailableNetworks = count($availableNetworks);

        $groupedNetworks = [];

        for ($i = 10, $j = 5, $k = 0; $countAvailableNetworks >= $j; $i--, $j++) {
            if ($this->isStartNetworkDescription($i)) {
                $this->checkNetworkConnection($groupedNetworks, $currentBssid, $k);

                list($i, $k) = $this->nextNetwork($k);
            } else {
                $groupedNetworks[$k][] = $this->extractingDataFromString($availableNetworks[$j]);
            }
        }

        return $groupedNetworks;
    }

    /**
     * Checking which network currently connected and set a flag.
     *
     * @param array $groupedNetworks
     * @param array $currentBssid
     * @param int   $networkBlockIndex
     */
    private function checkNetworkConnection(array &$groupedNetworks, array $currentBssid, int $networkBlockIndex): void
    {
        if ($this->isConnected($groupedNetworks[$networkBlockIndex][self::BSSID_KEY], $currentBssid)) {
            $groupedNetworks[$networkBlockIndex][] = true;
        }
    }

    /**
     * Checking that iterable row is start of description.
     *
     * @param int $firstRowIndex
     *
     * @return bool
     */
    private function isStartNetworkDescription(int $firstRowIndex): bool
    {
        return $firstRowIndex == self::ZERO_KEY;
    }

    /**
     * Setting vars to state for new iteration of processing network description.
     *
     * @param int $nextRowIndex
     *
     * @return array
     */
    private function nextNetwork(int $nextRowIndex): array
    {
        return [self::NETWORK_DESCRIPTION_ROWS_AMOUNT, $nextRowIndex + self::NETWORK_DESCRIPTION_BLOCK_STEP];
    }

    /**
     * @param $row
     *
     * @return string
     */
    private function extractingDataFromString($row): string
    {
        $title = strtok($row, ':') ?: '';
        $value = substr($row, strlen($title));

        return trim(ltrim($value, ':'));
    }
}

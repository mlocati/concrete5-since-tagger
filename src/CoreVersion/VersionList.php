<?php

declare(strict_types=1);

namespace MLocati\C5SinceTagger\CoreVersion;

use Exception;
use GuzzleHttp\Client;

class VersionList
{
    /**
     * List of ready-to-use package installations.
     *
     * @var array
     */
    private static $map = [
        '5.7.0' => 'https://www.concrete5.org/download_file/-/view/70843/8497/',
        '5.7.0.1' => 'https://www.concrete5.org/download_file/-/view/70916/8497/',
        '5.7.0.3' => 'https://www.concrete5.org/download_file/-/view/71260/8497/',
        '5.7.0.4' => 'https://www.concrete5.org/download_file/-/view/71439/8497/',
        '5.7.1' => 'https://www.concrete5.org/download_file/-/view/72166/8497/',
        '5.7.2' => 'https://www.concrete5.org/download_file/-/view/72447/8497/',
        '5.7.2.1' => 'https://www.concrete5.org/download_file/-/view/73241/8497/',
        '5.7.3' => 'https://www.concrete5.org/download_file/-/view/74252/8497/',
        '5.7.3.1' => 'https://www.concrete5.org/download_file/-/view/74619/8497/',
        '5.7.4' => 'https://www.concrete5.org/download_file/-/view/78769/8497/',
        '5.7.4.1' => 'https://www.concrete5.org/download_file/-/view/78949/8497/',
        '5.7.4.2' => 'https://www.concrete5.org/download_file/-/view/79252/8497/',
        '5.7.5' => 'https://www.concrete5.org/download_file/-/view/81497/8497/',
        '5.7.5.1' => 'https://www.concrete5.org/download_file/-/view/81601/8497/',
        '5.7.5.2' => 'https://www.concrete5.org/download_file/-/view/82243/8497/',
        '5.7.5.3' => 'https://www.concrete5.org/download_file/-/view/84191/8497/',
        '5.7.5.4' => 'https://www.concrete5.org/download_file/-/view/85488/8497/',
        '5.7.5.5' => 'https://www.concrete5.org/download_file/-/view/85712/8497/',
        '5.7.5.6' => 'https://www.concrete5.org/download_file/-/view/85780/8497/',
        '5.7.5.7' => 'https://www.concrete5.org/download_file/-/view/88522/8497/',
        '5.7.5.8' => 'https://www.concrete5.org/download_file/-/view/89071/8497/',
        '5.7.5.9' => 'https://www.concrete5.org/download_file/-/view/90125/8497/',
        '5.7.5.10' => 'https://www.concrete5.org/download_file/-/view/92657/8497/',
        '5.7.5.11' => 'https://www.concrete5.org/download_file/-/view/92833/8497/',
        '5.7.5.12' => 'https://www.concrete5.org/download_file/-/view/92911/8497/',
        '5.7.5.13' => 'https://www.concrete5.org/download_file/-/view/93075/8497/',
        '8.0.0' => 'https://www.concrete5.org/download_file/-/view/92663/8497/',
        '8.0.1' => 'https://www.concrete5.org/download_file/-/view/92834/8497/',
        '8.0.2' => 'https://www.concrete5.org/download_file/-/view/92910/8497/',
        '8.0.3' => 'https://www.concrete5.org/download_file/-/view/93074/8497/',
        '8.1.0' => 'https://www.concrete5.org/download_file/-/view/93797/8497/',
        '8.2.0' => 'https://www.concrete5.org/download_file/-/view/96765/8497/',
        '8.2.1' => 'https://www.concrete5.org/download_file/-/view/96959/8497/',
        '8.3.0' => 'https://www.concrete5.org/download_file/-/view/99806/8497/',
        '8.3.1' => 'https://www.concrete5.org/download_file/-/view/99963/8497/',
        '8.3.2' => 'https://www.concrete5.org/download_file/-/view/100595/8497/',
        '8.4.0' => 'https://www.concrete5.org/download_file/-/view/104344/8497/',
        '8.4.1' => 'https://www.concrete5.org/download_file/-/view/105022/8497/',
        '8.4.2' => 'https://www.concrete5.org/download_file/-/view/105477/8497/',
        '8.4.3' => 'https://www.concrete5.org/download_file/-/view/107738/8497/',
        '8.4.4' => 'https://www.concrete5.org/download_file/-/view/108181/8497/',
        '8.4.5' => 'https://www.concrete5.org/download_file/-/view/108839/8497/',
        '8.5.0' => 'https://www.concrete5.org/download_file/-/view/109116/8497/',
        '8.5.1' => 'https://www.concrete5.org/download_file/-/view/109615/8497/',
    ];

    /**
     * @var string[]|null
     */
    private $packagistVersions;

    private function getPackagistVersions(): array
    {
        if ($this->packagistVersions === null) {
            $this->packagistVersions = $this->fetchVersionsFromPackagist();
        }

        return $this->packagistVersions;
    }

    private function fetchVersionsFromPackagist(): array
    {
        $client = new Client();
        $response = $client->request('GET', 'https://repo.packagist.org/p/concrete5/core.json');
        /* @var \GuzzleHttp\Psr7\Response $response */
        if ($response->getStatusCode() !== 200) {
            throw new Exception($response->getReasonPhrase());
        }
        $data = \json_decode((string) $response->getBody(), true, 512, \defined('JSON_THROW_ON_ERROR') ? \JSON_THROW_ON_ERROR : 0);
        if (\is_array($data) && isset($data['packages']) && \is_array($data['packages']) && isset($data['packages']['concrete5/core']) && \is_array($data['packages']['concrete5/core'])) {
            $versions = \array_keys($data['packages']['concrete5/core']);
            \usort($versions, 'version_compare');

            return $versions;
        }
        throw new Exception('Invalid packagist response');
    }

    /**
     * @return string[]
     */
    public function getAvailableVersions(): array
    {
        $readyVersions = \array_keys(self::$map);
        $packagistVersions = $this->fetchVersionsFromPackagist();
        $versions = \array_unique(\array_merge($readyVersions, $packagistVersions));
        \usort($versions, 'version_compare');

        return $versions;
    }

    public function getVersionUrl(string $version): ?string
    {
        if (isset(self::$map[$version])) {
            return self::$map[$version];
        }
        $packagistVersions = $this->getPackagistVersions();
        if (\in_array($version, $packagistVersions)) {
            $m = null;
            if (\preg_match('/^dev-(.+)$/', $version, $m)) {
                $ghVersion = $m[1];
            } else {
                $ghVersion = $version;
            }

            return "https://codeload.github.com/concrete5/concrete5/zip/{$ghVersion}";
        }

        return null;
    }
}

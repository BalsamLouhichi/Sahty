<?php

namespace App\Service;

use App\Entity\Evenement;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EvenementOperationalInsightsService
{
    private const MAPBOX_URL = 'https://api.mapbox.com/geocoding/v5/mapbox.places';
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
    private const OPEN_METEO_URL = 'https://api.open-meteo.com/v1/forecast';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $mapboxAccessToken = ''
    ) {
    }

    public function analyze(Evenement $evenement): array
    {
        $insights = [
            'location_valid' => true,
            'location_message' => null,
            'weather_warning' => null,
        ];

        if (!$this->isPhysicalEvent($evenement)) {
            return $insights;
        }

        $lieu = trim((string) $evenement->getLieu());
        if ($lieu === '') {
            $insights['location_valid'] = false;
            $insights['location_message'] = 'Le lieu est obligatoire pour un evenement presentiel ou hybride.';
            return $insights;
        }

        $geo = $this->geocodeLocation($lieu);
        if ($geo === null) {
            $insights['location_valid'] = false;
            $insights['location_message'] = 'Le lieu saisi n\'a pas pu etre verifie automatiquement. Merci de verifier l\'adresse (ville, rue, etablissement).';
            return $insights;
        }

        $weatherWarning = $this->buildWeatherWarningForEventDate(
            $geo['lat'],
            $geo['lon'],
            $evenement->getDateDebut()
        );
        if ($weatherWarning !== null) {
            $insights['weather_warning'] = $weatherWarning;
        }

        return $insights;
    }

    private function isPhysicalEvent(Evenement $evenement): bool
    {
        return in_array((string) $evenement->getMode(), ['presentiel', 'hybride'], true);
    }

    private function geocodeLocation(string $query): ?array
    {
        if ($this->mapboxAccessToken !== '') {
            $mapboxResult = $this->geocodeWithMapbox($query);
            if ($mapboxResult !== null) {
                return $mapboxResult;
            }
        }

        return $this->geocodeWithNominatim($query);
    }

    private function geocodeWithMapbox(string $query): ?array
    {
        try {
            $encoded = rawurlencode($query);
            $url = sprintf('%s/%s.json', self::MAPBOX_URL, $encoded);

            $response = $this->httpClient->request('GET', $url, [
                'query' => [
                    'access_token' => $this->mapboxAccessToken,
                    'limit' => 1,
                ],
                'timeout' => 5,
            ]);

            $payload = $response->toArray(false);
            $features = $payload['features'] ?? [];
            if (!is_array($features) || count($features) === 0) {
                return null;
            }

            $coords = $features[0]['center'] ?? null;
            if (!is_array($coords) || count($coords) < 2) {
                return null;
            }

            return [
                'lat' => (float) $coords[1],
                'lon' => (float) $coords[0],
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function geocodeWithNominatim(string $query): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::NOMINATIM_URL, [
                'query' => [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => 1,
                ],
                'headers' => [
                    // Required by Nominatim usage policy.
                    'User-Agent' => 'SahtyEventModule/1.0',
                ],
                'timeout' => 5,
            ]);

            $results = $response->toArray(false);
            if (!is_array($results) || count($results) === 0) {
                return null;
            }

            $first = $results[0];
            if (!isset($first['lat'], $first['lon'])) {
                return null;
            }

            return [
                'lat' => (float) $first['lat'],
                'lon' => (float) $first['lon'],
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildWeatherWarningForEventDate(float $lat, float $lon, ?\DateTimeInterface $eventDate): ?string
    {
        if ($eventDate === null) {
            return null;
        }

        $eventDay = $eventDate->format('Y-m-d');
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $horizon = (new \DateTimeImmutable('today +16 days'))->format('Y-m-d');

        if ($eventDay < $today || $eventDay > $horizon) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', self::OPEN_METEO_URL, [
                'query' => [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'daily' => 'temperature_2m_max,precipitation_probability_max',
                    'timezone' => 'auto',
                    'start_date' => $eventDay,
                    'end_date' => $eventDay,
                ],
                'timeout' => 5,
            ]);

            $payload = $response->toArray(false);
            $daily = $payload['daily'] ?? null;
            if (!is_array($daily)) {
                return null;
            }

            $temperature = $daily['temperature_2m_max'][0] ?? null;
            $precipitation = $daily['precipitation_probability_max'][0] ?? null;

            if (is_numeric($precipitation) && (float) $precipitation >= 70) {
                return sprintf(
                    'Alerte meteo : risque de precipitation eleve (%s%%) le %s. Recommande : prevoir un plan B ou basculer en mode hybride/en ligne.',
                    (string) (int) round((float) $precipitation),
                    $eventDay
                );
            }

            if (is_numeric($temperature) && (float) $temperature >= 38) {
                return sprintf(
                    'Alerte meteo : temperature elevee prevue (%s C) le %s. Recommande : adapter les horaires et les mesures de confort.',
                    (string) (int) round((float) $temperature),
                    $eventDay
                );
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }
}

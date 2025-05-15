<?php

namespace AMoschou\TDev\Messaging\App\Client;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class OAuth2Token
{
    private int|string|null $tokenKey;
    private Client $client;
    private bool $readOnly;
    private array $scope;
    private bool $scopeChanged;
    private string $access_token;

    // private array $requestData;
    // private Response $response;
    // private Collection $successfulResponse;
    public CarbonImmutable $requestAt;
    public int $expireAt;


    public function __construct(Client $client, int|string|null $tokenKey = null)
    {
        $this->tokenKey = $tokenKey;
        $this->client = $client;
        $this->readOnly = is_null($tokenKey);
        $this->scope = [];
        $this->scopeChanged = true;
    }

    public function scope(string|array $names, string|array $permissions, $value = true)
    {
        if ($this->readOnly) {
            return $this;
        }

        $this->scopeChanged = true;

        if ($names === 'all') {
            $names = ['free-trial-numbers', 'messages', 'reports', 'virtual-numbers'];
        }

        if ($permissions === 'all') {
            $permissions = ['read', 'write'];
        }

        $names = Arr::wrap($names);
        $permissions = Arr::wrap($permissions);

        foreach ($names as $name) {
            foreach ($permissions as $permission) {
                $this->scope[$name][$permission] = $value;
            }
        }

        return $this;
    }

    public function getClientId()
    {
        return $this->client->getId();
    }

    public function getTokenKey()
    {
        return $this->tokenKey;
    }

    public function use()
    {
        if ($this->scopeChanged || $this->isExpired()) {
            $response = $this->request();

            if ($response->successful()) {
                $this->access_token = $response->json('access_token');

                var_dump('Using the following token: ', $this->access_token);

                return $this->access_token;
            }
        } else {
            var_dump('Using the following token: ', $this->access_token);

            return $this->access_token;
        }
    }

    public function isExpired() {
        return $this->expireAt < now()->timestamp;
    }

    private function request()
    {
        if (count($this->scope) === 0) {
            $this->scope('all', 'all');
        }

        $scopes = [];

        foreach ($this->scope as $name => $permissions) {
            foreach ($permissions as $permission => $value) {
                if ($value) {
                    $scopes[] = "{$name}:{$permission}";
                }
            }
        }

        $data = [
            'grant_type' => 'client_credentials',
            'scope' => implode(' ', $scopes),
        ];

        $now = now();
        $this->requestAt = $now->toImmutable();
        $requestAt = $now->timestamp;

        $response = $this->client->post('https://products.api.telstra.com/v2/oauth/token', null, [
            'with-bearer' => false,
            'as-form' => true,
            'data' => $data,
        ]);

        if ($response->successful()) {
            $this->scopeChanged = false;

            // Soft expiry 5 minutes before (minus 300 seconds)
            $this->expireAt = $requestAt + ((int) $response->collect()->get('expires_in')) - 300;

            return $response;
        }

    //     return $this;
    }
}


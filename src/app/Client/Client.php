<?php

namespace AMoschou\TDev\Messaging\App\Client;

class Client
{
    private string|null $id;
    private string|null $secret;
    private OAuth2Token $defaultToken;
    private array $tokens;
    private ClientFreeTrialNumbers $freeTrialNumbers;

    public function __construct(string|array|null $credentials = null)
    {
        $credentials = is_null($credentials) ? 'default' : $credentials;

        if (is_string($credentials)) {
            $this->id = config("services.tdev.{$credentials}.id", null);
            $this->secret = config("services.tdev.{$credentials}.secret", null);
        }

        if (is_array($credentials)) {
            $this->id = $credentials['id'] ?? $credentials[0] ?? null;
            $this->secret = $credentials['secret'] ?? $credentials[1] ?? null;
        }

        if (is_null($this->id) || is_null($this->secret)) {
            // Bad credentials //
        }

        $this->defaultToken = new OAuth2Token($this);

        $this->tokens = [];
    }

    public function token(int|string|null $tokenKey = null, bool $replaceExisting = false): OAuth2Token
    {
        // ->token() returns the default token.
        // ->token($tokenKey) returns the token identified by $tokenKey, creating it first if it does not exist.
        // ->token($tokenKey, true) creates a new token, and returns it.

        if (is_null($tokenKey)) {
            return $this->defaultToken;
        }

        if (! isset($this->tokens[$tokenKey])) {
            $replaceExisting = true;
        }

        if ($replaceExisting) {
            $this->tokens[$id] = new OAuth2Token($this, $id);
        }

        return $this->tokens[$id];
    }

    public function getId()
    {
        return $this->id;
    }

    public function get($url, $tokenKey, $options = [])
    {
        return $this->request('get', $url, $tokenKey, $options);
    }

    public function post($url, $tokenKey, $options = [])
    {
        return $this->request('post', $url, $tokenKey, $options);
    }
    
    private function request($method, $url, $tokenKey, $options)
    {
        $withBearer = $options['with-bearer'] ?? true;
        $withClient = $options['with-client'] ?? true;
        $data = $options['data'] ?? [];
        $headers = $options['headers'] ?? [];
        $acceptJson = $options['accept-json'] ?? false;
        $asForm = $options['as-form'] ?? false;

        $bearer = $withBearer ? $this->token($tokenKey)->use() : null;

        if ($withClient) {
            $data = array_merge($data, [
                'client_id' => $this->id,
                'client_secret' => $this->secret,
            ]);
        }

        $http = is_null($bearer)
            ? Http::withHeaders($headers)
            : Http::withToken($bearer)->withHeaders($headers);

        $http = $acceptJson ? $http->acceptJson() : $http;

        if ($method === 'post' && $asForm) {
            $http = $http->asForm();
        }

        $response = match ($method) {
            'post' => $http->post($url, $data),
            'get' => $http->get($url),
        };

        return $response;
    }

    public function freeTrialNumbers(int|string|OAuth2Token|null $tokenInput = null)
    {
        if ($tokenInput instanceof OAuth2Token) {
            if ($tokenInput->getClientId() === $this->getId()) {
                return new ClientFreeTrialNumbers($this, $tokenInput);
            } else {
                // bad token (token's client does not match current client) //
            }
        }

        return new ClientFreeTrialNumbers($this, $this->token($tokenInput));
    }

    public function healthCheck(int|string|OAuth2Token|null $tokenInput = null)
    {
        return new ClientHealthCheck($this, $this->token($tokenInput));
    }

    public function senderNames(int|string|OAuth2Token|null $tokenInput = null)
    {
        return new ClientSenderNames($this, $this->token($tokenInput));
    }
}
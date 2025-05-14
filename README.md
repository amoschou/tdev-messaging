Create a new client using the default credential as defined in the config
file. If other credentials are defined in the config file, you can use the
key to identify one. Or you can provide $id and $secret as an array to use a
different credential. These come from your Telstra Developer account.

```php
$client = new MessagingClient;
$client = new MessagingClient('client-key');
$client = new MessagingClient(['id' => $id, 'secret' => $secret]);
$client = new MessagingClient([$id, $secret]);
```


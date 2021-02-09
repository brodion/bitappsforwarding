# Bit Apps Forwarding API

PHP wrapper over [BitApps Forwarding API](https://developer.bitaps.com/forwarding)

## Usage
```php
use Skytraffic\BitApps\BitAppsForwarding;

$bitApps = new BitAppsForwarding();

#create payment address for user

$forwardingAddress = 'YOUR_BITCOIN_WALLET_ADDRESS';
$callbackUrl = 'https://domain.com/bitapps/callback';

$response = $bitApps->createPaymentAddress($forwardingAddress, $callbackUrl);

#rate limit remaining after last request
$bitApps->getRatelimitRemaining();

```
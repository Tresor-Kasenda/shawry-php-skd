# Shwary PHP SDK

Official PHP SDK for the Shwary Merchant API - Mobile Money Payments for DRC, Kenya, and Uganda.

## Installation

```bash
composer require shwary/php-sdk
```

## Requirements

- PHP 8.1 or higher
- Guzzle HTTP Client 7.0+

## Quick Start

### Option 1: Environment Variables (Recommended)

```env
SHWARY_MERCHANT_ID=your-merchant-uuid
SHWARY_MERCHANT_KEY=shwary_live_merchant_secret
SHWARY_SANDBOX=false
```

```php
use Shwary\Shwary;

// Auto-initialize from environment variables
Shwary::initFromEnvironment();
```

### Option 2: Manual Configuration

```php
use Shwary\Config;
use Shwary\ShwaryClient;

$config = new Config(
    merchantId: 'your-merchant-uuid',
    merchantKey: 'shwary_live_merchant_secret',
    sandbox: false
);

$client = new ShwaryClient($config);
```

### Option 3: Array Configuration

```php
use Shwary\Shwary;

Shwary::initFromArray([
    'merchant_id' => 'your-merchant-uuid',
    'merchant_key' => 'shwary_live_merchant_secret',
    'sandbox' => false,
    'timeout' => 30,
]);
```

## Usage

### Making Payments

#### Simple Payment (DRC)

```php
use Shwary\Shwary;
use Shwary\Enums\Country;

Shwary::initFromEnvironment();

// Shortcut method for DRC
$transaction = Shwary::payDRC(
    amount: 5000,
    phone: '+243812345678',
    callbackUrl: 'https://your-app.com/webhooks/shwary'
);

echo "Transaction ID: " . $transaction->id;
echo "Status: " . $transaction->status->value;
```

#### Generic Payment

```php
use Shwary\Enums\Country;

// For Kenya
$transaction = Shwary::pay(
    amount: 1000,
    phone: '+254712345678',
    country: Country::KENYA,
    callbackUrl: 'https://your-app.com/webhooks/shwary'
);

// For Uganda
$transaction = Shwary::pay(
    amount: 5000,
    phone: '+256712345678',
    country: Country::UGANDA
);
```

#### Using PaymentRequest (Full Control)

```php
use Shwary\DTOs\PaymentRequest;
use Shwary\Enums\Country;

$request = PaymentRequest::create(
    amount: 5000,
    phone: '+243812345678',
    country: Country::DRC,
    callbackUrl: 'https://your-app.com/webhooks/shwary'
);

$transaction = Shwary::createPayment($request);
```

### Sandbox Mode (Testing)

```php
use Shwary\Enums\Country;

// Sandbox payment - no real mobile money call
$transaction = Shwary::sandboxPay(
    amount: 5000,
    phone: '+243812345678',
    country: Country::DRC,
    callbackUrl: 'https://your-app.com/webhooks/shwary'
);

// Status will be immediately "completed"
var_dump($transaction->isSandbox); // true
var_dump($transaction->isCompleted()); // true
```

### Handling Webhooks

```php
// In your webhook controller
use Shwary\Shwary;

Shwary::initFromEnvironment();

try {
    // Auto-parse from php://input
    $transaction = Shwary::webhook()->parseFromGlobals();
    
    // Or from a JSON string
    // $transaction = Shwary::parseWebhook($jsonPayload);
    
    if ($transaction->isCompleted()) {
        // Payment successful
        updateOrderStatus($transaction->referenceId, 'paid');
    } elseif ($transaction->isFailed()) {
        // Payment failed
        handleFailedPayment($transaction->id, $transaction->failureReason);
    }
    
    // Success response
    header('Content-Type: application/json');
    echo json_encode(Shwary::webhook()->createResponse(true));
    
} catch (\Shwary\Exceptions\ShwaryException $e) {
    http_response_code(400);
    echo json_encode(Shwary::webhook()->createResponse(false, $e->getMessage()));
}
```

### Laravel Integration

```php
// config/shwary.php
return [
    'merchant_id' => env('SHWARY_MERCHANT_ID'),
    'merchant_key' => env('SHWARY_MERCHANT_KEY'),
    'sandbox' => env('SHWARY_SANDBOX', false),
    'timeout' => env('SHWARY_TIMEOUT', 30),
];
```

```php
// app/Providers/ShwaryServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Shwary\Config;
use Shwary\ShwaryClient;

class ShwaryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ShwaryClient::class, function ($app) {
            return new ShwaryClient(
                Config::fromArray(config('shwary'))
            );
        });
    }
}
```

```php
// Controller
use Shwary\ShwaryClient;
use Shwary\Enums\Country;

class PaymentController extends Controller
{
    public function __construct(
        private ShwaryClient $shwary
    ) {}

    public function initiatePayment(Request $request)
    {
        $transaction = $this->shwary->payDRC(
            amount: $request->amount,
            phone: $request->phone,
            callbackUrl: route('webhooks.shwary')
        );

        return response()->json([
            'transaction_id' => $transaction->id,
            'status' => $transaction->status->value,
        ]);
    }
}
```

```php
// Webhook Controller
use Illuminate\Http\Request;
use Shwary\ShwaryClient;

class WebhookController extends Controller
{
    public function handleShwary(Request $request, ShwaryClient $shwary)
    {
        $transaction = $shwary->parseWebhook($request->getContent());

        if ($transaction->isCompleted()) {
            // Process successful payment
            Order::where('reference', $transaction->referenceId)
                ->update(['status' => 'paid']);
        }

        return response()->json(['success' => true]);
    }
}
```

## Error Handling

```php
use Shwary\Exceptions\AuthenticationException;
use Shwary\Exceptions\ValidationException;
use Shwary\Exceptions\ApiException;

try {
    $transaction = Shwary::payDRC(5000, '+243812345678');
} catch (AuthenticationException $e) {
    // Invalid credentials (401)
    log_error('Auth failed: ' . $e->getMessage());
} catch (ValidationException $e) {
    // Validation error (amount, phone, etc.)
    $context = $e->getContext();
    log_error('Validation failed: ' . $e->getMessage(), $context);
} catch (ApiException $e) {
    // Other API errors (404, 502, etc.)
    if ($e->getCode() === 404) {
        // Client not found
    } elseif ($e->getCode() === 502) {
        // Gateway error - retry later
    }
}
```

## Supported Countries

| Code | Country | Currency | Dial Code | Minimum Amount |
|------|---------|----------|-----------|----------------|
| `DRC` | Democratic Republic of Congo | CDF | +243 | > 2900 |
| `KE` | Kenya | KES | +254 | > 0 |
| `UG` | Uganda | UGX | +256 | > 0 |

## Transaction Structure

```php
$transaction->id;                  // Transaction UUID
$transaction->userId;              // Merchant UUID
$transaction->amount;              // Amount
$transaction->currency;            // Currency (CDF, KES, UGX)
$transaction->status;              // TransactionStatus enum
$transaction->recipientPhoneNumber; // Client phone number
$transaction->referenceId;         // Unique reference
$transaction->failureReason;       // Failure reason (if applicable)
$transaction->completedAt;         // Completion date
$transaction->createdAt;           // Creation date
$transaction->isSandbox;           // Test mode flag

// Helper methods
$transaction->isPending();         // true if pending
$transaction->isCompleted();       // true if successful
$transaction->isFailed();          // true if failed
$transaction->isTerminal();        // true if final state
```

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome! Please submit a Pull Request.

## License

MIT License - see [LICENSE](LICENSE) for details.
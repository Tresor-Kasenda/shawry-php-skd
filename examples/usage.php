<?php

/**
 * Shwary SDK Usage Examples
 *
 * This file demonstrates different ways to use the SDK
 * to initiate Mobile Money payments.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Shwary\Config;
use Shwary\Shwary;
use Shwary\ShwaryClient;
use Shwary\DTOs\PaymentRequest;
use Shwary\Enums\Country;
use Shwary\Exceptions\AuthenticationException;
use Shwary\Exceptions\ValidationException;
use Shwary\Exceptions\ApiException;

// ============================================================================
// METHOD 1: Using the Shwary Facade (Recommended)
// ============================================================================

echo "=== METHOD 1: Shwary Facade ===\n\n";

// Initialize from environment variables
// Set these variables in your .env or system environment:
// SHWARY_MERCHANT_ID=your-merchant-id
// SHWARY_MERCHANT_KEY=your-merchant-key
// SHWARY_SANDBOX=true

Shwary::initFromArray([
    'merchant_id' => 'f5a9f5db-1b33-4d76-9168-0035a6f71170',
    'merchant_key' => 'shwary_test_merchant_secret',
    'sandbox' => true, // Test mode
]);

try {
    // Simple payment in DRC
    $transaction = Shwary::payDRC(
        amount: 5000,
        phone: '+243812345678',
        callbackUrl: 'https://your-app.com/webhooks/shwary'
    );

    echo "Transaction created!\n";
    echo "- ID: {$transaction->id}\n";
    echo "- Amount: {$transaction->amount} {$transaction->currency}\n";
    echo "- Status: {$transaction->status->value}\n";
    echo "- Sandbox: " . ($transaction->isSandbox ? 'Yes' : 'No') . "\n\n";

} catch (ValidationException $e) {
    echo "Validation error: {$e->getMessage()}\n";
    print_r($e->getContext());
} catch (AuthenticationException $e) {
    echo "Authentication error: {$e->getMessage()}\n";
} catch (ApiException $e) {
    echo "API error [{$e->getCode()}]: {$e->getMessage()}\n";
}

// Reset for subsequent examples
Shwary::reset();

// ============================================================================
// METHOD 2: Using the Client Directly
// ============================================================================

echo "\n=== METHOD 2: Direct Client ===\n\n";

$config = new Config(
    merchantId: 'f5a9f5db-1b33-4d76-9168-0035a6f71170',
    merchantKey: 'shwary_test_merchant_secret',
    sandbox: true
);

$client = new ShwaryClient($config);

// Payment in Kenya
try {
    $transaction = $client->payKenya(
        amount: 1000,
        phone: '+254712345678'
    );

    echo "Kenya transaction created!\n";
    echo "- ID: {$transaction->id}\n";
    echo "- Currency: {$transaction->currency}\n\n";

} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ============================================================================
// METHOD 3: Using PaymentRequest (Full Control)
// ============================================================================

echo "\n=== METHOD 3: PaymentRequest ===\n\n";

$request = PaymentRequest::create(
    amount: 10000,
    phone: '+256712345678',
    country: Country::UGANDA,
    callbackUrl: 'https://your-app.com/webhooks/shwary'
);

echo "PaymentRequest created:\n";
echo json_encode($request->toArray(), JSON_PRETTY_PRINT) . "\n\n";

try {
    $transaction = $client->createSandboxPayment($request);
    echo "Uganda transaction created!\n";
    echo "- Status: {$transaction->status->value}\n";
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ============================================================================
// WEBHOOK HANDLER EXAMPLE
// ============================================================================

echo "\n=== EXAMPLE: Webhook Handler ===\n\n";

// Simulate a webhook payload
$webhookPayload = json_encode([
    'id' => 'tx-webhook-123',
    'userId' => 'merchant-uuid',
    'amount' => 5000,
    'currency' => 'CDF',
    'type' => 'deposit',
    'status' => 'completed',
    'recipientPhoneNumber' => '+243812345678',
    'referenceId' => 'order-12345',
    'metadata' => null,
    'failureReason' => null,
    'completedAt' => date('c'),
    'createdAt' => date('c'),
    'updatedAt' => date('c'),
    'isSandbox' => true,
]);

$transaction = $client->parseWebhook($webhookPayload);

echo "Webhook parsed:\n";
echo "- Transaction ID: {$transaction->id}\n";
echo "- Reference: {$transaction->referenceId}\n";
echo "- Status: {$transaction->status->value}\n";
echo "- Is Completed: " . ($transaction->isCompleted() ? 'Yes' : 'No') . "\n";
echo "- Is Terminal: " . ($transaction->isTerminal() ? 'Yes' : 'No') . "\n\n";

// Create a response for Shwary
$response = $client->webhook()->createResponse(true, 'Processing successful');
echo "Webhook response:\n";
echo json_encode($response, JSON_PRETTY_PRINT) . "\n";

// ============================================================================
// USING ENUMS
// ============================================================================

echo "\n=== USING ENUMS ===\n\n";

foreach (Country::cases() as $country) {
    echo "Country: {$country->getCountryName()}\n";
    echo "  - Code: {$country->value}\n";
    echo "  - Currency: {$country->getCurrency()}\n";
    echo "  - Dial Code: {$country->getDialCode()}\n";
    echo "  - Min Amount: {$country->getMinimumAmount()}\n\n";
}

echo "\n✅ Examples completed!\n";
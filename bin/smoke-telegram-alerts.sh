#!/usr/bin/env bash
# Real end-to-end smoke for Telegram async alerts.
# Run from host:  ./bin/smoke-telegram-alerts.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

BACKEND="${BACKEND_CONTAINER:-boofstore-backend}"
QUEUE="${QUEUE_CONTAINER:-boofstore-queue}"

echo "==> 1) Clear config cache + show Telegram config"
docker exec "$BACKEND" php artisan config:clear >/dev/null
docker exec "$BACKEND" php artisan tinker --execute="
echo json_encode([
  'enabled' => config('telegram.enabled'),
  'order' => config('telegram.channels.order'),
  'error' => config('telegram.channels.error'),
  'queue' => config('telegram.queue'),
  'token_prefix' => substr((string) config('telegram.bots.mybot.token'), 0, 12),
  'horizon_queues' => config('horizon.defaults.supervisor-1.queue'),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
"

echo
echo "==> 2) Ensure Horizon is up"
docker exec "$QUEUE" php artisan horizon:status || docker restart "$QUEUE" >/dev/null
sleep 2

echo
echo "==> 3) Domain smoke (register + wallet pay on existing product + fake 500)"
docker exec "$BACKEND" php artisan tinker --execute="
use Domain\\Payment\\Models\\Transaction;
use Domain\\Product\\Models\\Order;
use Domain\\Product\\Models\\Product;
use Domain\\User\\Models\\User;
use Domain\\Wallet\\Models\\Wallet;
use Illuminate\\Auth\\Events\\Registered;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Hash;

\$stamp = now()->format('YmdHis');
\$email = \"telegram.smoke.{\$stamp}@example.com\";

echo \"--- REGISTER (Registered event) ---\n\";
\$user = User::create([
    'customer_number' => User::generateCustumerNumber(),
    'role_id' => 2,
    'status' => 1,
    'email' => \$email,
    'password' => Hash::make('Password1!'),
    'email_verified_at' => now(),
    'verified_at' => now(),
    'nickname' => 'tg-smoke-'.\$stamp,
]);
try { \$user->assignRole(['user']); } catch (Throwable \$e) { echo 'role: '.\$e->getMessage().\"\n\"; }
Wallet::create([
    'user_id' => \$user->id,
    'balance' => 99_000_000,
    'currency' => Wallet::IRR,
    'status' => 1,
]);
event(new Registered(\$user));
\$token = \$user->createToken('telegram-smoke')->plainTextToken;
echo \"user_id={\$user->id} email={\$email}\n\";

echo \"--- CREATE ORDER + WALLET PAY (existing product) ---\n\";
\$product = Product::query()
    ->where('active', 1)
    ->where('status', Product::COMPLETED)
    ->where('is_failed', 0)
    ->whereHas('sizes', function (\$q) {
        \$q->where('status', 1)->whereHas('stock', fn (\$s) => \$s->where('quantity', '>', 0));
    })
    ->with(['sizes' => fn (\$q) => \$q->where('status', 1)->with('stock')])
    ->first();

if (! \$product) {
    echo \"FAIL: no sellable product with stock found\n\";
    exit(1);
}

\$size = \$product->sizes->first(fn (\$s) => (\$s->stock?->quantity ?? 0) > 0);
if (! \$size) {
    echo \"FAIL: no size with stock\n\";
    exit(1);
}
echo \"product_id={\$product->id} size_id={\$size->id} amount={\$product->amount}\n\";

\$kernel = app(\\Illuminate\\Contracts\\Http\\Kernel::class);

\$createReq = Request::create('/api/profile/orders', 'POST', [
    'products' => [
        ['id' => \$product->id, 'count' => 1, 'size_id' => \$size->id],
    ],
], server: [
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_AUTHORIZATION' => 'Bearer '.\$token,
]);
\$createRes = \$kernel->handle(\$createReq);
echo 'create_status='.\$createRes->getStatusCode().' body='.substr(\$createRes->getContent(), 0, 400).\"\n\";
\$kernel->terminate(\$createReq, \$createRes);

\$order = Order::query()->where('user_id', \$user->id)->latest('id')->first();
if (! \$order) {
    echo \"FAIL: order not created\n\";
    exit(1);
}
echo \"order_id={\$order->id} code={\$order->code} total={\$order->total_amount}\n\";

\$payReq = Request::create('/api/profile/orders/'.\$order->id.'/pay', 'POST', [
    'payment_method' => Transaction::WALLET,
    'fullname' => 'Telegram Smoke',
    'address' => 'Smoke address',
    'postal_code' => '1234567890',
], server: [
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_AUTHORIZATION' => 'Bearer '.\$token,
]);
\$payRes = \$kernel->handle(\$payReq);
echo 'pay_status='.\$payRes->getStatusCode().' body='.substr(\$payRes->getContent(), 0, 400).\"\n\";
echo 'order_after='.\$order->fresh()->status.\"\n\";
\$kernel->terminate(\$payReq, \$payRes);

echo \"--- FAKE 500 (report) ---\n\";
report(new RuntimeException('Telegram smoke fake 500 '.\$stamp));
echo \"reported\n\";
"

echo
echo "==> 4) Drain low queue"
docker exec "$BACKEND" php artisan queue:work redis --queue=low --stop-when-empty --max-jobs=20 --tries=3 || true

echo
echo "==> Done. Check Telegram:"
echo "    Order: @boofstore_order_notification"
echo "    Error: @boofstore_error_notification"

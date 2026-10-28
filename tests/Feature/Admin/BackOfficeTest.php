<?php

declare(strict_types=1);

use App\Actions\Catalog\ImportCatalog;
use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Staff\Pages\ManageStaff;
use App\Filament\Widgets\CouponPerformance;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\ShopStats;
use App\Filament\Widgets\TopProducts;
use App\Models\ActivityLog;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\CsvExport;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function staff(Role $role): User
{
    $user = User::factory()->create(['role' => $role]);
    test()->actingAs($user);

    return $user;
}

describe('roles', function (): void {
    it('lets every staff role into the panel and keeps customers out', function (): void {
        $panel = Filament::getPanel('admin');

        expect(User::factory()->create(['is_admin' => true])->canAccessPanel($panel))->toBeTrue()
            ->and(User::factory()->create(['role' => Role::Support])->canAccessPanel($panel))->toBeTrue()
            ->and(User::factory()->create()->canAccessPanel($panel))->toBeFalse();
    });

    it('treats the legacy admin flag as the admin role', function (): void {
        expect(User::factory()->create(['is_admin' => true])->staffRole())->toBe(Role::Admin);
    });

    it('limits each role to its areas', function (): void {
        $preparer = User::factory()->create(['role' => Role::Preparer]);
        $support = User::factory()->create(['role' => Role::Support]);

        expect($preparer->canAccessArea('orders'))->toBeTrue()
            ->and($preparer->canAccessArea('stock'))->toBeTrue()
            ->and($preparer->canAccessArea('marketing'))->toBeFalse()
            ->and($support->canAccessArea('customers'))->toBeTrue()
            ->and($support->canAccessArea('stock'))->toBeFalse()
            ->and($support->canAccessArea('staff'))->toBeFalse();
    });

    it('hides restricted resources from a role without access', function (): void {
        staff(Role::Support);

        $this->get('/admin/stock-movements')->assertForbidden();
        $this->get('/admin/coupons')->assertForbidden();
        $this->get('/admin/orders')->assertOk();
    });

    it('lets an admin create staff with a role and keeps role out of mass assignment', function (): void {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ManageStaff::class)
            ->callAction('create', data: ['name' => 'Léa', 'email' => 'lea@example.test', 'role' => 'preparer', 'password' => 'a-long-password-1'])
            ->assertHasNoFormErrors();

        expect(User::firstWhere('email', 'lea@example.test')->role)->toBe(Role::Preparer)
            ->and(User::create(['name' => 'X', 'email' => 'x@example.test', 'password' => 'secret-password', 'role' => 'admin'])->role)->toBeNull();
    });
});

describe('activity log', function (): void {
    it('records who changed what from the back-office', function (): void {
        $admin = staff(Role::Admin);
        $product = Product::factory()->create(['base_price' => 5000]);

        $product->update(['base_price' => 6000]);

        $log = ActivityLog::query()->where('event', 'updated')->sole();

        expect($log->user_id)->toBe($admin->id)
            ->and($log->subject_label)->toBe($product->name)
            ->and($log->changes['base_price'])->toBe(['old' => 5000, 'new' => 6000]);
    });

    it('ignores customer and system writes', function (): void {
        $this->actingAs(User::factory()->create());
        Product::factory()->create()->update(['name' => 'Renamed']);
        Order::factory()->create();

        expect(ActivityLog::query()->count())->toBe(0);
    });

    it('lists the trail for admins only', function (): void {
        $admin = staff(Role::Admin);
        Coupon::factory()->create();

        Livewire::test(ListActivityLogs::class)->assertCanSeeTableRecords(ActivityLog::query()->get());

        $this->actingAs(User::factory()->create(['role' => Role::Support]));
        $this->get('/admin/activity-logs')->assertForbidden();
    });
});

describe('orders', function (): void {
    it('adds an internal note attributed to its author', function (): void {
        $admin = staff(Role::Support);
        $order = Order::factory()->paid()->create();

        Livewire::test(NotesRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewOrder::class])
            ->callTableAction('create', data: ['body' => 'Client à rappeler'])
            ->assertHasNoTableActionErrors();

        $note = $order->notes()->sole();
        expect($note->body)->toBe('Client à rappeler')->and($note->user_id)->toBe($admin->id);
    });

    it('prints packing slips for several orders in one PDF', function (): void {
        staff(Role::Preparer);
        $orders = Order::factory()->paid()->count(2)->create();

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('packingSlip')->table($orders->first()))
            ->assertHasNoErrors();
        Livewire::test(ListOrders::class)
            ->selectTableRecords($orders->modelKeys())
            ->callAction(TestAction::make('packingSlips')->table()->bulk())
            ->assertHasNoErrors();
    });

    it('marks several paid orders shipped and skips the others', function (): void {
        Mail::fake();
        staff(Role::Preparer);
        $paid = Order::factory()->paid()->count(2)->create();
        $pending = Order::factory()->create();

        Livewire::test(ListOrders::class)
            ->selectTableRecords([...$paid->modelKeys(), $pending->id])
            ->callAction(TestAction::make('ship')->table()->bulk())
            ->assertNotified('2 commande(s) expédiée(s)');

        expect($paid->every(fn (Order $order): bool => $order->fresh()->status === OrderStatus::Shipped))->toBeTrue()
            ->and($pending->fresh()->status)->toBe(OrderStatus::Pending);
    });

    it('exports orders to CSV', function (): void {
        staff(Role::Admin);
        $order = Order::factory()->paid()->create(['coupon_code' => 'ETE10']);

        Livewire::test(ListOrders::class)
            ->callTableAction('export')
            ->assertFileDownloaded();
    });
});

describe('customers', function (): void {
    it('shows orders count and lifetime value over paid orders only', function (): void {
        staff(Role::Support);
        $customer = User::factory()->create();
        Order::factory()->for($customer)->paid()->totalling(10000)->create();
        Order::factory()->for($customer)->paid()->totalling(5000)->create();
        Order::factory()->for($customer)->totalling(99900)->create(['status' => OrderStatus::Cancelled]);

        Livewire::test(ListCustomers::class)->assertCanSeeTableRecords([$customer]);
        Livewire::test(ViewCustomer::class, ['record' => $customer->id])
            ->assertSee('150,00');

        $row = CustomerResource::getEloquentQuery()->find($customer->id);
        expect($row->getAttribute('orders_count'))->toBe(2)->and((int) $row->getAttribute('lifetime_value'))->toBe(15000);
    });
});

describe('csv', function (): void {
    it('neutralises spreadsheet formulas', function (): void {
        ob_start();
        CsvExport::download('x.csv', ['a'], [['=HYPERLINK("evil")'], ['-12.5'], ['plain']])->sendContent();
        $csv = ob_get_clean();

        expect($csv)->toContain("'=HYPERLINK")->toContain('-12.5')->not->toContain(';=HYPERLINK');
    });
});

describe('catalog import', function (): void {
    function csvFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import');
        file_put_contents($path, $content);

        return $path;
    }

    it('creates and updates products and variants, moving stock through the ledger', function (): void {
        $admin = User::factory()->create(['is_admin' => true]);
        $header = "product_slug;product_name;category;base_price;sku;size;color;stock\n";

        $result = app(ImportCatalog::class)(csvFile($header."tee-import;Tee Import;T-shirts;35,50;TI-M-N;M;Noir;8\ntee-import;Tee Import;T-shirts;35,50;TI-L-N;L;Noir;3\n"), $admin);

        expect($result)->toBe(['products' => 1, 'variants' => 2]);
        $product = Product::firstWhere('slug', 'tee-import');
        expect($product->base_price)->toBe(3550)->and($product->variants)->toHaveCount(2);

        app(ImportCatalog::class)(csvFile($header."tee-import;Tee Import;T-shirts;39;TI-M-N;M;Noir;5\n"), $admin);

        $variant = ProductVariant::firstWhere('sku', 'TI-M-N');
        expect($variant->stock)->toBe(5)
            ->and($product->fresh()->base_price)->toBe(3900)
            ->and($variant->stockMovements()->count())->toBe(2);
    });

    it('rejects the whole file when a row is invalid and reports the line', function (): void {
        $admin = User::factory()->create(['is_admin' => true]);
        $header = "product_slug,product_name,category,base_price,sku,size,color,stock\n";

        expect(fn () => app(ImportCatalog::class)(csvFile($header."ok,Ok,Cat,10,S1,M,Noir,1\nbad,Bad,Cat,abc,S2,M,Noir,1\n"), $admin))
            ->toThrow(InvalidArgumentException::class, 'Ligne 3');
        expect(Product::query()->count())->toBe(0);
    });

    it('rejects a file with missing columns', function (): void {
        $admin = User::factory()->create(['is_admin' => true]);

        expect(fn () => app(ImportCatalog::class)(csvFile("a;b\n1;2\n"), $admin))->toThrow(InvalidArgumentException::class, 'Colonnes attendues');
    });

    it('refuses a SKU that belongs to another product', function (): void {
        $admin = User::factory()->create(['is_admin' => true]);
        ProductVariant::factory()->create(['sku' => 'TAKEN']);
        $header = "product_slug;product_name;category;base_price;sku;size;color;stock\n";

        expect(fn () => app(ImportCatalog::class)(csvFile($header."new;New;Cat;10;TAKEN;M;Noir;1\n"), $admin))
            ->toThrow(InvalidArgumentException::class, 'autre produit');
    });
});

describe('dashboard', function (): void {
    it('renders the widgets over the chosen period', function (): void {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $order = Order::factory()->paid()->create(['subtotal' => 13000, 'discount_total' => 1000, 'shipping_total' => 0, 'total' => 12000, 'coupon_code' => 'ETE10', 'paid_at' => now()->subDay()]);
        OrderItem::create(['order_id' => $order->id, 'product_name' => 'Hoodie Atlas', 'variant_label' => 'M', 'sku' => 'H-M', 'unit_price' => 12000, 'quantity' => 1, 'total' => 12000]);

        $this->get('/admin')->assertOk();

        Livewire::test(ShopStats::class, ['pageFilters' => ['days' => 7]])->assertSee('120,00');
        Livewire::test(RevenueChart::class, ['pageFilters' => ['days' => 7]])->assertOk();
        Livewire::test(TopProducts::class, ['pageFilters' => ['days' => 7]])->assertSee('Hoodie Atlas');
        Livewire::test(CouponPerformance::class, ['pageFilters' => ['days' => 7]])->assertSee('ETE10');
        Livewire::test(Dashboard::class)->assertOk();
    });
});

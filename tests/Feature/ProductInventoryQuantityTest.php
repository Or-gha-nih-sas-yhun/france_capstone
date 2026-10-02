<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductInventoryQuantityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Inventory Admin',
            'email' => 'inventory.admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function editing_a_product_adds_to_the_remaining_quantity(): void
    {
        $product = Product::create([
            'sku' => 'STOCK-001',
            'name' => 'Stock Item',
            'price' => 25,
            'quantity' => 12,
        ]);

        $response = $this->actingAs($this->admin())->post(route('products.store'), [
            'action' => 'edit',
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'additional_quantity' => 8,
            'quantity' => 999,
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertSame(20, $product->fresh()->quantity);
    }

    /** @test */
    public function the_edit_form_shows_remaining_and_additional_quantity_fields(): void
    {
        $product = Product::create([
            'sku' => 'STOCK-002',
            'name' => 'Another Stock Item',
            'price' => 30,
            'quantity' => 7,
        ]);

        $this->actingAs($this->admin())
            ->get(route('products.index', ['edit' => $product->id]))
            ->assertOk()
            ->assertSee('Remaining Quantity')
            ->assertSee('Additional Quantity')
            ->assertSee('value="7" readonly', false)
            ->assertSee('name="additional_quantity"', false);
    }
}

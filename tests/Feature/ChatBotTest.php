<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChatBotTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_is_redirected_to_login_on_chat_index()
    {
        $response = $this->get(route('chat.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function customer_only_sees_their_own_and_bot_or_admin_messages_on_chat()
    {
        $customer1 = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $customer2 = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Seed some messages
        Message::create(['user_name' => 'John Doe', 'message' => 'John message']);
        Message::create(['user_name' => 'Jane Smith', 'message' => 'Jane message']);
        Message::create(['user_name' => "Mera's Support Bot", 'message' => 'Hello John']);
        Message::create(['user_name' => 'Admin User', 'message' => 'Admin announcement']);

        // Login as Customer 1
        $this->actingAs($customer1);

        // View chat index
        $response = $this->get(route('chat.index'));
        $response->assertStatus(200);

        // Customer 1 should see:
        // - John Doe's message
        // - Bot message
        // - Admin message
        // Customer 1 should NOT see:
        // - Jane Smith's message (privacy)
        $response->assertSee('John message');
        $response->assertSee('Hello John');
        $response->assertSee('Admin announcement');
        $response->assertDontSee('Jane message');

        // Verify JSON messages API behaves the same
        $apiResponse = $this->get(route('chat.messages'));
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonFragment(['user_name' => 'John Doe', 'message' => 'John message']);
        $apiResponse->assertJsonFragment(['user_name' => "Mera's Support Bot", 'message' => 'Hello John']);
        $apiResponse->assertJsonFragment(['user_name' => 'Admin User', 'message' => 'Admin announcement']);
        $apiResponse->assertJsonMissing(['user_name' => 'Jane Smith', 'message' => 'Jane message']);
    }

    /** @test */
    public function customer_asking_for_faber_castel_eraser_ranks_it_first_among_faber_castel_products()
    {
        // Create multiple Faber-Castell products where pencil is inserted before eraser
        \App\Models\Product::create([
            'sku' => 'SCH-FC-PENCIL',
            'name' => 'Faber-Castell 2B Pencil',
            'category' => 'School Supplies',
            'price' => 95.00,
            'quantity' => 50,
        ]);
        \App\Models\Product::create([
            'sku' => 'SCH-FC-BALLPEN',
            'name' => 'Faber-Castell Ballpen 0.7mm',
            'category' => 'School Supplies',
            'price' => 20.00,
            'quantity' => 60,
        ]);
        \App\Models\Product::create([
            'sku' => 'SCH-FC-ERASER',
            'name' => 'Faber-Castell Dust-Free Eraser',
            'category' => 'School Supplies',
            'price' => 22.00,
            'quantity' => 100,
        ]);
        \App\Models\Product::create([
            'sku' => 'SCH-GEN-ERASER',
            'name' => 'Generic White Eraser',
            'category' => 'School Supplies',
            'price' => 15.00,
            'quantity' => 40,
        ]);

        // Query with single 'l' typo "faber castel eraser"
        $response = $this->postJson(route('chat.bot-response'), [
            'message' => 'do you have faber castel eraser?'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $responseData = $response->json();
        $this->assertNotEmpty($responseData['products']);

        // Faber-Castell Dust-Free Eraser MUST be the first product on the list
        $this->assertEquals('Faber-Castell Dust-Free Eraser', $responseData['products'][0]['name']);

        // Check that the text reply also lists the eraser first
        $reply = $responseData['reply'];
        $eraserPos = strpos($reply, 'Faber-Castell Dust-Free Eraser');
        $pencilPos = strpos($reply, 'Faber-Castell 2B Pencil');

        $this->assertNotFalse($eraserPos);
        if ($pencilPos !== false) {
            $this->assertLessThan($pencilPos, $eraserPos, 'Faber-Castell Eraser must appear before Faber-Castell Pencil');
        }
    }

    /** @test */
    public function bot_product_relevance_search_is_applicable_to_all_queries()
    {
        \App\Models\Product::create([
            'sku' => 'SCH-PAD-YLW',
            'name' => 'Yellow Pad Paper 80 Leaves',
            'category' => 'School Supplies',
            'price' => 45.00,
            'quantity' => 80,
        ]);
        \App\Models\Product::create([
            'sku' => 'SCH-NOTE-80',
            'name' => 'Spiral Notebook 80 Pages',
            'category' => 'School Supplies',
            'price' => 25.00,
            'quantity' => 100,
        ]);

        $response = $this->postJson(route('chat.bot-response'), [
            'message' => 'how much is yellow pad paper'
        ]);

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertNotEmpty($responseData['products']);
        $this->assertEquals('Yellow Pad Paper 80 Leaves', $responseData['products'][0]['name']);
    }

    private function seedSchoolSupplies(): void
    {
        \App\Models\Product::create([
            'sku' => 'SCH-FC-PENCIL',
            'name' => 'Faber-Castell 2B Pencil',
            'category' => 'School Supplies',
            'price' => 95.00,
            'quantity' => 50,
        ]);
        \App\Models\Product::create([
            'sku' => 'SCH-FC-ERASER',
            'name' => 'Faber-Castell Dust-Free Eraser',
            'category' => 'School Supplies',
            'price' => 22.00,
            'quantity' => 100,
        ]);
        \App\Models\Product::create([
            'sku' => 'SCH-BALLPEN-BLU',
            'name' => 'Blue Ballpen',
            'category' => 'School Supplies',
            'price' => 12.00,
            'quantity' => 80,
        ]);
        \App\Models\Product::create([
            'sku' => 'HH-DET-BAR',
            'name' => 'Detergent Bar',
            'category' => 'Household',
            'price' => 25.00,
            'quantity' => 100,
        ]);
    }

    /** @test */
    public function bot_states_a_product_is_not_available_when_the_store_does_not_carry_it()
    {
        $this->seedSchoolSupplies();

        foreach (['do you have a laptop?', 'how much is the laptop?', 'Hi! Do you have laptops available?'] as $question) {
            $response = $this->postJson(route('chat.bot-response'), ['message' => $question]);

            $response->assertStatus(200);
            $reply = $response->json('reply');

            $this->assertStringContainsString('is currently not available in our product catalog', $reply, $question);
            $this->assertStringContainsString('Laptop', $reply, $question);
            $this->assertStringNotContainsString('Featured Store Products', $reply, $question);
            $this->assertStringNotContainsString('Welcome to', $reply, $question);
            $this->assertStringContainsString('School Supplies, Household', $reply, $question);
            $this->assertSame([], $response->json('products'), $question);
        }
    }

    /** @test */
    public function bot_states_a_product_is_not_available_when_the_customer_types_only_the_item_name()
    {
        $this->seedSchoolSupplies();

        foreach (['sock' => '"Sock"', 'Shoes?' => '"Shoes"', 'rubber shoes' => '"Rubber Shoes"'] as $question => $requested) {
            $response = $this->postJson(route('chat.bot-response'), ['message' => $question]);

            $reply = $response->json('reply');
            $this->assertStringContainsString("{$requested} is currently not available in our product catalog", $reply, $question);
            $this->assertStringNotContainsString('Thank you for your message', $reply, $question);
            $this->assertSame([], $response->json('products'), $question);
        }

        // A bare name of an item the store carries still returns that item
        $response = $this->postJson(route('chat.bot-response'), ['message' => 'ballpen']);
        $this->assertSame(['Blue Ballpen'], array_column($response->json('products'), 'name'));

        // Short courtesy messages are not mistaken for product names
        foreach (['thank you', 'ok bye', 'Store Hours 🕒'] as $message) {
            $reply = $this->postJson(route('chat.bot-response'), ['message' => $message])->json('reply');
            $this->assertStringNotContainsString('not available', $reply, $message);
        }
    }

    /** @test */
    public function bot_does_not_pass_off_a_look_alike_word_as_the_requested_product()
    {
        $this->seedSchoolSupplies();

        // "glue" is one letter away from "blue" but is not the Blue Ballpen
        $response = $this->postJson(route('chat.bot-response'), ['message' => 'do you have glue?']);

        $this->assertStringContainsString('"Glue" is currently not available', $response->json('reply'));
        $this->assertSame([], $response->json('products'));
    }

    /** @test */
    public function bot_offers_similar_items_when_only_part_of_the_request_matches()
    {
        $this->seedSchoolSupplies();

        $response = $this->postJson(route('chat.bot-response'), [
            'message' => 'do you have faber castell crayons?'
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');

        $this->assertStringContainsString('"Faber Castell Crayons" is currently not available', $reply);
        $this->assertStringContainsString('similar items', $reply);
        $this->assertStringNotContainsString('Matching Products Found', $reply);

        $names = array_column($response->json('products'), 'name');
        $this->assertNotEmpty($names);
        foreach ($names as $name) {
            $this->assertStringStartsWith('Faber-Castell', $name);
        }
    }

    /** @test */
    public function bot_still_answers_with_the_product_when_it_is_carried()
    {
        $this->seedSchoolSupplies();

        $response = $this->postJson(route('chat.bot-response'), ['message' => 'Hello, do you have 10 erasers?']);

        $this->assertStringNotContainsString('not available', $response->json('reply'));
        $this->assertSame(['Faber-Castell Dust-Free Eraser'], array_column($response->json('products'), 'name'));
    }

    /** @test */
    public function bot_says_a_carried_product_is_out_of_stock()
    {
        \App\Models\Product::create([
            'sku' => 'OFF-STAPLER',
            'name' => 'Heavy Duty Stapler',
            'category' => 'Office Supplies',
            'price' => 150.00,
            'quantity' => 0,
        ]);

        $response = $this->postJson(route('chat.bot-response'), ['message' => 'is the stapler available?']);

        $reply = $response->json('reply');
        $this->assertStringContainsString('Heavy Duty Stapler', $reply);
        $this->assertStringContainsString('currently out of stock', $reply);
    }

    /** @test */
    public function general_product_questions_still_list_featured_products()
    {
        $this->seedSchoolSupplies();

        $response = $this->postJson(route('chat.bot-response'), ['message' => 'What products are in stock?']);

        $this->assertStringContainsString('Featured Store Products', $response->json('reply'));
        $this->assertNotEmpty($response->json('products'));
    }

    /** @test */
    public function customer_asking_about_messenger_immediately_receives_live_handoff()
    {
        $response = $this->postJson(route('chat.bot-response'), [
            'message' => 'can I chat live on Messenger with an agent?'
        ]);

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertNotNull($responseData['handoff']);
        $this->assertEquals('Chat live on Messenger', $responseData['handoff']['label']);
        $this->assertStringContainsString('facebook.com', $responseData['handoff']['url']);
    }

    /** @test */
    public function customer_message_triggers_automated_bot_response()
    {
        $customer = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $this->actingAs($customer);

        // Send a greeting to trigger the bot greeting
        $response = $this->post(route('chat.store'), [
            'message' => 'Hello chatbot'
        ]);

        $response->assertRedirect(route('chat.index'));

        // Check if John's message and the bot's message exist in DB
        $this->assertDatabaseHas('messages', [
            'user_name' => 'John Doe',
            'message' => 'Hello chatbot'
        ]);

        $this->assertDatabaseHas('messages', [
            'user_name' => "Mera's Support Bot",
            'message' => "Hello! 👋 Welcome to Mera's Merchandise support assistant. How can I help you today? You can ask me about our products, store hours, location, or payment options."
        ]);
    }

    /** @test */
    public function admin_can_see_all_messages_on_chat()
    {
        $customer = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        Message::create(['user_name' => 'John Doe', 'message' => 'John message']);
        Message::create(['user_name' => "Mera's Support Bot", 'message' => 'Bot message']);

        $this->actingAs($admin);

        $response = $this->get(route('chat.index'));
        $response->assertStatus(200);
        $response->assertSee('John message');
        $response->assertSee('Bot message');

        $apiResponse = $this->get(route('chat.messages'));
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonFragment(['user_name' => 'John Doe', 'message' => 'John message']);
        $apiResponse->assertJsonFragment(['user_name' => "Mera's Support Bot", 'message' => 'Bot message']);
    }
}

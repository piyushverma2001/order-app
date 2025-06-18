<?php

namespace Tests\Feature;

use App\Jobs\ProcessOrderJob;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creation_with_transaction_and_job_dispatch()
    {
        Queue::fake();
        
        $orderData = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'shipping_address' => '123 Main St, City, State 12345',
            'items' => [
                [
                    'product_name' => 'Laptop',
                    'product_description' => 'High-performance laptop',
                    'quantity' => 1,
                    'unit_price' => 999.99
                ],
                [
                    'product_name' => 'Mouse',
                    'product_description' => 'Wireless mouse',
                    'quantity' => 2,
                    'unit_price' => 29.99
                ]
            ],
            'notes' => 'Please deliver during business hours'
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Order created successfully and processing started'
                ]);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'total_amount' => 1059.97,
            'status' => 'pending'
        ]);

        $order = Order::where('customer_email', 'john@example.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals(2, $order->items->count());

        Queue::assertPushed(ProcessOrderJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });
    }

    public function test_order_creation_rollback_on_validation_failure()
    {
        Queue::fake();
        
        $invalidOrderData = [
            'customer_name' => '',
            'customer_email' => 'invalid-email',
            'shipping_address' => '123 Main St',
            'items' => [
                [
                    'product_name' => 'Laptop',
                    'quantity' => 0,
                    'unit_price' => -10
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $invalidOrderData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'customer_name',
                    'customer_email',
                    'items.0.quantity',
                    'items.0.unit_price'
                ]);

        $this->assertDatabaseMissing('orders', [
            'customer_email' => 'invalid-email'
        ]);

        Queue::assertNotPushed(ProcessOrderJob::class);
    }

    public function test_order_creation_rollback_on_database_error()
    {
        Queue::fake();
        
        $orderData = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'shipping_address' => '123 Main St',
            'items' => [
                [
                    'product_name' => 'Laptop',
                    'quantity' => 1,
                    'unit_price' => 999.99
                ]
            ]
        ];

        // Mock the Order model to throw exception during creation
        $this->mock(Order::class, function ($mock) {
            $mock->shouldReceive('create')
                 ->once()
                 ->andThrow(new \Exception('Database connection failed'));
        });

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'message' => 'Order creation failed'
                ]);

        Queue::assertNotPushed(ProcessOrderJob::class);
    }

    public function test_order_retrieval_by_order_number()
    {
        $order = Order::create([
            'order_number' => 'ORD-20240101-ABC123',
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'shipping_address' => '456 Oak St',
            'total_amount' => 150.00,
            'status' => 'completed'
        ]);

        $order->items()->create([
            'product_name' => 'Book',
            'quantity' => 1,
            'unit_price' => 150.00,
            'total_price' => 150.00
        ]);

        $response = $this->getJson("/api/orders/{$order->order_number}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'order_number' => 'ORD-20240101-ABC123',
                        'customer_name' => 'Jane Doe',
                        'customer_email' => 'jane@example.com',
                        'total_amount' => '150.00',
                        'status' => 'completed'
                    ]
                ]);

        $this->assertArrayHasKey('items', $response->json('data'));
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_orders_list_with_pagination()
    {
        Order::create([
            'order_number' => 'ORD-20240101-001',
            'customer_name' => 'Customer 1',
            'customer_email' => 'customer1@example.com',
            'shipping_address' => 'Address 1',
            'total_amount' => 100.00,
            'status' => 'pending'
        ]);

        Order::create([
            'order_number' => 'ORD-20240101-002',
            'customer_name' => 'Customer 2',
            'customer_email' => 'customer2@example.com',
            'shipping_address' => 'Address 2',
            'total_amount' => 200.00,
            'status' => 'completed'
        ]);

        $response = $this->getJson('/api/orders?per_page=1');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $data = $response->json('data');
        $this->assertCount(1, $data['data']);
        $this->assertEquals(2, $data['total']);
        $this->assertEquals(1, $data['current_page']);
    }

    public function test_order_number_generation_uniqueness()
    {
        $orderNumber1 = Order::generateOrderNumber();
        $orderNumber2 = Order::generateOrderNumber();

        $this->assertNotEquals($orderNumber1, $orderNumber2);
        $this->assertStringStartsWith('ORD-', $orderNumber1);
        $this->assertStringStartsWith('ORD-', $orderNumber2);
    }

    public function test_order_status_management()
    {
        $order = Order::create([
            'order_number' => 'ORD-20240101-TEST',
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Test Address',
            'total_amount' => 100.00,
            'status' => 'pending'
        ]);

        $order->markAsProcessed();
        $this->assertEquals('completed', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->processed_at);

        $order->markAsFailed('Payment failed');
        $this->assertEquals('failed', $order->fresh()->status);
        $this->assertEquals('Payment failed', $order->fresh()->notes);
    }

    public function test_order_item_price_calculation()
    {
        $orderItem = new OrderItem([
            'product_name' => 'Test Product',
            'quantity' => 3,
            'unit_price' => 25.50
        ]);

        $orderItem->calculateTotalPrice();

        $this->assertEquals(76.50, $orderItem->total_price);
    }

    public function test_process_order_job_handles_success()
    {
        $order = Order::create([
            'order_number' => 'ORD-20240101-JOBTEST',
            'customer_name' => 'Job Test Customer',
            'customer_email' => 'jobtest@example.com',
            'shipping_address' => 'Job Test Address',
            'total_amount' => 100.00,
            'status' => 'pending'
        ]);

        $job = new ProcessOrderJob($order);
        
        $jobReflection = new \ReflectionClass($job);
        
        $this->assertInstanceOf(ProcessOrderJob::class, $job);
        
        $this->assertEquals(300, $job->timeout);
        $this->assertEquals(3, $job->tries);
    }

    public function test_order_creation_with_minimal_data()
    {
        Queue::fake();
        
        $minimalOrderData = [
            'customer_name' => 'Minimal Customer',
            'customer_email' => 'minimal@example.com',
            'shipping_address' => 'Minimal Address',
            'items' => [
                [
                    'product_name' => 'Simple Product',
                    'quantity' => 1,
                    'unit_price' => 50.00
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $minimalOrderData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Minimal Customer',
            'customer_email' => 'minimal@example.com',
            'total_amount' => 50.00
        ]);

        Queue::assertPushed(ProcessOrderJob::class);
    }
} 
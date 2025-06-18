<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Order - Task App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-50">
    <div id="app" class="min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-8">Create New Order</h1>
                    
                    <form @submit.prevent="submitOrder" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Customer Name *
                                </label>
                                <input 
                                    type="text" 
                                    v-model="form.customer_name"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Customer Email *
                                </label>
                                <input 
                                    type="email" 
                                    v-model="form.customer_email"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Shipping Address *
                            </label>
                            <textarea 
                                v-model="form.shipping_address"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            ></textarea>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Order Items</h3>
                                <button 
                                    type="button"
                                    @click="addItem"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-colors"
                                >
                                    Add Item
                                </button>
                            </div>

                            <div v-for="(item, index) in form.items" :key="index" class="border border-gray-200 rounded-lg p-4 mb-4">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                                        <input 
                                            type="text" 
                                            v-model="item.product_name"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                        <input 
                                            type="text" 
                                            v-model="item.product_description"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                                        <input 
                                            type="number" 
                                            v-model="item.quantity"
                                            min="1"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price *</label>
                                        <input 
                                            type="number" 
                                            v-model="item.unit_price"
                                            min="0"
                                            step="0.01"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required
                                        >
                                    </div>
                                </div>
                                <div class="mt-3 flex justify-between items-center">
                                    <span class="text-sm text-gray-600">
                                        Total: @{{ (item.quantity * item.unit_price).toFixed(2) }}
                                    </span>
                                    <button 
                                        type="button"
                                        @click="removeItem(index)"
                                        class="text-red-500 hover:text-red-700 text-sm font-medium"
                                        v-if="form.items.length > 1"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Notes
                            </label>
                            <textarea 
                                v-model="form.notes"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Any additional notes about the order..."
                            ></textarea>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">Order Summary</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Items:</span>
                                    <span class="font-medium">@{{ form.items.length }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Amount:</span>
                                    <span class="font-bold text-lg text-green-600">@{{ totalAmount.toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <button 
                                type="button"
                                @click="resetForm"
                                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors"
                            >
                                Reset
                            </button>
                            <button 
                                type="submit"
                                :disabled="loading"
                                class="px-6 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors disabled:opacity-50"
                            >
                                <span v-if="loading">Creating Order...</span>
                                <span v-else>Create Order</span>
                            </button>
                        </div>
                    </form>

                    <div v-if="successMessage" class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">Order Created Successfully!</h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>Order Number: <strong>@{{ successMessage.order_number }}</strong></p>
                                    <p>Status: <span class="capitalize">@{{ successMessage.status }}</span></p>
                                    <p>Total Amount: <strong>@{{ successMessage.total_amount }}</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="errorMessage" class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error Creating Order</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>@{{ errorMessage }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    form: {
                        customer_name: '',
                        customer_email: '',
                        shipping_address: '',
                        items: [
                            {
                                product_name: '',
                                product_description: '',
                                quantity: 1,
                                unit_price: 0
                            }
                        ],
                        notes: ''
                    },
                    loading: false,
                    successMessage: null,
                    errorMessage: null
                }
            },
            computed: {
                totalAmount() {
                    return this.form.items.reduce((total, item) => {
                        return total + (item.quantity * item.unit_price);
                    }, 0);
                }
            },
            methods: {
                addItem() {
                    this.form.items.push({
                        product_name: '',
                        product_description: '',
                        quantity: 1,
                        unit_price: 0
                    });
                },
                removeItem(index) {
                    if (this.form.items.length > 1) {
                        this.form.items.splice(index, 1);
                    }
                },
                resetForm() {
                    this.form = {
                        customer_name: '',
                        customer_email: '',
                        shipping_address: '',
                        items: [
                            {
                                product_name: '',
                                product_description: '',
                                quantity: 1,
                                unit_price: 0
                            }
                        ],
                        notes: ''
                    };
                    this.successMessage = null;
                    this.errorMessage = null;
                },
                async submitOrder() {
                    this.loading = true;
                    this.successMessage = null;
                    this.errorMessage = null;

                    try {
                        const response = await axios.post('/api/orders', this.form);
                        
                        if (response.data.success) {
                            this.successMessage = response.data.data;
                            this.resetForm();
                        } else {
                            this.errorMessage = response.data.message || 'An error occurred';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        
                        if (error.response && error.response.data) {
                            if (error.response.data.errors) {
                                const errorMessages = Object.values(error.response.data.errors).flat();
                                this.errorMessage = errorMessages.join(', ');
                            } else {
                                this.errorMessage = error.response.data.message || 'An error occurred';
                            }
                        } else {
                            this.errorMessage = 'Network error. Please try again.';
                        }
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }).mount('#app');
    </script>
</body>
</html> 
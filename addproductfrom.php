<?php
include 'db.php'; // Database connection


$message = ""; // Variable to store success/error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = trim($_POST['product_name']);
    $sku = trim($_POST['sku']);
    $price = $_POST['price'];
    $weight = $_POST['weight'];

    if (empty($product_name) || empty($price)) {
        $message = "<p class='text-red-500'>All fields are required!</p>";
    } else {
        // Check if product already exists
        $check_stmt = $conn->prepare("SELECT id FROM products WHERE product_name = ?");
        $check_stmt->bind_param("s", $product_name);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "<p class='text-red-500'>Product already exists!</p>";
        } else {
            // Insert new product
            $stmt = $conn->prepare("INSERT INTO products (product_name,sku, price,weight) VALUES (?,?, ?,?)");
            $stmt->bind_param("ssdd", $product_name,$sku, $price,$weight);

            if ($stmt->execute()) {
                $message = "<p class='text-green-500'>Product added successfully!</p>";
            } else {
                $message = "<p class='text-red-500'>Error: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
$conn->close();
?>

<section class="mt-8">
    <h3 class="text-xl font-bold text-[var(--primary-color)] mb-4 text-center">Add Product</h3>

    <!-- Message Display -->
    <?php if (!empty($message)) : ?>
        <div class="text-center mb-4">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-md shadow-md">
        <form action="" method="POST">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold">Product Name</label>
                    <input type="text" name="product_name" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold">SKU</label>
                    <input type="text" name="sku" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Price</label>
                    <input type="number" name="price" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Weight</label>
                    <input type="number" name="weight" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                </div>
            </div>
            <div class="mt-6  justify-end">
                <button type="submit" class="bg-[var(--primary-color)] text-white px-6 py-2 rounded-md">
                    Add Product
                </button>
            </div>
        </form>
    </div>
</section>

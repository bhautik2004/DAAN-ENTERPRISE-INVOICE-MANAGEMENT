<?php
include 'db.php'; // Database connection

$message = ""; // Variable to store success/error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = trim($_POST['customer_id']);
    $distributer_name = trim($_POST['distributer_name']);
    $distributer_address = trim($_POST['distributer_address']);
    $mobile = trim($_POST['mobile']);
    $email = trim($_POST['email']);
    $note = trim($_POST['note']);
    $status = trim($_POST['status']);

    // Basic validation
    if (empty($customer_id) || empty($distributer_name) || empty($distributer_address) || empty($mobile)) {
        $message = "<p class='text-red-500'>Customer ID, Name, Address, and Mobile are required!</p>";
    } else {
        // Check if distributor already exists by customer_id
        $check_stmt = $conn->prepare("SELECT id FROM distributors WHERE customer_id = ?");
        $check_stmt->bind_param("s", $customer_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "<p class='text-red-500'>Distributor with this Customer ID already exists!</p>";
        } else {
            // Insert new distributor
            $stmt = $conn->prepare("INSERT INTO distributors (customer_id, distributer_name, distributer_address, mobile, email, note, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $customer_id, $distributer_name, $distributer_address, $mobile, $email, $note, $status);

            if ($stmt->execute()) {
                $message = "<p class='text-green-500'>Distributor added successfully!</p>";
            } else {
                $message = "<p class='text-red-500'>Error: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    $conn->close();
}
?>

<section class="mt-8">
    <h3 class="text-xl font-bold text-[var(--primary-color)] mb-4 text-center">Add Distributor</h3>

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
                    <label class="block text-sm font-semibold">Customer ID</label>
                    <input type="text" name="customer_id" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Distributor Name</label>
                    <input type="text" name="distributer_name" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-semibold">Address</label>
                    <textarea name="distributer_address" rows="3" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Mobile</label>
                    <input type="text" name="mobile" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Email</label>
                    <input type="email" name="email" class="w-full p-2 mt-1 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-semibold">Status</label>
                    <select name="status" class="w-full p-2 mt-1 border border-gray-300 rounded-md" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Note</label>
                    <textarea name="note" rows="2" class="w-full p-2 mt-1 border border-gray-300 rounded-md"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="bg-[var(--primary-color)] text-white px-6 py-2 rounded-md">
                    Add Distributor
                </button>
            </div>
        </form>
    </div>
</section>
<aside class="w-56 bg-[var(--primary-color)] text-white h-screen p-5 fixed overflow-y-auto scrollbar-hide">
    <div class="flex flex-col items-center space-y-2 p-4">
        <span class="text-2xl font-bold">Dashboard</span>
    </div>

    <nav>
        <ul class="space-y-4">
            <li class="hover:bg-gray-700 rounded-md cursor-pointer">
                <a href="index.php" class="flex items-center space-x-2 p-2 w-full block">
                    <i class="fas fa-home"></i> <span>Home</span>
                </a>
            </li>
            <li class="hover:bg-gray-700 rounded-md cursor-pointer">
                <a href="createinvoice.php" class="flex items-center space-x-2 p-2 w-full block">
                    <i class="fas fa-file-invoice"></i> <span>Create Invoice</span>
                </a>
            </li>
            <li class="hover:bg-gray-700 rounded-md cursor-pointer">
                <a href="orders.php" class="flex items-center space-x-2 p-2 w-full block">
                    <i class="fas fa-file-invoice-dollar"></i> <span>Show Invoices</span>
                </a>
            </li>

            <?php if ($_SESSION['role'] == 'Admin') { ?>
            <li class="hover:bg-gray-700 rounded-md cursor-pointer">
                <a href="products.php" class="flex items-center space-x-2 p-2 w-full block">
                    <i class="fas fa-box"></i> <span>Products</span>
                </a>
            </li>
            <li class="hover:bg-gray-700 rounded-md cursor-pointer">
                <a href="distributers.php" class="flex items-center space-x-2 p-2 w-full block">
                    <i class="fas fa-truck"></i> <span>Distributors</span>
                </a>
            </li>
            <li class="hover:bg-gray-700 rounded-md cursor-pointer">
                <a href="employees.php" class="flex items-center space-x-2 p-2 w-full block">
                    <i class="fas fa-users"></i> <span>Show Employees</span>
                </a>
            </li>
            <li class="hover:bg-gray-700 rounded-md cursor-pointer">
                <a href="admin_profile.php" class="flex items-center space-x-2 p-2 w-full block">
                    <i class="fas fa-user"></i> <span>Profile</span>
                </a>
            </li>

            <li class="hover:bg-gray-700 rounded-md cursor-pointer">
                <a href="emp_monthly_revenue.php" class="flex items-center space-x-2 p-2 w-full block">
                    <i class="fa-solid fa-indian-rupee-sign text-white-500 text-xl"></i> <span>Employee Monthly Revenue</span>
                </a>
            </li>
            <li class="hover:bg-gray-700 rounded-md cursor-pointer">
                <a href="emp_yarely_revenue.php" class="flex items-center space-x-2 p-2 w-full block">
                    <i class="fa-solid fa-indian-rupee-sign text-white-500 text-xl"></i> <span>Employee Yearly Revenue</span>
                </a>
            </li>
            <li class="hover:bg-gray-700 rounded-md cursor-pointer">
                <a href="import_excel.php" class="flex items-center space-x-2 p-2 w-full block">
                    <i class="fas fa-file-import"></i> <span>Import Invoices</span>
                </a>
            </li>
            <?php } ?>

            <li class="bg-red-500 hover:bg-red-600 rounded-md cursor-pointer">
                <a href="logout.php" class="flex items-center space-x-2 p-2 w-full block">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<style>
    aside::-webkit-scrollbar {
        width: 0px;
        background: transparent;
    }

    aside {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    /* Prevent scroll bleed to main content */
    aside:hover ~ main {
        pointer-events: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('aside');
    
    sidebar.addEventListener('wheel', function(e) {
        const isScrollable = this.scrollHeight > this.clientHeight;
        const isAtTop = this.scrollTop === 0;
        const isAtBottom = this.scrollTop + this.clientHeight >= this.scrollHeight - 1;
        
        if (!isScrollable || 
            (isAtTop && e.deltaY < 0) || 
            (isAtBottom && e.deltaY > 0)) {
            e.preventDefault();
        }
    }, { passive: false });
});
</script>
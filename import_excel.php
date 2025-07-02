<?php 
// session_start();
include 'header.php';
include 'head.php';
?>

<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 ml-64 p-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-2xl mx-auto">
            <!-- Status Message -->
            <?php if (isset($_SESSION['import_status'])): ?>
                <div class="<?php echo $_SESSION['import_status'] === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> border px-4 py-3 rounded relative mb-4">
                    <?php echo $_SESSION['import_status'] === 'success' ? '✅ Import successful!' : '❌ Import failed!'; ?>
                </div>
                <?php unset($_SESSION['import_status']); ?>
            <?php endif; ?>
            
            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Import Invoices</h2>
            
            <div class="mb-6">
                <a href="download_excel_format.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>
                    Download Excel Template
                </a>
            </div>
            
            <form action="importaction.php" method="post" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-1">
                        Select Excel File
                    </label>
                    <input type="file" name="excel_file" id="excel_file" required accept=".xlsx, .xls, .csv"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <button type="submit" name="import_excel" 
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-file-import mr-2"></i>
                    Import Invoices
                </button>
            </form>
        </div>
    </main>
    
    <?php include 'scripts.php'; ?>
</body>
</html>